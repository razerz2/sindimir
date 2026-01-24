@extends('admin.layouts.app')

@section('title', 'Notificações')

@php
    $eventGroups = $eventos->groupBy('curso_id')->map(fn ($group) => $group->map(fn ($event) => [
        'id' => $event->id,
        'label' => "{$event->numero_evento} — " . ($event->data_inicio ? $event->data_inicio->format('d/m/Y') : 'sem data'),
    ]));
@endphp

@section('content')
    <style>
        .section-card {
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
        }
        .input-field {
            width: 100%;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--content-text);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        .input-field:disabled {
            opacity: 0.6;
        }
        .helper-text {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        .pill {
            border-radius: 999px;
            border: 1px solid var(--border-color);
            padding: 6px 10px;
            font-size: 0.85rem;
        }
        .preview-tab.active {
            border-bottom: 2px solid var(--color-primary);
            font-weight: 600;
        }
    </style>

    <section class="space-y-6">
        <header>
            <h1 class="text-3xl font-semibold text-[var(--content-text)]">Notificações guiadas</h1>
            <p class="text-sm opacity-70">Organizamos as etapas para garantir clareza, consistência e segurança no disparo.</p>
        </header>

        <form id="notification-flow" class="space-y-6" method="POST" action="{{ route('admin.notificacoes.store') }}">
            @csrf

            <div class="section-card space-y-3">
                <p class="text-xs uppercase tracking-widest opacity-60">Contexto da Notificação</p>
                <h2 class="text-xl font-semibold text-[var(--content-text)]">Identifique o cenário</h2>
                <p class="text-sm opacity-70">Selecione o curso e, se aplicável, o evento relacionado.</p>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="space-y-2 text-sm text-[var(--content-text)]">
                        Tipo de notificação
                        <select id="notification-type" name="notification_type" class="input-field">
                            <option value="" disabled selected>Selecione o tipo</option>
                            @foreach ($notificationTypes as $type)
                                <option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst(strtolower($type->value))) }}</option>
                            @endforeach
                        </select>
                        <span class="helper-text">Escolha o tipo de comunicação.</span>
                    </label>
                    <label class="space-y-2 text-sm text-[var(--content-text)]">
                        Curso
                        <select id="curso-select" name="curso_id" class="input-field">
                            <option value="" disabled selected>Selecione um curso</option>
                            @foreach ($cursos as $curso)
                                <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                            @endforeach
                        </select>
                        <span class="helper-text">Base para o público e eventos.</span>
                    </label>
                    <label class="space-y-2 text-sm text-[var(--content-text)]">
                        Evento (opcional)
                        <select id="evento-select" name="evento_curso_id" class="input-field" disabled>
                            <option value="">Selecione um evento específico</option>
                        </select>
                        <span class="helper-text">Habilitado quando houver evento no curso.</span>
                    </label>
                </div>
            </div>

            <div class="section-card space-y-3">
                <p class="text-xs uppercase tracking-widest opacity-60">Público-Alvo</p>
                <h2 class="text-xl font-semibold text-[var(--content-text)]">Defina quem receberá</h2>
                <p class="text-sm opacity-70">Defina quais alunos receberão esta notificação.</p>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]">
                        <input type="checkbox" name="segmentos[]" value="all" class="segment-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]">
                        Todos os alunos do curso
                    </label>
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]">
                        <input type="checkbox" name="segmentos[]" value="inscritos" class="segment-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]">
                        Alunos inscritos
                    </label>
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]">
                        <input type="checkbox" name="segmentos[]" value="lista" class="segment-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]">
                        Alunos na lista de espera
                    </label>
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]">
                        <input type="checkbox" name="segmentos[]" value="sem_matricula" class="segment-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]">
                        Apenas alunos sem matrícula ativa
                    </label>
                </div>
                <p id="segment-counter" class="text-sm opacity-70">Total estimado de alunos: 0</p>
            </div>

            <div class="section-card space-y-3">
                <p class="text-xs uppercase tracking-widest opacity-60">Mensagem</p>
                <h2 class="text-xl font-semibold text-[var(--content-text)]">Visualize o conteúdo</h2>
                <p class="text-sm opacity-70">O conteúdo será usado para Email e/ou WhatsApp.</p>
                <textarea id="message-preview" class="input-field min-h-[5rem]" placeholder="Ex: Olá {{'{aluno_nome}'}}, temos vagas disponíveis no curso {{'{curso_nome}'}}. Acesse {{'{link}'}}." readonly></textarea>
                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <label class="flex-1 text-sm text-[var(--content-text)]">
                        Preview para aluno
                        <select id="preview-aluno" class="input-field mt-1">
                            @foreach ($alunos as $aluno)
                                <option value="{{ $aluno->id }}">{{ $aluno->nome_completo }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button id="preview-refresh" type="button" class="btn btn-ghost text-sm">Atualizar preview</button>
                </div>
                <div class="flex gap-3 border-b border-white/15 text-sm">
                    <button type="button" data-preview-tab="email" class="preview-tab active px-3 py-2">Email</button>
                    <button type="button" data-preview-tab="whatsapp" class="preview-tab px-3 py-2 opacity-70">WhatsApp</button>
                </div>
                <div id="preview-email" class="preview-panel space-y-3">
                    <p class="text-xs uppercase tracking-widest opacity-60">Assunto</p>
                    <div id="preview-email-subject" class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] p-3 text-sm text-[var(--content-text)] min-h-[3rem]">
                        Preencha o contexto para carregar o assunto.
                    </div>
                    <p class="text-xs uppercase tracking-widest opacity-60">Corpo</p>
                    <div id="preview-email-body" class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] p-3 text-sm text-[var(--content-text)] min-h-[4rem] whitespace-pre-line">
                        O corpo será exibido aqui.
                    </div>
                </div>
                <div id="preview-whatsapp" class="preview-panel hidden space-y-3">
                    <p class="text-xs uppercase tracking-widest opacity-60">Texto</p>
                    <div id="preview-whatsapp-text" class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] p-3 text-sm text-[var(--content-text)] min-h-[3rem] whitespace-pre-line">
                        O texto será exibido aqui.
                    </div>
                </div>
                <p class="text-xs opacity-70">Esta é uma prévia. O envio será feito por fila.</p>
            </div>

            <div class="section-card space-y-3">
                <p class="text-xs uppercase tracking-widest opacity-60">Canais de Envio</p>
                <h2 class="text-xl font-semibold text-[var(--content-text)]">Defina os canais</h2>
                <p class="text-sm opacity-70">Selecione por onde os alunos serão notificados.</p>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]" title="Email ativo nas configurações">
                        <input id="canal-email" name="canal_email" {{ $settings['email'] ? '' : 'disabled' }} class="channel-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]" type="checkbox" checked>
                        Email
                    </label>
                    @unless ($settings['email'])
                        <div class="rounded-xl border border-orange-500/40 bg-orange-500/10 px-3 py-2 text-xs text-orange-200">Email está desativado nas configurações.</div>
                    @endunless
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]" title="{{ $settings['whatsapp'] ? '' : 'WhatsApp inativo' }}">
                        <input id="canal-whatsapp" name="canal_whatsapp" {{ $settings['whatsapp'] ? '' : 'disabled' }} class="channel-checkbox h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]" type="checkbox" checked>
                        WhatsApp
                    </label>
                    @unless ($settings['whatsapp'])
                        <div class="rounded-xl border border-orange-500/40 bg-orange-500/10 px-3 py-2 text-xs text-orange-200">
                            WhatsApp está desativado nas configurações. Ative em Configurações → Notificações.
                        </div>
                    @endunless
                </div>
                <div id="channel-warning" class="hidden rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs text-red-200">
                    Selecione ao menos um canal para continuar.
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr,320px]">
                <div class="section-card space-y-4">
                    <p class="text-xs uppercase tracking-widest opacity-60">Resumo do Envio</p>
                    <h2 class="text-xl font-semibold text-[var(--content-text)]">Revise antes de confirmar</h2>
                    <p class="text-sm opacity-70">Revise as informações antes de confirmar.</p>
                    <ul class="space-y-2 text-sm text-[var(--content-text)]">
                        <li>Curso: <span id="summary-curso">—</span></li>
                        <li>Evento: <span id="summary-evento">Nenhum evento</span></li>
                        <li>Público-alvo: <span id="summary-publico">—</span></li>
                        <li>Total estimado: <span id="summary-total">0</span> alunos</li>
                        <li>Canais ativos: <span id="summary-canais">—</span></li>
                    </ul>
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
                        Esta notificação será enviada para <strong id="summary-total-inline">0</strong> alunos via
                        <strong id="summary-channel-inline">—</strong>.
                    </div>
                </div>
                <div class="section-card space-y-4">
                    <p class="text-xs uppercase tracking-widest opacity-60">Confirmação</p>
                    <h2 class="text-xl font-semibold text-[var(--content-text)]">Pronto para disparar</h2>
                    <p class="text-sm opacity-70">Confirme para enfileirar via fila.</p>
                    <label class="flex items-center gap-3 text-sm text-[var(--content-text)]">
                        <input id="confirm-checkbox" class="h-4 w-4 rounded border border-[var(--border-color)] bg-[var(--card-bg)] text-[var(--color-primary)]" type="checkbox">
                        Confirmo que revisei as informações e desejo enviar esta notificação
                    </label>
                    <button id="submit-notifications" class="btn btn-primary w-full" type="submit" disabled>
                        <span class="submit-label">Enfileirar notificações</span>
                        <span class="submit-loading hidden">Enviando...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const events = @json($eventGroups);
            const courseSelect = document.getElementById('curso-select');
            const eventSelect = document.getElementById('evento-select');
            const typeSelect = document.getElementById('notification-type');
            const segments = document.querySelectorAll('.segment-checkbox');
            const counter = document.getElementById('segment-counter');
            const previewAluno = document.getElementById('preview-aluno');
            const previewBtn = document.getElementById('preview-refresh');
            const previewEmailSubject = document.getElementById('preview-email-subject');
            const previewEmailBody = document.getElementById('preview-email-body');
            const previewWhatsApp = document.getElementById('preview-whatsapp-text');
            const previewTabs = document.querySelectorAll('.preview-tab');
            const previewPanels = document.querySelectorAll('.preview-panel');
            const channelCheckboxes = document.querySelectorAll('.channel-checkbox');
            const channelWarning = document.getElementById('channel-warning');
            const confirmCheckbox = document.getElementById('confirm-checkbox');
            const submitButton = document.getElementById('submit-notifications');
            const summaryCurso = document.getElementById('summary-curso');
            const summaryEvento = document.getElementById('summary-evento');
            const summaryPublico = document.getElementById('summary-publico');
            const summaryTotal = document.getElementById('summary-total');
            const summaryCanais = document.getElementById('summary-canais');
            const form = document.getElementById('notification-flow');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const totalStudents = {{ $alunos->count() }};
            const segmentEstimates = {
                all: totalStudents,
                inscritos: Math.floor(totalStudents * 0.6),
                lista: Math.floor(totalStudents * 0.25),
                sem_matricula: Math.max(0, totalStudents - Math.floor(totalStudents * 0.7)),
            };
            const previewUrl = "{{ route('admin.notificacoes.preview') }}";
            let currentEstimate = 0;

            function updateEventSelect() {
                const selectedCourse = courseSelect.value;
                eventSelect.innerHTML = '<option value="">Selecione um evento específico</option>';
                eventSelect.disabled = !selectedCourse || !events[selectedCourse];
                if (events[selectedCourse]) {
                    eventSelect.disabled = false;
                    eventSelect.classList.remove('text-white/60');
                    eventSelect.classList.add('text-white');
                    events[selectedCourse].forEach(event => {
                        const option = document.createElement('option');
                        option.value = event.id;
                        option.textContent = event.label;
                        eventSelect.appendChild(option);
                    });
                } else {
                    eventSelect.classList.add('text-white/60');
                    eventSelect.classList.remove('text-white');
                }
            }

            function updateSegmentCounter() {
                const selected = Array.from(segments)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);
                const total = selected.length
                    ? selected.reduce((sum, key) => sum + (segmentEstimates[key] ?? 0), 0)
                    : 0;
                currentEstimate = total;
                counter.textContent = `Total estimado de alunos: ${total || '0'}`;
                summaryTotal.textContent = total || '0';
                document.getElementById('summary-total-inline').textContent = total || '0';
                summaryPublico.textContent = selected.length ? selected.map(key => {
                    switch (key) {
                        case 'all': return 'Todos os alunos';
                        case 'inscritos': return 'Alunos inscritos';
                        case 'lista': return 'Lista de espera';
                        case 'sem_matricula': return 'Sem matrícula ativa';
                        default: return key;
                    }
                }).join(', ') : '—';
            }

            function updateSummary() {
                summaryCurso.textContent = courseSelect.selectedOptions[0]?.textContent ?? '—';
                summaryEvento.textContent = eventSelect.selectedOptions[0]?.textContent ?? 'Nenhum evento';
                const channels = Array.from(channelCheckboxes)
                    .filter(checkbox => checkbox.checked && !checkbox.disabled)
                    .map(checkbox => checkbox.id === 'canal-email' ? 'Email' : 'WhatsApp');
                summaryCanais.textContent = channels.length ? channels.join(' / ') : 'Nenhum canal selecionado';
                document.getElementById('summary-channel-inline').textContent = channels.length ? channels.join(' / ') : 'nenhum canal';
            }

            function toggleSubmitState() {
                const hasChannel = Array.from(channelCheckboxes).some(checkbox => checkbox.checked && !checkbox.disabled);
                channelWarning.classList.toggle('hidden', hasChannel);
                submitButton.disabled = !(hasChannel && confirmCheckbox.checked);
            }

            function setPreview(tab) {
                previewTabs.forEach(button => {
                    button.classList.toggle('active', button.dataset.previewTab === tab);
                    button.classList.toggle('opacity-100', button.dataset.previewTab === tab);
                    button.classList.toggle('opacity-60', button.dataset.previewTab !== tab);
                });
                previewPanels.forEach(panel => {
                    panel.classList.toggle('hidden', panel.id !== `preview-${tab}`);
                });
            }

            async function fetchPreview() {
                const type = typeSelect.value;
                const course = courseSelect.value;
                const aluno = previewAluno?.value ?? null;

                if (!type || !course || !aluno) {
                    previewEmailSubject.textContent = 'Selecione o tipo, curso e aluno para gerar o preview.';
                    previewEmailBody.textContent = 'Selecione o tipo, curso e aluno para gerar o preview.';
                    previewWhatsApp.textContent = 'Selecione o tipo, curso e aluno para gerar o preview.';
                    return;
                }

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            aluno_id: aluno,
                            curso_id: course,
                            notification_type: type,
                        }),
                    });
                    const data = await response.json();
                    const emailAtivo = !document.getElementById('canal-email').disabled;
                    const whatsappAtivo = !document.getElementById('canal-whatsapp').disabled;
                    if (emailAtivo) {
                        previewEmailSubject.textContent = data.assunto_email;
                        previewEmailBody.textContent = data.corpo_email;
                    } else {
                        previewEmailSubject.textContent = 'Email desativado nas configurações.';
                        previewEmailBody.textContent = 'Ative o canal para visualizar o conteúdo.';
                    }
                    if (whatsappAtivo) {
                        previewWhatsApp.textContent = data.texto_whatsapp;
                    } else {
                        previewWhatsApp.textContent = 'WhatsApp desativado nas configurações.';
                    }
                    document.getElementById('message-preview').value = data.corpo_email;
                } catch (error) {
                    previewEmailSubject.textContent = 'Não foi possível carregar o preview.';
                    previewEmailBody.textContent = 'Erro ao gerar o preview.';
                    previewWhatsApp.textContent = 'Erro ao gerar o preview.';
                }
            }

            form.addEventListener('submit', () => {
                submitButton.querySelector('.submit-label').classList.add('hidden');
                submitButton.querySelector('.submit-loading').classList.remove('hidden');
            });

            courseSelect.addEventListener('change', () => {
                updateEventSelect();
                updateSummary();
                fetchPreview();
            });
            eventSelect.addEventListener('change', updateSummary);
            typeSelect.addEventListener('change', fetchPreview);
            segments.forEach(checkbox => checkbox.addEventListener('change', () => {
                updateSegmentCounter();
                updateSummary();
            }));
            channelCheckboxes.forEach(checkbox => checkbox.addEventListener('change', () => {
                toggleSubmitState();
                updateSummary();
            }));
            confirmCheckbox.addEventListener('change', toggleSubmitState);
            previewTabs.forEach(button => button.addEventListener('click', () => setPreview(button.dataset.previewTab)));

            updateEventSelect();
            updateSegmentCounter();
            updateSummary();
            toggleSubmitState();
            setPreview('email');
        });
    </script>
@endsection
