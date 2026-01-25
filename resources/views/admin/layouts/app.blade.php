<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Admin') - Sindimir</title>
        @php($favicon = config_db('tema.favicon'))
        @if ($favicon)
            <link rel="icon" href="{{ asset($favicon) }}">
        @endif
        @vite(['resources/css/app.css'])
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
        <style>
            :root {
                {!! $themeCssVariables !!}
                --sidebar-bg: var(--color-primary);
                --sidebar-text: #ffffff;
                --content-bg: var(--color-background);
                --content-text: var(--color-text);
                --border-color: var(--color-border);
                --card-bg: var(--color-card);
            }
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                font-family: "Segoe UI", "Inter", Arial, sans-serif;
                background: var(--content-bg);
                color: var(--content-text);
            }
            .layout {
                display: flex;
                min-height: 100vh;
            }
            .sidebar {
                width: 260px;
                position: fixed;
                inset: 0 auto 0 0;
                background: var(--sidebar-bg);
                color: var(--sidebar-text);
                padding: 24px 18px;
                display: flex;
                flex-direction: column;
                gap: 24px;
                overflow-y: auto;
            }
            .sidebar-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                font-size: 1rem;
                letter-spacing: 0.3px;
            }
            .sidebar-brand img {
                height: 34px;
            }
            .sidebar nav {
                display: grid;
                gap: 6px;
            }
            .nav-link {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--sidebar-text);
                text-decoration: none;
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 0.95rem;
                opacity: 0.86;
                transition: all 0.2s ease;
            }
            .nav-link:hover {
                opacity: 1;
                background: rgba(255, 255, 255, 0.14);
            }
            .nav-link.active {
                opacity: 1;
                background: rgba(255, 255, 255, 0.22);
                font-weight: 700;
            }
            .content {
                flex: 1;
                padding: 0 32px 32px;
                margin-left: 260px;
                height: 100vh;
                overflow-y: auto;
            }
            .admin-topbar {
                position: sticky;
                top: 0;
                z-index: 40;
                background: transparent;
                margin-top: 0;
                padding: 0;
                border-bottom-left-radius: 12px;
                border-bottom-right-radius: 12px;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            }
            .admin-topbar-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                background: var(--card-bg);
                padding: 0.65rem 1rem;
            }
            .content header {
                margin-bottom: 0;
            }
            .page-header {
                margin-bottom: 0;
                padding-bottom: 2.5rem;
            }
            .admin-content {
                padding-top: 2rem;
            }
            .page-title {
                margin: 0;
                font-size: 1.6rem;
                font-weight: 700;
            }
            .page-subtitle {
                margin: 6px 0 0;
                opacity: 0.75;
                font-size: 0.95rem;
            }
            .content-card {
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 24px;
            }
            .page-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 16px;
            }
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 14px;
                border-radius: 10px;
                border: 1px solid var(--border-color);
                font-weight: 600;
                font-size: 0.95rem;
                text-decoration: none;
                color: inherit;
                background: transparent;
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            .btn:hover {
                transform: translateY(-1px);
            }
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
            .btn-primary {
                background: var(--color-primary);
                color: var(--card-bg);
                border-color: var(--color-primary);
            }
            .btn-ghost {
                background: transparent;
            }
            .btn-danger {
                border-color: #ef4444;
                color: #ef4444;
                background: transparent;
            }
            .table-wrapper {
                width: 100%;
                overflow-x: auto;
            }
            table.dataTable.table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 0.95rem;
            }
            .table th,
            .table td {
                padding: 12px 14px;
                border-bottom: 1px solid var(--border-color);
                text-align: left;
                vertical-align: middle;
            }
            .table thead th {
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                opacity: 0.7;
            }
            .table-actions {
                display: inline-flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 600;
                background: rgba(15, 61, 46, 0.12);
                color: var(--color-primary);
            }
            .badge.success {
                background: rgba(15, 61, 46, 0.12);
                color: var(--color-primary);
            }
            .badge.warning {
                background: rgba(234, 179, 8, 0.15);
                color: #b45309;
            }
            .badge.info {
                background: rgba(59, 130, 246, 0.15);
                color: #1d4ed8;
            }
            .badge.neutral {
                background: rgba(148, 163, 184, 0.2);
                color: #475569;
            }
            .badge.danger {
                background: rgba(239, 68, 68, 0.15);
                color: #b91c1c;
            }
            .alert {
                padding: 12px 14px;
                border-radius: 10px;
                border: 1px solid var(--border-color);
                margin-bottom: 16px;
                background: var(--card-bg);
            }
            .dataTables_wrapper .dataTables_filter label {
                font-weight: 600;
                font-size: 0.9rem;
            }
            .dataTables_wrapper .dataTables_filter input {
                margin-left: 8px;
                padding: 8px 10px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                background: var(--card-bg);
            }
            .dataTables_wrapper .dataTables_length select {
                padding: 6px 8px;
                border-radius: 8px;
                border: 1px solid var(--border-color);
                background: var(--card-bg);
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                border-radius: 8px;
                padding: 6px 10px;
                margin: 0 2px;
            }
            .pagination {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 18px;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            .kpi-card {
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 18px 20px;
                display: grid;
                gap: 8px;
                min-height: 120px;
            }
            .kpi-label {
                font-size: 0.9rem;
                opacity: 0.7;
            }
            .kpi-value {
                font-size: 1.8rem;
                font-weight: 700;
            }
            .kpi-icon {
                width: 28px;
                height: 28px;
                opacity: 0.6;
            }
            .section-title {
                margin: 28px 0 12px;
                font-size: 1.2rem;
                font-weight: 700;
            }
            .alert-card {
                border-left: 4px solid var(--color-primary);
            }
            .alert-list {
                display: grid;
                gap: 10px;
                margin: 0;
                padding-left: 16px;
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <aside class="sidebar">
                @include('admin.partials.sidebar')
            </aside>
            <main class="content">
                <div class="admin-topbar">
                <div class="admin-topbar-inner flex items-center justify-between gap-4">
                        <div class="text-sm font-semibold text-[var(--content-text)] opacity-80">
                            Painel administrativo
                        </div>
                        <div class="flex items-center gap-3">
                            <details class="relative">
                                <summary class="list-none cursor-pointer rounded-full border border-[var(--border-color)] p-2 text-[var(--content-text)] hover:bg-black/5">
                                    <span class="sr-only">Notificacoes</span>
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.17V11a6 6 0 1 0-12 0v3.17a2 2 0 0 1-.6 1.43L4 17h5" />
                                        <path d="M9 17a3 3 0 0 0 6 0" />
                                    </svg>
                                    <span class="absolute -right-1 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                                        0
                                    </span>
                                </summary>
                                <div class="absolute right-0 mt-2 w-72 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] p-4 text-sm shadow-lg">
                                    <p class="font-semibold">Notificacoes</p>
                                    <p class="mt-2 text-[var(--content-text)] opacity-70">Nenhuma notificacao.</p>
                                </div>
                            </details>

                            <details class="relative">
                                <summary class="list-none cursor-pointer rounded-full border border-[var(--border-color)] px-3 py-2 text-sm font-semibold text-[var(--content-text)] hover:bg-black/5">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[var(--color-primary)] text-xs font-bold text-white">
                                        {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                                    </span>
                                    <span class="ml-2 hidden sm:inline">{{ auth()->user()?->name ?? 'Administrador' }}</span>
                                </summary>
                                <div class="absolute right-0 mt-2 w-56 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] p-2 text-sm shadow-lg">
                                    <a class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-black/5" href="{{ route('admin.usuarios.index') }}">
                                        <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                            <path d="M16 11a4 4 0 1 0-8 0" />
                                            <path d="M4 20a8 8 0 0 1 16 0" />
                                        </svg>
                                        <span>Perfil</span>
                                    </a>
                                    <a class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-black/5" href="{{ route('admin.configuracoes.index') }}">
                                        <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                            <path d="M12 8a4 4 0 1 0 0 8" />
                                            <path d="M4 12h2m12 0h2M12 4v2m0 12v2M6.5 6.5l1.4 1.4m8.2 8.2l1.4 1.4m0-11l-1.4 1.4m-8.2 8.2l-1.4 1.4" />
                                        </svg>
                                        <span>Configuracoes</span>
                                    </a>
                                    <form action="{{ route('logout') }}" method="POST" class="px-3 py-2">
                                        @csrf
                                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left hover:bg-black/5" type="submit">
                                            <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                                <path d="M10 17l5-5-5-5" />
                                                <path d="M15 12H3" />
                                                <path d="M19 4h2v16h-2" />
                                            </svg>
                                            <span>Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
                <div class="admin-content">
                    <header class="page-header">
                        <h2 class="page-title">@yield('title')</h2>
                        @hasSection('subtitle')
                            <p class="page-subtitle">@yield('subtitle')</p>
                        @endif
                    </header>
                    <div class="content-card">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                window.jQuery('.datatable').DataTable({
                    paging: false,
                    info: false,
                    searching: true,
                    ordering: true,
                    language: {
                        search: 'Buscar:',
                        zeroRecords: 'Nenhum registro encontrado',
                        emptyTable: 'Nenhum registro encontrado',
                        infoEmpty: 'Nenhum registro encontrado',
                        paginate: {
                            next: 'Proximo',
                            previous: 'Anterior',
                        },
                    },
                });
            });
        </script>
    </body>
</html>
