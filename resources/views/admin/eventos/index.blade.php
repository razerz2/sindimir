@extends('admin.layouts.app')

@section('title', 'Eventos')

@section('subtitle')
    Controle de turmas e eventos associados aos cursos.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'icon' => 'calendar', 'current' => true],
    ]" />
@endsection

@section('content')
    @php
        $currentSort = $sort ?? 'created_at';
        $currentDirection = $direction ?? 'desc';
        $baseQuery = request()->except(['page', 'sort', 'direction']);
        $sortUrl = function (string $column) use ($baseQuery, $currentSort, $currentDirection): string {
            $nextDirection = $currentSort === $column
                ? ($currentDirection === 'asc' ? 'desc' : 'asc')
                : 'asc';

            return route('admin.eventos.index', array_merge($baseQuery, [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
        $sortIndicator = fn (string $column): string => $currentSort === $column
            ? ($currentDirection === 'asc' ? ' ↑' : ' ↓')
            : '';
    @endphp

    <div class="page-actions">
        <form method="GET" action="{{ route('admin.eventos.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Buscar por número, curso, município ou turno"
                maxlength="100"
                class="min-h-[40px] min-w-[280px] flex-1 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
            >
            <select
                name="per_page"
                class="min-h-[40px] rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                onchange="this.form.submit()"
            >
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <x-admin.action variant="primary" icon="filter" type="submit">Buscar</x-admin.action>
            <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.eventos.index') }}">Limpar</x-admin.action>
        </form>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.eventos.create') }}">Novo evento</x-admin.action>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper overflow-x-auto w-full">
        <table class="table w-full table-auto">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('curso') }}" style="color:inherit;text-decoration:none;">Curso{{ $sortIndicator('curso') }}</a></th>
                    <th class="w-32 whitespace-nowrap"><a href="{{ $sortUrl('numero_evento') }}" style="color:inherit;text-decoration:none;">Número{{ $sortIndicator('numero_evento') }}</a></th>
                    <th class="w-56 whitespace-nowrap"><a href="{{ $sortUrl('data_inicio') }}" style="color:inherit;text-decoration:none;">Período{{ $sortIndicator('data_inicio') }}</a></th>
                    <th class="w-48"><a href="{{ $sortUrl('municipio') }}" style="color:inherit;text-decoration:none;">Município{{ $sortIndicator('municipio') }}</a></th>
                    <th class="w-36 whitespace-nowrap"><a href="{{ $sortUrl('turno') }}" style="color:inherit;text-decoration:none;">Turno{{ $sortIndicator('turno') }}</a></th>
                    <th class="w-32 whitespace-nowrap">Status</th>
                    <th class="text-right whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($eventos as $evento)
                    <tr>
                        <td>
                            @if ($evento->curso)
                                {{ $evento->curso->nome }}@if ($evento->curso->trashed()) (removido) @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap">{{ $evento->numero_evento }}</td>
                        <td class="whitespace-nowrap">{{ $evento->data_inicio->format('d/m/Y') }} a {{ $evento->data_fim->format('d/m/Y') }}</td>
                        <td>{{ $evento->municipio }}</td>
                        <td class="whitespace-nowrap">{{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : '-' }}</td>
                        <td>
                            <span class="badge {{ $evento->ativo ? '' : 'warning' }}">
                                {{ $evento->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <div class="table-actions" style="flex-wrap: nowrap; justify-content: flex-end;">
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.eventos.show', $evento) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.eventos.edit', $evento) }}">Editar</x-admin.action>
                                <form
                                    action="{{ route('admin.eventos.destroy', $evento) }}"
                                    method="POST"
                                    style="display:inline"
                                    data-confirm="Deseja realmente excluir este evento? Esta ação é irreversível."
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.action variant="danger" icon="trash" type="submit">Excluir</x-admin.action>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-actions" style="margin-top: 12px; margin-bottom: 0;">
        <div class="text-sm opacity-75">
            @if ($eventos->total() > 0)
                Exibindo {{ $eventos->firstItem() }} a {{ $eventos->lastItem() }} de {{ $eventos->total() }} registros.
            @else
                Exibindo 0 a 0 de 0 registros.
            @endif
        </div>
    </div>

    {{ $eventos->links() }}
@endsection
