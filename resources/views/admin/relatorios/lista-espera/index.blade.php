@extends('admin.layouts.app')

@section('title', 'Relatório de Lista de Espera')

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório exibe todos os alunos que entraram na lista de espera dos eventos e o status de cada chamada.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.lista-espera.index') }}" class="space-y-4">
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
                    id="evento_curso_id"
                    name="evento_curso_id"
                    label="Evento"
                    :options="$filtrosSelect['eventoOptions']"
                    :selected="$filtros['evento_curso_id'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="status"
                    name="status"
                    label="Status da lista"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="posicao_max"
                    name="posicao_max"
                    label="Posição na fila"
                    :options="$filtrosSelect['posicaoOptions']"
                    :selected="$filtros['posicao_max'] ?? null"
                    placeholder="Todas"
                />
                <x-admin.select
                    id="possui_matricula"
                    name="possui_matricula"
                    label="Possui matrícula?"
                    :options="$filtrosSelect['matriculaOptions']"
                    :selected="$filtros['possui_matricula'] ?? null"
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
                <a class="btn btn-ghost" href="{{ route('admin.relatorios.lista-espera.index') }}">Limpar</a>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $listaEspera->total() }}
            </div>
            <a
                class="btn btn-primary"
                href="{{ route('admin.relatorios.lista-espera.export', request()->query()) }}"
            >
                Exportar Excel
            </a>
        </div>

        @if ($listaEspera->count() === 0)
            <div class="alert">
                Nenhum registro encontrado com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data de entrada</th>
                            <th>Aluno</th>
                            <th>CPF</th>
                            <th>Curso</th>
                            <th>Evento</th>
                            <th>Posição</th>
                            <th>Status da lista</th>
                            <th>Data da chamada</th>
                            <th>Respondeu?</th>
                            <th>Matrícula gerada?</th>
                            <th>Status da matrícula</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listaEspera as $item)
                            <tr>
                                <td>{{ $item->data_entrada_formatada }}</td>
                                <td>{{ $item->aluno_nome }}</td>
                                <td>{{ $item->aluno_cpf }}</td>
                                <td>{{ $item->curso_nome }}</td>
                                <td>{{ $item->evento_label }}</td>
                                <td>{{ $item->posicao ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $item->status_badge }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td>{{ $item->chamado_em_formatada }}</td>
                                <td>{{ $item->respondeu_label }}</td>
                                <td>{{ $item->matricula_gerada_label }}</td>
                                <td>{{ $item->status_matricula_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $listaEspera->links() }}
        @endif
    </div>
@endsection
