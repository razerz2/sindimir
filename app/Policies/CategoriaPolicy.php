<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Categoria $categoria): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->role === UserRole::Admin;
    }
}
