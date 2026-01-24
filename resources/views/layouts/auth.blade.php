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
            .container {
                max-width: 1120px;
                margin: 0 auto;
                padding: 0 24px;
            }
            .auth-wrapper {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 48px 0 64px;
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
        </style>
    </head>
    <body>
        <main class="auth-wrapper">
            <div class="container">
                @yield('content')
            </div>
        </main>
    </body>
</html>
