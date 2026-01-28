<?php

namespace App\Support;

class Cpf
{
    public static function normalize(?string $cpf): string
    {
        return preg_replace('/\D+/', '', $cpf ?? '') ?? '';
    }

    public static function format(?string $cpf): string
    {
        $cpf = self::normalize($cpf);

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    public static function isValid(?string $cpf): bool
    {
        $cpf = self::normalize($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $rest = ($sum * 10) % 11;
        $digit1 = $rest === 10 ? 0 : $rest;

        if ($digit1 !== (int) $cpf[9]) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $rest = ($sum * 10) % 11;
        $digit2 = $rest === 10 ? 0 : $rest;

        return $digit2 === (int) $cpf[10];
    }
}
