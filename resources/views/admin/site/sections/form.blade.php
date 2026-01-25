@extends('admin.layouts.app')

@section('title', $section->exists ? 'Editar section' : 'Nova section')

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @php
        $isHomeSlot = $section->exists && in_array($section->slug, \App\Models\SiteSection::HOME_SLOTS, true);
    @endphp

    <div class="content-card">
        <form
            class="space-y-8"
            method="POST"
            action="{{ $section->exists ? route('admin.site.sections.update', $section) : route('admin.site.sections.store') }}"
        >
            @csrf
            @if ($section->exists)
                @method('PUT')
            @endif

            <div>
                <h3 class="text-base font-semibold">Dados básicos</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.input name="slug" label="Slug" required :value="$section->slug" :readonly="$isHomeSlot" />
                    @if ($isHomeSlot)
                        <input type="hidden" name="tipo" value="{{ old('tipo', $section->tipo) }}">
                    @endif
                    <x-admin.select name="tipo" label="Tipo" required :disabled="$isHomeSlot">
                        @foreach (['hero_com_resultados', 'cards_grid', 'cta_card'] as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo', $section->tipo) === $tipo)>
                                {{ ucfirst($tipo) }}
                            </option>
                        @endforeach
                    </x-admin.select>
                    <x-admin.input name="titulo" label="Título" :value="$section->titulo" />
                    <x-admin.textarea name="subtitulo" label="Subtítulo">{{ old('subtitulo', $section->subtitulo) }}</x-admin.textarea>
                    <x-admin.select name="ativo" label="Status" required>
                        <option value="1" @selected((string) old('ativo', $section->ativo ? '1' : '0') === '1')>Ativo</option>
                        <option value="0" @selected((string) old('ativo', $section->ativo ? '1' : '0') === '0')>Inativo</option>
                    </x-admin.select>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold">Conteúdo</h3>
                <div class="mt-4 space-y-6">
                    <div data-section-type="hero_com_resultados" class="section-content">
                        <h4 class="text-sm font-semibold">Hero + resultados</h4>
                        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-admin.input name="conteudo[tag]" label="Tag" :value="data_get($section->conteudo, 'tag')" />
                        </div>
                        <div class="mt-4 space-y-4" id="hero-buttons-container">
                            @php
                                $heroButtons = old('conteudo.botoes', data_get($section->conteudo, 'botoes', []));
                            @endphp
                            @foreach ($heroButtons as $index => $button)
                                <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-3">
                                    <x-admin.input name="conteudo[botoes][{{ $index }}][label]" label="Texto do botão"
                                        :value="data_get($button, 'label')" />
                                    <x-admin.input name="conteudo[botoes][{{ $index }}][url]" label="Link do botão"
                                        :value="data_get($button, 'url')" />
                                    <x-admin.select name="conteudo[botoes][{{ $index }}][style]" label="Estilo">
                                        @foreach (['primary', 'outline'] as $style)
                                            <option value="{{ $style }}" @selected(data_get($button, 'style') === $style)>
                                                {{ ucfirst($style) }}
                                            </option>
                                        @endforeach
                                    </x-admin.select>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-ghost mt-3" type="button" id="add-hero-button">Adicionar botão</button>
                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-admin.input name="conteudo[resultados_titulo]" label="Título dos resultados"
                                :value="data_get($section->conteudo, 'resultados_titulo')" />
                            <x-admin.input name="conteudo[resultados_subtitulo]" label="Subtítulo dos resultados"
                                :value="data_get($section->conteudo, 'resultados_subtitulo')" />
                        </div>
                        <div class="mt-4 space-y-4" id="results-container">
                            @php
                                $resultados = old('conteudo.resultados', data_get($section->conteudo, 'resultados', []));
                            @endphp
                            @foreach ($resultados as $index => $resultado)
                                <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-2">
                                    <x-admin.input name="conteudo[resultados][{{ $index }}][titulo]" label="Título"
                                        :value="data_get($resultado, 'titulo')" />
                                    <x-admin.input name="conteudo[resultados][{{ $index }}][texto]" label="Texto"
                                        :value="data_get($resultado, 'texto')" />
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-ghost mt-3" type="button" id="add-result">Adicionar resultado</button>
                    </div>

                    <div data-section-type="cards_grid" class="section-content hidden">
                        <h4 class="text-sm font-semibold">Cards</h4>
                        <div class="mt-3 space-y-4" id="cards-container">
                            @php
                                $cards = old('conteudo.cards', data_get($section->conteudo, 'cards', []));
                            @endphp
                            @foreach ($cards as $index => $card)
                                <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-3">
                                    <x-admin.input name="conteudo[cards][{{ $index }}][titulo]" label="Título"
                                        :value="data_get($card, 'titulo')" />
                                    <x-admin.input name="conteudo[cards][{{ $index }}][texto]" label="Texto"
                                        :value="data_get($card, 'texto')" />
                                    <x-admin.input name="conteudo[cards][{{ $index }}][icone]" label="Ícone (string)"
                                        :value="data_get($card, 'icone')" />
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-ghost mt-3" type="button" id="add-card">Adicionar card</button>
                    </div>

                    <div data-section-type="cta_card" class="section-content hidden">
                        <h4 class="text-sm font-semibold">CTA</h4>
                        <div class="mt-4 space-y-4" id="cta-buttons-container">
                            @php
                                $ctaButtons = old('conteudo.botoes', data_get($section->conteudo, 'botoes', []));
                            @endphp
                            @foreach ($ctaButtons as $index => $button)
                                <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-3">
                                    <x-admin.input name="conteudo[botoes][{{ $index }}][label]" label="Texto do botão"
                                        :value="data_get($button, 'label')" />
                                    <x-admin.input name="conteudo[botoes][{{ $index }}][url]" label="Link do botão"
                                        :value="data_get($button, 'url')" />
                                    <x-admin.select name="conteudo[botoes][{{ $index }}][style]" label="Estilo">
                                        @foreach (['primary', 'outline'] as $style)
                                            <option value="{{ $style }}" @selected(data_get($button, 'style') === $style)>
                                                {{ ucfirst($style) }}
                                            </option>
                                        @endforeach
                                    </x-admin.select>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-ghost mt-3" type="button" id="add-cta-button">Adicionar botão</button>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold">Estilo visual</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <x-admin.select name="estilo[background_type]" label="Tipo de background">
                        @foreach (['color', 'image', 'gradient'] as $type)
                            <option value="{{ $type }}" @selected(data_get($section->estilo, 'background_type') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </x-admin.select>
                    <x-admin.input name="estilo[background_color]" label="Cor de fundo" type="color"
                        :value="data_get($section->estilo, 'background_color')" />
                    <x-admin.select name="estilo[background_image_id]" label="Imagem de fundo">
                        <option value="">Sem imagem</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}" @selected((int) data_get($section->estilo, 'background_image_id') === $asset->id)>
                                #{{ $asset->id }} - {{ $asset->path }}
                            </option>
                        @endforeach
                    </x-admin.select>
                    <x-admin.input name="estilo[overlay_opacity]" label="Opacidade do overlay" type="number" step="0.1"
                        :value="data_get($section->estilo, 'overlay_opacity')" />
                    <x-admin.input name="estilo[text_color]" label="Cor do texto" type="color"
                        :value="data_get($section->estilo, 'text_color')" />
                    <x-admin.select name="estilo[container_width]" label="Largura do container">
                        @foreach (['default', 'wide', 'full'] as $width)
                            <option value="{{ $width }}" @selected(data_get($section->estilo, 'container_width') === $width)>
                                {{ ucfirst($width) }}
                            </option>
                        @endforeach
                    </x-admin.select>
                    <x-admin.input name="estilo[padding_top]" label="Padding topo"
                        :value="data_get($section->estilo, 'padding_top')" />
                    <x-admin.input name="estilo[padding_bottom]" label="Padding base"
                        :value="data_get($section->estilo, 'padding_bottom')" />
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold">Mídia</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.input id="media-file" name="media_file" type="file" label="Upload de imagem" />
                    <x-admin.input id="media-alt" name="media_alt" label="Texto alternativo" />
                </div>
                <button class="btn btn-ghost mt-3" type="button" id="upload-media">Enviar imagem</button>
                <p class="mt-2 text-sm opacity-70" id="media-feedback"></p>
            </div>

            <div class="flex items-center gap-3">
                <button class="btn btn-primary" type="submit">
                    {{ $section->exists ? 'Salvar alterações' : 'Criar section' }}
                </button>
                <a class="btn btn-ghost" href="{{ route('admin.site.sections.index') }}">Voltar</a>
            </div>
        </form>
    </div>

    <template id="card-template">
        <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-3">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Título</label>
                <input data-field="titulo" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Texto</label>
                <input data-field="texto" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Ícone (string)</label>
                <input data-field="icone" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
        </div>
    </template>

    <template id="button-template">
        <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-3">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Texto do botão</label>
                <input data-field="label" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Link do botão</label>
                <input data-field="url" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Estilo</label>
                <select data-field="style" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
                    <option value="primary">Primary</option>
                    <option value="outline">Outline</option>
                </select>
            </div>
        </div>
    </template>

    <template id="result-template">
        <div class="grid grid-cols-1 gap-4 rounded-xl border border-[var(--border-color)] p-4 md:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Título</label>
                <input data-field="titulo" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-[var(--content-text)]">Texto</label>
                <input data-field="texto" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
            </div>
        </div>
    </template>

    <script>
        const tipoSelect = document.querySelector('select[name="tipo"]');
        const sections = document.querySelectorAll('.section-content');

        function toggleSections() {
            const value = tipoSelect.value;
            sections.forEach((section) => {
                section.classList.toggle('hidden', section.dataset.sectionType !== value);
            });
        }

        tipoSelect?.addEventListener('change', toggleSections);
        toggleSections();

        const cardsContainer = document.getElementById('cards-container');
        const addCardButton = document.getElementById('add-card');
        const cardTemplate = document.getElementById('card-template');

        addCardButton?.addEventListener('click', () => {
            const index = cardsContainer.children.length;
            const clone = cardTemplate.content.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                const field = input.dataset.field;
                input.name = `conteudo[cards][${index}][${field}]`;
            });
            cardsContainer.appendChild(clone);
        });

        const buttonTemplate = document.getElementById('button-template');
        const heroButtonsContainer = document.getElementById('hero-buttons-container');
        const addHeroButton = document.getElementById('add-hero-button');

        addHeroButton?.addEventListener('click', () => {
            const index = heroButtonsContainer.children.length;
            const clone = buttonTemplate.content.cloneNode(true);
            clone.querySelectorAll('select, input').forEach((input) => {
                const field = input.dataset.field;
                input.name = `conteudo[botoes][${index}][${field}]`;
            });
            heroButtonsContainer.appendChild(clone);
        });

        const ctaButtonsContainer = document.getElementById('cta-buttons-container');
        const addCtaButton = document.getElementById('add-cta-button');

        addCtaButton?.addEventListener('click', () => {
            const index = ctaButtonsContainer.children.length;
            const clone = buttonTemplate.content.cloneNode(true);
            clone.querySelectorAll('select, input').forEach((input) => {
                const field = input.dataset.field;
                input.name = `conteudo[botoes][${index}][${field}]`;
            });
            ctaButtonsContainer.appendChild(clone);
        });

        const resultsContainer = document.getElementById('results-container');
        const addResultButton = document.getElementById('add-result');
        const resultTemplate = document.getElementById('result-template');

        addResultButton?.addEventListener('click', () => {
            const index = resultsContainer.children.length;
            const clone = resultTemplate.content.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                const field = input.dataset.field;
                input.name = `conteudo[resultados][${index}][${field}]`;
            });
            resultsContainer.appendChild(clone);
        });

        const uploadButton = document.getElementById('upload-media');
        const mediaFileInput = document.getElementById('media-file');
        const mediaAltInput = document.getElementById('media-alt');
        const mediaFeedback = document.getElementById('media-feedback');

        uploadButton?.addEventListener('click', async () => {
            if (!mediaFileInput?.files?.length) {
                mediaFeedback.textContent = 'Selecione um arquivo primeiro.';
                return;
            }

            const formData = new FormData();
            formData.append('file', mediaFileInput.files[0]);
            formData.append('alt_text', mediaAltInput.value || '');

            const response = await fetch(@json(route('admin.site.media.store')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: formData,
            });

            if (!response.ok) {
                mediaFeedback.textContent = 'Falha ao enviar imagem.';
                return;
            }

            const data = await response.json();
            mediaFeedback.textContent = `Imagem enviada (#${data.id}).`;
            mediaFileInput.value = '';
        });
    </script>
@endsection
