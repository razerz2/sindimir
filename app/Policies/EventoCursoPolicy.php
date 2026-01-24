<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EventoCurso;
use App\Models\User;

class EventoCursoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, EventoCurso $eventoCurso): bool
    {
        return $user->role === UserRole::Admin;
    }
}
