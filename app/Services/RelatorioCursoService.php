<?php

namespace App\Services;

use App\Enums\StatusMatricula;
use App\Exports\CursosExport;
use App\Models\Curso;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioCursoService
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
        $export = new CursosExport($query);

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
            'eventosOptions' => $this->getEventosOptions(),
            'vagasOptions' => $this->getVagasOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $eventosTotal = DB::table('evento_cursos')
            ->select('curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('curso_id');

        $eventosAtivos = DB::table('evento_cursos')
            ->select('curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->where('ativo', true)
            ->groupBy('curso_id');

        $matriculasConfirmadas = DB::table('matriculas')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'matriculas.evento_curso_id')
            ->select('evento_cursos.curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('matriculas.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->where('matriculas.status', StatusMatricula::Confirmada->value)
            ->groupBy('evento_cursos.curso_id');

        $matriculasTotal = DB::table('matriculas')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'matriculas.evento_curso_id')
            ->select('evento_cursos.curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('matriculas.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->groupBy('evento_cursos.curso_id');

        $listaTotal = DB::table('lista_espera')
            ->join('evento_cursos', 'evento_cursos.id', '=', 'lista_espera.evento_curso_id')
            ->select('evento_cursos.curso_id', DB::raw('COUNT(*) as total'))
            ->whereNull('lista_espera.deleted_at')
            ->whereNull('evento_cursos.deleted_at')
            ->groupBy('evento_cursos.curso_id');

        $query = DB::table('cursos')
            ->leftJoinSub($eventosTotal, 'eventos_total', function ($join) {
                $join->on('eventos_total.curso_id', '=', 'cursos.id');
            })
            ->leftJoinSub($eventosAtivos, 'eventos_ativos', function ($join) {
                $join->on('eventos_ativos.curso_id', '=', 'cursos.id');
            })
            ->leftJoinSub($matriculasConfirmadas, 'matriculas_confirmadas', function ($join) {
                $join->on('matriculas_confirmadas.curso_id', '=', 'cursos.id');
            })
            ->leftJoinSub($matriculasTotal, 'matriculas_total', function ($join) {
                $join->on('matriculas_total.curso_id', '=', 'cursos.id');
            })
            ->leftJoinSub($listaTotal, 'lista_total', function ($join) {
                $join->on('lista_total.curso_id', '=', 'cursos.id');
            })
            ->whereNull('cursos.deleted_at')
            ->select([
                'cursos.id',
                'cursos.nome as curso_nome',
                'cursos.ativo',
                'cursos.limite_vagas',
                'cursos.created_at',
            ])
            ->selectRaw('COALESCE(eventos_total.total, 0) as eventos_total')
            ->selectRaw('COALESCE(eventos_ativos.total, 0) as eventos_ativos')
            ->selectRaw('COALESCE(matriculas_confirmadas.total, 0) as matriculas_confirmadas')
            ->selectRaw('COALESCE(matriculas_total.total, 0) as matriculas_total')
            ->selectRaw('COALESCE(lista_total.total, 0) as lista_total')
            ->selectRaw('(COALESCE(matriculas_total.total, 0) + COALESCE(lista_total.total, 0)) as inscricoes_total')
            ->selectRaw('(COALESCE(cursos.limite_vagas, 0) * COALESCE(eventos_total.total, 0)) as vagas_totais')
            ->selectRaw(
                'GREATEST((COALESCE(cursos.limite_vagas, 0) * COALESCE(eventos_total.total, 0)) - COALESCE(matriculas_confirmadas.total, 0), 0) as vagas_disponiveis'
            );

        $this->applyFilters($query, $filtros);

        $query->orderBy('cursos.created_at', 'desc');

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

        if (! empty($filtros['status'])) {
            $query->where('cursos.ativo', $filtros['status'] === 'ativo');
        }

        if (! empty($filtros['possui_eventos_ativos'])) {
            if ($filtros['possui_eventos_ativos'] === 'sim') {
                $query->whereRaw('COALESCE(eventos_ativos.total, 0) > 0');
            } else {
                $query->whereRaw('COALESCE(eventos_ativos.total, 0) = 0');
            }
        }

        if (! empty($filtros['possui_vagas'])) {
            $operator = $filtros['possui_vagas'] === 'sim' ? '>' : '<=';
            $query->whereRaw(
                'GREATEST((COALESCE(cursos.limite_vagas, 0) * COALESCE(eventos_total.total, 0)) - COALESCE(matriculas_confirmadas.total, 0), 0) '
                . $operator . ' 0'
            );
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('cursos.created_at', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('cursos.created_at', '<=', $filtros['data_fim']);
        }
    }

    private function mapRow(object $row): object
    {
        $row->status_label = $row->ativo ? 'Ativo' : 'Inativo';
        $row->created_at_formatada = $this->formatDate($row->created_at);
        $row->vagas_badge = $this->getVagasBadge($row->vagas_disponiveis, (bool) $row->ativo);
        $row->lista_badge = $this->getListaBadge($row->lista_total);

        return $row;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'ativo', 'label' => 'Ativo'],
            ['value' => 'inativo', 'label' => 'Inativo'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getEventosOptions(): array
    {
        return [
            ['value' => 'sim', 'label' => 'Sim'],
            ['value' => 'nao', 'label' => 'Não'],
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

    private function getVagasBadge(int $vagasDisponiveis, bool $ativo): string
    {
        if (! $ativo) {
            return 'neutral';
        }

        return $vagasDisponiveis <= 0 ? 'danger' : 'success';
    }

    private function getListaBadge(int $listaTotal): string
    {
        return $listaTotal > 0 ? 'warning' : '';
    }

    private function formatDate(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
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
