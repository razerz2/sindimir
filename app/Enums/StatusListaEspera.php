<?php

namespace App\Enums;

enum StatusListaEspera: string
{
    case Aguardando = 'aguardando';
    case Chamado = 'chamado';
    case Expirado = 'expirado';
    case Cancelado = 'cancelado';
}
