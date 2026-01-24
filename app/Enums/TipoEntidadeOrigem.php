<?php

namespace App\Enums;

enum TipoEntidadeOrigem: string
{
    case SindicatoProdutoresRurais = 'sindicato_produtores_rurais';
    case SindicatoTrabalhadoresRurais = 'sindicato_trabalhadores_rurais';
    case CrasRedeAssistencia = 'cras_rede_assistencia';
    case CentroExcelencia = 'centro_excelencia';
    case ComandoMilitarOeste = 'comando_militar_oeste';
}
