<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class JwtAuthService
{
    public function login(array $credentials): array
    {
        $token = $this->guard()->attempt($credentials);

        if (! $token) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $this->ensureActive($this->guard()->user());

        return $this->tokenResponse($token);
    }

    public function user(): User
    {
        $user = $this->guard()->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        $this->ensureActive($user);

        return $user->loadMissing('profile');
    }

    public function tokenFor(User $user): array
    {
        $this->ensureActive($user);

        return $this->tokenResponse($this->guard()->login($user));
    }

    public function logout(): void
    {
        try {
            $this->guard()->logout(true);
        } catch (JWTException) {
        }
    }

    private function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
        ];
    }

    private function ensureActive(?User $user): void
    {
        if (! $user instanceof User || $user->isBanned()) {
            throw new AuthenticationException('This account is blocked.');
        }
    }

    private function guard(): JWTGuard
    {
        return auth('api');
    }
}
