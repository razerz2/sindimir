@extends('admin.layouts.app')

@section('title', 'Usuários')

@section('subtitle')
    Gestão de acessos ao sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Usuários', 'icon' => 'user', 'current' => true],
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

            return route('admin.usuarios.index', array_merge($baseQuery, [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
        $sortIndicator = fn (string $column): string => $currentSort === $column
            ? ($currentDirection === 'asc' ? ' (A-Z)' : ' (Z-A)')
            : '';
    @endphp

    <div class="page-actions">
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Buscar por nome, e-mail, WhatsApp, perfil ou status"
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
            <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.usuarios.index') }}">Limpar</x-admin.action>
        </form>
        @if (auth()->user()?->role === \App\Enums\UserRole::Admin)
            <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.usuarios.create') }}">
                Novo usuário
            </x-admin.action>
        @endif
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper overflow-x-auto w-full">
        <table class="table w-full table-auto">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('name') }}" style="color:inherit;text-decoration:none;">Nome{{ $sortIndicator('name') }}</a></th>
                    <th><a href="{{ $sortUrl('email') }}" style="color:inherit;text-decoration:none;">E-mail{{ $sortIndicator('email') }}</a></th>
                    <th class="whitespace-nowrap">WhatsApp</th>
                    <th class="w-40 whitespace-nowrap"><a href="{{ $sortUrl('role') }}" style="color:inherit;text-decoration:none;">Perfil{{ $sortIndicator('role') }}</a></th>
                    <th class="w-40 whitespace-nowrap"><a href="{{ $sortUrl('status') }}" style="color:inherit;text-decoration:none;">Status{{ $sortIndicator('status') }}</a></th>
                    <th class="text-right whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->display_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td class="whitespace-nowrap">{{ $user->whatsapp_formatado ?: '-' }}</td>
                        <td>
                            <span class="badge neutral">
                                {{ $user->role?->label() ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $user->email_verified_at ? 'success' : 'warning' }}">
                                {{ $user->email_verified_at ? 'Verificado' : 'Pendente' }}
                            </span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <div class="table-actions" style="flex-wrap: nowrap; justify-content: flex-end;">
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.usuarios.show', $user) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.usuarios.edit', $user) }}">Editar</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="lock" href="{{ route('admin.usuarios.senha.edit', $user) }}">Redefinir senha</x-admin.action>
                                <form action="{{ route('admin.usuarios.destroy', $user) }}" method="POST" style="display:inline">
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
            @if ($users->total() > 0)
                Exibindo {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} registros.
            @else
                Exibindo 0 a 0 de 0 registros.
            @endif
        </div>
    </div>

    {{ $users->links() }}
@endsection
