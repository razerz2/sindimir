@extends('admin.layouts.app')

@section('title', 'Redefinir senha')

@section('subtitle')
    Informe uma nova senha para o usuário.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Usuários', 'href' => route('admin.usuarios.index'), 'icon' => 'user'],
        ['label' => 'Redefinir senha', 'icon' => 'lock', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.usuarios.show', $user) }}">Voltar</x-admin.action>
    </div>

    <div class="content-card space-y-6">
        <div>
            <p class="text-sm text-[var(--content-text)] opacity-70">
                Usuário: <strong>{{ $user->display_name }}</strong> ({{ $user->email }})
            </p>
        </div>

        <form action="{{ route('admin.usuarios.senha.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <x-admin.input name="password" label="Nova senha" type="password" required />
                <x-admin.input name="password_confirmation" label="Confirmar nova senha" type="password" required />
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <x-admin.action as="a" variant="ghost" icon="x" href="{{ route('admin.usuarios.show', $user) }}">Cancelar</x-admin.action>
                <x-admin.action variant="primary" icon="check" type="submit">Salvar senha</x-admin.action>
            </div>
        </form>
    </div>
@endsection
