<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBan;

class AdminUserService
{
    public function update(User $user, User $administrator, array $data): User
    {
        return $user->getConnection()->transaction(function () use ($user, $administrator, $data): User {
            if (array_key_exists('role', $data)) {
                $user->profile()->update(['role' => $data['role']]);
            }

            if (($data['banned'] ?? null) === true) {
                UserBan::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['banned_by' => $administrator->id, 'reason' => $data['ban_reason'] ?? null],
                );

                $user->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }

            if (($data['banned'] ?? null) === false) {
                $user->ban()->delete();
            }

            return $user->load(['profile', 'ban'])->loadCount('orders');
        }, 3);
    }
}
