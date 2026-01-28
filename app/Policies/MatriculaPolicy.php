<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Matricula;
use App\Models\User;

class MatriculaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function view(User $user, Matricula $matricula): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function update(User $user, Matricula $matricula): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function delete(User $user, Matricula $matricula): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }
}
