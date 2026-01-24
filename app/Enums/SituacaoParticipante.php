<?php

namespace App\Enums;

enum SituacaoParticipante: string
{
    case Desempregado = 'desempregado';
    case AposentadoPensionista = 'aposentado_pensionista';
    case ProdutorEmpregador = 'produtor_empregador';
    case ProdutorArrendatario = 'produtor_arrendatario';
    case CooperadoAssociado = 'cooperado_associado';
    case FamiliaProdutorRural = 'familia_produtor_rural';
    case FamiliaTrabalhadorRural = 'familia_trabalhador_rural';
    case TrabalhadorTemporario = 'trabalhador_temporario';
    case TrabalhadorPermanente = 'trabalhador_permanente';
    case AutonomoProfissionalLiberal = 'autonomo_profissional_liberal';
    case ProdutorAgriculturaFamiliar = 'produtor_agricultura_familiar';
    case Outros = 'outros';
    case NaoDeclarada = 'nao_declarada';
}
