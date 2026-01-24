@extends('admin.layouts.app')

@section('title', 'Relatório de Eventos')

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório exibe todos os eventos dos cursos, com capacidade, ocupação e status atual.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.eventos.index') }}" class="space-y-4">
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
                    id="status_evento"
                    name="status_evento"
                    label="Status do evento"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status_evento'] ?? null"
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
                <a class="btn btn-ghost" href="{{ route('admin.relatorios.eventos.index') }}">Limpar</a>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $eventos->total() }}
            </div>
            <a
                class="btn btn-primary"
                href="{{ route('admin.relatorios.eventos.export', request()->query()) }}"
            >
                Exportar Excel
            </a>
        </div>

        @if ($eventos->count() === 0)
            <div class="alert">
                Nenhum evento encontrado com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Evento</th>
                            <th>Data início</th>
                            <th>Data fim</th>
                            <th>Horário</th>
                            <th>Capacidade</th>
                            <th>Inscrições</th>
                            <th>Matrículas confirmadas</th>
                            <th>Lista de espera</th>
                            <th>Vagas disponíveis</th>
                            <th>Status do evento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($eventos as $evento)
                            <tr>
                                <td>{{ $evento->curso_nome }}</td>
                                <td>{{ $evento->evento_label }}</td>
                                <td>{{ $evento->data_inicio_formatada }}</td>
                                <td>{{ $evento->data_fim_formatada }}</td>
                                <td>{{ $evento->turno_label }}</td>
                                <td>{{ $evento->limite_vagas ?? 0 }}</td>
                                <td>{{ $evento->inscricoes_total }}</td>
                                <td>{{ $evento->matriculas_confirmadas }}</td>
                                <td>{{ $evento->lista_aguardando }}</td>
                                <td>
                                    <div class="flex flex-col gap-1">
                                        <span>{{ $evento->vagas_disponiveis }}</span>
                                        <span class="badge {{ $evento->vagas_badge }}">{{ $evento->vagas_badge_label }}</span>
                                    </div>
                                </td>
                                <td>{{ $evento->status_evento_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $eventos->links() }}
        @endif
    </div>
@endsection
