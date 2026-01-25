<section class="section" id="contato">
    <div class="card" style="display: grid; gap: 16px;">
        <h2 class="section-title" style="margin: 0;">
            {{ $section->titulo }}
        </h2>

        <p class="section-subtitle" style="margin: 0;">
            {{ $section->subtitulo }}
        </p>

        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            @foreach($section->conteudo['botoes'] as $botao)
                <a class="btn {{ $botao['style'] }}" href="{{ $botao['url'] }}">
                    {{ $botao['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
