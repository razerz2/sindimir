@extends('layouts.auth')

@section('title', 'Entrar - Admin')

@section('content')
    <style>
        :root {
            --primary: #0f3d2e;
        }
    </style>
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
                <img src="{{ asset('assets/images/logo-default.png') }}" alt="Sindimir" style="height: 28px;">
                <span>Sindimir Admin</span>
            </div>
            <a class="tag" href="{{ route('public.institucional') }}">Voltar para institucional</a>
        </div>
        <span class="tag">Acesso administrativo</span>
        <h1 class="section-title" style="margin-top: 16px;">Entrar</h1>
        <p class="section-subtitle">Use suas credenciais de administrador para acessar o painel.</p>

        @if (session('status'))
            <p class="tag">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="card" style="border-color: var(--primary);">
                <p><strong>Nao foi possivel entrar:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">E-mail</label>
                <input
                    class="input"
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="password">Senha</label>
                <input
                    class="input"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <label class="tag" style="gap: 8px;">
                <input type="checkbox" name="remember" value="1">
                Manter conectado
            </label>

            <button class="btn primary" type="submit">Entrar</button>
        </form>
    </div>
@endsection
