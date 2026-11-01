<?php

use App\Models\RefreshToken;
use App\Models\User;
use Symfony\Component\HttpFoundation\Cookie;

function refreshCookieFrom($response): Cookie
{
    return collect($response->headers->getCookies())
        ->first(fn (Cookie $cookie) => $cookie->getName() === config('auth_tokens.refresh.cookie'));
}

test('users can receive a jwt with valid credentials', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.role', 'customer')
        ->assertJsonStructure(['access_token', 'expires_in']);

    $cookie = refreshCookieFrom($response);

    expect($cookie)
        ->not->toBeNull()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->isSecure())->toBeFalse()
        ->and($cookie->getSameSite())->toBe('strict')
        ->and($cookie->getPath())->toBe('/api/auth');
});

test('invalid credentials are rejected', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

test('login validates the submitted credentials', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'not-an-email',
        'password' => '',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('authenticated users can retrieve their account', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', 'customer');
});

test('authenticated users can invalidate their token', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Signed out successfully.');

    $this->withToken($token)
        ->getJson('/api/auth/me')
        ->assertUnauthorized();
});

test('account endpoint requires a jwt', function () {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});

test('refresh token rotates and returns a new access token', function () {
    $user = User::factory()->create();
    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $firstCookie = refreshCookieFrom($login);

    $refresh = $this
        ->withCredentials()
        ->withUnencryptedCookie($firstCookie->getName(), $firstCookie->getValue())
        ->postJson('/api/auth/refresh');

    $refresh
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['access_token', 'expires_in']);

    $secondCookie = refreshCookieFrom($refresh);

    expect($secondCookie->getValue())
        ->not->toBe($firstCookie->getValue())
        ->and(RefreshToken::query()->whereNull('revoked_at')->count())->toBe(1);
});

test('reuse of a rotated refresh token revokes its token family', function () {
    $user = User::factory()->create();
    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $firstCookie = refreshCookieFrom($login);
    $refresh = $this
        ->withCredentials()
        ->withUnencryptedCookie($firstCookie->getName(), $firstCookie->getValue())
        ->postJson('/api/auth/refresh');
    $secondCookie = refreshCookieFrom($refresh);

    $this
        ->withCredentials()
        ->withUnencryptedCookie($firstCookie->getName(), $firstCookie->getValue())
        ->postJson('/api/auth/refresh')
        ->assertUnauthorized();

    $this
        ->withCredentials()
        ->withUnencryptedCookie($secondCookie->getName(), $secondCookie->getValue())
        ->postJson('/api/auth/refresh')
        ->assertUnauthorized();

    expect(RefreshToken::query()->whereNull('revoked_at')->count())->toBe(0);
});

test('expired refresh tokens are rejected', function () {
    $user = User::factory()->create();
    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $cookie = refreshCookieFrom($login);

    RefreshToken::query()->update(['expires_at' => now()->subMinute()]);

    $this
        ->withCredentials()
        ->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
        ->postJson('/api/auth/refresh')
        ->assertUnauthorized();
});

test('logout revokes the refresh token and clears its cookie', function () {
    $user = User::factory()->create();
    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $cookie = refreshCookieFrom($login);

    $response = $this
        ->withCredentials()
        ->withToken($login->json('access_token'))
        ->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
        ->postJson('/api/auth/logout');

    $response->assertOk();

    expect(RefreshToken::query()->whereNull('revoked_at')->count())->toBe(0)
        ->and(refreshCookieFrom($response)->getExpiresTime())->toBeLessThan(time());

    $this
        ->withCredentials()
        ->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
        ->postJson('/api/auth/refresh')
        ->assertUnauthorized();
});

test('refresh endpoint requires a refresh cookie', function () {
    $this->postJson('/api/auth/refresh')->assertUnauthorized();
});
