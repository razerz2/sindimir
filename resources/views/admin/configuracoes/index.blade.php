@extends('admin.layouts.app')

@section('title', 'Configurações')

@section('content')
    <form action="{{ route('admin.configuracoes.update') }}" method="POST" class="space-y-6">
        @csrf

        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        <div class="content-card">
            <div class="mb-6 flex flex-wrap gap-2">
                <button class="btn btn-ghost tab-button active" type="button" data-tab="geral">Geral</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="tema">Tema</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="notificacoes">Notificacoes</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="auto-notificacoes">Notificações Automáticas</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="whatsapp">WhatsApp</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="email">E-mail (SMTP)</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="auditoria">Auditoria</button>
            </div>

            <div class="tab-panel" data-tab-panel="geral">
                <h3 class="section-title">Configuracoes gerais</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-admin.input
                        id="sistema_nome"
                        name="sistema_nome"
                        label="Nome do sistema"
                        :value="$settings['sistema_nome'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="sistema_email"
                        name="sistema_email"
                        label="E-mail padrao"
                        type="email"
                        :value="$settings['sistema_email'] ?? ''"
                    />
                    <x-admin.checkbox
                        name="sistema_ativo"
                        label="Sistema ativo"
                        :checked="$settings['sistema_ativo'] ?? true"
                    />
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="tema">
                <h3 class="section-title">Tema do sistema</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-admin.input
                        id="tema_cor_primaria"
                        name="tema_cor_primaria"
                        label="Cor primaria"
                        :value="$theme['cor_primaria'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="tema_cor_secundaria"
                        name="tema_cor_secundaria"
                        label="Cor secundaria"
                        :value="$theme['cor_secundaria'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="tema_cor_destaque"
                        name="tema_cor_destaque"
                        label="Cor de destaque"
                        :value="$settings['tema_cor_destaque'] ?? ''"
                    />
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="notificacoes">
                <h3 class="section-title">Notificacoes</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.checkbox
                        name="notificacao_email_ativo"
                        label="Ativar envio de e-mail"
                        :checked="$settings['notificacao_email_ativo'] ?? true"
                    />
                    <x-admin.checkbox
                        name="notificacao_whatsapp_ativo"
                        label="Ativar envio de WhatsApp"
                        :checked="$settings['notificacao_whatsapp_ativo'] ?? false"
                    />
                </div>
                @php
                    $types = \App\Enums\NotificationType::cases();
                    $templatePayload = $templates->mapWithKeys(function ($group, $typeKey) {
                        $email = $group->firstWhere('canal', 'email');
                        $whatsapp = $group->firstWhere('canal', 'whatsapp');

                        return [
                            $typeKey => [
                                'email' => [
                                    'ativo' => $email?->ativo ?? true,
                                    'assunto' => $email?->assunto ?? '',
                                    'conteudo' => $email?->conteudo ?? '',
                                ],
                                'whatsapp' => [
                                    'ativo' => $whatsapp?->ativo ?? true,
                                    'conteudo' => $whatsapp?->conteudo ?? '',
                                ],
                            ],
                        ];
                    })->toArray();
                @endphp

                <div class="mt-6 space-y-4" x-data="templateEditor({{ json_encode($templatePayload) }})">
                    <h4 class="section-title">Templates por tipo</h4>
                    <p class="text-sm text-slate-500">
                        Selecione um tipo para editar o template correspondente.
                    </p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <label for="template_type" class="text-sm font-semibold text-[var(--content-text)]">Tipo de notificação</label>
                            <select
                                id="template_type"
                                name="template_type"
                                class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                x-model="selectedType"
                                x-on:change="loadType()"
                            >
                                <option value="">Selecione um tipo</option>
                                <option value="CURSO_DISPONIVEL">Curso disponível</option>
                                <option value="VAGA_ABERTA">Vaga aberta</option>
                                <option value="LEMBRETE_CURSO">Lembrete de curso</option>
                                <option value="MATRICULA_CONFIRMADA">Matrícula confirmada</option>
                                <option value="LISTA_ESPERA_CHAMADA">Lista de espera chamada</option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-200 bg-white p-4 text-sm text-slate-500" x-show="selectedType && !hasTemplate">
                        Nenhum template cadastrado para este tipo.
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4" x-show="selectedType">
                        <div class="mb-4 flex items-center justify-between">
                            <h5 class="text-sm font-semibold text-slate-700">Editar template</h5>
                            <span class="text-xs text-slate-400" x-show="loading">Carregando...</span>
                        </div>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-600">Email</span>
                                    <label class="text-xs text-slate-500">
                                        <input type="checkbox" x-model="email.ativo" x-bind:name="`templates[${selectedType}][email][ativo]`" value="1">
                                        Ativo
                                    </label>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-[var(--content-text)]">Assunto</label>
                                    <input
                                        type="text"
                                        x-model="email.assunto"
                                        x-bind:name="`templates[${selectedType}][email][assunto]`"
                                        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                    >
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-[var(--content-text)]">Conteúdo (Email)</label>
                                    <textarea
                                        rows="4"
                                        x-model="email.conteudo"
                                        x-bind:name="`templates[${selectedType}][email][conteudo]`"
                                        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-600">WhatsApp</span>
                                    <label class="text-xs text-slate-500">
                                        <input type="checkbox" x-model="whatsapp.ativo" x-bind:name="`templates[${selectedType}][whatsapp][ativo]`" value="1">
                                        Ativo
                                    </label>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold text-[var(--content-text)]">Conteúdo (WhatsApp)</label>
                                    <textarea
                                        rows="4"
                                        x-model="whatsapp.conteudo"
                                        x-bind:name="`templates[${selectedType}][whatsapp][conteudo]`"
                                        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Variáveis disponíveis: <code>@{{aluno_nome}}</code>,
                            <code>@{{curso_nome}}</code>, <code>@{{datas}}</code>,
                            <code>@{{vagas}}</code>, <code>@{{link}}</code>.
                        </p>
                        <div class="mt-4 flex items-center justify-between">
                            @if (session('status'))
                                <span class="text-xs text-emerald-600">{{ session('status') }}</span>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                Salvar template deste tipo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="whatsapp">
                <h3 class="section-title">WhatsApp</h3>
                <div class="mb-4 flex items-center gap-3">
                    <span class="badge">{{ $whatsappStatus }}</span>
                    <p class="text-sm text-slate-500">Status da integracao.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.select
                        id="whatsapp_provedor"
                        name="whatsapp_provedor"
                        label="Provedor"
                        :options="[
                            ['value' => 'meta', 'label' => 'Meta (Cloud API)'],
                            ['value' => 'zapi', 'label' => 'Z-API'],
                        ]"
                        :selected="$settings['whatsapp_provedor'] ?? null"
                        placeholder="Selecione"
                    />
                    <x-admin.input
                        id="whatsapp_token"
                        name="whatsapp_token"
                        label="Token / API Key"
                        :value="$settings['whatsapp_token'] ?? ''"
                    />
                    <x-admin.input
                        id="whatsapp_phone_number_id"
                        name="whatsapp_phone_number_id"
                        label="Phone Number ID (Meta)"
                        :value="$settings['whatsapp_phone_number_id'] ?? ''"
                    />
                    <x-admin.input
                        id="whatsapp_webhook_url"
                        name="whatsapp_webhook_url"
                        label="Webhook URL"
                        :value="$settings['whatsapp_webhook_url'] ?? ''"
                    />
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="auto-notificacoes">
                <h3 class="section-title">Notificações Automáticas</h3>
                <p class="text-sm text-slate-500">
                    Notificações automáticas são disparadas por processos do sistema (scheduler/jobs).
                    As notificações manuais são enviadas na tela de Notificações.
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    O template usado em cada automação é definido em Templates por Tipo (seleção acima).
                </p>

                <div class="mt-6 space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Lembrete de curso</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>LEMBRETE_CURSO</code>.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_lembrete_ativo"
                                label="Ativar"
                                :checked="$settings['auto_lembrete_ativo'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_lembrete_email"
                                label="Email"
                                :checked="$settings['auto_lembrete_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_lembrete_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_lembrete_whatsapp'] ?? false"
                            />
                            <x-admin.input
                                id="auto_lembrete_dias_antes"
                                name="auto_lembrete_dias_antes"
                                label="Dias antes"
                                type="number"
                                :value="$settings['auto_lembrete_dias_antes'] ?? 2"
                            />
                            <x-admin.input
                                id="auto_lembrete_horario"
                                name="auto_lembrete_horario"
                                label="Horário de envio"
                                :value="$settings['auto_lembrete_horario'] ?? '08:00'"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Matrícula confirmada</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>MATRICULA_CONFIRMADA</code>.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_matricula_ativo"
                                label="Ativar"
                                :checked="$settings['auto_matricula_ativo'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_matricula_email"
                                label="Email"
                                :checked="$settings['auto_matricula_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_matricula_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_matricula_whatsapp'] ?? false"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Vaga aberta (lista de espera)</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>LISTA_ESPERA_CHAMADA</code>.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_vaga_ativo"
                                label="Ativar"
                                :checked="$settings['auto_vaga_ativo'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_vaga_email"
                                label="Email"
                                :checked="$settings['auto_vaga_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_vaga_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_vaga_whatsapp'] ?? false"
                            />
                            <x-admin.input
                                id="auto_vaga_tempo_limite"
                                name="auto_vaga_tempo_limite"
                                label="Tempo limite (horas)"
                                type="number"
                                :value="$settings['auto_vaga_tempo_limite'] ?? 24"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Curso disponível</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>CURSO_DISPONIVEL</code>.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_curso_ativo"
                                label="Ativar"
                                :checked="$settings['auto_curso_ativo'] ?? false"
                            />
                            <x-admin.checkbox
                                name="auto_curso_email"
                                label="Email"
                                :checked="$settings['auto_curso_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_curso_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_curso_whatsapp'] ?? false"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="email">
                <h3 class="section-title">E-mail (SMTP)</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.input
                        id="smtp_host"
                        name="smtp_host"
                        label="Host"
                        :value="$settings['smtp_host'] ?? ''"
                    />
                    <x-admin.input
                        id="smtp_port"
                        name="smtp_port"
                        label="Porta"
                        type="number"
                        :value="$settings['smtp_port'] ?? ''"
                    />
                    <x-admin.input
                        id="smtp_username"
                        name="smtp_username"
                        label="Usuario"
                        :value="$settings['smtp_username'] ?? ''"
                    />
                    <x-admin.input
                        id="smtp_password"
                        name="smtp_password"
                        label="Senha"
                        type="password"
                        :value="$settings['smtp_password'] ?? ''"
                    />
                    <x-admin.select
                        id="smtp_encryption"
                        name="smtp_encryption"
                        label="Criptografia"
                        :options="[
                            ['value' => 'tls', 'label' => 'TLS'],
                            ['value' => 'ssl', 'label' => 'SSL'],
                        ]"
                        :selected="$settings['smtp_encryption'] ?? null"
                        placeholder="Selecione"
                    />
                    <x-admin.input
                        id="smtp_from_email"
                        name="smtp_from_email"
                        label="E-mail remetente"
                        type="email"
                        :value="$settings['smtp_from_email'] ?? ''"
                    />
                    <x-admin.input
                        id="smtp_from_name"
                        name="smtp_from_name"
                        label="Nome do remetente"
                        :value="$settings['smtp_from_name'] ?? ''"
                    />
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="auditoria">
                <h3 class="section-title">Auditoria</h3>
                <div class="flex items-center gap-3">
                    <span class="badge">Ativo</span>
                    <p class="text-sm text-slate-500">
                        Alteracoes registradas automaticamente via AuditoriaObserver.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button class="btn btn-primary" type="submit">Salvar configuracoes</button>
        </div>
    </form>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function templateEditor(templates) {
            return {
                selectedType: '',
                loading: false,
                hasTemplate: false,
                email: { ativo: true, assunto: '', conteudo: '' },
                whatsapp: { ativo: true, conteudo: '' },
                loadType() {
                    if (!this.selectedType) {
                        this.hasTemplate = false;
                        return;
                    }
                    this.loading = true;
                    const data = templates[this.selectedType] || null;
                    setTimeout(() => {
                        this.hasTemplate = !!data;
                        this.email = {
                            ativo: data?.email?.ativo ?? true,
                            assunto: data?.email?.assunto ?? '',
                            conteudo: data?.email?.conteudo ?? '',
                        };
                        this.whatsapp = {
                            ativo: data?.whatsapp?.ativo ?? true,
                            conteudo: data?.whatsapp?.conteudo ?? '',
                        };
                        this.loading = false;
                    }, 150);
                },
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const buttons = Array.from(document.querySelectorAll('.tab-button'));
            const panels = Array.from(document.querySelectorAll('.tab-panel'));

            function activateTab(tab) {
                buttons.forEach((button) => {
                    const isActive = button.dataset.tab === tab;
                    button.classList.toggle('btn-primary', isActive);
                    button.classList.toggle('btn-ghost', !isActive);
                    button.classList.toggle('active', isActive);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);
                });
            }

            buttons.forEach((button) => {
                button.addEventListener('click', () => activateTab(button.dataset.tab));
            });

            if (buttons.length) {
                activateTab(buttons[0].dataset.tab);
            }
        });
    </script>
@endsection
