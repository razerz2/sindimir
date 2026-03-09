@extends('admin.layouts.app')

@section('title', 'Configurações')

@section('subtitle')
    Gerencie parâmetros do sistema, integrações e preferências administrativas.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Configurações', 'icon' => 'settings', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.configuracoes.update') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
        @csrf

        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        <div class="content-card">
            <div class="mb-6 flex flex-wrap gap-2">
                <button class="btn btn-ghost tab-button active" type="button" data-tab="geral">Geral</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="seguranca">Segurança</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="tema">Tema</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="catalogo">Catálogos</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="notificacoes">Notificações</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="auto-notificacoes">Notificações Automáticas</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="whatsapp">WhatsApp</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="bot">Bot</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="google-contatos">Google Contatos</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="email">E-mail (SMTP)</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="footer">Footer</button>
                <button class="btn btn-ghost tab-button" type="button" data-tab="auditoria">Auditoria</button>
            </div>

            <div class="tab-panel" data-tab-panel="geral">
                <h3 class="section-title">Configurações gerais</h3>
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
                        label="E-mail padrão"
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
                <h3 class="section-title">Segurança</h3>
                <p class="text-sm text-slate-500">
                    Configure a autenticação em dois fatores para acesso ao sistema.
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
                        label="Perfis obrigatórios"
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
                        label="Expiração do código (minutos)"
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
                    O canal escolhido deve estar ativo em Notificações e configurado em WhatsApp/E-mail.
                </p>
            </div>

            <div class="tab-panel hidden" data-tab-panel="tema">
                <h3 class="section-title">Tema do sistema</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-admin.input
                        id="tema_cor_primaria"
                        name="tema_cor_primaria"
                        label="Cor primária"
                        :value="$theme['cor_primaria'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="tema_cor_secundaria"
                        name="tema_cor_secundaria"
                        label="Cor secundária"
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
                    <x-admin.action as="a" variant="ghost" icon="settings" href="{{ route('admin.catalogo.categorias.index') }}">
                        Categorias de curso
                    </x-admin.action>
                    <x-admin.action as="a" variant="ghost" icon="settings" href="{{ route('admin.catalogo.estados.index') }}">
                        Estados (UF)
                    </x-admin.action>
                    <x-admin.action as="a" variant="ghost" icon="settings" href="{{ route('admin.catalogo.municipios.index') }}">
                        Municípios
                    </x-admin.action>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="notificacoes">
                <h3 class="section-title">Notificações</h3>
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
                    <x-admin.select
                        id="notificacao_destinatarios"
                        name="notificacao_destinatarios"
                        label="Destinatários das notificações"
                        :options="[
                            ['value' => 'alunos', 'label' => 'Somente alunos'],
                            ['value' => 'contatos_externos', 'label' => 'Somente contatos externos'],
                            ['value' => 'ambos', 'label' => 'Alunos e contatos externos'],
                        ]"
                        :selected="$settings['notificacao_destinatarios'] ?? 'alunos'"
                        placeholder="Selecione"
                    />
                </div>
                @php
                    $existingTemplatePayload = $templates->mapWithKeys(function ($group, $typeKey) {
                        $email = $group->firstWhere('canal', 'email');
                        $whatsapp = $group->firstWhere('canal', 'whatsapp');

                        return [
                            $typeKey => [
                                'email' => $email
                                    ? [
                                        'ativo' => $email->ativo,
                                        'assunto' => $email->assunto ?? '',
                                        'conteudo' => $email->conteudo ?? '',
                                    ]
                                    : null,
                                'whatsapp' => $whatsapp
                                    ? [
                                        'ativo' => $whatsapp->ativo,
                                        'conteudo' => $whatsapp->conteudo ?? '',
                                    ]
                                    : null,
                            ],
                        ];
                    })->toArray();
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
                    $defaultTemplateType = $templateTypeOptions[0]['value'] ?? '';
                @endphp

                <div
                    class="mt-6 space-y-4"
                    x-data="templateEditor(@js($templatePayload), @js($existingTemplatePayload), @js($defaultTemplateType), @js($templateVariables ?? []))"
                >
                    <h4 class="section-title">Templates por tipo</h4>
                    <p class="text-sm text-slate-500">
                        Selecione o canal e o tipo para editar o template correspondente.
                    </p>

                    <input type="hidden" name="template_type" x-model="selectedType">
                    <input type="hidden" name="template_channel" x-model="selectedChannel">

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <label for="template_channel" class="text-sm font-semibold text-[var(--content-text)]">Canal</label>
                                    <select
                                        id="template_channel"
                                        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                        x-model="selectedChannel"
                                        x-on:change="handleSelectionChange()"
                                    >
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="email">E-mail</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="template_type" class="text-sm font-semibold text-[var(--content-text)]">Tipo de notificação</label>
                                    <select
                                        id="template_type"
                                        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                        x-model="selectedType"
                                        x-on:change="handleSelectionChange()"
                                    >
                                        @foreach ($templateTypeOptions as $typeOption)
                                            <option value="{{ $typeOption['value'] }}">{{ $typeOption['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-4 text-sm text-slate-500" x-show="selectedType && !hasTemplate">
                                Sem template cadastrado ainda.
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4" x-show="selectedType">
                                <div class="mb-4 flex items-center justify-between">
                                    <h5 class="text-sm font-semibold text-slate-700">Editor do template</h5>
                                    <span class="text-xs text-slate-400" x-show="loading">Carregando...</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-slate-600">Ativo</span>
                                        <label class="text-xs text-slate-500">
                                            <input type="hidden" name="template_active" value="0">
                                            <input type="checkbox" x-model="editor.active" name="template_active" value="1">
                                            Ativo
                                        </label>
                                    </div>

                                    <div class="space-y-3" x-show="selectedChannel === 'email'">
                                        <div class="flex flex-col gap-2">
                                            <label class="text-sm font-semibold text-[var(--content-text)]">Assunto</label>
                                            <input
                                                type="text"
                                                name="template_subject"
                                                x-model="editor.subject"
                                                x-on:focus="setFocusedField($event.target)"
                                                class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                            >
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-semibold text-[var(--content-text)]" x-text="selectedChannel === 'email' ? 'Conteúdo (E-mail)' : 'Conteúdo (WhatsApp)'"></label>
                                        <textarea
                                            rows="6"
                                            name="template_content"
                                            x-model="editor.content"
                                            x-on:focus="setFocusedField($event.target)"
                                            class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                                        ></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-xs text-emerald-600" x-show="copyMessage" x-text="copyMessage"></span>
                                    <x-admin.action variant="primary" icon="check" type="submit" name="submit_context" value="template">
                                        Salvar template deste tipo
                                    </x-admin.action>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <h5 class="mb-3 text-sm font-semibold text-slate-700">Variáveis disponíveis</h5>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <th class="px-2 py-2">Variável</th>
                                            <th class="px-2 py-2">Descrição</th>
                                            <th class="px-2 py-2 text-right">Copiar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in variables" :key="item.variable">
                                            <tr class="border-b border-slate-100">
                                                <td class="px-2 py-2"><code x-text="item.variable"></code></td>
                                                <td class="px-2 py-2 text-slate-600" x-text="item.description"></td>
                                                <td class="px-2 py-2 text-right">
                                                    <button type="button" class="btn btn-ghost text-xs" x-on:click="copyVariable(item.variable)">
                                                        Copiar
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-panel hidden" data-tab-panel="whatsapp">
                <h3 class="section-title">WhatsApp</h3>
                <div class="mb-4 flex items-center gap-3">
                    <span class="badge">{{ $whatsappStatus }}</span>
                    <p class="text-sm text-slate-500">Status da integração.</p>
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
                        <x-admin.action
                            variant="primary"
                            icon="check"
                            type="submit"
                            formaction="{{ route('admin.configuracoes.whatsapp.testar') }}"
                            formmethod="POST"
                            :disabled="! $whatsappReady"
                        >
                            Testar envio
                        </x-admin.action>
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="bot">
                <h3 class="section-title">Bot</h3>
                <p class="text-sm text-slate-500">
                    Configure o bot de atendimento via WhatsApp para menu de cursos e cancelamentos.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-admin.checkbox
                        name="bot_enabled"
                        label="Ativar bot"
                        :checked="$settings['bot_enabled'] ?? false"
                    />
                    <x-admin.select
                        id="bot_provider"
                        name="bot_provider"
                        label="Provedor ativo"
                        :options="[
                            ['value' => 'meta', 'label' => 'Meta (Cloud API)'],
                            ['value' => 'zapi', 'label' => 'Z-API'],
                        ]"
                        :selected="$settings['bot_provider'] ?? 'meta'"
                    />
                    <x-admin.select
                        id="bot_credentials_mode"
                        name="bot_credentials_mode"
                        label="Modo de credenciais"
                        :options="[
                            ['value' => 'inherit_notifications', 'label' => 'Usar credenciais das notificações'],
                            ['value' => 'custom', 'label' => 'Usar credenciais próprias do bot'],
                        ]"
                        :selected="$settings['bot_credentials_mode'] ?? 'inherit_notifications'"
                    />
                    <x-admin.input
                        id="bot_session_timeout_minutes"
                        name="bot_session_timeout_minutes"
                        label="Timeout de sessão (min)"
                        type="number"
                        :value="$settings['bot_session_timeout_minutes'] ?? 15"
                    />
                    <x-admin.input
                        id="bot_reset_keyword"
                        name="bot_reset_keyword"
                        label="Palavra de reset"
                        :value="$settings['bot_reset_keyword'] ?? 'menu'"
                        hint='Ex: "menu"'
                    />
                </div>

                <div id="bot-credentials-custom" class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-700">Credenciais próprias do bot</h4>
                    <p class="mt-1 text-xs text-slate-500">
                        Ative o modo "Usar credenciais próprias do bot" para preencher estes campos.
                    </p>

                    <div id="bot-credentials-meta" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-admin.input
                            id="bot_meta_phone_number_id"
                            name="bot_meta_phone_number_id"
                            label="Meta Phone Number ID (bot)"
                            :value="$settings['bot_meta_phone_number_id'] ?? ''"
                        />
                        <x-admin.input
                            id="bot_meta_access_token"
                            name="bot_meta_access_token"
                            label="Meta Access Token (bot)"
                            :value="$settings['bot_meta_access_token'] ?? ''"
                        />
                    </div>

                    <div id="bot-credentials-zapi" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-admin.input
                            id="bot_zapi_instance_id"
                            name="bot_zapi_instance_id"
                            label="Z-API Instance ID (bot)"
                            :value="$settings['bot_zapi_instance_id'] ?? ''"
                        />
                        <x-admin.input
                            id="bot_zapi_token"
                            name="bot_zapi_token"
                            label="Z-API Token (bot)"
                            :value="$settings['bot_zapi_token'] ?? ''"
                        />
                        <x-admin.input
                            id="bot_zapi_client_token"
                            name="bot_zapi_client_token"
                            label="Z-API Client Token (bot)"
                            :value="$settings['bot_zapi_client_token'] ?? ''"
                        />
                        <x-admin.input
                            id="bot_zapi_base_url"
                            name="bot_zapi_base_url"
                            label="Z-API Base URL (bot)"
                            :value="$settings['bot_zapi_base_url'] ?? ''"
                        />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin.textarea
                        id="bot_welcome_message"
                        name="bot_welcome_message"
                        label="Mensagem de boas-vindas"
                        rows="3"
                        :value="$settings['bot_welcome_message'] ?? ''"
                    />
                    <x-admin.textarea
                        id="bot_fallback_message"
                        name="bot_fallback_message"
                        label="Mensagem de fallback"
                        rows="3"
                        :value="$settings['bot_fallback_message'] ?? ''"
                    />
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-admin.textarea
                            id="bot_entry_keywords"
                            name="bot_entry_keywords"
                            label="Palavras-chave de entrada"
                            rows="4"
                            :value="$settings['bot_entry_keywords'] ?? ''"
                        />
                        <p class="mt-2 text-xs text-slate-500">
                            Informe uma palavra por linha (ou JSON array), por exemplo: oi, olá, iniciar.
                        </p>
                    </div>
                    <div>
                        <x-admin.textarea
                            id="bot_exit_keywords"
                            name="bot_exit_keywords"
                            label="Palavras-chave de saída"
                            rows="4"
                            :value="$settings['bot_exit_keywords'] ?? ''"
                        />
                        <p class="mt-2 text-xs text-slate-500">
                            Informe uma palavra por linha (ou JSON array), ex.: sair, tchau, encerrar.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <x-admin.textarea
                        id="bot_close_message"
                        name="bot_close_message"
                        label="Mensagem de encerramento"
                        rows="3"
                        :value="$settings['bot_close_message'] ?? ''"
                    />
                </div>

                <div class="mt-4">
                    <x-admin.textarea
                        id="bot_inactive_close_message"
                        name="bot_inactive_close_message"
                        label="Mensagem de encerramento por inatividade"
                        rows="3"
                        :value="$settings['bot_inactive_close_message'] ?? ''"
                    />
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-700">Opções de cursos</h4>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <x-admin.input
                            id="bot_courses_limit"
                            name="bot_courses_limit"
                            label="Limite de cursos no menu"
                            type="number"
                            :value="$settings['bot_courses_limit'] ?? 10"
                        />
                        <x-admin.select
                            id="bot_courses_order"
                            name="bot_courses_order"
                            label="Ordenação dos cursos"
                            :options="[
                                ['value' => 'asc', 'label' => 'Data crescente'],
                                ['value' => 'desc', 'label' => 'Data decrescente'],
                            ]"
                            :selected="$settings['bot_courses_order'] ?? 'asc'"
                        />
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-700">Opções de cancelamento</h4>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <x-admin.input
                            id="bot_cancel_limit"
                            name="bot_cancel_limit"
                            label="Limite de inscrições listadas"
                            type="number"
                            :value="$settings['bot_cancel_limit'] ?? 10"
                        />
                        <x-admin.select
                            id="bot_cancel_order"
                            name="bot_cancel_order"
                            label="Ordenação das inscrições"
                            :options="[
                                ['value' => 'desc', 'label' => 'Mais recentes'],
                                ['value' => 'asc', 'label' => 'Mais antigas'],
                            ]"
                            :selected="$settings['bot_cancel_order'] ?? 'desc'"
                        />
                        <x-admin.checkbox
                            name="bot_cancel_require_confirm"
                            label="Exigir confirmação"
                            :checked="$settings['bot_cancel_require_confirm'] ?? true"
                        />
                        <x-admin.checkbox
                            name="bot_cancel_require_valid_cpf"
                            label="Exigir CPF válido"
                            :checked="$settings['bot_cancel_require_valid_cpf'] ?? true"
                        />
                        <x-admin.checkbox
                            name="bot_cancel_only_active_events"
                            label="Somente eventos ativos"
                            :checked="$settings['bot_cancel_only_active_events'] ?? true"
                        />
                        <x-admin.checkbox
                            name="bot_audit_log_enabled"
                            label="Salvar log de mensagens"
                            :checked="$settings['bot_audit_log_enabled'] ?? true"
                        />
                    </div>
                </div>
            </div>

            <div class="tab-panel hidden" data-tab-panel="google-contatos">
                <h3 class="section-title">Google Contatos</h3>
                <p class="text-sm text-slate-500">
                    Conecte a conta Google da empresa para importar contatos do telefone.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span class="badge">{{ $googleStatus }}</span>
                    @if ($googleConnected && $googleAccount)
                        <span class="text-xs text-slate-500">
                            {{ $googleAccount['email'] ?? '' }}
                        </span>
                    @endif
                </div>

                @if (! $googleConfigured)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                        Configure as credenciais Google no arquivo <code>.env</code> antes de conectar.
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($googleConfigured && ! $googleConnected)
                        <x-admin.action as="a" variant="primary" icon="settings" href="{{ route('admin.google.contacts.connect') }}">
                            Conectar conta Google
                        </x-admin.action>
                    @endif

                    @if ($googleConfigured && $googleConnected)
                        <x-admin.action
                            variant="primary"
                            icon="download"
                            type="submit"
                            formaction="{{ route('admin.google.contacts.import') }}"
                            formmethod="POST"
                        >
                            Importar contatos
                        </x-admin.action>
                        <x-admin.action
                            type="submit"
                            variant="ghost"
                            icon="x"
                            formaction="{{ route('admin.google.contacts.disconnect') }}"
                            formmethod="POST"
                            onclick="return confirm('Deseja remover a conexão com o Google?')"
                        >
                            Desconectar
                        </x-admin.action>
                    @endif
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
                                label="E-mail"
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
                                label="E-mail"
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
                        <h4 class="text-sm font-semibold text-slate-700">Confirmação de inscrição</h4>
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
                                label="E-mail"
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
                                label="E-mail"
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
                                label="E-mail"
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
                                label="E-mail"
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
                                label="E-mail"
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
                        label="Usuário"
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
                        label="Título"
                        :value="$settings['footer_titulo'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_contato_titulo"
                        name="footer_contato_titulo"
                        label="Título de contato"
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
                        label="Título de endereço"
                        :value="$settings['footer_endereco_titulo'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_endereco_linha1"
                        name="footer_endereco_linha1"
                        label="Endereço linha 1"
                        :value="$settings['footer_endereco_linha1'] ?? ''"
                        required
                    />
                    <x-admin.input
                        id="footer_endereco_linha2"
                        name="footer_endereco_linha2"
                        label="Endereço linha 2"
                        :value="$settings['footer_endereco_linha2'] ?? ''"
                        required
                    />
                </div>
                <div class="mt-4">
                    <x-admin.textarea
                        id="footer_descricao"
                        name="footer_descricao"
                        label="Descrição"
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
                        Alterações registradas automaticamente via AuditoriaObserver.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-admin.action variant="primary" icon="check" type="submit" name="submit_context" value="all">Salvar configurações</x-admin.action>
        </div>
    </form>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function templateEditor(templates, existingTemplates, defaultType, variables) {
            return {
                templates,
                existingTemplates,
                variables,
                defaultType,
                selectedType: defaultType || '',
                selectedChannel: 'whatsapp',
                loading: false,
                hasTemplate: false,
                copyMessage: '',
                focusedField: null,
                editor: { active: true, subject: '', content: '' },
                init() {
                    this.loadEditor();
                },
                setFocusedField(field) {
                    this.focusedField = field;
                },
                handleSelectionChange() {
                    this.loadEditor();
                },
                loadEditor() {
                    if (!this.selectedType) {
                        this.hasTemplate = false;
                        this.editor = { active: true, subject: '', content: '' };
                        return;
                    }

                    this.loading = true;
                    const typeData = this.templates[this.selectedType] || {};
                    const channelData = typeData[this.selectedChannel] || null;
                    const existingByType = this.existingTemplates[this.selectedType] || {};
                    this.hasTemplate = !!existingByType[this.selectedChannel];

                    setTimeout(() => {
                        this.editor = {
                            active: channelData?.ativo ?? true,
                            subject: channelData?.assunto ?? '',
                            content: channelData?.conteudo ?? '',
                        };
                        this.loading = false;
                    }, 150);
                },
                async copyVariable(variable) {
                    const inserted = this.insertIntoFocusedField(variable);

                    try {
                        await navigator.clipboard.writeText(variable);
                        this.copyMessage = inserted
                            ? 'Variável copiada e inserida no campo.'
                            : 'Variável copiada para a área de transferência.';
                    } catch (error) {
                        this.copyMessage = inserted
                            ? 'Variável inserida no campo.'
                            : 'Não foi possível copiar automaticamente.';
                    }

                    setTimeout(() => {
                        this.copyMessage = '';
                    }, 2500);
                },
                insertIntoFocusedField(variable) {
                    if (!this.focusedField || !(this.focusedField instanceof HTMLInputElement || this.focusedField instanceof HTMLTextAreaElement)) {
                        return false;
                    }

                    const field = this.focusedField;
                    const start = typeof field.selectionStart === 'number' ? field.selectionStart : field.value.length;
                    const end = typeof field.selectionEnd === 'number' ? field.selectionEnd : field.value.length;
                    field.value = field.value.slice(0, start) + variable + field.value.slice(end);
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.focus();

                    const cursor = start + variable.length;
                    if (typeof field.setSelectionRange === 'function') {
                        field.setSelectionRange(cursor, cursor);
                    }

                    return true;
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
            const botProvider = document.getElementById('bot_provider');
            const botCredentialsMode = document.getElementById('bot_credentials_mode');
            const botCredentialsCustom = document.getElementById('bot-credentials-custom');
            const botCredentialsMeta = document.getElementById('bot-credentials-meta');
            const botCredentialsZapi = document.getElementById('bot-credentials-zapi');

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

            function setSectionVisibility(section, visible) {
                if (!section) {
                    return;
                }

                section.hidden = !visible;
                section.classList.toggle('hidden', !visible);
                section.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !visible;
                });
            }

            function updateVisibility() {
                if (!botProvider || !botCredentialsMode || !botCredentialsCustom || !botCredentialsMeta || !botCredentialsZapi) {
                    return;
                }

                const isCustom = botCredentialsMode.value === 'custom';
                const isMeta = botProvider.value === 'meta';
                const isZapi = botProvider.value === 'zapi';

                setSectionVisibility(botCredentialsCustom, isCustom);
                setSectionVisibility(botCredentialsMeta, isCustom && isMeta);
                setSectionVisibility(botCredentialsZapi, isCustom && isZapi);
            }

            if (botProvider) {
                botProvider.addEventListener('change', updateVisibility);
            }

            if (botCredentialsMode) {
                botCredentialsMode.addEventListener('change', updateVisibility);
            }

            updateVisibility();

            buttons.forEach((button) => {
                button.addEventListener('click', () => activateTab(button.dataset.tab));
            });

            if (buttons.length) {
                activateTab(buttons[0].dataset.tab);
            }
        });
    </script>
@endsection
