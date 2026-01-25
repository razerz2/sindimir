@php
    $style = $section->resolved_style ?? [];
    $content = $section->resolved_content ?? $section->conteudo ?? [];
    $cards = $content['cards'] ?? [];
    $hasOverlay = $style['has_overlay'] ?? false;
@endphp

<section class="site-section site-section--{{ $section->id }} site-section--{{ $section->tipo }} {{ $hasOverlay ? 'has-overlay' : '' }}">
    @if ($hasOverlay)
        <span class="site-section-overlay"></span>
    @endif
    <div class="container site-section-container">
        @if ($section->titulo)
            <h2 class="section-title">{{ $section->titulo }}</h2>
        @endif
        @if ($section->subtitulo)
            <p class="section-subtitle">{{ $section->subtitulo }}</p>
        @endif
        <div class="grid">
            @foreach ($cards as $card)
                <div class="card">
                    @if (!empty($card['icone']))
                        <span class="tag">{{ $card['icone'] }}</span>
                    @endif
                    <h3 class="section-title site-card-title">{{ $card['titulo'] ?? '' }}</h3>
                    <p class="section-subtitle">{{ $card['texto'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
