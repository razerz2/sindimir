@extends('admin.layouts.app')

@section('title', 'Redefinir senha')

@section('subtitle')
    Informe uma nova senha para o usuario.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-ghost" href="{{ route('admin.usuarios.show', $user) }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Voltar</span>
        </a>
    </div>

    <div class="content-card space-y-6">
        <div>
            <p class="text-sm text-[var(--content-text)] opacity-70">
                Usuario: <strong>{{ $user->display_name }}</strong> ({{ $user->email }})
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
                <a class="btn btn-ghost" href="{{ route('admin.usuarios.show', $user) }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7" />
                    </svg>
                    <span>Salvar senha</span>
                </button>
            </div>
        </form>
    </div>
@endsection
