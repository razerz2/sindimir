<?php

namespace App\Enums;

enum EstadoCivil: string
{
    case Casado = 'casado';
    case Solteiro = 'solteiro';
    case Separado = 'separado';
    case Viuvo = 'viuvo';
    case UniaoEstavel = 'uniao_estavel';
    case Divorciado = 'divorciado';
    case NaoDeclarado = 'nao_declarado';
}
