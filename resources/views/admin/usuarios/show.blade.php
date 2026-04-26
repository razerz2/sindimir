@extends('admin.layouts.app')

@section('title', 'Detalhes do usuário')

@section('subtitle')
    Detalhes do acesso.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Usuários', 'href' => route('admin.usuarios.index'), 'icon' => 'user'],
        ['label' => 'Detalhes do usuário', 'icon' => 'eye', 'current' => true],
    ]" />
@endsection

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="content-card">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Nome de exibição</p>
                <p class="text-lg font-semibold">{{ $user->display_name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Nome completo</p>
                <p class="text-lg font-semibold">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">E-mail</p>
                <p class="text-lg font-semibold">{{ $user->email }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">WhatsApp</p>
                <p class="text-lg font-semibold">{{ $user->whatsapp_formatado ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Perfil</p>
                <p class="text-lg font-semibold">
                    <span class="badge neutral">{{ $user->role?->label() ?? '-' }}</span>
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Status</p>
                <p class="text-lg font-semibold">
                    <span class="badge {{ $user->email_verified_at ? 'success' : 'warning' }}">
                        {{ $user->email_verified_at ? 'Verificado' : 'Pendente' }}
                    </span>
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Criado em</p>
                <p class="text-lg font-semibold">{{ $user->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Última atualização</p>
                <p class="text-lg font-semibold">{{ $user->updated_at?->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.action as="a" variant="primary" icon="edit" href="{{ route('admin.usuarios.edit', $user) }}">Editar</x-admin.action>
            <x-admin.action as="a" variant="ghost" icon="lock" href="{{ route('admin.usuarios.senha.edit', $user) }}">Redefinir senha</x-admin.action>
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.usuarios.index') }}">Voltar</x-admin.action>
            <form action="{{ route('admin.usuarios.destroy', $user) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <x-admin.action variant="danger" icon="trash" type="submit">Excluir usuário</x-admin.action>
            </form>
        </div>
    </div>
@endsection
