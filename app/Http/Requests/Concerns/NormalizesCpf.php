<?php

namespace App\Http\Requests\Concerns;

use App\Support\Cpf;

trait NormalizesCpf
{
    private function normalizeCpf(?string $value): string
    {
        return Cpf::normalize($value);
    }
}
