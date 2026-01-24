<?php

namespace App\Enums;

enum TurnoEvento: string
{
    case Manha = 'manha';
    case Tarde = 'tarde';
    case Noite = 'noite';
    case ManhaTarde = 'manha_tarde';
    case NaoDeclarado = 'nao_declarado';
}
