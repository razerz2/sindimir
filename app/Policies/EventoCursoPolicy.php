<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EventoCurso;
use App\Models\User;

class EventoCursoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function view(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function update(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function delete(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }
}
