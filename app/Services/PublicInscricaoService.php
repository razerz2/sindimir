<?php

namespace App\Services;

use App\Models\Aluno;
use App\Support\Cpf;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PublicInscricaoService
{
    public function __construct(
        private readonly AlunoService $alunoService,
        private readonly MatriculaService $matriculaService
    ) {
    }

    /**
     * @return array{action: string, aluno: ?Aluno}
     */
    public function resolverPorCpf(string $cpf): array
    {
        $cpf = Cpf::normalize($cpf);

        $aluno = Aluno::query()
            ->whereCpf($cpf)
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
        $cpf = Cpf::normalize($data['cpf']);

        $data['cpf'] = $cpf;

        return DB::transaction(function () use ($data, $deficiencias, $descricaoDeficiencia) {
            $aluno = Aluno::query()
            ->whereCpf($data['cpf'])
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

    /**
     * @return array{tipo: string, registro: \App\Models\Matricula|\App\Models\ListaEspera}
     */
    public function inscreverAlunoNoEvento(Aluno $aluno, int $eventoCursoId): array
    {
        return $this->matriculaService->solicitarInscricao($aluno->id, $eventoCursoId, false);
    }
}
