@extends('admin.layouts.app')

@section('title', 'Relatório de auditoria')

@section('subtitle')
    Rastreabilidade de ações realizadas no sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Relatórios', 'href' => route('admin.relatorios.index'), 'icon' => 'settings'],
        ['label' => 'Auditoria', 'icon' => 'eye', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório exibe todas as ações realizadas no sistema com rastreabilidade completa.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.auditoria.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <x-admin.select
                    id="user_id"
                    name="user_id"
                    label="Usuário"
                    :options="$filtrosSelect['userOptions']"
                    :selected="$filtros['user_id'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="action"
                    name="action"
                    label="Tipo de ação"
                    :options="$filtrosSelect['actionOptions']"
                    :selected="$filtros['action'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="entity"
                    name="entity"
                    label="Entidade"
                    :options="$filtrosSelect['entityOptions']"
                    :selected="$filtros['entity'] ?? null"
                    placeholder="Todas"
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
                <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.relatorios.auditoria.index') }}">Limpar</x-admin.action>
                <x-admin.action variant="primary" icon="filter" type="submit">Filtrar</x-admin.action>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $auditorias->total() }}
            </div>
            <x-admin.action
                as="a"
                variant="primary"
                icon="download"
                href="{{ route('admin.relatorios.auditoria.export', request()->query()) }}"
            >
                Exportar Excel
            </x-admin.action>
        </div>

        @if ($auditorias->count() === 0)
            <div class="alert">
                Nenhuma ação encontrada com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Usuário</th>
                            <th>Perfil</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>ID</th>
                            <th>IP</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($auditorias as $auditoria)
                            <tr>
                                <td>{{ $auditoria->data_acao_formatada }}</td>
                                <td>{{ $auditoria->user_label }}</td>
                                <td>{{ $auditoria->perfil_label }}</td>
                                <td>
                                    <span class="badge {{ $auditoria->acao_badge }}">
                                        {{ $auditoria->acao_label }}
                                    </span>
                                </td>
                                <td>{{ $auditoria->entidade_label }}</td>
                                <td>{{ $auditoria->entidade_id ?? '-' }}</td>
                                <td>{{ $auditoria->ip ?? '-' }}</td>
                                <td>
                                    @if ($auditoria->has_details)
                                        <details>
                                            <summary class="btn btn-ghost">Ver detalhes</summary>
                                            <div class="mt-2 space-y-2 text-xs">
                                                <div>
                                                    <div class="font-semibold">Antes</div>
                                                    <pre class="whitespace-pre-wrap rounded-lg border border-[var(--border-color)] bg-[var(--card-bg)] p-2">{{ $auditoria->before_label }}</pre>
                                                </div>
                                                <div>
                                                    <div class="font-semibold">Depois</div>
                                                    <pre class="whitespace-pre-wrap rounded-lg border border-[var(--border-color)] bg-[var(--card-bg)] p-2">{{ $auditoria->after_label }}</pre>
                                                </div>
                                            </div>
                                        </details>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $auditorias->links() }}
        @endif
    </div>
@endsection
