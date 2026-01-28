<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoService
{
    public function get(string $chave, mixed $default = null): mixed
    {
        if (! Schema::hasTable('configuracoes')) {
            return $default;
        }

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
