<?php

namespace App\Services;

use App\Enums\StatusMatricula;
use App\Exports\InscricoesExport;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Support\Cpf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioInscricaoService
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
        $export = new InscricoesExport($query);

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
            'statusOptions' => $this->getStatusOptions(),
            'origemOptions' => $this->getOrigemOptions(),
            'matriculaOptions' => $this->getMatriculaOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $matriculasQuery = $this->buildMatriculasQuery();
        $listaEsperaQuery = $this->buildListaEsperaQuery();

        $union = $matriculasQuery->unionAll($listaEsperaQuery);

        $query = DB::query()->fromSub($union, 'inscricoes');

        $this->applyFilters($query, $filtros);

        $query->orderBy('data_inscricao', 'desc');

        return $query;
    }

    private function buildMatriculasQuery(): Builder
    {
        $notificationSubquery = $this->buildNotificationSubquery('matriculas');
        $manualSubquery = $this->buildManualSubquery('matriculas', Matricula::class);

        return DB::table('matriculas')
            ->join('alunos', 'alunos.id', '=', 'matriculas.aluno_id')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'matriculas.evento_curso_id')
            ->join('cursos', 'cursos.id', '=', 'evento_cursos.curso_id')
            ->whereNull('matriculas.deleted_at')
            ->whereNull('alunos.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->select([
                'matriculas.id as inscricao_id',
                'matriculas.created_at as data_inscricao',
                'matriculas.aluno_id',
                'matriculas.evento_curso_id',
                'evento_cursos.curso_id',
                'alunos.nome_completo as aluno_nome',
                'alunos.cpf as aluno_cpf',
                'cursos.nome as curso_nome',
                'evento_cursos.numero_evento as evento_numero',
                'evento_cursos.data_inicio as evento_data_inicio',
                'matriculas.status as status_matricula',
            ])
            ->selectRaw("'matricula' as origem_base")
            ->selectRaw(
                "CASE
                    WHEN matriculas.status = ? THEN 'convertida'
                    WHEN matriculas.status = ? THEN 'cancelada'
                    ELSE 'ativa'
                END as status_inscricao",
                [StatusMatricula::Confirmada->value, StatusMatricula::Cancelada->value]
            )
            ->selectRaw('matriculas.id as matricula_id')
            ->selectRaw(
                'EXISTS (' . $notificationSubquery->toSql() . ') as tem_notificacao',
                $notificationSubquery->getBindings()
            )
            ->selectRaw(
                'EXISTS (' . $manualSubquery->toSql() . ') as tem_manual',
                $manualSubquery->getBindings()
            );
    }

    private function buildListaEsperaQuery(): Builder
    {
        $notificationSubquery = $this->buildNotificationSubquery('lista_espera');
        $manualSubquery = $this->buildManualSubquery('lista_espera', ListaEspera::class);

        return DB::table('lista_espera')
            ->join('alunos', 'alunos.id', '=', 'lista_espera.aluno_id')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'lista_espera.evento_curso_id')
            ->join('cursos', 'cursos.id', '=', 'evento_cursos.curso_id')
            ->leftJoin('matriculas', function ($join) {
                $join->on('matriculas.aluno_id', '=', 'lista_espera.aluno_id')
                    ->on('matriculas.evento_curso_id', '=', 'lista_espera.evento_curso_id')
                    ->whereNull('matriculas.deleted_at');
            })
            ->whereNull('lista_espera.deleted_at')
            ->whereNull('alunos.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->select([
                'lista_espera.id as inscricao_id',
                'lista_espera.created_at as data_inscricao',
                'lista_espera.aluno_id',
                'lista_espera.evento_curso_id',
                'evento_cursos.curso_id',
                'alunos.nome_completo as aluno_nome',
                'alunos.cpf as aluno_cpf',
                'cursos.nome as curso_nome',
                'evento_cursos.numero_evento as evento_numero',
                'evento_cursos.data_inicio as evento_data_inicio',
                'matriculas.status as status_matricula',
            ])
            ->selectRaw("'lista_espera' as origem_base")
            ->selectRaw(
                "CASE
                    WHEN matriculas.id IS NOT NULL THEN 'convertida'
                    WHEN lista_espera.status IN ('cancelado', 'expirado') THEN 'cancelada'
                    ELSE 'ativa'
                END as status_inscricao"
            )
            ->selectRaw('matriculas.id as matricula_id')
            ->selectRaw(
                'EXISTS (' . $notificationSubquery->toSql() . ') as tem_notificacao',
                $notificationSubquery->getBindings()
            )
            ->selectRaw(
                'EXISTS (' . $manualSubquery->toSql() . ') as tem_manual',
                $manualSubquery->getBindings()
            );
    }

    private function buildNotificationSubquery(string $inscricaoTable): Builder
    {
        return DB::table('notificacao_logs')
            ->selectRaw('1')
            ->whereColumn('notificacao_logs.aluno_id', "{$inscricaoTable}.aluno_id")
            ->whereColumn('notificacao_logs.created_at', '<=', "{$inscricaoTable}.created_at")
            ->where(function (Builder $query) use ($inscricaoTable) {
                $query->whereColumn('notificacao_logs.evento_curso_id', "{$inscricaoTable}.evento_curso_id")
                    ->orWhere(function (Builder $subquery) {
                        $subquery->whereNull('notificacao_logs.evento_curso_id')
                            ->whereColumn('notificacao_logs.curso_id', 'evento_cursos.curso_id');
                    });
            });
    }

    private function buildManualSubquery(string $inscricaoTable, string $entityType): Builder
    {
        return DB::table('auditorias')
            ->selectRaw('1')
            ->where('auditorias.acao', 'criado')
            ->where('auditorias.entidade_type', $entityType)
            ->whereNotNull('auditorias.user_id')
            ->whereColumn('auditorias.entidade_id', "{$inscricaoTable}.id");
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function applyFilters(Builder $query, array $filtros): void
    {
        if (! empty($filtros['curso_id'])) {
            $query->where('curso_id', (int) $filtros['curso_id']);
        }

        if (! empty($filtros['evento_curso_id'])) {
            $query->where('evento_curso_id', (int) $filtros['evento_curso_id']);
        }

        if (! empty($filtros['status_inscricao'])) {
            $query->where('status_inscricao', $filtros['status_inscricao']);
        }

        if (! empty($filtros['possui_matricula'])) {
            if ($filtros['possui_matricula'] === 'sim') {
                $query->whereNotNull('matricula_id');
            } else {
                $query->whereNull('matricula_id');
            }
        }

        if (! empty($filtros['origem'])) {
            if ($filtros['origem'] === 'notificacao') {
                $query->where('tem_notificacao', true);
            } elseif ($filtros['origem'] === 'manual') {
                $query->where('tem_notificacao', false)->where('tem_manual', true);
            } else {
                $query->where('tem_notificacao', false)->where('tem_manual', false);
            }
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('data_inscricao', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('data_inscricao', '<=', $filtros['data_fim']);
        }
    }

    private function mapRow(object $row): object
    {
        $row->status_inscricao_label = $this->getStatusInscricaoLabel($row->status_inscricao);
        $row->status_inscricao_badge = $this->getStatusInscricaoBadge($row->status_inscricao);
        $row->matricula_gerada_label = $row->matricula_id ? 'Sim' : 'Não';
        $row->status_matricula_label = $this->getStatusMatriculaLabel($row->status_matricula);
        $row->origem_label = $this->getOrigemLabel((bool) $row->tem_notificacao, (bool) $row->tem_manual);
        $row->evento_label = $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio);
        $row->data_inscricao_formatada = $this->formatDateTime($row->data_inscricao);
        $row->aluno_cpf = Cpf::format($row->aluno_cpf);

        return $row;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'ativa', 'label' => 'Ativa'],
            ['value' => 'convertida', 'label' => 'Convertida'],
            ['value' => 'cancelada', 'label' => 'Cancelada'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getOrigemOptions(): array
    {
        return [
            ['value' => 'publica', 'label' => 'Pública'],
            ['value' => 'manual', 'label' => 'Manual (admin)'],
            ['value' => 'notificacao', 'label' => 'Notificação'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getMatriculaOptions(): array
    {
        return [
            ['value' => 'sim', 'label' => 'Sim'],
            ['value' => 'nao', 'label' => 'Não'],
        ];
    }

    private function getStatusInscricaoLabel(string $status): string
    {
        return match ($status) {
            'convertida' => 'Convertida',
            'cancelada' => 'Cancelada',
            default => 'Ativa',
        };
    }

    private function getStatusInscricaoBadge(string $status): string
    {
        return match ($status) {
            'convertida' => 'success',
            'cancelada' => 'danger',
            default => 'warning',
        };
    }

    private function getStatusMatriculaLabel(?string $status): string
    {
        if (! $status) {
            return '-';
        }

        return match ($status) {
            StatusMatricula::Confirmada->value => 'Confirmada',
            StatusMatricula::Cancelada->value => 'Cancelada',
            StatusMatricula::Expirada->value => 'Vencida/Expirada',
            default => 'Pendente',
        };
    }

    private function getOrigemLabel(bool $temNotificacao, bool $temManual): string
    {
        if ($temNotificacao) {
            return 'Notificação';
        }

        if ($temManual) {
            return 'Manual';
        }

        return 'Pública';
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
