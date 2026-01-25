@php
    $style = $section->resolved_style ?? [];
    $content = $section->resolved_content ?? $section->conteudo ?? [];
    $buttons = $content['botoes'] ?? [];
    $hasOverlay = $style['has_overlay'] ?? false;
@endphp

<section class="site-section site-section--{{ $section->id }} site-section--{{ $section->tipo }} {{ $hasOverlay ? 'has-overlay' : '' }}">
    @if ($hasOverlay)
        <span class="site-section-overlay"></span>
    @endif
    <div class="container site-section-container">
        <div class="card">
            @if ($section->titulo)
                <h2 class="section-title">{{ $section->titulo }}</h2>
            @endif
            @if ($section->subtitulo)
                <p class="section-subtitle">{{ $section->subtitulo }}</p>
            @endif
            @if (!empty($buttons))
                <div class="site-section-actions">
                    @foreach ($buttons as $button)
                        @if (!empty($button['label']) && !empty($button['url']))
                            <a class="btn {{ $button['style'] ?? 'primary' }}" href="{{ $button['url'] }}">
                                {{ $button['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
