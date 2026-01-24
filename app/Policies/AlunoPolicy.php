<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\User;

class AlunoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Aluno $aluno): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Aluno $aluno): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Aluno $aluno): bool
    {
        return $user->role === UserRole::Admin;
    }
}
