@extends('layouts.public')

@section('title', 'Entrar')

@section('content')
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <h1 class="section-title">Acesso</h1>
        <p class="section-subtitle">Entre com suas credenciais para acessar o painel.</p>

        @if (session('status'))
            <p class="tag">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="card" style="border-color: var(--primary);">
                <p><strong>Não foi possível entrar:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('admin.login.store') }}">
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
