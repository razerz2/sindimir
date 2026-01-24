<?php

namespace App\Services;

use App\Models\Aluno;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PublicInscricaoService
{
    public function __construct(private readonly AlunoService $alunoService)
    {
    }

    /**
     * @return array{action: string, aluno: ?Aluno}
     */
    public function resolverPorCpf(string $cpf): array
    {
        $aluno = Aluno::query()
            ->where('cpf', $cpf)
            ->first();

        if (! $aluno) {
            return [
                'action' => 'cadastro',
                'aluno' => null,
            ];
        }

        if ($aluno->user_id) {
            return [
                'action' => 'login',
                'aluno' => $aluno,
            ];
        }

        return [
            'action' => 'cadastro',
            'aluno' => $aluno,
        ];
    }

    public function cadastrarAluno(array $data, array $deficiencias = [], ?string $descricaoDeficiencia = null): Aluno
    {
        return DB::transaction(function () use ($data, $deficiencias, $descricaoDeficiencia) {
            $aluno = Aluno::query()
                ->where('cpf', $data['cpf'])
                ->lockForUpdate()
                ->first();

            if ($aluno && $aluno->user_id) {
                throw new RuntimeException('Aluno já possui usuário vinculado.');
            }

            if ($aluno) {
                return $this->alunoService->update($aluno, $data, $deficiencias, $descricaoDeficiencia);
            }

            return $this->alunoService->create($data, $deficiencias, $descricaoDeficiencia);
        });
    }
}
