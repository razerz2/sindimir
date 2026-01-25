@extends('layouts.public')

@section('title')
    {{ $metaTitle ?? 'Contato' }}
@endsection

@section('content')
    <section class="section">
        <h1 class="section-title">Contato</h1>
        <p class="section-subtitle">
            Fale com o Sindicato Rural de Miranda e Bodoquena para tirar dúvidas,
            solicitar informações ou agendar atendimento.
        </p>
        <div class="grid">
            <div class="card">
                <h3 style="margin-top: 0;">{{ data_get($footer, 'contato_titulo', 'Contato') }}</h3>
                <p class="muted">{{ data_get($footer, 'contato_email', 'contato@sindimir.org') }}</p>
                <p class="muted">{{ data_get($footer, 'contato_telefone', '(00) 0000-0000') }}</p>
            </div>
            <div class="card">
                <h3 style="margin-top: 0;">{{ data_get($footer, 'endereco_titulo', 'Endereco') }}</h3>
                <p class="muted">{{ data_get($footer, 'endereco_linha1', 'Rua da Industria, 1000') }}</p>
                <p class="muted">{{ data_get($footer, 'endereco_linha2', 'Distrito Industrial') }}</p>
            </div>
            <div class="card">
                <h3 style="margin-top: 0;">Atendimento</h3>
                <p class="muted">Segunda a sexta-feira</p>
                <p class="muted">07h30 às 11h00 • 13h00 às 17h00</p>
            </div>
        </div>
    </section>
@endsection

@section('footer')
    @include('public.institucional.partials.footer')
@endsection
