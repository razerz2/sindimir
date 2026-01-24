<div class="sidebar-brand">
    <img src="{{ asset('assets/images/logo-default.png') }}" alt="Sindimir">
    <span>Admin Sindimir</span>
</div>
<nav>
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M3 12l9-8 9 8" />
            <path d="M9 21V9h6v12" />
        </svg>
        Dashboard
    </a>
    <a class="nav-link {{ request()->routeIs('admin.cursos.*') ? 'active' : '' }}" href="{{ route('admin.cursos.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 6h16M4 12h16M4 18h10" />
        </svg>
        Cursos
    </a>
    <a class="nav-link {{ request()->routeIs('admin.eventos.*') ? 'active' : '' }}" href="{{ route('admin.eventos.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4M8 2v4M3 10h18" />
        </svg>
        Eventos
    </a>
    <a class="nav-link {{ request()->routeIs('admin.alunos.*') ? 'active' : '' }}" href="{{ route('admin.alunos.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M16 11a4 4 0 1 0-8 0" />
            <path d="M4 20a8 8 0 0 1 16 0" />
        </svg>
        Alunos
    </a>
    <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M16 11a4 4 0 1 0-8 0" />
            <path d="M20 20a6 6 0 0 0-12 0" />
        </svg>
        Usuarios
    </a>
    <a class="nav-link {{ request()->routeIs('admin.auditoria.*') ? 'active' : '' }}" href="{{ route('admin.auditoria.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 5h16v14H4z" />
            <path d="M8 9h8M8 13h8M8 17h5" />
        </svg>
        Auditoria
    </a>
    <a class="nav-link {{ request()->routeIs('admin.notificacoes.*') ? 'active' : '' }}" href="{{ route('admin.notificacoes.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 4h16v4h-1v9c0 1.1-.9 2-2 2h-10c-1.1 0-2-.9-2-2v-9h-1z" />
            <path d="M12 14l4-4-4-4-4 4z" />
        </svg>
        Notificações
    </a>
    <a class="nav-link {{ request()->routeIs('admin.relatorios.*') ? 'active' : '' }}" href="{{ route('admin.relatorios.index') }}">
        <svg class="h-5 w-5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M4 5h16v14H4z" />
            <path d="M8 9h8M8 13h8M8 17h5" />
        </svg>
        Relatórios
    </a>
</nav>
