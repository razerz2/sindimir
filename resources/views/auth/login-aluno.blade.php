@extends('layouts.auth')

@section('title', 'Entrar - Aluno')

@section('content')
    <style>
        :root {
            --primary: #1d4ed8;
        }
    </style>
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
                <img src="{{ asset('assets/images/logo-default.png') }}" alt="Sindimir" style="height: 28px;">
                <span>Area do aluno</span>
            </div>
            <a class="tag" href="{{ route('public.home') }}">Voltar ao site</a>
        </div>
        <span class="tag">Area do aluno</span>
        <h1 class="section-title" style="margin-top: 16px;">Entrar</h1>
        <p class="section-subtitle">Acesse sua area para acompanhar inscricoes e cursos.</p>

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

        <form class="form" method="POST" action="{{ route('aluno.login.store') }}">
            @csrf

            <div class="field">
                <label for="cpf">CPF</label>
                <input
                    class="input"
                    id="cpf"
                    name="cpf"
                    type="text"
                    value="{{ \App\Support\Cpf::format(old('cpf')) }}"
                    placeholder="000.000.000-00"
                    inputmode="numeric"
                    data-mask="cpf"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <label class="tag" style="gap: 8px;">
                <input type="checkbox" name="remember" value="1">
                Manter conectado
            </label>

            <button class="btn primary" type="submit">Entrar</button>
        </form>
    </div>
    @include('partials.input-masks')
@endsection
