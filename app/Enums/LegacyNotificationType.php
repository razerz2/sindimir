<?php

namespace App\Enums;

final class LegacyNotificationType
{
    public const EVENTO_CRIADO = 'EVENTO_CRIADO';
    public const EVENTO_CANCELADO = 'EVENTO_CANCELADO';
    public const INSCRICAO_CONFIRMAR = 'INSCRICAO_CONFIRMAR';
    public const INSCRICAO_CANCELADA = 'INSCRICAO_CANCELADA';
    public const LEMBRETE_CURSO = 'LEMBRETE_CURSO';
    public const MATRICULA_CONFIRMADA = 'MATRICULA_CONFIRMADA';
    public const LISTA_ESPERA_CHAMADA = 'LISTA_ESPERA_CHAMADA';
    public const USUARIO_INSCRICAO_CURSO = 'USUARIO_INSCRICAO_CURSO';
    public const USUARIO_CANCELAMENTO_CURSO = 'USUARIO_CANCELAMENTO_CURSO';
    public const USUARIO_RESUMO_DIARIO_CURSOS = 'USUARIO_RESUMO_DIARIO_CURSOS';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::EVENTO_CRIADO,
            self::EVENTO_CANCELADO,
            self::INSCRICAO_CONFIRMAR,
            self::INSCRICAO_CANCELADA,
            self::LEMBRETE_CURSO,
            self::MATRICULA_CONFIRMADA,
            self::LISTA_ESPERA_CHAMADA,
            self::USUARIO_INSCRICAO_CURSO,
            self::USUARIO_CANCELAMENTO_CURSO,
            self::USUARIO_RESUMO_DIARIO_CURSOS,
        ];
    }
}
