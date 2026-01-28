@extends('admin.layouts.app')

@section('title', 'Usuário')

@section('subtitle')
    Detalhes do acesso.
@endsection

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="content-card">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Nome de exibicao</p>
                <p class="text-lg font-semibold">{{ $user->display_name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Nome completo</p>
                <p class="text-lg font-semibold">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--content-text)] opacity-70">Email</p>
                <p class="text-lg font-semibold">{{ $user->email }}</p>
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
            <a class="btn btn-primary" href="{{ route('admin.usuarios.edit', $user) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h4.5L19.75 9.75l-4.5-4.5L4 16.5V21z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5l4 4" />
                </svg>
                <span>Editar</span>
            </a>
            <a class="btn btn-ghost" href="{{ route('admin.usuarios.senha.edit', $user) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0 1 10 0v3" />
                    <rect x="5" y="10" width="14" height="10" rx="2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v2" />
                </svg>
                <span>Redefinir senha</span>
            </a>
            <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">Voltar</a>
            <form action="{{ route('admin.usuarios.destroy', $user) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 7l1 12.5A2.5 2.5 0 0 0 8.5 22h7a2.5 2.5 0 0 0 2.5-2.5L19 7" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1V7" />
                    </svg>
                    <span>Excluir usuário</span>
                </button>
            </form>
        </div>
    </div>
@endsection
