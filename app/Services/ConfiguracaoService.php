<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\DB;

class ConfiguracaoService
{
    public function get(string $chave, mixed $default = null): mixed
    {
        $config = Configuracao::query()
            ->where('chave', $chave)
            ->first();

        return $config?->valor ?? $default;
    }

    public function set(string $chave, mixed $valor, ?string $descricao = null): Configuracao
    {
        return DB::transaction(function () use ($chave, $valor, $descricao) {
            return Configuracao::updateOrCreate(
                ['chave' => $chave],
                [
                    'valor' => $valor,
                    'descricao' => $descricao,
                ]
            );
        });
    }
}
