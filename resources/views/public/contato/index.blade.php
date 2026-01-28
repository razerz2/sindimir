@extends('layouts.public')

@section('title')
    {{ $metaTitle ?? 'Contato' }}
@endsection

@section('content')
    <section class="section" id="contato">
        <h1 class="section-title">Contato</h1>
        <p class="section-subtitle">
            Envie sua mensagem para o Sindicato Rural de Miranda e Bodoquena.
        </p>

        @if (session('success'))
            <div class="card" style="border-color: var(--primary); margin-bottom: 18px;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="card" style="border-color: #b91c1c; margin-bottom: 18px;">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="card" style="border-color: #b91c1c; margin-bottom: 18px;">
                <p><strong>Corrija os campos abaixo:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid" style="grid-template-columns: 1fr;">
            <div class="card">
                <form method="POST" action="{{ route('public.contato.enviar') }}" class="form">
                    @csrf

                    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                        <div class="field">
                            <label for="nome">Nome completo</label>
                            <input class="input" type="text" id="nome" name="nome" value="{{ old('nome') }}" required>
                        </div>

                        <div class="field">
                            <label for="email">E-mail</label>
                            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="telefone">Telefone</label>
                        <input
                            class="input"
                            type="text"
                            id="telefone"
                            name="telefone"
                            value="{{ \App\Support\Phone::format(old('telefone')) }}"
                            placeholder="(00) 00000-0000"
                            inputmode="numeric"
                            data-mask="phone"
                        >
                    </div>

                    <div class="field">
                        <label for="assunto">Assunto</label>
                        <input class="input" type="text" id="assunto" name="assunto" value="{{ old('assunto') }}" required>
                    </div>

                    <div class="field">
                        <label for="mensagem">Mensagem</label>
                        <textarea class="input" id="mensagem" name="mensagem" rows="5" required>{{ old('mensagem') }}</textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn primary">
                            Enviar mensagem
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @include('partials.input-masks')
@endsection

@section('footer')
    @include('public.institucional.partials.footer')
@endsection
