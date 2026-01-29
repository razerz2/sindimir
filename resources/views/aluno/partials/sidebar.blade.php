<div class="sidebar-brand">
    @php($logo = config_db('tema.logo'))
    <img src="{{ $logo ? asset($logo) : asset('assets/images/logo-default.png') }}" alt="Sindimir">
    <span>Área do Aluno</span>
</div>
<nav>
    <a class="nav-link {{ request()->routeIs('aluno.perfil') ? 'active' : '' }}" href="{{ route('aluno.perfil') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M16 11a4 4 0 1 0-8 0" />
            <path d="M4 20a8 8 0 0 1 16 0" />
        </svg>
        Perfil
    </a>
    <a class="nav-link {{ request()->routeIs('aluno.inscricoes') ? 'active' : '' }}" href="{{ route('aluno.inscricoes') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 6h16M4 12h16M4 18h10" />
        </svg>
        Inscrição em cursos
    </a>
    <a class="nav-link {{ request()->routeIs('aluno.historico') ? 'active' : '' }}" href="{{ route('aluno.historico') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M8 9h8M8 13h8M8 17h5" />
        </svg>
        Histórico
    </a>
    <a class="nav-link {{ request()->routeIs('aluno.preferencias') ? 'active' : '' }}" href="{{ route('aluno.preferencias') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M12 8a4 4 0 1 0 0 8" />
            <path d="M4 12h2m12 0h2M12 4v2m0 12v2M6.5 6.5l1.4 1.4m8.2 8.2l1.4 1.4m0-11l-1.4 1.4m-8.2 8.2l-1.4 1.4" />
        </svg>
        Preferências
    </a>
</nav>
<form action="{{ route('aluno.logout') }}" method="POST" class="mt-auto">
    @csrf
    <button class="nav-link w-full" type="submit">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H3" />
            <path d="M19 4h2v16h-2" />
        </svg>
        Logout
    </button>
</form>
