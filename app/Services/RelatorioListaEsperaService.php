<?php

namespace App\Services;

use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Exports\ListaEsperaExport;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Support\Cpf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioListaEsperaService
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
        $export = new ListaEsperaExport($query);

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
            'posicaoOptions' => $this->getPosicaoOptions(),
            'matriculaOptions' => $this->getMatriculaOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $query = DB::table('lista_espera')
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
                'lista_espera.id',
                'lista_espera.created_at as data_entrada',
                'lista_espera.status as status_lista',
                'lista_espera.posicao',
                'lista_espera.chamado_em',
                'alunos.nome_completo as aluno_nome',
                'alunos.cpf as aluno_cpf',
                'cursos.nome as curso_nome',
                'evento_cursos.numero_evento as evento_numero',
                'evento_cursos.data_inicio as evento_data_inicio',
                'matriculas.id as matricula_id',
                'matriculas.status as status_matricula',
            ]);

        $this->applyFilters($query, $filtros);

        $query->orderBy('lista_espera.created_at', 'desc');

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

        if (! empty($filtros['evento_curso_id'])) {
            $query->where('lista_espera.evento_curso_id', (int) $filtros['evento_curso_id']);
        }

        if (! empty($filtros['status'])) {
            $query->where('lista_espera.status', $filtros['status']);
        }

        if (! empty($filtros['posicao_max'])) {
            $query->whereNotNull('lista_espera.posicao')
                ->where('lista_espera.posicao', '<=', (int) $filtros['posicao_max']);
        }

        if (! empty($filtros['possui_matricula'])) {
            if ($filtros['possui_matricula'] === 'sim') {
                $query->whereNotNull('matriculas.id');
            } else {
                $query->whereNull('matriculas.id');
            }
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('lista_espera.created_at', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('lista_espera.created_at', '<=', $filtros['data_fim']);
        }
    }

    private function mapRow(object $row): object
    {
        $row->status_label = $this->getStatusListaLabel($row->status_lista, $row->matricula_id);
        $row->status_badge = $this->getStatusListaBadge($row->status_lista, $row->matricula_id);
        $row->data_entrada_formatada = $this->formatDateTime($row->data_entrada);
        $row->chamado_em_formatada = $this->formatDateTime($row->chamado_em);
        $row->respondeu_label = $row->matricula_id ? 'Sim' : 'Não';
        $row->matricula_gerada_label = $row->matricula_id ? 'Sim' : 'Não';
        $row->status_matricula_label = $this->getStatusMatriculaLabel($row->status_matricula);
        $row->evento_label = $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio);
        $row->aluno_cpf = Cpf::format($row->aluno_cpf);

        return $row;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStatusOptions(): array
    {
        return collect(StatusListaEspera::cases())
            ->map(fn (StatusListaEspera $status) => [
                'value' => $status->value,
                'label' => $this->getStatusListaLabel($status->value, null),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function getPosicaoOptions(): array
    {
        return [
            ['value' => 5, 'label' => 'Top 5'],
            ['value' => 10, 'label' => 'Top 10'],
            ['value' => 20, 'label' => 'Top 20'],
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

    private function getStatusListaLabel(string $status, ?int $matriculaId): string
    {
        if ($matriculaId) {
            return 'Convertido';
        }

        return match ($status) {
            StatusListaEspera::Chamado->value => 'Chamado',
            StatusListaEspera::Expirado->value,
            StatusListaEspera::Cancelado->value => 'Expirado',
            default => 'Aguardando',
        };
    }

    private function getStatusListaBadge(string $status, ?int $matriculaId): string
    {
        if ($matriculaId) {
            return 'success';
        }

        return match ($status) {
            StatusListaEspera::Chamado->value => 'info',
            StatusListaEspera::Expirado->value,
            StatusListaEspera::Cancelado->value => 'danger',
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
