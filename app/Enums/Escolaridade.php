<?php

namespace App\Enums;

enum Escolaridade: string
{
    case SemEscolaridade = 'sem_escolaridade';
    case EnsinoFundamentalIncompleto = 'ensino_fundamental_incompleto';
    case EnsinoFundamentalCompleto = 'ensino_fundamental_completo';
    case EnsinoMedioTecnicoIncompleto = 'ensino_medio_tecnico_incompleto';
    case EnsinoMedioTecnicoCompleto = 'ensino_medio_tecnico_completo';
    case EnsinoSuperiorIncompleto = 'ensino_superior_incompleto';
    case EnsinoSuperiorCompleto = 'ensino_superior_completo';
    case PosGraduacaoIncompleta = 'pos_graduacao_incompleta';
    case PosGraduacaoCompleta = 'pos_graduacao_completa';
    case Mestrado = 'mestrado';
    case Doutorado = 'doutorado';
    case NaoDeclarada = 'nao_declarada';
}
