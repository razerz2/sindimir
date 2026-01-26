<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\Aluno;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Models\NotificationLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatriculaService
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * @return array{tipo: string, registro: Matricula|ListaEspera}
     */
    public function solicitarInscricao(
        int $alunoId,
        int $eventoCursoId,
        bool $enviarConfirmacaoNotificacao = true
    ): array
    {
        return DB::transaction(function () use ($alunoId, $eventoCursoId, $enviarConfirmacaoNotificacao) {
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
                $limiteVagas = (int) $evento->curso?->limite_vagas;
                $ocupadas = Matricula::query()
                    ->where('evento_curso_id', $eventoCursoId)
                    ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
                    ->lockForUpdate()
                    ->count();

                $temVaga = $limiteVagas <= 0 || $ocupadas < $limiteVagas;

                if ($temVaga) {
                    $matricula = Matricula::create([
                        'aluno_id' => $alunoId,
                        'evento_curso_id' => $eventoCursoId,
                        'status' => StatusMatricula::Confirmada,
                        'data_confirmacao' => CarbonImmutable::now(),
                    ]);

                    $listaExistente->delete();
                    $this->reordenarListaEspera($eventoCursoId);

                    return [
                        'tipo' => 'matricula',
                        'registro' => $matricula,
                    ];
                }

                return [
                    'tipo' => 'lista_espera',
                    'registro' => $listaExistente,
                ];
            }

            $limiteVagas = (int) $evento->curso?->limite_vagas;

            $ocupadas = Matricula::query()
                ->where('evento_curso_id', $eventoCursoId)
                ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
                ->lockForUpdate()
                ->count();

            if ($limiteVagas <= 0 || $ocupadas < $limiteVagas) {
                $tempoLimiteHoras = (int) $this->configuracaoService->get(
                    'notificacao.auto.inscricao_confirmacao.tempo_limite_horas',
                    24
                );
                $diasAntesConfirmacao = (int) $this->configuracaoService->get(
                    'notificacao.auto.inscricao_confirmacao.dias_antes',
                    0
                );
                $enviarConfirmacaoAgora = $this->deveEnviarConfirmacaoAgora($evento, $diasAntesConfirmacao);

                $matricula = Matricula::create([
                    'aluno_id' => $alunoId,
                    'evento_curso_id' => $eventoCursoId,
                    'status' => StatusMatricula::Pendente,
                    'data_expiracao' => $enviarConfirmacaoAgora
                        ? CarbonImmutable::now()->addHours(max(1, $tempoLimiteHoras))
                        : null,
                ]);

                if ($enviarConfirmacaoAgora && $enviarConfirmacaoNotificacao) {
                    $this->notificarConfirmacaoInscricao($matricula, $evento, $tempoLimiteHoras);
                }

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

            if ($matricula->data_expiracao && $matricula->data_expiracao->isPast()) {
                throw new RuntimeException('Prazo de confirmação expirado.');
            }

            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->findOrFail($matricula->evento_curso_id);

            $limiteVagas = (int) $evento->curso?->limite_vagas;

            $ocupadas = Matricula::query()
                ->where('evento_curso_id', $evento->id)
                ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
                ->where('id', '!=', $matricula->id)
                ->lockForUpdate()
                ->count();

            if ($limiteVagas > 0 && $ocupadas >= $limiteVagas) {
                throw new RuntimeException('Limite de vagas atingido para o evento.');
            }

            $matricula->update([
                'status' => StatusMatricula::Confirmada,
                'data_confirmacao' => CarbonImmutable::now(),
                'data_expiracao' => null,
            ]);

            $this->notificarMatriculaConfirmada($matricula, $evento);

            return $matricula;
        });
    }

    public function cancelarMatricula(Matricula $matricula): Matricula
    {
        return DB::transaction(function () use ($matricula) {
            $matricula = Matricula::query()
                ->lockForUpdate()
                ->findOrFail($matricula->id);

            if ($matricula->status === StatusMatricula::Cancelada) {
                return $matricula;
            }

            $matricula->update([
                'status' => StatusMatricula::Cancelada,
            ]);

            return $matricula;
        });
    }

    public function cancelarMatriculaEEnviarParaListaEspera(Matricula $matricula): Matricula
    {
        return DB::transaction(function () use ($matricula) {
            $matricula = Matricula::query()
                ->lockForUpdate()
                ->findOrFail($matricula->id);

            if ($matricula->status !== StatusMatricula::Cancelada) {
                $matricula->update([
                    'status' => StatusMatricula::Cancelada,
                ]);
            }

            $eventoId = $matricula->evento_curso_id;
            $alunoId = $matricula->aluno_id;

            $ultimaPosicao = ListaEspera::withTrashed()
                ->where('evento_curso_id', $eventoId)
                ->lockForUpdate()
                ->max('posicao');

            $novaPosicao = ($ultimaPosicao ?? 0) + 1;

            $lista = ListaEspera::withTrashed()
                ->where('evento_curso_id', $eventoId)
                ->where('aluno_id', $alunoId)
                ->lockForUpdate()
                ->first();

            if ($lista) {
                if ($lista->trashed()) {
                    $lista->restore();
                }

                $lista->update([
                    'status' => StatusListaEspera::Aguardando,
                    'posicao' => $novaPosicao,
                    'chamado_em' => null,
                ]);
            } else {
                ListaEspera::create([
                    'aluno_id' => $alunoId,
                    'evento_curso_id' => $eventoId,
                    'status' => StatusListaEspera::Aguardando,
                    'posicao' => $novaPosicao,
                ]);
            }

            return $matricula;
        });
    }

    public function moverListaEspera(ListaEspera $item, string $direcao): bool
    {
        return DB::transaction(function () use ($item, $direcao) {
            $item = ListaEspera::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            if (! in_array($item->status, [StatusListaEspera::Aguardando, StatusListaEspera::Chamado], true)) {
                return false;
            }

            $query = ListaEspera::query()
                ->where('evento_curso_id', $item->evento_curso_id)
                ->where('status', StatusListaEspera::Aguardando)
                ->lockForUpdate();

            if ($direcao === 'up') {
                $vizinho = $query
                    ->where('posicao', '<', $item->posicao)
                    ->orderByDesc('posicao')
                    ->first();
            } else {
                $vizinho = $query
                    ->where('posicao', '>', $item->posicao)
                    ->orderBy('posicao')
                    ->first();
            }

            if (! $vizinho) {
                return false;
            }

            $posicaoAtual = $item->posicao;
            $item->update(['posicao' => $vizinho->posicao]);
            $vizinho->update(['posicao' => $posicaoAtual]);

            return true;
        });
    }

    public function inscreverDaListaEspera(ListaEspera $item): bool
    {
        return DB::transaction(function () use ($item) {
            $item = ListaEspera::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            if ($item->status !== StatusListaEspera::Aguardando) {
                return false;
            }

            $evento = EventoCurso::query()
                ->with('curso')
                ->lockForUpdate()
                ->findOrFail($item->evento_curso_id);

            $limiteVagas = (int) $evento->curso?->limite_vagas;

            $ocupadas = Matricula::query()
                ->where('evento_curso_id', $evento->id)
                ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
                ->lockForUpdate()
                ->count();

            if ($limiteVagas > 0 && $ocupadas >= $limiteVagas) {
                return false;
            }

            $matricula = Matricula::query()
                ->where('evento_curso_id', $evento->id)
                ->where('aluno_id', $item->aluno_id)
                ->lockForUpdate()
                ->first();

            if (! $matricula) {
                Matricula::create([
                    'aluno_id' => $item->aluno_id,
                    'evento_curso_id' => $evento->id,
                    'status' => StatusMatricula::Confirmada,
                    'data_confirmacao' => CarbonImmutable::now(),
                ]);
            } else {
                $matricula->update([
                    'status' => StatusMatricula::Confirmada,
                    'data_confirmacao' => CarbonImmutable::now(),
                    'data_expiracao' => null,
                ]);
            }

            $item->delete();
            $this->reordenarListaEspera($evento->id);

            return true;
        });
    }

    public function removerDaListaEspera(ListaEspera $item): void
    {
        DB::transaction(function () use ($item) {
            $item = ListaEspera::query()
                ->lockForUpdate()
                ->findOrFail($item->id);

            $eventoId = $item->evento_curso_id;
            $item->delete();

            $this->reordenarListaEspera($eventoId);
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

    public function enviarConfirmacoesAgendadas(): int
    {
        $diasAntes = (int) $this->configuracaoService->get(
            'notificacao.auto.inscricao_confirmacao.dias_antes',
            0
        );
        if ($diasAntes <= 0) {
            return 0;
        }

        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.ativo', true);
        if (! $autoAtivo) {
            return 0;
        }

        $dataAlvo = CarbonImmutable::now()->addDays($diasAntes)->startOfDay();
        $limite = $dataAlvo->endOfDay();
        $tempoLimiteHoras = (int) $this->configuracaoService->get(
            'notificacao.auto.inscricao_confirmacao.tempo_limite_horas',
            24
        );

        $eventos = EventoCurso::query()
            ->with([
                'curso',
                'matriculas' => function ($query) {
                    $query->where('status', StatusMatricula::Pendente)
                        ->with('aluno');
                },
            ])
            ->whereBetween('data_inicio', [$dataAlvo, $limite])
            ->where('ativo', true)
            ->get();

        $notificacoes = 0;

        foreach ($eventos as $evento) {
            foreach ($evento->matriculas as $matricula) {
                if (! $matricula->aluno) {
                    continue;
                }

                if ($this->confirmacaoJaEnviada($matricula, $evento)) {
                    continue;
                }

                $this->notificarConfirmacaoInscricao($matricula, $evento, $tempoLimiteHoras);
                $notificacoes++;
            }
        }

        return $notificacoes;
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

        $ocupadas = Matricula::query()
            ->where('evento_curso_id', $evento->id)
            ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
            ->lockForUpdate()
            ->count();

        $vagasDisponiveis = $limiteVagas - $ocupadas;

        if ($vagasDisponiveis <= 0) {
            return 0;
        }

        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.ativo', true);

        if (! $autoAtivo) {
            return 0;
        }

        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.email', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.whatsapp', false);
        $emailGlobal = (bool) $this->configuracaoService->get('notificacao.email_ativo', true);
        $whatsappGlobal = (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false);
        $emailAtivo = $emailAtivo && $emailGlobal;
        $whatsappAtivo = $whatsappAtivo && $whatsappGlobal;
        $modo = (string) $this->configuracaoService->get('notificacao.auto.lista_espera.modo', 'sequencial');
        $intervaloMinutos = (int) $this->configuracaoService->get('notificacao.auto.lista_espera.intervalo_minutos', 60);
        $agora = CarbonImmutable::now();

        if (! $emailAtivo && ! $whatsappAtivo) {
            return 0;
        }

        if ($modo === 'sequencial') {
            $ultimoChamado = ListaEspera::query()
                ->where('evento_curso_id', $evento->id)
                ->where('status', StatusListaEspera::Chamado)
                ->orderByDesc('chamado_em')
                ->lockForUpdate()
                ->value('chamado_em');

            if ($ultimoChamado) {
                $limiteIntervalo = CarbonImmutable::parse($ultimoChamado)->addMinutes($intervaloMinutos);
                if ($limiteIntervalo->isFuture()) {
                    return 0;
                }
            }
        }

        $listaEsperaQuery = ListaEspera::query()
            ->with('aluno')
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusListaEspera::Aguardando)
            ->orderBy('posicao')
            ->orderBy('created_at')
            ->lockForUpdate();

        if ($modo === 'sequencial') {
            $listaEsperaQuery->limit(1);
        }

        $listaEspera = $listaEsperaQuery->get();

        if ($listaEspera->isEmpty()) {
            return 0;
        }

        $processadas = 0;

        foreach ($listaEspera as $item) {
            if (! $item->aluno) {
                continue;
            }

            $this->notificationService->disparar(
                [$item->aluno],
                $evento,
                NotificationType::LISTA_ESPERA_CHAMADA,
                $emailAtivo,
                $whatsappAtivo
            );

            $item->update([
                'status' => StatusListaEspera::Chamado,
                'chamado_em' => $agora,
            ]);

            $processadas++;

            if ($modo === 'sequencial') {
                break;
            }
        }

        return $processadas;
    }

    private function reordenarListaEspera(int $eventoId): void
    {
        $itens = ListaEspera::query()
            ->where('evento_curso_id', $eventoId)
            ->where('status', StatusListaEspera::Aguardando)
            ->orderBy('posicao')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $posicao = 1;

        foreach ($itens as $item) {
            if ($item->posicao !== $posicao) {
                $item->update(['posicao' => $posicao]);
            }
            $posicao++;
        }
    }

    private function notificarConfirmacaoInscricao(Matricula $matricula, EventoCurso $evento, int $tempoLimiteHoras): void
    {
        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.ativo', true);

        if (! $autoAtivo) {
            return;
        }

        $matricula->update([
            'data_expiracao' => CarbonImmutable::now()->addHours(max(1, $tempoLimiteHoras)),
        ]);

        $aluno = Aluno::query()->find($matricula->aluno_id);

        if (! $aluno) {
            return;
        }

        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.canal.email', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.canal.whatsapp', false);
        $validadeMinutos = max(1, $tempoLimiteHoras) * 60;

        $this->notificationService->disparar(
            [$aluno],
            $evento,
            NotificationType::INSCRICAO_CONFIRMAR,
            $emailAtivo,
            $whatsappAtivo,
            $validadeMinutos
        );
    }

    private function deveEnviarConfirmacaoAgora(EventoCurso $evento, int $diasAntes): bool
    {
        if ($diasAntes <= 0) {
            return true;
        }

        if (! $evento->data_inicio) {
            return true;
        }

        $limite = CarbonImmutable::now()->addDays($diasAntes)->endOfDay();

        return $evento->data_inicio->endOfDay()->lessThanOrEqualTo($limite);
    }

    private function confirmacaoJaEnviada(Matricula $matricula, EventoCurso $evento): bool
    {
        return NotificationLog::query()
            ->where('aluno_id', $matricula->aluno_id)
            ->where('evento_curso_id', $evento->id)
            ->where('notification_type', NotificationType::INSCRICAO_CONFIRMAR->value)
            ->where('status', 'success')
            ->exists();
    }

    private function notificarMatriculaConfirmada(Matricula $matricula, EventoCurso $evento): void
    {
        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.ativo', true);

        if (! $autoAtivo) {
            return;
        }

        $aluno = Aluno::query()->find($matricula->aluno_id);

        if (! $aluno) {
            return;
        }

        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.email', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.whatsapp', false);

        $this->notificationService->disparar(
            [$aluno],
            $evento,
            NotificationType::MATRICULA_CONFIRMADA,
            $emailAtivo,
            $whatsappAtivo
        );
    }
}
