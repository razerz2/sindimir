<?php

namespace App\Services;

use App\Enums\StatusMatricula;
use App\Exports\MatriculasExport;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Support\Cpf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioMatriculaService
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
        $export = new MatriculasExport($query);

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

        $eventos = EventoCurso::query()
            ->with('curso:id,nome')
            ->orderBy('data_inicio')
            ->get(['id', 'curso_id', 'numero_evento', 'data_inicio']);

        $cursoOptions = $cursos->map(fn (Curso $curso) => [
            'value' => $curso->id,
            'label' => $curso->nome,
        ])->all();

        $eventoOptions = $eventos->map(fn (EventoCurso $evento) => [
            'value' => $evento->id,
            'label' => $this->formatEventoFiltroLabel($evento),
        ])->all();

        return [
            'cursoOptions' => $cursoOptions,
            'eventoOptions' => $eventoOptions,
            'statusOptions' => $this->getStatusOptions()->all(),
            'canalOptions' => $this->getCanalOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $notificationSubquery = $this->buildNotificationSubquery();

        $query = DB::table('matriculas')
            ->join('alunos', 'alunos.id', '=', 'matriculas.aluno_id')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'matriculas.evento_curso_id')
            ->join('cursos', 'cursos.id', '=', 'evento_cursos.curso_id')
            ->whereNull('matriculas.deleted_at')
            ->whereNull('alunos.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->select([
                'matriculas.id',
                'alunos.nome_completo as aluno_nome',
                'alunos.cpf as aluno_cpf',
                'cursos.nome as curso_nome',
                'evento_cursos.numero_evento as evento_numero',
                'evento_cursos.data_inicio as evento_data_inicio',
                'matriculas.status',
                'matriculas.created_at as data_inscricao',
                'matriculas.data_confirmacao',
                'matriculas.data_expiracao',
            ])
            ->selectRaw(
                'EXISTS (' . $notificationSubquery->toSql() . ') as tem_notificacao',
                $notificationSubquery->getBindings()
            );

        $this->applyFilters($query, $filtros);

        if ($forExport) {
            $query->orderBy('matriculas.created_at', 'desc');
        } else {
            $query->orderBy('matriculas.created_at', 'desc');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function applyFilters(Builder $query, array $filtros): void
    {
        if (! empty($filtros['curso_id'])) {
            $query->where('cursos.id', (int) $filtros['curso_id']);
        }

        if (! empty($filtros['evento_curso_id'])) {
            $query->where('evento_cursos.id', (int) $filtros['evento_curso_id']);
        }

        if (! empty($filtros['status'])) {
            $query->where('matriculas.status', $filtros['status']);
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('matriculas.created_at', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('matriculas.created_at', '<=', $filtros['data_fim']);
        }

        if (! empty($filtros['canal_origem'])) {
            $notificationSubquery = $this->buildNotificationSubquery();
            if ($filtros['canal_origem'] === 'notificacao') {
                $query->whereExists($notificationSubquery);
            } else {
                $query->whereNotExists($notificationSubquery);
            }
        }
    }

    private function buildNotificationSubquery(): Builder
    {
        return DB::table('notificacao_logs')
            ->selectRaw('1')
            ->whereColumn('notificacao_logs.aluno_id', 'matriculas.aluno_id')
            ->whereColumn('notificacao_logs.created_at', '<=', 'matriculas.created_at')
            ->where(function (Builder $query) {
                $query->whereColumn('notificacao_logs.evento_curso_id', 'matriculas.evento_curso_id')
                    ->orWhere(function (Builder $subquery) {
                        $subquery->whereNull('notificacao_logs.evento_curso_id')
                            ->whereColumn('notificacao_logs.curso_id', 'evento_cursos.curso_id');
                    });
            });
    }

    private function mapRow(object $row): object
    {
        $row->status_label = $this->getStatusLabel($row->status);
        $row->status_badge = $this->getStatusBadgeClass($row->status);
        $row->canal_origem_label = $row->tem_notificacao ? 'Notificação' : 'Manual';
        $row->evento_label = $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio);
        $row->data_inscricao_formatada = $this->formatDateTime($row->data_inscricao);
        $row->data_confirmacao_formatada = $this->formatDateTime($row->data_confirmacao);
        $row->data_expiracao_formatada = $this->formatDateTime($row->data_expiracao);
        $row->aluno_cpf = Cpf::format($row->aluno_cpf);

        return $row;
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            StatusMatricula::Confirmada->value => 'Confirmada',
            StatusMatricula::Cancelada->value => 'Cancelada',
            StatusMatricula::Expirada->value => 'Vencida/Expirada',
            default => 'Pendente',
        };
    }

    private function getStatusBadgeClass(string $status): string
    {
        return $status === StatusMatricula::Confirmada->value ? '' : 'warning';
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): Collection
    {
        return collect(StatusMatricula::cases())
            ->map(fn (StatusMatricula $status) => [
                'value' => $status->value,
                'label' => $this->getStatusLabel($status->value),
            ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getCanalOptions(): array
    {
        return [
            ['value' => 'manual', 'label' => 'Manual'],
            ['value' => 'notificacao', 'label' => 'Notificação'],
        ];
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

    private function formatEventoFiltroLabel(EventoCurso $evento): string
    {
        $cursoNome = $evento->curso?->nome ?? 'Curso';
        $dataInicio = $evento->data_inicio?->format('d/m/Y');
        $dataLabel = $dataInicio ? " - {$dataInicio}" : '';

        return "{$cursoNome} | Evento {$evento->numero_evento}{$dataLabel}";
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
