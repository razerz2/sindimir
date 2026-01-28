@extends('admin.layouts.app')

@section('title', 'Novo usuario')

@section('subtitle')
    Cadastre um novo acesso ao sistema.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Voltar</span>
        </a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <form action="{{ route('admin.usuarios.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <x-admin.input name="name" label="Nome completo" required value="{{ old('name') }}" />
            <x-admin.input
                name="nome_exibicao"
                label="Nome de exibicao"
                value="{{ old('nome_exibicao') }}"
                hint="Se vazio, o sistema exibira o nome completo."
            />
            <x-admin.input name="email" label="Email" type="email" required value="{{ old('email') }}" />
            <x-admin.select
                name="role"
                label="Perfil"
                :options="$roleOptions"
                selected="{{ old('role') }}"
                required
            />
            <x-admin.input name="password" label="Senha" type="password" required />
            <x-admin.input name="password_confirmation" label="Confirmar senha" type="password" required />
        </div>

        @php
            $selectedModules = old('module_permissions', $defaultModules);
            $selectedModules = is_array($selectedModules) ? $selectedModules : [];
        @endphp

        <div class="content-card space-y-4" id="module-permissions-section">
            <div>
                <h3 class="text-base font-semibold">Permissoes por modulo</h3>
                <p class="text-sm text-[var(--content-text)] opacity-70">
                    Disponivel apenas para usuarios comuns. Administradores nao tem restricoes.
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($moduleOptions as $moduleKey => $moduleLabel)
                    <label class="flex items-center gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm font-semibold">
                        <input
                            type="checkbox"
                            name="module_permissions[]"
                            value="{{ $moduleKey }}"
                            class="h-4 w-4 rounded border-[var(--border-color)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]/40"
                            {{ in_array($moduleKey, $selectedModules, true) ? 'checked' : '' }}
                        >
                        <span>{{ $moduleLabel }}</span>
                    </label>
                @endforeach
            </div>
            @error('module_permissions')
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7" />
                </svg>
                <span>Criar usuario</span>
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.querySelector('select[name="role"]');
            const moduleSection = document.getElementById('module-permissions-section');
            const moduleInputs = moduleSection?.querySelectorAll('input[type="checkbox"]') ?? [];

            function toggleModules() {
                if (!roleSelect || !moduleSection) {
                    return;
                }
                const isUsuario = roleSelect.value === 'usuario';
                moduleSection.classList.toggle('hidden', !isUsuario);
                moduleInputs.forEach((input) => {
                    input.disabled = !isUsuario;
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleModules);
                toggleModules();
            }
        });
    </script>
@endsection
