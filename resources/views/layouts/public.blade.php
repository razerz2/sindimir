<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Sindimir')</title>
        @if (!empty($themeFavicon))
            <link rel="icon" href="{{ asset($themeFavicon) }}">
        @endif
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
                width: 38px;
                border-radius: 999px;
                object-fit: cover;
            }
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 18px;
                font-weight: 600;
                font-size: 0.95rem;
            }
            .nav-links a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .nav-icon {
                width: 16px;
                height: 16px;
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
                background: #0f3d2e;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                padding: 36px 0;
                color: #ffffff;
            }
            footer .muted {
                color: rgba(255, 255, 255, 0.8);
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
                    align-items: center;
                    gap: 16px;
                }
                .nav-brand {
                    width: 100%;
                    justify-content: flex-start;
                }
                .nav-links {
                    width: 100%;
                    justify-content: center;
                    row-gap: 12px;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <div class="container navbar">
                <a class="nav-brand" href="{{ route('public.home') }}">
                    <img src="{{ $themeLogo ? asset($themeLogo) : asset('assets/images/logo-default.png') }}" alt="Sindimir">
                    <span>Sindimir</span>
                </a>
                <nav class="nav-links">
                    <a href="{{ route('public.home') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M3 12l9-8 9 8" />
                            <path d="M9 21V9h6v12" />
                        </svg>
                        Início
                    </a>
                    <a href="{{ route('public.cursos') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h10" />
                        </svg>
                        Cursos
                    </a>
                    <a href="{{ route('public.cpf') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M7 7h10v10H7z" />
                            <path d="M9 5h6M9 19h6" />
                            <path d="M10 9h4M10 12h4M10 15h3" />
                        </svg>
                        Inscrição
                    </a>
                    <a href="{{ route('public.contato') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M4 6h16v12H4z" />
                            <path d="M4 6l8 7 8-7" />
                        </svg>
                        Contato
                    </a>
                    <a href="{{ route('aluno.login') }}">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M10 17l5-5-5-5" />
                            <path d="M15 12H3" />
                            <path d="M19 4h2v16h-2" />
                        </svg>
                        Login Aluno
                    </a>
                </nav>
            </div>
        </header>
        @if ($wrapContent ?? true)
            <main
                @if(!empty($themeBackgroundImage))
                    style="
                        background-image:
                            linear-gradient({{ $themeBackgroundOverlay ?? 'rgba(255,255,255,0.85)' }}, {{ $themeBackgroundOverlay ?? 'rgba(255,255,255,0.85)' }}),
                            url('{{ asset($themeBackgroundImage) }}');
                        background-size: {{ $themeBackgroundSize ?? 'cover' }};
                        background-position: {{ $themeBackgroundPosition ?? 'center' }};
                        background-repeat: no-repeat;
                    "
                @endif
            >
                <div class="container">
                    @yield('content')
                </div>
            </main>
        @else
            @yield('content')
        @endif
        @yield('footer')
        @include('partials.dialog')
    </body>
</html>
