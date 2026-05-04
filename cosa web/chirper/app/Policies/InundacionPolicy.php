<?php

namespace App\Policies;

use App\Models\Inundacion;
use App\Models\User;

class InundacionPolicy
{
    public function view(User $user, Inundacion $report): bool
    {
        return true; // Todos pueden ver las inundaciones consolidadas
    }

    public function create(User $user): bool
    {
        return $user->isCitizen() && ! $user->isBanned();
    }

    public function update(User $user, Inundacion $report): bool
    {
        return $user->isAuthority();
    }
}
