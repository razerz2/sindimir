<?php

namespace App\Services\Bot;

final class BotState
{
    public const MENU = 'MENU';
    public const CURSOS_LIST = 'CURSOS_LIST';
    public const CURSO_ACTION = 'CURSO_ACTION';
    public const CURSO_CPF = 'CURSO_CPF';
    public const CURSO_ALUNO_CONFIRM = 'CURSO_ALUNO_CONFIRM';
    public const CURSO_ALUNO_EDIT_FIELD = 'CURSO_ALUNO_EDIT_FIELD';
    public const CURSO_ALUNO_EDIT_REVIEW = 'CURSO_ALUNO_EDIT_REVIEW';
    public const ALUNO_CPF = 'ALUNO_CPF';
    public const ALUNO_MENU = 'ALUNO_MENU';
    public const ALUNO_VIEW_DATA = 'ALUNO_VIEW_DATA';
    public const ALUNO_EDIT_FIELD = 'ALUNO_EDIT_FIELD';
    public const ALUNO_EDIT_REVIEW = 'ALUNO_EDIT_REVIEW';
    public const ALUNO_INSCRICOES_LIST = 'ALUNO_INSCRICOES_LIST';
    public const ALUNO_INSCRICAO_ACTION = 'ALUNO_INSCRICAO_ACTION';
    public const CANCEL_CPF = 'CANCEL_CPF';
    public const CANCEL_LIST = 'CANCEL_LIST';
    public const CANCEL_CONFIRM = 'CANCEL_CONFIRM';
    public const ENDED = 'ENDED';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MENU,
            self::CURSOS_LIST,
            self::CURSO_ACTION,
            self::CURSO_CPF,
            self::CURSO_ALUNO_CONFIRM,
            self::CURSO_ALUNO_EDIT_FIELD,
            self::CURSO_ALUNO_EDIT_REVIEW,
            self::ALUNO_CPF,
            self::ALUNO_MENU,
            self::ALUNO_VIEW_DATA,
            self::ALUNO_EDIT_FIELD,
            self::ALUNO_EDIT_REVIEW,
            self::ALUNO_INSCRICOES_LIST,
            self::ALUNO_INSCRICAO_ACTION,
            self::CANCEL_CPF,
            self::CANCEL_LIST,
            self::CANCEL_CONFIRM,
            self::ENDED,
        ];
    }
}
