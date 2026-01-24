<?php

namespace App\Services;

use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatriculaService
{
    /**
     * @return array{tipo: string, registro: Matricula|ListaEspera}
     */
    public function solicitarInscricao(int $alunoId, int $eventoCursoId): array
    {
        return DB::transaction(function () use ($alunoId, $eventoCursoId) {
            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->find($eventoCursoId);

            if (! $evento) {
                throw new ModelNotFoundException('Evento não encontrado.');
            }

            $matriculaExistente = Matricula::query()
                ->where('aluno_id', $alunoId)
                ->where('evento_curso_id', $eventoCursoId)
                ->lockForUpdate()
                ->first();

            if ($matriculaExistente) {
                return [
                    'tipo' => 'matricula',
                    'registro' => $matriculaExistente,
                ];
            }

            $listaExistente = ListaEspera::query()
                ->where('aluno_id', $alunoId)
                ->where('evento_curso_id', $eventoCursoId)
                ->lockForUpdate()
                ->first();

            if ($listaExistente) {
                return [
                    'tipo' => 'lista_espera',
                    'registro' => $listaExistente,
                ];
            }

            $limiteVagas = (int) $evento->curso?->limite_vagas;

            $confirmadas = Matricula::query()
                ->where('evento_curso_id', $eventoCursoId)
                ->where('status', StatusMatricula::Confirmada)
                ->lockForUpdate()
                ->count();

            if ($limiteVagas > 0 && $confirmadas < $limiteVagas) {
                $matricula = Matricula::create([
                    'aluno_id' => $alunoId,
                    'evento_curso_id' => $eventoCursoId,
                    'status' => StatusMatricula::Confirmada,
                    'data_confirmacao' => CarbonImmutable::now(),
                ]);

                return [
                    'tipo' => 'matricula',
                    'registro' => $matricula,
                ];
            }

            $ultimaPosicao = ListaEspera::query()
                ->where('evento_curso_id', $eventoCursoId)
                ->lockForUpdate()
                ->max('posicao');

            $listaEspera = ListaEspera::create([
                'aluno_id' => $alunoId,
                'evento_curso_id' => $eventoCursoId,
                'status' => StatusListaEspera::Aguardando,
                'posicao' => ($ultimaPosicao ?? 0) + 1,
            ]);

            return [
                'tipo' => 'lista_espera',
                'registro' => $listaEspera,
            ];
        });
    }

    public function confirmarMatricula(Matricula $matricula): Matricula
    {
        return DB::transaction(function () use ($matricula) {
            $matricula = Matricula::query()
                ->lockForUpdate()
                ->findOrFail($matricula->id);

            if ($matricula->status === StatusMatricula::Confirmada) {
                return $matricula;
            }

            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->findOrFail($matricula->evento_curso_id);

            $limiteVagas = (int) $evento->curso?->limite_vagas;

            $confirmadas = Matricula::query()
                ->where('evento_curso_id', $evento->id)
                ->where('status', StatusMatricula::Confirmada)
                ->lockForUpdate()
                ->count();

            if ($limiteVagas > 0 && $confirmadas >= $limiteVagas) {
                throw new RuntimeException('Limite de vagas atingido para o evento.');
            }

            $matricula->update([
                'status' => StatusMatricula::Confirmada,
                'data_confirmacao' => CarbonImmutable::now(),
                'data_expiracao' => null,
            ]);

            return $matricula;
        });
    }

    public function expirarMatriculasVencidas(): int
    {
        $eventos = Matricula::query()
            ->where('status', StatusMatricula::Pendente)
            ->whereNotNull('data_expiracao')
            ->where('data_expiracao', '<', CarbonImmutable::now())
            ->distinct()
            ->pluck('evento_curso_id');

        $totalExpiradas = 0;

        foreach ($eventos as $eventoId) {
            $totalExpiradas += $this->expirarMatriculasVencidasPorEvento((int) $eventoId);
        }

        return $totalExpiradas;
    }

    public function chamarListaEspera(EventoCurso $evento): int
    {
        return DB::transaction(function () use ($evento) {
            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->findOrFail($evento->id);

            return $this->chamarListaEsperaComEventoTravado($evento);
        });
    }

    private function expirarMatriculasVencidasPorEvento(int $eventoId): int
    {
        return DB::transaction(function () use ($eventoId) {
            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->findOrFail($eventoId);

            $matriculas = Matricula::query()
                ->where('evento_curso_id', $evento->id)
                ->where('status', StatusMatricula::Pendente)
                ->whereNotNull('data_expiracao')
                ->where('data_expiracao', '<', CarbonImmutable::now())
                ->lockForUpdate()
                ->get();

            if ($matriculas->isNotEmpty()) {
                Matricula::query()
                    ->whereIn('id', $matriculas->pluck('id'))
                    ->update(['status' => StatusMatricula::Expirada]);
            }

            $this->chamarListaEsperaComEventoTravado($evento);

            return $matriculas->count();
        });
    }

    private function chamarListaEsperaComEventoTravado(EventoCurso $evento): int
    {
        $limiteVagas = (int) $evento->curso?->limite_vagas;

        if ($limiteVagas <= 0) {
            return 0;
        }

        $confirmadas = Matricula::query()
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusMatricula::Confirmada)
            ->lockForUpdate()
            ->count();

        $vagasDisponiveis = $limiteVagas - $confirmadas;

        if ($vagasDisponiveis <= 0) {
            return 0;
        }

        $listaEspera = ListaEspera::query()
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusListaEspera::Aguardando)
            ->orderBy('posicao')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->limit($vagasDisponiveis)
            ->get();

        if ($listaEspera->isEmpty()) {
            return 0;
        }

        $processadas = 0;

        foreach ($listaEspera as $item) {
            $matricula = Matricula::query()
                ->where('aluno_id', $item->aluno_id)
                ->where('evento_curso_id', $evento->id)
                ->lockForUpdate()
                ->first();

            if (! $matricula) {
                $matricula = Matricula::create([
                    'aluno_id' => $item->aluno_id,
                    'evento_curso_id' => $evento->id,
                    'status' => StatusMatricula::Confirmada,
                    'data_confirmacao' => CarbonImmutable::now(),
                ]);
            } elseif ($matricula->status !== StatusMatricula::Confirmada) {
                $matricula->update([
                    'status' => StatusMatricula::Confirmada,
                    'data_confirmacao' => CarbonImmutable::now(),
                    'data_expiracao' => null,
                ]);
            }

            $item->update([
                'status' => StatusListaEspera::Chamado,
                'chamado_em' => CarbonImmutable::now(),
            ]);

            $processadas++;
        }

        return $processadas;
    }
}
