<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Municipio;
use App\Models\User;

class MunicipioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Municipio $municipio): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Municipio $municipio): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Municipio $municipio): bool
    {
        return $user->role === UserRole::Admin;
    }
}
