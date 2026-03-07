<?php

namespace App\Services\Bot;

final class BotState
{
    public const MENU = 'MENU';
    public const CURSOS_LIST = 'CURSOS_LIST';
    public const CURSO_ACTION = 'CURSO_ACTION';
    public const CURSO_CPF = 'CURSO_CPF';
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
            self::CANCEL_CPF,
            self::CANCEL_LIST,
            self::CANCEL_CONFIRM,
            self::ENDED,
        ];
    }
}
