<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Área do Aluno') - Sindimir</title>
        @php($favicon = config_db('tema.favicon'))
        @if ($favicon)
            <link rel="icon" href="{{ asset($favicon) }}">
        @endif
        @vite(['resources/css/app.css'])
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
            .aluno-topbar {
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
            .aluno-topbar-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                background: var(--card-bg);
                padding: 0.65rem 1rem;
            }
            .page-header {
                margin-bottom: 0;
                padding-bottom: 2.5rem;
            }
            .aluno-content {
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
            .alert-card {
                border-left: 4px solid var(--color-primary);
            }
            .table-wrapper {
                width: 100%;
                overflow-x: auto;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
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
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            .card {
                background: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 18px 20px;
            }
            .section-title {
                margin: 28px 0 12px;
                font-size: 1.2rem;
                font-weight: 700;
            }
            .muted {
                opacity: 0.7;
                font-size: 0.95rem;
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <aside class="sidebar">
                @include('aluno.partials.sidebar')
            </aside>
            <main class="content">
                <div class="aluno-topbar">
                    <div class="aluno-topbar-inner">
                        <div class="text-sm font-semibold text-[var(--content-text)] opacity-80">
                            Área do aluno
                        </div>
                        <div class="text-sm font-semibold text-[var(--content-text)]">
                            {{ auth()->user()?->display_name ?? auth()->user()?->name ?? 'Aluno' }}
                        </div>
                    </div>
                </div>
                <div class="aluno-content">
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
    </body>
</html>
