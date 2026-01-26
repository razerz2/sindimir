<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Exports\NotificacoesExport;
use App\Models\Curso;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioNotificacaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $perPage = $this->resolvePerPage($filtros);
        $query = $this->buildQuery($filtros);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarExcel(array $filtros): Response
    {
        $query = $this->buildQuery($filtros, true);
        $export = new NotificacoesExport($query);

        return $export->download();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiltroData(): array
    {
        $cursos = Curso::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $cursoOptions = $cursos->map(fn (Curso $curso) => [
            'value' => $curso->id,
            'label' => $curso->nome,
        ])->all();

        return [
            'cursoOptions' => $cursoOptions,
            'tipoOptions' => $this->getTipoOptions()->all(),
            'canalOptions' => $this->getCanalOptions(),
            'statusOptions' => $this->getStatusOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $query = DB::table('notificacao_logs')
            ->join('alunos', 'alunos.id', '=', 'notificacao_logs.aluno_id')
            ->join('cursos', 'cursos.id', '=', 'notificacao_logs.curso_id')
            ->leftJoin('evento_cursos', 'evento_cursos.id', '=', 'notificacao_logs.evento_curso_id')
            ->select([
                'notificacao_logs.id',
                'notificacao_logs.created_at as data_envio',
                'alunos.nome_completo as aluno_nome',
                'cursos.nome as curso_nome',
                'evento_cursos.numero_evento as evento_numero',
                'evento_cursos.data_inicio as evento_data_inicio',
                'notificacao_logs.notification_type',
                'notificacao_logs.canal',
                'notificacao_logs.status',
                'notificacao_logs.erro',
            ]);

        $this->applyFilters($query, $filtros);

        $query->orderBy('notificacao_logs.created_at', 'desc');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function applyFilters(Builder $query, array $filtros): void
    {
        if (! empty($filtros['notification_type'])) {
            $query->where('notificacao_logs.notification_type', $filtros['notification_type']);
        }

        if (! empty($filtros['canal'])) {
            $query->where('notificacao_logs.canal', $filtros['canal']);
        }

        if (! empty($filtros['status'])) {
            $query->where('notificacao_logs.status', $filtros['status']);
        }

        if (! empty($filtros['curso_id'])) {
            $query->where('notificacao_logs.curso_id', (int) $filtros['curso_id']);
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('notificacao_logs.created_at', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('notificacao_logs.created_at', '<=', $filtros['data_fim']);
        }
    }

    private function mapRow(object $row): object
    {
        $row->tipo_label = $this->getTipoLabel($row->notification_type);
        $row->canal_label = $this->getCanalLabel($row->canal);
        $row->status_label = $this->getStatusLabel($row->status);
        $row->status_badge = $this->getStatusBadgeClass($row->status);
        $row->evento_label = $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio);
        $row->data_envio_formatada = $this->formatDateTime($row->data_envio);
        $row->erro_label = $row->erro ?: '-';

        return $row;
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function getTipoOptions(): Collection
    {
        return collect(NotificationType::cases())
            ->map(fn (NotificationType $type) => [
                'value' => $type->value,
                'label' => $this->getTipoLabel($type->value),
            ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getCanalOptions(): array
    {
        return [
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'whatsapp', 'label' => 'WhatsApp'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'success', 'label' => 'Sucesso'],
            ['value' => 'blocked', 'label' => 'Bloqueado'],
            ['value' => 'failed', 'label' => 'Falha'],
        ];
    }

    private function getTipoLabel(string $tipo): string
    {
        return match ($tipo) {
            NotificationType::EVENTO_CRIADO->value => 'Evento criado',
            NotificationType::EVENTO_CANCELADO->value => 'Evento cancelado',
            NotificationType::INSCRICAO_CONFIRMAR->value => 'Confirmacao de inscricao',
            NotificationType::INSCRICAO_CANCELADA->value => 'Inscricao cancelada',
            NotificationType::CURSO_DISPONIVEL->value => 'Curso disponivel',
            NotificationType::VAGA_ABERTA->value => 'Vaga aberta',
            NotificationType::LEMBRETE_CURSO->value => 'Lembrete de curso',
            NotificationType::MATRICULA_CONFIRMADA->value => 'Matricula confirmada',
            NotificationType::LISTA_ESPERA_CHAMADA->value => 'Lista de espera chamada',
            default => $tipo,
        };
    }

    private function getCanalLabel(string $canal): string
    {
        return $canal === 'whatsapp' ? 'WhatsApp' : 'Email';
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Sucesso',
            'blocked' => 'Bloqueado',
            'failed' => 'Falha',
            default => $status,
        };
    }

    private function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'success' => 'success',
            'blocked' => 'warning',
            'failed' => 'danger',
            default => 'warning',
        };
    }

    private function formatEventoLabel(?string $numeroEvento, ?string $dataInicio): string
    {
        if (! $numeroEvento) {
            return '-';
        }

        $dataLabel = $dataInicio ? CarbonImmutable::parse($dataInicio)->format('d/m/Y') : null;

        return $dataLabel
            ? "Evento {$numeroEvento} ({$dataLabel})"
            : "Evento {$numeroEvento}";
    }

    private function formatDateTime(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y H:i');
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function resolvePerPage(array $filtros): int
    {
        $perPage = (int) ($filtros['per_page'] ?? 15);

        return in_array($perPage, [15, 25, 50], true) ? $perPage : 15;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function getPerPageOptions(): array
    {
        return [
            ['value' => 15, 'label' => '15'],
            ['value' => 25, 'label' => '25'],
            ['value' => 50, 'label' => '50'],
        ];
    }
}
