<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen renders the React application', function () {
    $this->withoutVite()
        ->get('/register')
        ->assertOk()
        ->assertSee('id="auth-root"', false);
});

test('new customers can register and receive a confirmation message', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Test Customer',
        'email' => ' Customer@Example.com ',
        'password' => 'garden123456',
        'password_confirmation' => 'garden123456',
    ])->assertCreated()
        ->assertJsonPath('email', 'customer@example.com');

    $user = User::query()->where('email', 'customer@example.com')->firstOrFail();

    expect($user->email_verified_at)->toBeNull()
        ->and($user->profile->role)->toBe('customer')
        ->and($user->wallet)->not->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Confirm your Siberian Wine Garden email'
            && $mail->actionText === 'Confirm email address'
            && str_contains($mail->actionUrl, '/verify-email/'.$user->id.'/');
    });
});

test('registration validates identity and password data', function () {
    User::factory()->create(['email' => 'used@example.com']);

    $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'used@example.com',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration requires a password with at least twelve characters', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password' => 'garden12345',
        'password_confirmation' => 'garden12345',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('unverified customers cannot sign in', function () {
    $user = User::factory()->unverified()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnauthorized();

    $this->assertDatabaseCount('refresh_tokens', 0);
});

test('unverified customers cannot use protected api routes with an existing access token', function () {
    $user = User::factory()->unverified()->create();
    $token = auth('api')->login($user);

    auth('api')->setToken($token)->forgetUser();

    $this->withToken($token)->getJson('/api/cart')->assertUnauthorized();
});

test('customers can request another confirmation link without account disclosure', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $message = 'If this address belongs to an unverified account, a new confirmation link has been sent.';

    $this->postJson('/api/auth/email/verification-notification', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', $message);

    $this->postJson('/api/auth/email/verification-notification', ['email' => 'unknown@example.com'])
        ->assertOk()
        ->assertJsonPath('message', $message);

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
});
