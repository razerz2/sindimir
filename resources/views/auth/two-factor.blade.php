@extends('layouts.auth')

@section('title', 'Verificacao em dois fatores')

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
                <span>Verificação</span>
            </div>
        </div>
        <span class="tag">Autenticação em dois fatores</span>
        <h1 class="section-title" style="margin-top: 16px;">Informe o código</h1>
        <p class="section-subtitle">
            Enviamos um código de verificação via {{ $channel === 'whatsapp' ? 'WhatsApp' : 'E-mail' }}
            @if (!empty($destination))
                para {{ $destination }}.
            @endif
        </p>

        @if (session('status'))
            <p class="tag">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="card" style="border-color: var(--primary);">
                <p><strong>Nao foi possivel validar:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" method="POST" action="{{ route('2fa.verify') }}">
            @csrf

            <div class="field">
                <label for="code">Código</label>
                <input
                    class="input"
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="\d*"
                    maxlength="6"
                    value="{{ old('code') }}"
                    required
                    autofocus
                >
            </div>

            <button class="btn primary" type="submit">Validar</button>
        </form>

        <form method="POST" action="{{ route('2fa.resend') }}" style="margin-top: 12px;">
            @csrf
            <button class="btn" type="submit">Reenviar código</button>
        </form>
    </div>
@endsection
