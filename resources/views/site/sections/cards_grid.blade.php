<section class="section" id="{{ $section->slug }}">
    <h2 class="section-title">{{ $section->titulo }}</h2>

    <p class="section-subtitle">
        {{ $section->subtitulo }}
    </p>

    <div class="grid">
        @foreach($section->conteudo['cards'] as $card)
            <div class="card">
                <h3 style="margin-top: 0;">{{ $card['titulo'] }}</h3>
                <p class="muted">{{ $card['texto'] }}</p>
            </div>
        @endforeach
    </div>
</section>
