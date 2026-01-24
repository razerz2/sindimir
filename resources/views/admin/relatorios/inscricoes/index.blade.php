@extends('admin.layouts.app')

@section('title', 'Relatório de Inscrições')

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório exibe todas as inscrições realizadas no sistema, independentemente de terem sido convertidas em matrícula.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.inscricoes.index') }}" class="space-y-4">
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
                    id="status_inscricao"
                    name="status_inscricao"
                    label="Status da inscrição"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status_inscricao'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="origem"
                    name="origem"
                    label="Origem da inscrição"
                    :options="$filtrosSelect['origemOptions']"
                    :selected="$filtros['origem'] ?? null"
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
                <a class="btn btn-ghost" href="{{ route('admin.relatorios.inscricoes.index') }}">Limpar</a>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $inscricoes->total() }}
            </div>
            <a
                class="btn btn-primary"
                href="{{ route('admin.relatorios.inscricoes.export', request()->query()) }}"
            >
                Exportar Excel
            </a>
        </div>

        @if ($inscricoes->count() === 0)
            <div class="alert">
                Nenhuma inscrição encontrada com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data da inscrição</th>
                            <th>Aluno</th>
                            <th>CPF</th>
                            <th>Curso</th>
                            <th>Evento</th>
                            <th>Status da inscrição</th>
                            <th>Matrícula gerada?</th>
                            <th>Status da matrícula</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inscricoes as $inscricao)
                            <tr>
                                <td>{{ $inscricao->data_inscricao_formatada }}</td>
                                <td>{{ $inscricao->aluno_nome }}</td>
                                <td>{{ $inscricao->aluno_cpf }}</td>
                                <td>{{ $inscricao->curso_nome }}</td>
                                <td>{{ $inscricao->evento_label }}</td>
                                <td>
                                    <span class="badge {{ $inscricao->status_inscricao_badge }}">
                                        {{ $inscricao->status_inscricao_label }}
                                    </span>
                                </td>
                                <td>{{ $inscricao->matricula_gerada_label }}</td>
                                <td>{{ $inscricao->status_matricula_label }}</td>
                                <td>{{ $inscricao->origem_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $inscricoes->links() }}
        @endif
    </div>
@endsection
