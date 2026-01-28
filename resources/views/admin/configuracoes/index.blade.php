@extends('admin.layouts.app')

@section('title', 'Configurações')

@section('content')
    <form action="{{ route('admin.configuracoes.update') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
        @csrf

        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        <div class="content-card">
            <div class="mb-6 flex flex-wrap gap-2">
                <button class="btn btn-ghost tab-button active" type="button" data-tab="geral">Geral</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="seguranca">Seguranca</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="tema">Tema</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="catalogo">Catálogos</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="notificacoes">Notificacoes</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="auto-notificacoes">Notificações Automáticas</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="whatsapp">WhatsApp</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="email">E-mail (SMTP)</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="footer">Footer</button>
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

            <div class="tab-panel hidden" data-tab-panel="seguranca">
                <h3 class="section-title">Seguranca</h3>
                <p class="text-sm text-slate-500">
                    Configure a autenticacao em dois fatores para acesso ao sistema.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-admin.checkbox
                        name="two_factor_ativo"
                        label="Ativar 2FA"
                        :checked="$settings['two_factor_ativo'] ?? false"
                    />
                    <x-admin.select
                        id="two_factor_perfil"
                        name="two_factor_perfil"
                        label="Perfis obrigatorios"
                        :options="[
                            ['value' => 'admin', 'label' => 'Administradores'],
                            ['value' => 'aluno', 'label' => 'Alunos'],
                            ['value' => 'ambos', 'label' => 'Administradores e alunos'],
                        ]"
                        :selected="$settings['two_factor_perfil'] ?? 'admin'"
                        placeholder="Selecione"
                    />
                    <x-admin.select
                        id="two_factor_canal"
                        name="two_factor_canal"
                        label="Canal de envio"
                        :options="[
                            ['value' => 'email', 'label' => 'E-mail'],
                            ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                        ]"
                        :selected="$settings['two_factor_canal'] ?? 'email'"
                        placeholder="Selecione"
                    />
                    <x-admin.input
                        id="two_factor_expiracao_minutos"
                        name="two_factor_expiracao_minutos"
                        label="Expiracao do codigo (minutos)"
                        type="number"
                        :value="$settings['two_factor_expiracao_minutos'] ?? 10"
                    />
                    <x-admin.input
                        id="two_factor_max_tentativas"
                        name="two_factor_max_tentativas"
                        label="Limite de tentativas"
                        type="number"
                        :value="$settings['two_factor_max_tentativas'] ?? 5"
                    />
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    O canal escolhido deve estar ativo em Notificacoes e configurado em WhatsApp/E-mail.
                </p>
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
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.input
                        id="tema_logo"
                        name="tema_logo"
                        label="Logo do site"
                        type="file"
                        hint="Formatos aceitos: JPG, PNG, WEBP. Tamanho máximo: 2MB."
                    />
                    <x-admin.input
                        id="tema_favicon"
                        name="tema_favicon"
                        label="Favicon"
                        type="file"
                        hint="Formatos aceitos: PNG, ICO ou SVG. Tamanho máximo: 1MB."
                    />
                    <x-admin.input
                        id="tema_background_main_imagem"
                        name="tema_background_main_imagem"
                        label="Imagem de fundo institucional"
                        type="file"
                        hint="Formatos aceitos: JPG, PNG, WEBP. Tamanho máximo: 2MB."
                    />
                    <x-admin.input
                        id="tema_background_main_overlay"
                        name="tema_background_main_overlay"
                        label="Overlay do fundo (RGBA)"
                        :value="$settings['tema_background_main_overlay'] ?? 'rgba(255,255,255,0.85)'"
                        hint="Ex: rgba(255,255,255,0.85)"
                    />
                    <x-admin.input
                        id="tema_background_main_posicao"
                        name="tema_background_main_posicao"
                        label="Posição do fundo"
                        :value="$settings['tema_background_main_posicao'] ?? 'center'"
                        hint="Ex: center, top, bottom"
                    />
                    <x-admin.input
                        id="tema_background_main_tamanho"
                        name="tema_background_main_tamanho"
                        label="Tamanho do fundo"
                        :value="$settings['tema_background_main_tamanho'] ?? 'cover'"
                        hint="Ex: cover, contain"
                    />
                </div>
                @if (!empty($settings['tema_logo']) || !empty($settings['tema_favicon']))
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @if (!empty($settings['tema_logo']))
                            <div class="space-y-3">
                                <div class="overflow-hidden rounded-xl border border-[var(--border-color)] bg-white p-4">
                                    <img
                                        src="{{ asset($settings['tema_logo']) }}"
                                        alt="Pré-visualização do logo"
                                        class="h-16 w-auto object-contain"
                                    >
                                </div>
                                <x-admin.checkbox
                                    id="tema_logo_remover"
                                    name="tema_logo_remover"
                                    label="Remover logo atual"
                                    :checked="false"
                                />
                            </div>
                        @endif
                        @if (!empty($settings['tema_favicon']))
                            <div class="space-y-3">
                                <div class="inline-flex items-center gap-3 rounded-xl border border-[var(--border-color)] bg-white p-4">
                                    <img
                                        src="{{ asset($settings['tema_favicon']) }}"
                                        alt="Pré-visualização do favicon"
                                        class="h-10 w-10 object-contain"
                                    >
                                    <span class="text-xs text-slate-500">Pré-visualização</span>
                                </div>
                                <x-admin.checkbox
                                    id="tema_favicon_remover"
                                    name="tema_favicon_remover"
                                    label="Remover favicon atual"
                                    :checked="false"
                                />
                            </div>
                        @endif
                    </div>
                @endif
                @if (!empty($settings['tema_background_main_imagem']))
                    <div class="mt-4 space-y-3">
                        <div class="overflow-hidden rounded-xl border border-[var(--border-color)]">
                            <img
                                src="{{ asset($settings['tema_background_main_imagem']) }}"
                                alt="Pré-visualização do fundo institucional"
                                class="h-36 w-full object-cover"
                            >
                        </div>
                        <x-admin.checkbox
                            id="tema_background_main_imagem_remover"
                            name="tema_background_main_imagem_remover"
                            label="Remover imagem atual"
                            :checked="false"
                        />
                    </div>
                @endif
            </div>

            <div class="tab-panel hidden" data-tab-panel="catalogo">
                <h3 class="section-title">Catálogos</h3>
                <p class="text-sm text-slate-500">
                    Gerencie dados auxiliares usados em cadastros e relatórios.
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a class="btn btn-ghost" href="{{ route('admin.catalogo.categorias.index') }}">
                        Categorias de curso
                    </a>
                    <a class="btn btn-ghost" href="{{ route('admin.catalogo.estados.index') }}">
                        Estados (UF)
                    </a>
                    <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.index') }}">
                        Municípios
                    </a>
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
                    $templatePayload = array_replace_recursive($templateDefaults ?? [], $templatePayload);
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
                                <option value="EVENTO_CRIADO">Evento criado</option>
                                <option value="EVENTO_CANCELADO">Evento cancelado</option>
                                <option value="INSCRICAO_CONFIRMAR">Confirmacao de inscricao</option>
                                <option value="INSCRICAO_CANCELADA">Inscricao cancelada</option>
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
                            <code>@{{horario}}</code>, <code>@{{carga_horaria}}</code>,
                            <code>@{{turno}}</code>, <code>@{{vagas}}</code>,
                            <code>@{{link}}</code>.
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
                    <div id="whatsapp_client_token_field">
                        <x-admin.input
                            id="whatsapp_client_token"
                            name="whatsapp_client_token"
                            label="Client-Token (Z-API)"
                            :value="$settings['whatsapp_client_token'] ?? ''"
                        />
                    </div>
                    <x-admin.input
                        id="whatsapp_base_url"
                        name="whatsapp_base_url"
                        label="Base URL (Z-API)"
                        :value="$settings['whatsapp_base_url'] ?? ''"
                    />
                    <x-admin.input
                        id="whatsapp_instance"
                        name="whatsapp_instance"
                        label="Instance (Z-API)"
                        :value="$settings['whatsapp_instance'] ?? ''"
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

                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-700">Teste de envio</h4>
                    <p class="text-xs text-slate-500">
                        Envie uma mensagem rápida para validar a configuração do provedor.
                    </p>
                    @if (! $whatsappReady)
                        <p class="mt-2 text-xs text-amber-600">
                            Configure o provedor e salve antes de testar o envio.
                        </p>
                    @endif
                    @if (session('whatsapp_test_status'))
                        @php($testStatus = session('whatsapp_test_status'))
                        <div class="mt-3 rounded-xl border p-3 text-xs {{ $testStatus['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                            {{ $testStatus['message'] }}
                        </div>
                    @endif
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-admin.input
                            id="whatsapp_test_numero"
                            name="whatsapp_test_numero"
                            label="Número"
                            placeholder="Ex: 5567999999999"
                            :disabled="! $whatsappReady"
                        />
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label for="whatsapp_test_mensagem" class="text-sm font-semibold text-[var(--content-text)]">Mensagem</label>
                            <textarea
                                id="whatsapp_test_mensagem"
                                name="whatsapp_test_mensagem"
                                rows="3"
                                @if (! $whatsappReady) disabled @endif
                                class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40 @error('whatsapp_test_mensagem') border-red-500 @enderror"
                            >{{ old('whatsapp_test_mensagem') }}</textarea>
                            @error('whatsapp_test_mensagem')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            formaction="{{ route('admin.configuracoes.whatsapp.testar') }}"
                            formmethod="POST"
                            @if (! $whatsappReady) disabled @endif
                        >
                            Testar envio
                        </button>
                    </div>
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
                        <h4 class="text-sm font-semibold text-slate-700">Rate limit diário</h4>
                        <p class="text-xs text-slate-500">Controla o limite de notificações por aluno/tipo/canal ao dia.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="rate_limit_ativo"
                                label="Ativar"
                                :checked="$settings['rate_limit_ativo'] ?? true"
                            />
                            <x-admin.input
                                id="rate_limit_limite_diario"
                                name="rate_limit_limite_diario"
                                label="Limite diário"
                                type="number"
                                :value="$settings['rate_limit_limite_diario'] ?? 2"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Lembrete de curso</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>LEMBRETE_CURSO</code>.</p>
                        <p class="text-xs text-slate-500">Envia um lembrete automático para alunos confirmados alguns dias antes do evento.</p>
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
                        <h4 class="text-sm font-semibold text-slate-700">Evento criado</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>EVENTO_CRIADO</code>.</p>
                        <p class="text-xs text-slate-500">Notifica todos os alunos cadastrados quando um novo evento é criado.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_evento_criado_ativo"
                                label="Ativar"
                                :checked="$settings['auto_evento_criado_ativo'] ?? false"
                            />
                            <x-admin.checkbox
                                name="auto_evento_criado_email"
                                label="Email"
                                :checked="$settings['auto_evento_criado_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_evento_criado_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_evento_criado_whatsapp'] ?? false"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Confirmacao de inscricao</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>INSCRICAO_CONFIRMAR</code>.</p>
                        <p class="text-xs text-slate-500">Envia o link de confirmação para o aluno concluir a inscrição no evento.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_confirmacao_ativo"
                                label="Ativar"
                                :checked="$settings['auto_confirmacao_ativo'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_confirmacao_email"
                                label="Email"
                                :checked="$settings['auto_confirmacao_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_confirmacao_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_confirmacao_whatsapp'] ?? false"
                            />
                            <x-admin.input
                                id="auto_confirmacao_dias_antes"
                                name="auto_confirmacao_dias_antes"
                                label="Dias antes do evento"
                                type="number"
                                :value="$settings['auto_confirmacao_dias_antes'] ?? 0"
                            />
                            <x-admin.input
                                id="auto_confirmacao_tempo_limite"
                                name="auto_confirmacao_tempo_limite"
                                label="Prazo de confirmação (horas)"
                                type="number"
                                :value="$settings['auto_confirmacao_tempo_limite'] ?? 24"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Matrícula confirmada</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>MATRICULA_CONFIRMADA</code>.</p>
                        <p class="text-xs text-slate-500">Confirma ao aluno que sua matrícula foi aprovada no evento.</p>
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
                        <p class="text-xs text-slate-500">Informa alunos da lista de espera quando uma vaga fica disponível.</p>
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
                            <x-admin.select
                                id="auto_vaga_modo"
                                name="auto_vaga_modo"
                                label="Modo de envio"
                                :options="[
                                    ['value' => 'todos', 'label' => 'Notificar todos'],
                                    ['value' => 'sequencial', 'label' => 'Sequencial'],
                                ]"
                                :selected="$settings['auto_vaga_modo'] ?? 'sequencial'"
                            />
                            <x-admin.input
                                id="auto_vaga_tempo_limite"
                                name="auto_vaga_tempo_limite"
                                label="Intervalo entre envios (minutos)"
                                type="number"
                                :value="$settings['auto_vaga_tempo_limite'] ?? 60"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Cancelamento de evento</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>EVENTO_CANCELADO</code>.</p>
                        <p class="text-xs text-slate-500">Avisa inscritos e lista de espera sobre o cancelamento do evento.</p>
                        <div class="mt-3 grid gap-4 md:grid-cols-3">
                            <x-admin.checkbox
                                name="auto_evento_cancelado_ativo"
                                label="Ativar"
                                :checked="$settings['auto_evento_cancelado_ativo'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_evento_cancelado_email"
                                label="Email"
                                :checked="$settings['auto_evento_cancelado_email'] ?? true"
                            />
                            <x-admin.checkbox
                                name="auto_evento_cancelado_whatsapp"
                                label="WhatsApp"
                                :checked="$settings['auto_evento_cancelado_whatsapp'] ?? false"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-700">Curso disponível</h4>
                        <p class="text-xs text-slate-500">Usa o tipo <code>CURSO_DISPONIVEL</code>.</p>
                        <p class="text-xs text-slate-500">Divulga a abertura de inscrições para cursos disponíveis.</p>
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
                            <x-admin.input
                                id="auto_curso_horario_envio"
                                name="auto_curso_horario_envio"
                                label="Horário de envio"
                                type="time"
                                :value="$settings['auto_curso_horario_envio'] ?? '08:00'"
                                hint="Envia apenas quando o scheduler roda exatamente neste horário (HH:MM)."
                            />
                            <x-admin.input
                                id="auto_curso_dias_antes"
                                name="auto_curso_dias_antes"
                                label="Dias antes do evento"
                                type="number"
                                :value="$settings['auto_curso_dias_antes'] ?? 0"
                                hint="Bloqueia envios quando faltarem X dias ou menos para o início do evento."
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

            <div class="tab-panel hidden" data-tab-panel="footer">
                <h3 class="section-title">Footer</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.input
                        id="footer_titulo"
                        name="footer_titulo"
                        label="Titulo"
                        :value="$settings['footer_titulo'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_contato_titulo"
                        name="footer_contato_titulo"
                        label="Titulo de contato"
                        :value="$settings['footer_contato_titulo'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_contato_email"
                        name="footer_contato_email"
                        label="E-mail de contato"
                        type="email"
                        :value="$settings['footer_contato_email'] ?? ''"
                    />
                    <x-admin.input
                        id="footer_contato_telefone"
                        name="footer_contato_telefone"
                        label="Telefone de contato"
                        :value="$settings['footer_contato_telefone'] ?? ''"
                    />
                    <x-admin.input
                        id="footer_endereco_titulo"
                        name="footer_endereco_titulo"
                        label="Titulo de endereco"
                        :value="$settings['footer_endereco_titulo'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_endereco_linha1"
                        name="footer_endereco_linha1"
                        label="Endereco linha 1"
                        :value="$settings['footer_endereco_linha1'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_endereco_linha2"
                        name="footer_endereco_linha2"
                        label="Endereco linha 2"
                        :value="$settings['footer_endereco_linha2'] ?? ''"
                        required
                    />
                </div>
                <div class="mt-4">
                    <x-admin.textarea
                        id="footer_descricao"
                        name="footer_descricao"
                        label="Descricao"
                        rows="3"
                        :value="$settings['footer_descricao'] ?? ''"
                        required
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

            const whatsappProvider = document.getElementById('whatsapp_provedor');
            const whatsappClientTokenField = document.getElementById('whatsapp_client_token_field');

            function toggleWhatsappClientToken() {
                if (!whatsappProvider || !whatsappClientTokenField) {
                    return;
                }

                whatsappClientTokenField.classList.toggle('hidden', whatsappProvider.value !== 'zapi');
            }

            if (whatsappProvider) {
                whatsappProvider.addEventListener('change', toggleWhatsappClientToken);
                toggleWhatsappClientToken();
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
