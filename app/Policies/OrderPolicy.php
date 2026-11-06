<?php

namespace App\Policies;

use App\Models\User;

class OrderPolicy
{
    public function viewAdministration(User $user): bool
    {
        return $user->isAdmin();
    }
}
