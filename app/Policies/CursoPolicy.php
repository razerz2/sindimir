<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Curso;
use App\Models\User;

class CursoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('cursos');
    }

    public function view(User $user, Curso $curso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('cursos');
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('cursos');
    }

    public function update(User $user, Curso $curso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('cursos');
    }

    public function delete(User $user, Curso $curso): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('cursos');
    }
}
