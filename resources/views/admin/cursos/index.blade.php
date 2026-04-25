@extends('admin.layouts.app')

@section('title', 'Cursos')

@section('subtitle')
    Gestão de cursos cadastrados no sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'icon' => 'book', 'current' => true],
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

            return route('admin.cursos.index', array_merge($baseQuery, [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
        $sortIndicator = fn (string $column): string => $currentSort === $column
            ? ($currentDirection === 'asc' ? ' ↑' : ' ↓')
            : '';
    @endphp

    <div class="page-actions">
        <form method="GET" action="{{ route('admin.cursos.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Buscar por curso ou categoria"
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
            <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.cursos.index') }}">Limpar</x-admin.action>
        </form>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.cursos.create') }}">Novo curso</x-admin.action>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper overflow-x-auto w-full">
        <table class="table w-full table-auto">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('nome') }}" style="color:inherit;text-decoration:none;">Nome{{ $sortIndicator('nome') }}</a></th>
                    <th class="w-56"><a href="{{ $sortUrl('categoria') }}" style="color:inherit;text-decoration:none;">Categoria{{ $sortIndicator('categoria') }}</a></th>
                    <th class="w-40 whitespace-nowrap">Validade</th>
                    <th class="w-28 whitespace-nowrap">Vagas</th>
                    <th class="w-32 whitespace-nowrap">Status</th>
                    <th class="text-right whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cursos as $curso)
                    <tr>
                        <td>{{ $curso->nome }}</td>
                        <td>{{ $curso->categoria?->nome ?? '-' }}</td>
                        <td class="whitespace-nowrap">{{ $curso->validade?->format('d/m/Y') ?? '-' }}</td>
                        <td class="whitespace-nowrap">{{ $curso->limite_vagas }}</td>
                        <td>
                            <span class="badge {{ $curso->ativo ? '' : 'warning' }}">
                                {{ $curso->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <div class="table-actions" style="flex-wrap: nowrap; justify-content: flex-end;">
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.cursos.show', $curso) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.cursos.edit', $curso) }}">Editar</x-admin.action>
                                <form
                                    action="{{ route('admin.cursos.destroy', $curso) }}"
                                    method="POST"
                                    style="display:inline"
                                    data-confirm="Deseja realmente excluir este curso? Os eventos vinculados também serão removidos."
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
                        <td colspan="6">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-actions" style="margin-top: 12px; margin-bottom: 0;">
        <div class="text-sm opacity-75">
            @if ($cursos->total() > 0)
                Exibindo {{ $cursos->firstItem() }} a {{ $cursos->lastItem() }} de {{ $cursos->total() }} registros.
            @else
                Exibindo 0 a 0 de 0 registros.
            @endif
        </div>
    </div>

    {{ $cursos->links() }}
@endsection
