<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function registrar(string $acao, Model $entidade, array $dados = []): void
    {
        Auditoria::create([
            'user_id' => Auth::id(),
            'acao' => $acao,
            'entidade_type' => $entidade::class,
            'entidade_id' => $entidade->getKey(),
            'dados' => $dados,
            'ip' => Request::ip(),
            'user_agent' => (string) Request::header('User-Agent'),
        ]);
    }
}
