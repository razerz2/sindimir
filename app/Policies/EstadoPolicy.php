<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Estado;
use App\Models\User;

class EstadoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Estado $estado): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Estado $estado): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Estado $estado): bool
    {
        return $user->role === UserRole::Admin;
    }
}
