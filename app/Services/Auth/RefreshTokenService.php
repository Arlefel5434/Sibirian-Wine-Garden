<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshTokenService
{
    public function issue(User $user, Request $request, ?string $familyId = null): array
    {
        $id = (string) Str::uuid();
        $secret = $this->secret();

        RefreshToken::query()->create([
            'id' => $id,
            'family_id' => $familyId ?? (string) Str::uuid(),
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $secret),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'expires_at' => now()->addMinutes($this->ttl()),
        ]);

        return [
            'value' => $id.'.'.$secret,
            'expires_at' => now()->addMinutes($this->ttl()),
        ];
    }

    public function rotate(string $plainToken, Request $request): array
    {
        [$id, $secret] = $this->split($plainToken);
        $connection = RefreshToken::query()->getModel()->getConnection();

        $result = $connection->transaction(function () use ($id, $secret, $request): array {
            $token = RefreshToken::query()->lockForUpdate()->find($id);

            if (! $token || ! hash_equals($token->token_hash, hash('sha256', $secret))) {
                throw new AuthenticationException('The refresh token is invalid.');
            }

            if ($token->revoked_at) {
                $this->revokeFamily($token->family_id);

                return ['error' => 'Refresh token reuse was detected.'];
            }

            if ($token->expires_at->isPast()) {
                $token->update(['revoked_at' => now()]);

                return ['error' => 'The refresh token has expired.'];
            }

            $token->update([
                'last_used_at' => now(),
                'revoked_at' => now(),
            ]);

            return [
                'user' => $token->user,
                'refresh' => $this->issue($token->user, $request, $token->family_id),
            ];
        }, 3);

        if (isset($result['error'])) {
            throw new AuthenticationException($result['error']);
        }

        return $result;
    }

    public function revoke(?string $plainToken): void
    {
        if (! $plainToken) {
            return;
        }

        try {
            [$id, $secret] = $this->split($plainToken);
        } catch (AuthenticationException) {
            return;
        }

        $token = RefreshToken::query()->find($id);

        if ($token && hash_equals($token->token_hash, hash('sha256', $secret))) {
            $token->update(['revoked_at' => $token->revoked_at ?? now()]);
        }
    }

    public function cookie(array $refresh): Cookie
    {
        return Cookie::create(
            name: $this->cookieName(),
            value: $refresh['value'],
            expire: $refresh['expires_at'],
            path: config('auth_tokens.refresh.path'),
            domain: config('auth_tokens.refresh.domain'),
            secure: config('auth_tokens.refresh.secure'),
            httpOnly: true,
            sameSite: config('auth_tokens.refresh.same_site'),
        );
    }

    public function forgetCookie(): Cookie
    {
        return Cookie::create(
            name: $this->cookieName(),
            value: '',
            expire: now()->subYear(),
            path: config('auth_tokens.refresh.path'),
            domain: config('auth_tokens.refresh.domain'),
            secure: config('auth_tokens.refresh.secure'),
            httpOnly: true,
            sameSite: config('auth_tokens.refresh.same_site'),
        );
    }

    public function cookieName(): string
    {
        return config('auth_tokens.refresh.cookie');
    }

    private function revokeFamily(string $familyId): void
    {
        RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function split(string $plainToken): array
    {
        $parts = explode('.', $plainToken, 2);

        if (count($parts) !== 2 || ! Str::isUuid($parts[0]) || $parts[1] === '') {
            throw new AuthenticationException('The refresh token is invalid.');
        }

        return $parts;
    }

    private function secret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    private function ttl(): int
    {
        return config('auth_tokens.refresh.ttl');
    }
}
