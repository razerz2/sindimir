<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminConfig = config('app.admin', [
            'name' => 'Administrador',
            'email' => 'admin@sindimir.local',
            'password' => 'admin123',
        ]);

        User::updateOrCreate(
            ['email' => $adminConfig['email']],
            [
                'name' => $adminConfig['name'],
                'password' => Hash::make($adminConfig['password']),
                'role' => UserRole::Admin,
            ]
        );
    }
}
