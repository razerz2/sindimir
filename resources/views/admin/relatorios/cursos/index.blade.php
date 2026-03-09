@extends('admin.layouts.app')

@section('title', 'Relatório de cursos')

@section('subtitle')
    Visão consolidada de status, vagas e ocupação dos cursos.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Relatórios', 'href' => route('admin.relatorios.index'), 'icon' => 'settings'],
        ['label' => 'Cursos', 'icon' => 'book', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório apresenta uma visão consolidada dos cursos e sua ocupação.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.cursos.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <x-admin.select
                    id="curso_id"
                    name="curso_id"
                    label="Curso"
                    :options="$filtrosSelect['cursoOptions']"
                    :selected="$filtros['curso_id'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="status"
                    name="status"
                    label="Status do curso"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="possui_eventos_ativos"
                    name="possui_eventos_ativos"
                    label="Possui eventos ativos?"
                    :options="$filtrosSelect['eventosOptions']"
                    :selected="$filtros['possui_eventos_ativos'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="possui_vagas"
                    name="possui_vagas"
                    label="Possui vagas disponíveis?"
                    :options="$filtrosSelect['vagasOptions']"
                    :selected="$filtros['possui_vagas'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.input
                    id="data_inicio"
                    name="data_inicio"
                    label="Data inicial"
                    type="date"
                    :value="$filtros['data_inicio'] ?? ''"
                />
                <x-admin.input
                    id="data_fim"
                    name="data_fim"
                    label="Data final"
                    type="date"
                    :value="$filtros['data_fim'] ?? ''"
                />
                <x-admin.select
                    id="per_page"
                    name="per_page"
                    label="Itens por página"
                    :options="$filtrosSelect['perPageOptions']"
                    :selected="$filtros['per_page'] ?? 15"
                />
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.relatorios.cursos.index') }}">Limpar</x-admin.action>
                <x-admin.action variant="primary" icon="filter" type="submit">Filtrar</x-admin.action>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $cursos->total() }}
            </div>
            <x-admin.action
                as="a"
                variant="primary"
                icon="download"
                href="{{ route('admin.relatorios.cursos.export', request()->query()) }}"
            >
                Exportar Excel
            </x-admin.action>
        </div>

        @if ($cursos->count() === 0)
            <div class="alert">
                Nenhum curso encontrado com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Status</th>
                            <th>Total de eventos</th>
                            <th>Total de vagas</th>
                            <th>Matrículas confirmadas</th>
                            <th>Inscrições totais</th>
                            <th>Lista de espera</th>
                            <th>Vagas disponíveis</th>
                            <th>Data de criação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cursos as $curso)
                            <tr>
                                <td>{{ $curso->curso_nome }}</td>
                                <td>
                                    <span class="badge {{ $curso->ativo ? '' : 'neutral' }}">
                                        {{ $curso->status_label }}
                                    </span>
                                </td>
                                <td>{{ $curso->eventos_total }}</td>
                                <td>{{ $curso->vagas_totais }}</td>
                                <td>{{ $curso->matriculas_confirmadas }}</td>
                                <td>{{ $curso->inscricoes_total }}</td>
                                <td>
                                    <span class="badge {{ $curso->lista_badge }}">
                                        {{ $curso->lista_total }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $curso->vagas_badge }}">
                                        {{ $curso->vagas_disponiveis }}
                                    </span>
                                </td>
                                <td>{{ $curso->created_at_formatada }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $cursos->links() }}
        @endif
    </div>
@endsection
