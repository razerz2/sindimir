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
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: var(--background);
                color: var(--text);
            }
            header {
                background: var(--primary);
                color: #ffffff;
                padding: 16px 24px;
            }
            header nav a {
                color: #ffffff;
                margin-right: 16px;
                text-decoration: none;
                font-weight: 600;
            }
            main {
                padding: 32px 24px;
                max-width: 960px;
                margin: 0 auto;
            }
            .card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <header>
            <nav>
                <a href="{{ route('public.institucional') }}">Institucional</a>
                <a href="{{ route('public.cursos') }}">Cursos</a>
                <a href="{{ route('public.cpf') }}">Inscrição por CPF</a>
            </nav>
        </header>
        <main>
            <h1>@yield('title')</h1>
            @yield('content')
        </main>
    </body>
</html>
