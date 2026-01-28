<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ListaEspera;
use App\Models\User;

class ListaEsperaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function view(User $user, ListaEspera $listaEspera): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function update(User $user, ListaEspera $listaEspera): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }

    public function delete(User $user, ListaEspera $listaEspera): bool
    {
        return $user->role === UserRole::Admin || $user->hasModuleAccess('eventos');
    }
}
