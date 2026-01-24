@extends('admin.layouts.app')

@section('title', 'Relatório de Matrículas')

@section('content')
    <div class="space-y-6">
        <form method="GET" action="{{ route('admin.relatorios.matriculas.index') }}" class="space-y-4">
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
                    label="Status da matrícula"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status'] ?? null"
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
                    id="canal_origem"
                    name="canal_origem"
                    label="Canal de origem"
                    :options="$filtrosSelect['canalOptions']"
                    :selected="$filtros['canal_origem'] ?? null"
                    placeholder="Todos"
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
                <a class="btn btn-ghost" href="{{ route('admin.relatorios.matriculas.index') }}">Limpar</a>
                <button class="btn btn-primary" type="submit">Filtrar</button>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $matriculas->total() }}
            </div>
            <a
                class="btn btn-primary"
                href="{{ route('admin.relatorios.matriculas.export', request()->query()) }}"
            >
                Exportar Excel
            </a>
        </div>

        @if ($matriculas->count() === 0)
            <div class="alert">
                Nenhuma matrícula encontrada com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>CPF</th>
                            <th>Curso</th>
                            <th>Evento</th>
                            <th>Status</th>
                            <th>Data da inscrição</th>
                            <th>Data da confirmação</th>
                            <th>Data de vencimento</th>
                            <th>Canal de origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matriculas as $matricula)
                            <tr>
                                <td>{{ $matricula->aluno_nome }}</td>
                                <td>{{ $matricula->aluno_cpf }}</td>
                                <td>{{ $matricula->curso_nome }}</td>
                                <td>{{ $matricula->evento_label }}</td>
                                <td>
                                    <span class="badge {{ $matricula->status_badge }}">
                                        {{ $matricula->status_label }}
                                    </span>
                                </td>
                                <td>{{ $matricula->data_inscricao_formatada }}</td>
                                <td>{{ $matricula->data_confirmacao_formatada }}</td>
                                <td>{{ $matricula->data_expiracao_formatada }}</td>
                                <td>{{ $matricula->canal_origem_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $matriculas->links() }}
        @endif
    </div>
@endsection
