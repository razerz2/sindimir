<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Aluno = 'aluno';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Aluno => 'Aluno',
        };
    }
}
