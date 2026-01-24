@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('subtitle')
    Visao geral administrativa com indicadores e alertas.
@endsection

@section('content')
    <div class="grid">
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M4 19h16M6 19V9m6 10V5m6 14v-7" />
            </svg>
            <div class="kpi-value">{{ $kpis['total_cursos_ativos'] }}</div>
            <div class="kpi-label">Cursos ativos</div>
        </div>
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M16 11a4 4 0 1 0-8 0m12 9a6 6 0 0 0-12 0" />
            </svg>
            <div class="kpi-value">{{ $kpis['total_alunos'] }}</div>
            <div class="kpi-label">Alunos cadastrados</div>
        </div>
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M8 7h8M8 11h8M8 15h4M6 3h12a2 2 0 0 1 2 2v14l-4-3H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
            </svg>
            <div class="kpi-value">{{ $kpis['matriculas_pendentes'] }}</div>
            <div class="kpi-label">Matriculas pendentes</div>
        </div>
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M20 7l-9 9-4-4" />
            </svg>
            <div class="kpi-value">{{ $kpis['matriculas_confirmadas'] }}</div>
            <div class="kpi-label">Matriculas confirmadas</div>
        </div>
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M12 8v4l3 3M12 2a10 10 0 1 0 10 10" />
            </svg>
            <div class="kpi-value">{{ $kpis['cursos_lotados'] }}</div>
            <div class="kpi-label">Cursos lotados</div>
        </div>
        <div class="kpi-card">
            <svg class="kpi-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path d="M3 13h18M6 6h12M6 20h12" />
            </svg>
            <div class="kpi-value">{{ $kpis['alunos_lista_espera'] }}</div>
            <div class="kpi-label">Lista de espera</div>
        </div>
    </div>

    <div class="content-card mt-6">
        <div class="page-actions">
            <div>
                <h3 class="section-title">Filtros</h3>
                <p class="page-subtitle">Filtre os gráficos por período.</p>
            </div>
            @if (!empty($filters['period_type']))
                <span class="badge warning">Filtro ativo</span>
            @endif
        </div>
        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-6">
            <div>
                <label class="text-sm font-semibold text-[var(--content-text)]">Período</label>
                <select id="period-type" name="period_type" class="mt-2 w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="day" {{ ($filters['period_type'] ?? '') === 'day' ? 'selected' : '' }}>Dia</option>
                    <option value="month" {{ ($filters['period_type'] ?? '') === 'month' ? 'selected' : '' }}>Mês</option>
                    <option value="year" {{ ($filters['period_type'] ?? '') === 'year' ? 'selected' : '' }}>Ano</option>
                </select>
            </div>
            <div id="period-day" class="hidden">
                <label class="text-sm font-semibold text-[var(--content-text)]">Dia</label>
                <input
                    type="date"
                    name="period_value"
                    value="{{ ($filters['period_type'] ?? '') === 'day' ? ($filters['period_value'] ?? '') : '' }}"
                    class="mt-2 w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm"
                >
            </div>
            <div id="period-month" class="hidden">
                <label class="text-sm font-semibold text-[var(--content-text)]">Mês</label>
                <input
                    type="month"
                    name="period_value"
                    value="{{ ($filters['period_type'] ?? '') === 'month' ? ($filters['period_value'] ?? '') : '' }}"
                    class="mt-2 w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm"
                >
            </div>
            <div id="period-year" class="hidden">
                <label class="text-sm font-semibold text-[var(--content-text)]">Ano</label>
                <input
                    type="number"
                    name="period_value"
                    min="2000"
                    max="2100"
                    value="{{ ($filters['period_type'] ?? '') === 'year' ? ($filters['period_value'] ?? '') : '' }}"
                    class="mt-2 w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm"
                >
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button id="apply-filters" class="btn btn-primary" type="submit">Aplicar filtros</button>
                <a class="btn btn-ghost" href="{{ route('admin.dashboard') }}">Limpar filtros</a>
            </div>
        </form>
    </div>

    <h3 class="section-title">Tendência e Distribuição</h3>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="content-card">
            <div class="page-actions">
                <div>
                    <h4 class="section-title">Matrículas ao longo do tempo</h4>
                    <p class="page-subtitle">Últimos 30 dias</p>
                </div>
            </div>
            <canvas id="chart-matriculas"></canvas>
        </div>
        <div class="content-card">
            <div class="page-actions">
                <div>
                    <h4 class="section-title">Status das Matrículas</h4>
                    <p class="page-subtitle">Distribuição atual</p>
                </div>
            </div>
            <canvas id="chart-status"></canvas>
        </div>
        <div class="content-card">
            <div class="page-actions">
                <div>
                    <h4 class="section-title">Cursos mais procurados</h4>
                    <p class="page-subtitle">Top 5 matrículas</p>
                </div>
            </div>
            <canvas id="chart-cursos"></canvas>
        </div>
    </div>

    <h3 class="section-title">Alertas</h3>
    <div class="content-card alert-card">
        <ul class="alert-list">
            <li>Cursos proximos do limite: <strong>{{ $alertas['cursos_proximos_limite'] }}</strong></li>
            <li>Matriculas vencidas: <strong>{{ $alertas['matriculas_vencidas'] }}</strong></li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const charts = @json($charts);
        const periodType = "{{ $filters['period_type'] ?? '' }}";
        const periodValue = "{{ $filters['period_value'] ?? '' }}";

        const periodDay = document.getElementById('period-day');
        const periodMonth = document.getElementById('period-month');
        const periodYear = document.getElementById('period-year');
        const periodSelect = document.getElementById('period-type');
        const applyButton = document.getElementById('apply-filters');

        function updatePeriodFields() {
            periodDay.classList.toggle('hidden', periodSelect.value !== 'day');
            periodMonth.classList.toggle('hidden', periodSelect.value !== 'month');
            periodYear.classList.toggle('hidden', periodSelect.value !== 'year');

            const required = periodSelect.value !== '';
            const activeInput = document.querySelector(`#period-${periodSelect.value} input`);
            applyButton.disabled = required && (!activeInput || activeInput.value === '');
        }

        if (periodType) {
            periodSelect.value = periodType;
        }
        updatePeriodFields();
        periodSelect.addEventListener('change', updatePeriodFields);
        document.querySelectorAll('#period-day input, #period-month input, #period-year input').forEach((input) => {
            input.addEventListener('input', updatePeriodFields);
        });

        const lineCtx = document.getElementById('chart-matriculas');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: charts.line.labels,
                    datasets: [
                        {
                            label: 'Confirmadas',
                            data: charts.line.confirmadas,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.2)',
                            tension: 0.3,
                        },
                        {
                            label: 'Pendentes',
                            data: charts.line.pendentes,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.2)',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }

        const pieCtx = document.getElementById('chart-status');
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: charts.pie.labels,
                    datasets: [
                        {
                            data: charts.pie.data,
                            backgroundColor: ['#16a34a', '#f97316', '#0ea5e9', '#ef4444'],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }

        const barCtx = document.getElementById('chart-cursos');
        if (barCtx) {
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: charts.bar.labels,
                    datasets: [
                        {
                            label: 'Matrículas',
                            data: charts.bar.data,
                            backgroundColor: 'rgba(15, 61, 46, 0.6)',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                },
            });
        }
    </script>
@endsection
