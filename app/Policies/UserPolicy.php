<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAdministration(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateAdministration(User $user, User $managedUser): bool
    {
        return $user->isAdmin() && ! $user->is($managedUser);
    }
}
