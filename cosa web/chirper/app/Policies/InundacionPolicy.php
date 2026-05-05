<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inundacion;
use App\Models\User;

class InundacionPolicy
{
    /** Todos los usuarios autenticados pueden ver inundaciones. */
    public function view(User $user, Inundacion $report): bool
    {
        return true;
    }

    /**
     * Solo ciudadanos no baneados pueden crear inundaciones directamente.
     * (Los reportes rápidos anónimos van por ReporteController.)
     */
    public function create(User $user): bool
    {
        return $user->isCitizen() && ! $user->isBanned();
    }

    /** Solo autoridades pueden actualizar (cambiar estado, etc.). */
    public function update(User $user, Inundacion $report): bool
    {
        return $user->isAuthority();
    }
}
