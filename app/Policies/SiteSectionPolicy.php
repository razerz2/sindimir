<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SiteSection;
use App\Models\User;

class SiteSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, SiteSection $section): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, SiteSection $section): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, SiteSection $section): bool
    {
        return $user->role === UserRole::Admin;
    }
}
