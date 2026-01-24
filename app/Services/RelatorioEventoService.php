<?php

namespace App\Services;

use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Exports\EventosExport;
use App\Models\Curso;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioEventoService
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
        $export = new EventosExport($query);

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
            'statusOptions' => $this->getStatusOptions(),
            'vagasOptions' => $this->getVagasOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $matriculasTotal = DB::table('matriculas')
            ->select('evento_curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('evento_curso_id');

        $matriculasConfirmadas = DB::table('matriculas')
            ->select('evento_curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->where('status', StatusMatricula::Confirmada->value)
            ->groupBy('evento_curso_id');

        $listaEsperaTotal = DB::table('lista_espera')
            ->select('evento_curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('evento_curso_id');

        $listaEsperaAguardando = DB::table('lista_espera')
            ->select('evento_curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->where('status', StatusListaEspera::Aguardando->value)
            ->groupBy('evento_curso_id');

        $query = DB::table('evento_cursos')
            ->join('cursos', 'cursos.id', '=', 'evento_cursos.curso_id')
            ->leftJoinSub($matriculasTotal, 'matriculas_total', function ($join) {
                $join->on('matriculas_total.evento_curso_id', '=', 'evento_cursos.id');
            })
            ->leftJoinSub($matriculasConfirmadas, 'matriculas_confirmadas', function ($join) {
                $join->on('matriculas_confirmadas.evento_curso_id', '=', 'evento_cursos.id');
            })
            ->leftJoinSub($listaEsperaTotal, 'lista_total', function ($join) {
                $join->on('lista_total.evento_curso_id', '=', 'evento_cursos.id');
            })
            ->leftJoinSub($listaEsperaAguardando, 'lista_aguardando', function ($join) {
                $join->on('lista_aguardando.evento_curso_id', '=', 'evento_cursos.id');
            })
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->select([
                'evento_cursos.id',
                'evento_cursos.numero_evento',
                'evento_cursos.data_inicio',
                'evento_cursos.data_fim',
                'evento_cursos.turno',
                'cursos.nome as curso_nome',
                'cursos.limite_vagas',
            ])
            ->selectRaw('COALESCE(matriculas_total.total, 0) as matriculas_total')
            ->selectRaw('COALESCE(matriculas_confirmadas.total, 0) as matriculas_confirmadas')
            ->selectRaw('COALESCE(lista_total.total, 0) as lista_total')
            ->selectRaw('COALESCE(lista_aguardando.total, 0) as lista_aguardando')
            ->selectRaw('(COALESCE(matriculas_total.total, 0) + COALESCE(lista_total.total, 0)) as inscricoes_total')
            ->selectRaw('GREATEST(COALESCE(cursos.limite_vagas, 0) - COALESCE(matriculas_confirmadas.total, 0), 0) as vagas_disponiveis');

        $this->applyFilters($query, $filtros);

        $query->orderBy('evento_cursos.data_inicio', 'desc');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function applyFilters(Builder $query, array $filtros): void
    {
        if (! empty($filtros['curso_id'])) {
            $query->where('evento_cursos.curso_id', (int) $filtros['curso_id']);
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('evento_cursos.data_inicio', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('evento_cursos.data_fim', '<=', $filtros['data_fim']);
        }

        if (! empty($filtros['status_evento'])) {
            $today = CarbonImmutable::today();
            if ($filtros['status_evento'] === 'futuro') {
                $query->whereDate('evento_cursos.data_inicio', '>', $today->toDateString());
            } elseif ($filtros['status_evento'] === 'em_andamento') {
                $query->whereDate('evento_cursos.data_inicio', '<=', $today->toDateString())
                    ->whereDate('evento_cursos.data_fim', '>=', $today->toDateString());
            } else {
                $query->whereDate('evento_cursos.data_fim', '<', $today->toDateString());
            }
        }

        if (! empty($filtros['possui_vagas'])) {
            $operator = $filtros['possui_vagas'] === 'sim' ? '>' : '<=';
            $query->whereRaw(
                'GREATEST(COALESCE(cursos.limite_vagas, 0) - COALESCE(matriculas_confirmadas.total, 0), 0) '
                . $operator . ' 0'
            );
        }
    }

    private function mapRow(object $row): object
    {
        $row->evento_label = $this->formatEventoLabel($row->numero_evento, $row->data_inicio);
        $row->data_inicio_formatada = $this->formatDate($row->data_inicio);
        $row->data_fim_formatada = $this->formatDate($row->data_fim);
        $row->turno_label = $this->formatTurno($row->turno);
        $row->status_evento_label = $this->getStatusEventoLabel($row->data_inicio, $row->data_fim);
        $row->vagas_badge = $this->getVagasBadge($row->vagas_disponiveis, $row->status_evento_label);
        $row->vagas_badge_label = $this->getVagasBadgeLabel($row->vagas_disponiveis, $row->status_evento_label);

        return $row;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'futuro', 'label' => 'Futuro'],
            ['value' => 'em_andamento', 'label' => 'Em andamento'],
            ['value' => 'encerrado', 'label' => 'Encerrado'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getVagasOptions(): array
    {
        return [
            ['value' => 'sim', 'label' => 'Sim'],
            ['value' => 'nao', 'label' => 'Não'],
        ];
    }

    private function getStatusEventoLabel(?string $dataInicio, ?string $dataFim): string
    {
        if (! $dataInicio || ! $dataFim) {
            return 'Indefinido';
        }

        $inicio = CarbonImmutable::parse($dataInicio)->startOfDay();
        $fim = CarbonImmutable::parse($dataFim)->endOfDay();
        $hoje = CarbonImmutable::now();

        if ($hoje->lt($inicio)) {
            return 'Futuro';
        }

        if ($hoje->gt($fim)) {
            return 'Encerrado';
        }

        return 'Em andamento';
    }

    private function getVagasBadge(int $vagasDisponiveis, string $statusEvento): string
    {
        if ($statusEvento === 'Encerrado') {
            return 'neutral';
        }

        return $vagasDisponiveis <= 0 ? 'danger' : 'success';
    }

    private function getVagasBadgeLabel(int $vagasDisponiveis, string $statusEvento): string
    {
        if ($statusEvento === 'Encerrado') {
            return 'Encerrado';
        }

        return $vagasDisponiveis <= 0 ? 'Lotado' : 'Com vagas';
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

    private function formatDate(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }

    private function formatTurno(?string $turno): string
    {
        if (! $turno) {
            return '-';
        }

        return ucfirst(str_replace('_', ' ', $turno));
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
