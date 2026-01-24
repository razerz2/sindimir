<?php

namespace App\Enums;

enum Sexo: string
{
    case Masculino = 'masculino';
    case Feminino = 'feminino';
    case NaoDeclarado = 'nao_declarado';
}
