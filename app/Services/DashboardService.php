<?php

namespace App\Services;

use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getAdminDashboardData(?string $periodType = null, ?string $periodValue = null): array
    {
        $now = CarbonImmutable::now();
        $limiteAlertaPercentual = (float) config('app.dashboard.limite_alerta_percentual', 0.8);

        $totalCursosAtivos = Curso::query()
            ->where('ativo', true)
            ->count();

        $totalAlunos = DB::table('alunos')->count();

        $matriculasPendentes = Matricula::query()
            ->where('status', StatusMatricula::Pendente)
            ->count();

        $matriculasConfirmadas = Matricula::query()
            ->where('status', StatusMatricula::Confirmada)
            ->count();

        $alunosListaEspera = ListaEspera::query()
            ->where('status', StatusListaEspera::Aguardando)
            ->count();

        $matriculasVencidas = Matricula::query()
            ->where('status', StatusMatricula::Pendente)
            ->whereNotNull('data_expiracao')
            ->where('data_expiracao', '<', $now)
            ->count();

        $eventosLotados = $this->countEventosComVagasOcupadas(1.0);
        $eventosProximosLimite = $this->countEventosComVagasOcupadas($limiteAlertaPercentual, true);

        return [
            'kpis' => [
                'total_cursos_ativos' => $totalCursosAtivos,
                'total_alunos' => $totalAlunos,
                'matriculas_pendentes' => $matriculasPendentes,
                'matriculas_confirmadas' => $matriculasConfirmadas,
                'cursos_lotados' => $eventosLotados,
                'alunos_lista_espera' => $alunosListaEspera,
            ],
            'alertas' => [
                'cursos_proximos_limite' => $eventosProximosLimite,
                'matriculas_vencidas' => $matriculasVencidas,
            ],
            'charts' => $this->buildChartData($now, $periodType, $periodValue),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChartData(CarbonImmutable $now, ?string $periodType, ?string $periodValue): array
    {
        [$inicio, $fim, $granularidade] = $this->resolvePeriodo($now, $periodType, $periodValue);

        $labels = [];
        $confirmadasSerie = [];
        $pendentesSerie = [];
        $seriePorStatus = [];

        if ($granularidade === 'month') {
            $matriculasPorMes = DB::table('matriculas')
                ->selectRaw('MONTH(created_at) as periodo, status, COUNT(*) as total')
                ->whereBetween('created_at', [$inicio, $fim])
                ->groupBy('periodo', 'status')
                ->get();

            foreach ($matriculasPorMes as $row) {
                $seriePorStatus[(int) $row->periodo][$row->status] = (int) $row->total;
            }

            for ($mes = 1; $mes <= 12; $mes++) {
                $labels[] = CarbonImmutable::create($inicio->year, $mes, 1)->format('m/Y');
                $confirmadasSerie[] = $seriePorStatus[$mes][StatusMatricula::Confirmada->value] ?? 0;
                $pendentesSerie[] = $seriePorStatus[$mes][StatusMatricula::Pendente->value] ?? 0;
            }
        } else {
            $matriculasPorDia = DB::table('matriculas')
                ->selectRaw('DATE(created_at) as periodo, status, COUNT(*) as total')
                ->whereBetween('created_at', [$inicio, $fim])
                ->groupBy('periodo', 'status')
                ->get();

            foreach ($matriculasPorDia as $row) {
                $seriePorStatus[$row->periodo][$row->status] = (int) $row->total;
            }

            $diaAtual = $inicio;
            while ($diaAtual <= $fim) {
                $dataKey = $diaAtual->toDateString();
                $labels[] = $diaAtual->format('d/m');
                $confirmadasSerie[] = $seriePorStatus[$dataKey][StatusMatricula::Confirmada->value] ?? 0;
                $pendentesSerie[] = $seriePorStatus[$dataKey][StatusMatricula::Pendente->value] ?? 0;
                $diaAtual = $diaAtual->addDay();
            }
        }

        $statusCountsQuery = DB::table('matriculas')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        $this->applyPeriodoFilter($statusCountsQuery, $periodType, $periodValue);

        $statusCounts = $statusCountsQuery
            ->pluck('total', 'status')
            ->toArray();

        $pieLabels = ['Confirmadas', 'Pendentes', 'Vencidas', 'Canceladas'];
        $pieData = [
            $statusCounts[StatusMatricula::Confirmada->value] ?? 0,
            $statusCounts[StatusMatricula::Pendente->value] ?? 0,
            $statusCounts[StatusMatricula::Expirada->value] ?? 0,
            $statusCounts[StatusMatricula::Cancelada->value] ?? 0,
        ];

        $cursosMaisProcuradosQuery = DB::table('matriculas')
            ->join('evento_cursos', 'matriculas.evento_curso_id', '=', 'evento_cursos.id')
            ->join('cursos', 'evento_cursos.curso_id', '=', 'cursos.id')
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->select('cursos.nome', DB::raw('COUNT(matriculas.id) as total'))
            ->groupBy('cursos.id', 'cursos.nome')
            ->orderByDesc('total');

        $this->applyPeriodoFilter($cursosMaisProcuradosQuery, $periodType, $periodValue);

        $cursosMaisProcurados = $cursosMaisProcuradosQuery
            ->limit(5)
            ->pluck('total', 'cursos.nome');

        return [
            'line' => [
                'labels' => $labels,
                'confirmadas' => $confirmadasSerie,
                'pendentes' => $pendentesSerie,
            ],
            'pie' => [
                'labels' => $pieLabels,
                'data' => $pieData,
            ],
            'bar' => [
                'labels' => $cursosMaisProcurados->keys()->values(),
                'data' => $cursosMaisProcurados->values(),
            ],
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function resolvePeriodo(CarbonImmutable $now, ?string $periodType, ?string $periodValue): array
    {
        if ($periodType === 'day' && $periodValue) {
            $dia = CarbonImmutable::parse($periodValue)->startOfDay();
            return [$dia, $dia->endOfDay(), 'day'];
        }

        if ($periodType === 'month' && $periodValue) {
            $mes = CarbonImmutable::parse($periodValue . '-01')->startOfMonth();
            return [$mes, $mes->endOfMonth(), 'day'];
        }

        if ($periodType === 'year' && $periodValue) {
            $ano = CarbonImmutable::create((int) $periodValue, 1, 1)->startOfYear();
            return [$ano, $ano->endOfYear(), 'month'];
        }

        return [$now->subDays(29)->startOfDay(), $now->endOfDay(), 'day'];
    }

    private function applyPeriodoFilter($query, ?string $periodType, ?string $periodValue): void
    {
        if ($periodType === 'day' && $periodValue) {
            $query->whereDate('matriculas.created_at', $periodValue);
        }

        if ($periodType === 'month' && $periodValue) {
            $parts = explode('-', $periodValue);
            $query->whereYear('matriculas.created_at', (int) ($parts[0] ?? 0))
                ->whereMonth('matriculas.created_at', (int) ($parts[1] ?? 0));
        }

        if ($periodType === 'year' && $periodValue) {
            $query->whereYear('matriculas.created_at', (int) $periodValue);
        }
    }

    private function countEventosComVagasOcupadas(float $limitePercentual, bool $apenasAbaixoLimite = false): int
    {
        $limitePercentual = max(0.0, min(1.0, $limitePercentual));

        $query = EventoCurso::query()
            ->join('cursos', 'evento_cursos.curso_id', '=', 'cursos.id')
            ->leftJoin('matriculas', function ($join) {
                $join->on('matriculas.evento_curso_id', '=', 'evento_cursos.id')
                    ->where('matriculas.status', StatusMatricula::Confirmada);
            })
            ->select('evento_cursos.id')
            ->whereNull('evento_cursos.deleted_at')
            ->whereNull('cursos.deleted_at')
            ->where('evento_cursos.ativo', true)
            ->where('cursos.ativo', true)
            ->where('cursos.limite_vagas', '>', 0)
            ->groupBy('evento_cursos.id', 'cursos.limite_vagas')
            ->havingRaw('COUNT(matriculas.id) >= (cursos.limite_vagas * ?)', [$limitePercentual]);

        if ($apenasAbaixoLimite) {
            $query->havingRaw('COUNT(matriculas.id) < cursos.limite_vagas');
        }

        return $query->count();
    }
}
