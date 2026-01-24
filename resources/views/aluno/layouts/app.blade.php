<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Área do Aluno') - Sindimir</title>
        @vite(['resources/css/app.css'])
        <style>
            :root {
                {!! $themeCssVariables !!}
                --sidebar-bg: var(--color-primary);
                --sidebar-text: #ffffff;
                --content-bg: var(--color-background);
                --content-text: var(--color-text);
                --border-color: var(--color-border);
            }
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: var(--content-bg);
                color: var(--content-text);
            }
            .layout {
                display: flex;
                min-height: 100vh;
            }
            .sidebar {
                width: 240px;
                background: var(--sidebar-bg);
                color: var(--sidebar-text);
                padding: 24px 16px;
            }
            .sidebar h1 {
                font-size: 18px;
                margin: 0 0 24px;
            }
            .sidebar nav a {
                display: block;
                color: var(--sidebar-text);
                text-decoration: none;
                padding: 10px 12px;
                border-radius: 6px;
                margin-bottom: 6px;
                font-size: 14px;
            }
            .sidebar nav a:hover {
                background: rgba(255, 255, 255, 0.15);
            }
            .content {
                flex: 1;
                padding: 32px;
            }
            .content header {
                margin-bottom: 24px;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 12px;
            }
            .content h2 {
                margin: 0;
                font-size: 22px;
            }
            .card {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <div class="layout">
            <aside class="sidebar">
                @include('aluno.partials.sidebar')
            </aside>
            <main class="content">
                <header>
                    <h2>@yield('title')</h2>
                </header>
                <div class="card">
                    @yield('content')
                </div>
            </main>
        </div>
    </body>
</html>
