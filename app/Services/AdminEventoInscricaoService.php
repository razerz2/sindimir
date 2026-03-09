<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\EventoCurso;
use App\Support\Cpf;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use RuntimeException;

class AdminEventoInscricaoService
{
    public function __construct(private readonly MatriculaService $matriculaService)
    {
    }

    /**
     * @return Collection<int, Aluno>
     */
    public function buscarAlunos(string $termo, int $limite = 20): Collection
    {
        $termo = trim($termo);

        if ($termo === '') {
            return collect();
        }

        $cpf = Cpf::normalize($termo);
        $telefone = Phone::normalize($termo);
        $limite = max(1, min($limite, 50));

        return Aluno::query()
            ->select(['id', 'nome_completo', 'cpf', 'email', 'celular', 'telefone'])
            ->where(function ($query) use ($termo, $cpf, $telefone): void {
                $query
                    ->where('nome_completo', 'like', '%' . $termo . '%')
                    ->orWhere('email', 'like', '%' . $termo . '%');

                if ($cpf !== '') {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') LIKE ?",
                        ['%' . $cpf . '%']
                    );
                }

                if ($telefone !== '') {
                    $query
                        ->orWhere('celular', 'like', '%' . $telefone . '%')
                        ->orWhere('telefone', 'like', '%' . $telefone . '%');
                }
            })
            ->orderBy('nome_completo')
            ->limit($limite)
            ->get();
    }

    /**
     * @return array{
     *     resultado: array{
     *         status: string,
     *         tipo: string,
     *         registro: \App\Models\Matricula|\App\Models\ListaEspera,
     *         debug: array{
     *             aluno_id: int,
     *             evento_curso_id: int,
     *             matricula_encontrada: array{id: int, status: string|null, deleted_at: string|null}|null,
     *             lista_espera_encontrada: array{id: int, status: string}|null
     *         }
     *     },
     *     mensagem: string
     * }
     */
    public function inscreverAluno(EventoCurso $evento, int $alunoId): array
    {
        $evento->loadMissing('curso');
        $this->validarEventoDisponivel($evento);

        $aluno = Aluno::query()->find($alunoId);

        if (! $aluno) {
            throw new ModelNotFoundException('Aluno nao encontrado.');
        }

        $resultado = $this->matriculaService->solicitarInscricao($aluno->id, $evento->id);

        return [
            'resultado' => $resultado,
            'mensagem' => $this->mensagemResultado($resultado),
        ];
    }

    /**
     * @param  array{status?: string, tipo?: string}  $resultado
     */
    private function mensagemResultado(array $resultado): string
    {
        $status = (string) ($resultado['status'] ?? '');

        if ($status === 'already_enrolled') {
            return 'Aluno ja possui inscricao ativa neste evento.';
        }

        if ($status === 'waitlist') {
            return 'Aluno ja esta na lista de espera deste evento.';
        }

        if ($status === 'no_vacancies' || (($resultado['tipo'] ?? null) === 'lista_espera')) {
            return 'Evento sem vagas imediatas. Aluno incluido na lista de espera.';
        }

        return 'Inscricao realizada com sucesso.';
    }

    private function validarEventoDisponivel(EventoCurso $evento): void
    {
        if (! $evento->ativo || ! $evento->curso || ! $evento->curso->ativo) {
            throw new RuntimeException('Evento indisponivel para inscricao.');
        }

        $today = CarbonImmutable::now((string) config('app.timezone'))->toDateString();
        $dataFim = $evento->data_fim?->toDateString();
        $dataInicio = $evento->data_inicio?->toDateString();

        $expirado = $dataFim
            ? $dataFim < $today
            : ($dataInicio ? $dataInicio < $today : false);

        if ($expirado) {
            throw new RuntimeException('Evento indisponivel para inscricao.');
        }
    }
}
