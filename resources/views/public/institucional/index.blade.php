@extends('layouts.public')

@php($wrapContent = false)

@section('title')
    {{ $metaTitle ?? 'Institucional' }}
@endsection

@php
    $bgImage = null;
    $overlay = 'rgba(255,255,255,0.85)';
    $position = 'center';
    $size = 'cover';

    if (function_exists('config_db')) {
        $bgImage = config_db('tema.background_main_imagem');
        $overlay = config_db('tema.background_main_overlay', $overlay);
        $position = config_db('tema.background_main_posicao', $position);
        $size = config_db('tema.background_main_tamanho', $size);
    }
@endphp

@section('content')
    <main
        @if(!empty($bgImage))
            style="
                background-image:
                    linear-gradient({{ $overlay }}, {{ $overlay }}),
                    url('{{ asset($bgImage) }}');
                background-size: {{ $size }};
                background-position: {{ $position }};
                background-repeat: no-repeat;
            "
        @endif
    >
        <div class="container">
            @if($hero)
            <section class="section">
                <div class="grid">
                    <div class="card" style="grid-column: span 2;">
                        <span class="tag">{{ $hero->conteudo['tag'] }}</span>

                        <h1 class="section-title" style="font-size: 2.2rem; margin-top: 16px;">
                            {{ $hero->titulo }}
                        </h1>

                        <p class="section-subtitle">
                            {{ $hero->subtitulo }}
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                            @foreach($hero->conteudo['botoes'] as $botao)
                                <a class="btn {{ $botao['style'] }}" href="{{ $botao['url'] }}">
                                    {{ $botao['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="card">
                        <h3 style="margin-top: 0;">
                            {{ $hero->conteudo['resultados_titulo'] }}
                        </h3>

                        <p class="muted">
                            {{ $hero->conteudo['resultados_subtitulo'] }}
                        </p>

                        <div style="display: grid; gap: 12px; margin-top: 20px;">
                            @foreach($hero->conteudo['resultados'] as $item)
                                <div>
                                    <strong>{{ $item['titulo'] }}</strong>
                                    <p class="muted">{{ $item['texto'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
            @endif

            @if($sobre)
            <section class="section" id="sobre">
                <h2 class="section-title">{{ $sobre->titulo }}</h2>
                <p class="section-subtitle">{{ $sobre->subtitulo }}</p>

                <div class="grid">
                    @foreach($sobre->conteudo['cards'] as $card)
                        <div class="card">
                            <h3 style="margin-top: 0;">{{ $card['titulo'] }}</h3>
                            <p class="muted">{{ $card['texto'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            @if($solucoes)
            <section class="section" id="solucoes">
                <h2 class="section-title">{{ $solucoes->titulo }}</h2>
                <p class="section-subtitle">{{ $solucoes->subtitulo }}</p>

                <div class="grid">
                    @foreach($solucoes->conteudo['cards'] as $card)
                        <div class="card">
                            <h3 style="margin-top: 0;">{{ $card['titulo'] }}</h3>
                            <p class="muted">{{ $card['texto'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            @if($diferenciais)
            <section class="section" id="diferenciais">
                <h2 class="section-title">{{ $diferenciais->titulo }}</h2>
                <p class="section-subtitle">{{ $diferenciais->subtitulo }}</p>

                <div class="grid">
                    @foreach($diferenciais->conteudo['cards'] as $card)
                        <div class="card">
                            <h3 style="margin-top: 0;">{{ $card['titulo'] }}</h3>
                            <p class="muted">{{ $card['texto'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            @if($cta)
            <section class="section" id="contato">
                <div class="card" style="display: grid; gap: 16px;">
                    <h2 class="section-title" style="margin: 0;">
                        {{ $cta->titulo }}
                    </h2>

                    <p class="section-subtitle" style="margin: 0;">
                        {{ $cta->subtitulo }}
                    </p>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        @foreach($cta->conteudo['botoes'] as $botao)
                            <a class="btn {{ $botao['style'] }}" href="{{ $botao['url'] }}">
                                {{ $botao['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        </div>
    </main>
@endsection

@section('footer')
    @include('public.institucional.partials.footer')
@endsection
