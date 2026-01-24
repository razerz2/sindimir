<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Sindimir')</title>
        <style>
            :root {
                color-scheme: light;
                {!! $themeCssVariables !!}
                --primary: var(--color-primary);
                --background: var(--color-background);
                --card: var(--color-card);
                --border: var(--color-border);
                --text: var(--color-text);
            }
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                font-family: "Segoe UI", "Inter", Arial, sans-serif;
                background: var(--background);
                color: var(--text);
            }
            a {
                color: inherit;
                text-decoration: none;
            }
            header {
                position: sticky;
                top: 0;
                z-index: 10;
                background: var(--card);
                border-bottom: 1px solid var(--border);
            }
            .container {
                max-width: 1120px;
                margin: 0 auto;
                padding: 0 24px;
            }
            .navbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 0;
                gap: 24px;
            }
            .nav-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                letter-spacing: 0.2px;
            }
            .nav-brand img {
                height: 38px;
            }
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 18px;
                font-weight: 600;
                font-size: 0.95rem;
            }
            main {
                padding: 48px 0 64px;
            }
            .section {
                padding: 48px 0;
            }
            .section-title {
                font-size: 1.6rem;
                margin: 0 0 16px;
            }
            .section-subtitle {
                margin: 0 0 28px;
                max-width: 720px;
                opacity: 0.85;
            }
            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 24px;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 18px;
            }
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 12px 18px;
                border-radius: 10px;
                border: 1px solid var(--border);
                font-weight: 600;
                transition: transform 0.2s ease;
            }
            .btn:hover {
                transform: translateY(-1px);
            }
            .btn.primary {
                background: var(--primary);
                border-color: var(--primary);
                color: var(--card);
            }
            .btn.outline {
                background: transparent;
            }
            .form {
                display: grid;
                gap: 16px;
            }
            .field {
                display: grid;
                gap: 8px;
            }
            .field label {
                font-weight: 600;
            }
            .input {
                width: 100%;
                padding: 12px 14px;
                border-radius: 10px;
                border: 1px solid var(--border);
                background: var(--card);
                color: var(--text);
                font-size: 1rem;
            }
            .input:focus {
                outline: 2px solid var(--primary);
                outline-offset: 2px;
            }
            .tag {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 999px;
                border: 1px solid var(--border);
                font-size: 0.85rem;
                opacity: 0.9;
            }
            footer {
                background: var(--card);
                border-top: 1px solid var(--border);
                padding: 36px 0;
            }
            .footer-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                font-size: 0.95rem;
            }
            .muted {
                opacity: 0.8;
            }
            @media (max-width: 720px) {
                .navbar {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <div class="container navbar">
                <a class="nav-brand" href="{{ route('public.home') }}">
                    <img src="{{ asset('assets/images/logo-default.png') }}" alt="Sindimir">
                    <span>Sindimir</span>
                </a>
                <nav class="nav-links">
                    <a href="{{ route('public.home') }}">Inicio</a>
                    <a href="{{ route('public.cursos') }}">Cursos</a>
                    <a href="{{ route('public.cpf') }}">Inscricao</a>
                    <a href="#contato">Contato</a>
                </nav>
            </div>
        </header>
        <main>
            <div class="container">
                @yield('content')
            </div>
        </main>
        @yield('footer')
    </body>
</html>
