<section class="section">
    <div class="grid">
        <div class="card" style="grid-column: span 2;">
            @if(data_get($section->conteudo, 'tag'))
                <span class="tag">{{ $section->conteudo['tag'] }}</span>
            @endif

            <h1 class="section-title" style="font-size: 2.2rem; margin-top: 16px;">
                {{ $section->titulo }}
            </h1>

            <p class="section-subtitle">
                {{ $section->subtitulo }}
            </p>

            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                @foreach($section->conteudo['botoes'] ?? [] as $botao)
                    <a class="btn {{ $botao['style'] }}" href="{{ $botao['url'] }}">
                        {{ $botao['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top: 0;">
                {{ $section->conteudo['resultados_titulo'] }}
            </h3>

            <p class="muted">
                {{ $section->conteudo['resultados_subtitulo'] }}
            </p>

            <div style="display: grid; gap: 12px; margin-top: 20px;">
                @foreach($section->conteudo['resultados'] ?? [] as $item)
                    <div>
                        <strong>{{ $item['titulo'] }}</strong>
                        <p class="muted">{{ $item['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
