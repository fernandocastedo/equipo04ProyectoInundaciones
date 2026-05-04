<?php

namespace App\Policies;

use App\Models\Inundacion;
use App\Models\User;

class AuthorityResponsePolicy
{
    public function create(User $user, Inundacion $report): bool
    {
        return $user->isAuthority();
    }
}
