<?php

namespace App\Support;

class Cpf
{
    public static function normalize(?string $cpf): string
    {
        return preg_replace('/\D+/', '', $cpf ?? '') ?? '';
    }
}
