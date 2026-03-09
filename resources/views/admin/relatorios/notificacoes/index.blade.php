@extends('admin.layouts.app')

@section('title', 'Relatório de notificações')

@section('subtitle')
    Auditoria dos envios de notificações por canal e status.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Relatórios', 'href' => route('admin.relatorios.index'), 'icon' => 'settings'],
        ['label' => 'Notificações', 'icon' => 'settings', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Este relatório exibe todos os envios de notificações do sistema.
        </p>

        <form method="GET" action="{{ route('admin.relatorios.notificacoes.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <x-admin.select
                    id="notification_type"
                    name="notification_type"
                    label="Tipo de notificação"
                    :options="$filtrosSelect['tipoOptions']"
                    :selected="$filtros['notification_type'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="canal"
                    name="canal"
                    label="Canal"
                    :options="$filtrosSelect['canalOptions']"
                    :selected="$filtros['canal'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="status"
                    name="status"
                    label="Status"
                    :options="$filtrosSelect['statusOptions']"
                    :selected="$filtros['status'] ?? null"
                    placeholder="Todos"
                />
                <x-admin.select
                    id="curso_id"
                    name="curso_id"
                    label="Curso"
                    :options="$filtrosSelect['cursoOptions']"
                    :selected="$filtros['curso_id'] ?? null"
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
                <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.relatorios.notificacoes.index') }}">Limpar</x-admin.action>
                <x-admin.action variant="primary" icon="filter" type="submit">Filtrar</x-admin.action>
            </div>
        </form>

        <div class="page-actions">
            <div class="text-sm font-semibold">
                Total de registros encontrados: {{ $notificacoes->total() }}
            </div>
            <x-admin.action
                as="a"
                variant="primary"
                icon="download"
                href="{{ route('admin.relatorios.notificacoes.export', request()->query()) }}"
            >
                Exportar Excel
            </x-admin.action>
        </div>

        @if ($notificacoes->count() === 0)
            <div class="alert">
                Nenhuma notificação encontrada com os filtros informados.
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/hora do envio</th>
                            <th>Destinatário</th>
                            <th>Tipo de destinatário</th>
                            <th>Curso</th>
                            <th>Evento</th>
                            <th>Tipo de notificação</th>
                            <th>Canal</th>
                            <th>Status</th>
                            <th>Mensagem de erro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notificacoes as $notificacao)
                            <tr>
                                <td>{{ $notificacao->data_envio_formatada }}</td>
                                <td>{{ $notificacao->destinatario_nome }}</td>
                                <td>{{ $notificacao->destinatario_tipo_label }}</td>
                                <td>{{ $notificacao->curso_nome }}</td>
                                <td>{{ $notificacao->evento_label }}</td>
                                <td>{{ $notificacao->tipo_label }}</td>
                                <td>{{ $notificacao->canal_label }}</td>
                                <td>
                                    <span class="badge {{ $notificacao->status_badge }}">
                                        {{ $notificacao->status_label }}
                                    </span>
                                </td>
                                <td>{{ $notificacao->erro_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $notificacoes->links() }}
        @endif
    </div>
@endsection
