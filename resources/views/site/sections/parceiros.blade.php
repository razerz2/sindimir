@php
    $style = $section->resolved_style ?? [];
    $content = $section->resolved_content ?? $section->conteudo ?? [];
    $logos = $content['logos'] ?? [];
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
            @foreach ($logos as $logo)
                @if (!empty($logo['url']))
                    <div class="card site-partner-card">
                        @if (!empty($logo['link']))
                            <a href="{{ $logo['link'] }}" target="_blank" rel="noopener">
                                <img class="site-partner-logo" src="{{ $logo['url'] }}" alt="{{ $logo['alt'] ?? 'Parceiro' }}" loading="lazy">
                            </a>
                        @else
                            <img class="site-partner-logo" src="{{ $logo['url'] }}" alt="{{ $logo['alt'] ?? 'Parceiro' }}" loading="lazy">
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
