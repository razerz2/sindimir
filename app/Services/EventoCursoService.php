<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\Aluno;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EventoCursoService
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function create(array $data): EventoCurso
    {
        return DB::transaction(function () use ($data) {
            $evento = EventoCurso::create($data);
            $evento->loadMissing('curso');

            $this->notificarEventoCriado($evento);

            return $evento;
        });
    }

    public function update(EventoCurso $eventoCurso, array $data): EventoCurso
    {
        return DB::transaction(function () use ($eventoCurso, $data) {
            $ativoAntes = (bool) $eventoCurso->ativo;
            $eventoCurso->update($data);
            $eventoCurso->loadMissing('curso');

            if ($ativoAntes && ! $eventoCurso->ativo) {
                $this->notificarEventoCancelado($eventoCurso);
            }

            return $eventoCurso;
        });
    }

    public function delete(EventoCurso $eventoCurso): void
    {
        DB::transaction(function () use ($eventoCurso) {
            $eventoCurso->loadMissing('curso');
            if ($eventoCurso->ativo) {
                $this->notificarEventoCancelado($eventoCurso);
            }
            $eventoCurso->delete();
        });
    }

    /**
     * @return array{
     *     total_vagas: int,
     *     total_inscritos: int,
     *     total_lista_espera: int,
     *     vagas_disponiveis: int,
     *     total_confirmadas: int
     * }
     */
    public function resumoVagas(EventoCurso $evento): array
    {
        $evento->loadMissing('curso');

        $totalVagas = (int) $evento->curso?->limite_vagas;

        $totalInscritos = Matricula::query()
            ->where('evento_curso_id', $evento->id)
            ->whereIn('status', [
                StatusMatricula::Confirmada->value,
                StatusMatricula::Pendente->value,
            ])
            ->count();

        $totalListaEspera = ListaEspera::query()
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusListaEspera::Aguardando)
            ->count();

        $totalConfirmadas = Matricula::query()
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusMatricula::Confirmada->value)
            ->count();

        $vagasDisponiveis = max($totalVagas - $totalConfirmadas, 0);

        return [
            'total_vagas' => $totalVagas,
            'total_inscritos' => $totalInscritos,
            'total_lista_espera' => $totalListaEspera,
            'vagas_disponiveis' => $vagasDisponiveis,
            'total_confirmadas' => $totalConfirmadas,
        ];
    }

    /**
     * @return Collection<int, Matricula>
     */
    public function inscritos(EventoCurso $evento): Collection
    {
        return Matricula::query()
            ->with('aluno')
            ->where('evento_curso_id', $evento->id)
            ->whereIn('status', [
                StatusMatricula::Confirmada->value,
                StatusMatricula::Pendente->value,
            ])
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, ListaEspera>
     */
    public function listaEspera(EventoCurso $evento): Collection
    {
        return ListaEspera::query()
            ->with('aluno')
            ->where('evento_curso_id', $evento->id)
            ->where('status', StatusListaEspera::Aguardando->value)
            ->orderBy('posicao')
            ->orderBy('created_at')
            ->get();
    }

    private function notificarEventoCriado(EventoCurso $evento): void
    {
        if (! $evento->ativo) {
            return;
        }

        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.ativo', false);

        if (! $autoAtivo) {
            return;
        }

        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.canal.email', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.canal.whatsapp', false);
        $alunos = Aluno::query()->get();

        if ($alunos->isEmpty() && $this->somenteAlunosComoDestinatarios()) {
            return;
        }

        $this->notificationService->disparar(
            $alunos,
            $evento,
            NotificationType::EVENTO_CRIADO,
            $emailAtivo,
            $whatsappAtivo
        );
    }

    private function notificarEventoCancelado(EventoCurso $evento): void
    {
        $autoAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.ativo', true);

        if (! $autoAtivo) {
            return;
        }

        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.canal.email', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.canal.whatsapp', false);

        $evento->loadMissing([
            'curso',
            'matriculas.aluno',
            'listaEspera.aluno',
        ]);

        $inscritos = $evento->matriculas
            ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
            ->pluck('aluno')
            ->filter()
            ->values();
        $lista = $evento->listaEspera
            ->whereIn('status', [StatusListaEspera::Aguardando, StatusListaEspera::Chamado])
            ->pluck('aluno')
            ->filter()
            ->values();

        $alunos = $inscritos->merge($lista)->unique('id')->values();

        if ($alunos->isEmpty() && $this->somenteAlunosComoDestinatarios()) {
            return;
        }

        $this->notificationService->disparar(
            $alunos,
            $evento,
            NotificationType::EVENTO_CANCELADO,
            $emailAtivo,
            $whatsappAtivo
        );
    }

    private function somenteAlunosComoDestinatarios(): bool
    {
        $destinatarios = (string) $this->configuracaoService->get('notificacao.destinatarios', 'alunos');

        return ! in_array($destinatarios, ['contatos_externos', 'ambos'], true);
    }
}
