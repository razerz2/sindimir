<?php

namespace App\Enums;

enum StatusMatricula: string
{
    case Pendente = 'pendente';
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';
    case Expirada = 'expirada';
}
