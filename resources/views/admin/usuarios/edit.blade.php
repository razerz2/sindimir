@extends('admin.layouts.app')

@section('title', 'Editar usuário')

@section('subtitle')
    Atualize as informações de acesso.
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

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <form action="{{ route('admin.usuarios.update', $user) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <x-admin-input name="name" label="Nome" required value="{{ old('name', $user->name) }}" />
            <x-admin-input name="email" label="Email" type="email" required value="{{ old('email', $user->email) }}" />
            <x-admin-select
                name="role"
                label="Perfil"
                :options="$roleOptions"
                selected="{{ old('role', $user->role?->value) }}"
                required
            />
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7" />
                </svg>
                <span>Salvar alterações</span>
            </button>
        </div>
    </form>
@endsection
