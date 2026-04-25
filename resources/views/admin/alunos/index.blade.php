@extends('admin.layouts.app')

@section('title', 'Alunos')

@section('subtitle')
    Relação de alunos cadastrados e acompanhamento rápido.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Alunos', 'icon' => 'user', 'current' => true],
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

            return route('admin.alunos.index', array_merge($baseQuery, [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };
        $sortIndicator = fn (string $column): string => $currentSort === $column
            ? ($currentDirection === 'asc' ? ' ↑' : ' ↓')
            : '';
    @endphp

    <div class="page-actions">
        <form method="GET" action="{{ route('admin.alunos.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Buscar por nome, CPF, e-mail, telefone ou município"
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
            <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.alunos.index') }}">Limpar</x-admin.action>
        </form>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.alunos.create') }}">Novo aluno</x-admin.action>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper overflow-x-auto w-full">
        <table class="table w-full table-auto">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('nome_completo') }}" style="color:inherit;text-decoration:none;">Nome{{ $sortIndicator('nome_completo') }}</a></th>
                    <th class="whitespace-nowrap"><a href="{{ $sortUrl('cpf') }}" style="color:inherit;text-decoration:none;">CPF{{ $sortIndicator('cpf') }}</a></th>
                    <th><a href="{{ $sortUrl('email') }}" style="color:inherit;text-decoration:none;">E-mail{{ $sortIndicator('email') }}</a></th>
                    <th class="w-48"><a href="{{ $sortUrl('municipio') }}" style="color:inherit;text-decoration:none;">Município{{ $sortIndicator('municipio') }}</a></th>
                    <th class="w-40 whitespace-nowrap">Telefone</th>
                    <th class="text-right whitespace-nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alunos as $aluno)
                    <tr>
                        <td>{{ $aluno->nome_completo }}</td>
                        <td>{{ \App\Support\Cpf::format($aluno->cpf) ?: '-' }}</td>
                        <td>{{ $aluno->email ?: '-' }}</td>
                        <td>{{ $aluno->municipio?->nome ?? '-' }}</td>
                        <td class="whitespace-nowrap">{{ \App\Support\Phone::format($aluno->celular ?? $aluno->telefone) ?: '-' }}</td>
                        <td class="text-right whitespace-nowrap">
                            <div class="table-actions" style="flex-wrap: nowrap; justify-content: flex-end;">
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.alunos.show', $aluno) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.alunos.edit', $aluno) }}">Editar</x-admin.action>
                                <form action="{{ route('admin.alunos.destroy', $aluno) }}" method="POST" style="display:inline">
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
            @if ($alunos->total() > 0)
                Exibindo {{ $alunos->firstItem() }} a {{ $alunos->lastItem() }} de {{ $alunos->total() }} registros.
            @else
                Exibindo 0 a 0 de 0 registros.
            @endif
        </div>
    </div>

    {{ $alunos->links() }}
@endsection
