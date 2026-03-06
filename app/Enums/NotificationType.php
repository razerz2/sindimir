<?php

namespace App\Enums;

enum NotificationType: string
{
    case CURSO_DISPONIVEL = 'CURSO_DISPONIVEL';
    case VAGA_ABERTA = 'VAGA_ABERTA';
    case LISTA_ESPERA = 'LISTA_ESPERA';

    public function label(): string
    {
        return match ($this) {
            self::CURSO_DISPONIVEL => 'Curso Disponível',
            self::VAGA_ABERTA => 'Vaga Aberta',
            self::LISTA_ESPERA => 'Lista de espera',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type) => $type->value,
            self::cases()
        );
    }
}
