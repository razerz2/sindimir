<?php

namespace App\Enums;

enum RendaFamiliar: string
{
    case AteMeioSalario = 'ate_meio_salario';
    case DeMeioAUmSalario = 'de_meio_a_um_salario';
    case DeUmATresSalarios = 'de_um_a_tres_salarios';
    case DeTresACincoSalarios = 'de_tres_a_cinco_salarios';
    case DeCincoADezSalarios = 'de_cinco_a_dez_salarios';
    case AcimaDezSalarios = 'acima_dez_salarios';
    case NaoDeclarada = 'nao_declarada';
}
