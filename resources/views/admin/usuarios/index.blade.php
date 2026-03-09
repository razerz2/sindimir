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
    <div class="page-actions">
        <div></div>
        @if (auth()->user()?->role === \App\Enums\UserRole::Admin)
            <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.usuarios.create') }}">
                Novo usuário
            </x-admin.action>
        @endif
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->display_name }}</td>
                        <td>{{ $user->email }}</td>
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
                        <td>
                            <div class="table-actions">
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
                        <td colspan="5">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
