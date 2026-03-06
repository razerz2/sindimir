<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\LegacyNotificationType;
use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use App\Services\ConfiguracaoService;
use App\Services\GoogleContactsService;
use App\Services\WhatsAppService;
use App\Services\ThemeService;
use App\Services\SiteSectionService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly ThemeService $themeService,
        private readonly WhatsAppService $whatsAppService,
        private readonly GoogleContactsService $googleContactsService
    ) {
    }

    public function index(): View
    {
        $smtpHost = $this->resolveSetting('smtp.host', config('mail.mailers.smtp.host'));
        $smtpPort = $this->resolveSetting('smtp.port', config('mail.mailers.smtp.port'));
        $smtpUsername = $this->resolveSetting('smtp.username', config('mail.mailers.smtp.username'));
        $smtpPassword = $this->resolveSetting('smtp.password', config('mail.mailers.smtp.password'));
        $smtpEncryption = $this->resolveSetting('smtp.encryption', config('mail.mailers.smtp.encryption'));
        $smtpFromEmail = $this->resolveSetting('smtp.from_email', config('mail.from.address'));
        $smtpFromName = $this->resolveSetting('smtp.from_name', config('mail.from.name'));
        $whatsappBaseUrl = $this->resolveSetting('whatsapp.base_url', config('services.whatsapp.zapi.base_url'));
        $whatsappInstance = $this->resolveSetting('whatsapp.instance', config('services.whatsapp.zapi.instance'));

        $settings = [
            'sistema_nome' => $this->configuracaoService->get('sistema.nome', config('app.name', 'Sindimir')),
            'sistema_email' => $this->configuracaoService->get('sistema.email_padrao', config('mail.from.address')),
            'sistema_ativo' => (bool) $this->configuracaoService->get('sistema.ativo', true),
            'notificacao_email_ativo' => (bool) $this->configuracaoService->get('notificacao.email_ativo', true),
            'notificacao_whatsapp_ativo' => (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false),
            'notificacao_destinatarios' => $this->configuracaoService->get('notificacao.destinatarios', 'alunos'),
            'two_factor_ativo' => (bool) $this->configuracaoService->get('seguranca.2fa.ativo', false),
            'two_factor_perfil' => $this->configuracaoService->get('seguranca.2fa.perfil', 'admin'),
            'two_factor_canal' => $this->configuracaoService->get('seguranca.2fa.canal', 'email'),
            'two_factor_expiracao_minutos' => (int) $this->configuracaoService->get('seguranca.2fa.expiracao_minutos', 10),
            'two_factor_max_tentativas' => (int) $this->configuracaoService->get('seguranca.2fa.max_tentativas', 5),
            'tema_cor_destaque' => $this->configuracaoService->get('tema.cor_destaque', null),
            'tema_logo' => $this->configuracaoService->get('tema.logo', null),
            'tema_favicon' => $this->configuracaoService->get('tema.favicon', null),
            'tema_background_main_imagem' => $this->configuracaoService->get('tema.background_main_imagem', null),
            'tema_background_main_overlay' => $this->configuracaoService->get('tema.background_main_overlay', 'rgba(255,255,255,0.85)'),
            'tema_background_main_posicao' => $this->configuracaoService->get('tema.background_main_posicao', 'center'),
            'tema_background_main_tamanho' => $this->configuracaoService->get('tema.background_main_tamanho', 'cover'),
            'footer_titulo' => $this->configuracaoService->get('site.footer.titulo', 'Sindimir'),
            'footer_descricao' => $this->configuracaoService->get(
                'site.footer.descricao',
                'Soluções digitais para capacitação, eventos e desenvolvimento do setor metal mecânico.'
            ),
            'footer_contato_titulo' => $this->configuracaoService->get('site.footer.contato_titulo', 'Contato'),
            'footer_contato_email' => $this->configuracaoService->get('site.footer.contato_email', 'contato@sindimir.org'),
            'footer_contato_telefone' => $this->configuracaoService->get('site.footer.contato_telefone', '(00) 0000-0000'),
            'footer_endereco_titulo' => $this->configuracaoService->get('site.footer.endereco_titulo', 'Endereço'),
            'footer_endereco_linha1' => $this->configuracaoService->get('site.footer.endereco_linha1', 'Rua da Industria, 1000'),
            'footer_endereco_linha2' => $this->configuracaoService->get('site.footer.endereco_linha2', 'Distrito Industrial'),
            'whatsapp_provedor' => $this->configuracaoService->get('whatsapp.provedor', null),
            'whatsapp_token' => $this->configuracaoService->get('whatsapp.token', null),
            'whatsapp_client_token' => $this->configuracaoService->get('whatsapp.client_token', null),
            'whatsapp_phone_number_id' => $this->configuracaoService->get('whatsapp.phone_number_id', null),
            'whatsapp_webhook_url' => $this->configuracaoService->get('whatsapp.webhook_url', null),
            'whatsapp_base_url' => $whatsappBaseUrl,
            'whatsapp_instance' => $whatsappInstance,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_username' => $smtpUsername,
            'smtp_password' => $smtpPassword,
            'smtp_encryption' => $smtpEncryption,
            'smtp_from_email' => $smtpFromEmail,
            'smtp_from_name' => $smtpFromName,
            'auto_lembrete_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.ativo', true),
            'auto_lembrete_email' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.canal.email', true),
            'auto_lembrete_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.canal.whatsapp', false),
            'auto_lembrete_dias_antes' => (int) $this->configuracaoService->get('notificacao.auto.lembrete.dias_antes', 2),
            'auto_lembrete_horario' => $this->configuracaoService->get('notificacao.auto.lembrete.horario', '08:00'),
            'auto_evento_criado_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.ativo', false),
            'auto_evento_criado_email' => (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.canal.email', true),
            'auto_evento_criado_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.evento_criado.canal.whatsapp', false),
            'auto_confirmacao_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.ativo', true),
            'auto_confirmacao_email' => (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.canal.email', true),
            'auto_confirmacao_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.canal.whatsapp', false),
            'auto_confirmacao_tempo_limite' => (int) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.tempo_limite_horas', 24),
            'auto_confirmacao_dias_antes' => (int) $this->configuracaoService->get('notificacao.auto.inscricao_confirmacao.dias_antes', 0),
            'auto_matricula_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.ativo', true),
            'auto_matricula_email' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.email', true),
            'auto_matricula_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.whatsapp', false),
            'auto_vaga_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.ativo', true),
            'auto_vaga_email' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.email', true),
            'auto_vaga_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.whatsapp', false),
            'auto_vaga_modo' => $this->configuracaoService->get('notificacao.auto.lista_espera.modo', 'sequencial'),
            'auto_vaga_tempo_limite' => (int) $this->configuracaoService->get(
                'notificacao.auto.lista_espera.intervalo_minutos',
                (int) $this->configuracaoService->get('notificacao.auto.lista_espera.tempo_limite_horas', 60)
            ),
            'auto_evento_cancelado_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.ativo', true),
            'auto_evento_cancelado_email' => (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.canal.email', true),
            'auto_evento_cancelado_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.evento_cancelado.canal.whatsapp', false),
            'auto_curso_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.ativo', false),
            'auto_curso_email' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.canal.email', true),
            'auto_curso_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.canal.whatsapp', false),
            'auto_curso_horario_envio' => $this->configuracaoService->get('notificacao.auto.curso_disponivel.horario_envio', '08:00'),
            'auto_curso_dias_antes' => (int) $this->configuracaoService->get('notificacao.auto.curso_disponivel.dias_antes', 0),
            'rate_limit_ativo' => (bool) $this->configuracaoService->get('notificacao.rate_limit.ativo', true),
            'rate_limit_limite_diario' => (int) $this->configuracaoService->get('notificacao.rate_limit.limite_diario', 2),
            'bot_enabled' => (bool) $this->configuracaoService->get('bot.enabled', false),
            'bot_provider' => (string) $this->configuracaoService->get('bot.provider', 'meta'),
            'bot_session_timeout_minutes' => (int) $this->configuracaoService->get('bot.session_timeout_minutes', 15),
            'bot_welcome_message' => (string) $this->configuracaoService->get(
                'bot.welcome_message',
                'Bem-vindo ao bot do Sindimir. Escolha uma opcao:'
            ),
            'bot_fallback_message' => (string) $this->configuracaoService->get(
                'bot.fallback_message',
                'Nao entendi sua mensagem. Escolha uma opcao valida.'
            ),
            'bot_entry_keywords' => $this->formatBotEntryKeywordsForTextarea(
                $this->configuracaoService->get('bot.entry_keywords', ['oi', 'ola'])
            ),
            'bot_reset_keyword' => (string) $this->configuracaoService->get('bot.reset_keyword', 'menu'),
            'bot_courses_limit' => (int) $this->configuracaoService->get('bot.courses.limit', 10),
            'bot_courses_order' => (string) $this->configuracaoService->get('bot.courses.order', 'asc'),
            'bot_cancel_limit' => (int) $this->configuracaoService->get('bot.cancel.limit', 10),
            'bot_cancel_order' => (string) $this->configuracaoService->get('bot.cancel.order', 'desc'),
            'bot_cancel_require_confirm' => (bool) (
                $this->configuracaoService->get('bot.cancel.require_confirm', null)
                ?? $this->configuracaoService->get('bot.cancel.require_confirmation', true)
            ),
            'bot_cancel_require_valid_cpf' => (bool) $this->configuracaoService->get('bot.cancel.require_valid_cpf', true),
            'bot_cancel_only_active_events' => (bool) $this->configuracaoService->get('bot.cancel.only_active_events', true),
            'bot_audit_log_enabled' => (bool) $this->configuracaoService->get('bot.audit_log_enabled', true),
        ];

        $theme = $this->themeService->getThemeColors();
        $templates = collect();
        $templateDefaults = $this->buildTemplateDefaultsPayload();
        $templateTypeOptions = $this->getTemplateTypeOptions();
        $templateVariables = $this->getTemplateVariables();

        if (Schema::hasTable('notification_templates')) {
            $this->ensureDefaultNotificationTemplates();
            $templates = NotificationTemplate::query()
                ->orderBy('notification_type')
                ->orderBy('canal')
                ->get()
                ->groupBy('notification_type');
        }

        $whatsappStatus = 'Pendente';
        if ($settings['whatsapp_provedor'] && $settings['whatsapp_token']) {
            $whatsappStatus = $settings['whatsapp_provedor'] === 'zapi' && ! $settings['whatsapp_client_token']
                ? 'Pendente'
                : 'Ativo';
        }

        $whatsappReady = false;
        if ($settings['whatsapp_provedor'] === 'zapi') {
            $whatsappReady = ! empty($settings['whatsapp_token'])
                && ! empty($settings['whatsapp_client_token'])
                && ! empty($settings['whatsapp_base_url'])
                && ! empty($settings['whatsapp_instance']);
        }
        if ($settings['whatsapp_provedor'] === 'meta') {
            $whatsappReady = ! empty($settings['whatsapp_token']) && ! empty($settings['whatsapp_phone_number_id']);
        }

        $googleConfigured = $this->googleContactsService->isConfigured();
        $googleConnected = $this->googleContactsService->isConnected();
        $googleAccount = $this->googleContactsService->getConnectedAccount();
        $googleStatus = $googleConnected ? 'Conectado' : ($googleConfigured ? 'Pendente' : 'Não configurado');

        return view('admin.configuracoes.index', compact(
            'settings',
            'theme',
            'whatsappStatus',
            'templates',
            'whatsappReady',
            'templateDefaults',
            'templateTypeOptions',
            'templateVariables',
            'googleConfigured',
            'googleConnected',
            'googleAccount',
            'googleStatus'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sistema_nome' => ['required', 'string', 'max:120'],
            'sistema_email' => ['nullable', 'email', 'max:120'],
            'sistema_ativo' => ['nullable', 'boolean'],
            'tema_cor_primaria' => ['required', 'string', 'max:20'],
            'tema_cor_secundaria' => ['required', 'string', 'max:20'],
            'tema_cor_destaque' => ['nullable', 'string', 'max:20'],
            'tema_logo' => ['nullable', 'image', 'max:2048'],
            'tema_logo_remover' => ['nullable', 'boolean'],
            'tema_favicon' => ['nullable', 'mimes:png,ico,svg', 'max:1024'],
            'tema_favicon_remover' => ['nullable', 'boolean'],
            'tema_background_main_imagem' => ['nullable', 'image', 'max:2048'],
            'tema_background_main_imagem_remover' => ['nullable', 'boolean'],
            'tema_background_main_overlay' => ['nullable', 'string', 'max:60'],
            'tema_background_main_posicao' => ['nullable', 'string', 'max:40'],
            'tema_background_main_tamanho' => ['nullable', 'string', 'max:40'],
            'notificacao_email_ativo' => ['nullable', 'boolean'],
            'notificacao_whatsapp_ativo' => ['nullable', 'boolean'],
            'notificacao_destinatarios' => ['nullable', 'string', 'in:alunos,contatos_externos,ambos'],
            'two_factor_ativo' => ['nullable', 'boolean'],
            'two_factor_perfil' => ['nullable', 'string', 'in:admin,aluno,ambos'],
            'two_factor_canal' => ['nullable', 'string', 'in:email,whatsapp'],
            'two_factor_expiracao_minutos' => ['nullable', 'integer', 'min:1', 'max:60'],
            'two_factor_max_tentativas' => ['nullable', 'integer', 'min:1', 'max:10'],
            'whatsapp_provedor' => ['nullable', 'string', 'in:meta,zapi'],
            'whatsapp_token' => ['nullable', 'string', 'max:200'],
            'whatsapp_client_token' => ['nullable', 'string', 'max:200'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:120'],
            'whatsapp_webhook_url' => ['nullable', 'url', 'max:200'],
            'whatsapp_base_url' => ['nullable', 'url', 'max:200'],
            'whatsapp_instance' => ['nullable', 'string', 'max:120'],
            'smtp_host' => ['nullable', 'string', 'max:120'],
            'smtp_port' => ['nullable', 'numeric'],
            'smtp_username' => ['nullable', 'string', 'max:120'],
            'smtp_password' => ['nullable', 'string', 'max:200'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'smtp_from_email' => ['nullable', 'email', 'max:120'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
            'footer_titulo' => ['required', 'string', 'max:120'],
            'footer_descricao' => ['required', 'string', 'max:500'],
            'footer_contato_titulo' => ['required', 'string', 'max:120'],
            'footer_contato_email' => ['nullable', 'email', 'max:120'],
            'footer_contato_telefone' => ['nullable', 'string', 'max:40'],
            'footer_endereco_titulo' => ['required', 'string', 'max:120'],
            'footer_endereco_linha1' => ['required', 'string', 'max:200'],
            'footer_endereco_linha2' => ['required', 'string', 'max:200'],
            'submit_context' => ['nullable', 'string', 'in:all,template'],
            'templates' => ['array'],
            'templates.*.email.assunto' => ['nullable', 'string', 'max:200'],
            'templates.*.email.conteudo' => ['nullable', 'string', 'max:4000'],
            'templates.*.email.ativo' => ['nullable', 'boolean'],
            'templates.*.whatsapp.conteudo' => ['nullable', 'string', 'max:4000'],
            'templates.*.whatsapp.ativo' => ['nullable', 'boolean'],
            'template_type' => [
                'nullable',
                'string',
                Rule::in($this->getTemplateTypeValues()),
                Rule::requiredIf(fn () => $request->input('submit_context') === 'template'),
            ],
            'template_channel' => [
                'nullable',
                'string',
                Rule::in(['whatsapp', 'email']),
                Rule::requiredIf(fn () => $request->input('submit_context') === 'template'),
            ],
            'template_active' => ['nullable', 'boolean'],
            'template_subject' => ['nullable', 'string', 'max:200'],
            'template_content' => ['nullable', 'string', 'max:4000'],
            'auto_lembrete_ativo' => ['nullable', 'boolean'],
            'auto_lembrete_email' => ['nullable', 'boolean'],
            'auto_lembrete_whatsapp' => ['nullable', 'boolean'],
            'auto_lembrete_dias_antes' => ['nullable', 'integer', 'min:1', 'max:365'],
            'auto_lembrete_horario' => ['nullable', 'string', 'max:10'],
            'auto_evento_criado_ativo' => ['nullable', 'boolean'],
            'auto_evento_criado_email' => ['nullable', 'boolean'],
            'auto_evento_criado_whatsapp' => ['nullable', 'boolean'],
            'auto_confirmacao_ativo' => ['nullable', 'boolean'],
            'auto_confirmacao_email' => ['nullable', 'boolean'],
            'auto_confirmacao_whatsapp' => ['nullable', 'boolean'],
            'auto_confirmacao_tempo_limite' => ['nullable', 'integer', 'min:1', 'max:168'],
            'auto_confirmacao_dias_antes' => ['nullable', 'integer', 'min:0', 'max:365'],
            'auto_matricula_ativo' => ['nullable', 'boolean'],
            'auto_matricula_email' => ['nullable', 'boolean'],
            'auto_matricula_whatsapp' => ['nullable', 'boolean'],
            'auto_vaga_ativo' => ['nullable', 'boolean'],
            'auto_vaga_email' => ['nullable', 'boolean'],
            'auto_vaga_whatsapp' => ['nullable', 'boolean'],
            'auto_vaga_modo' => ['nullable', 'string', 'in:todos,sequencial'],
            'auto_vaga_tempo_limite' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'auto_evento_cancelado_ativo' => ['nullable', 'boolean'],
            'auto_evento_cancelado_email' => ['nullable', 'boolean'],
            'auto_evento_cancelado_whatsapp' => ['nullable', 'boolean'],
            'auto_curso_ativo' => ['nullable', 'boolean'],
            'auto_curso_email' => ['nullable', 'boolean'],
            'auto_curso_whatsapp' => ['nullable', 'boolean'],
            'auto_curso_horario_envio' => ['nullable', 'string', 'max:10'],
            'auto_curso_dias_antes' => ['nullable', 'integer', 'min:0', 'max:365'],
            'rate_limit_ativo' => ['nullable', 'boolean'],
            'rate_limit_limite_diario' => ['nullable', 'integer', 'min:1', 'max:100'],
            'bot_enabled' => ['nullable', 'boolean'],
            'bot_provider' => ['nullable', 'string', 'in:meta,zapi'],
            'bot_session_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'bot_welcome_message' => ['nullable', 'string', 'max:2000'],
            'bot_fallback_message' => ['nullable', 'string', 'max:2000'],
            'bot_entry_keywords' => ['nullable', 'string', 'max:4000'],
            'bot_reset_keyword' => ['nullable', 'string', 'max:40'],
            'bot_courses_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'bot_courses_order' => ['nullable', 'string', 'in:asc,desc'],
            'bot_cancel_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'bot_cancel_order' => ['nullable', 'string', 'in:asc,desc'],
            'bot_cancel_require_confirm' => ['nullable', 'boolean'],
            'bot_cancel_require_valid_cpf' => ['nullable', 'boolean'],
            'bot_cancel_only_active_events' => ['nullable', 'boolean'],
            'bot_audit_log_enabled' => ['nullable', 'boolean'],
        ]);

        $this->configuracaoService->set('sistema.nome', $data['sistema_nome'], 'Nome do sistema');
        $this->configuracaoService->set('sistema.email_padrao', $data['sistema_email'] ?? null, 'Email padrao');
        $this->configuracaoService->set('sistema.ativo', (bool) $request->boolean('sistema_ativo'), 'Sistema ativo');
        $this->configuracaoService->set('tema.cor_primaria', $data['tema_cor_primaria'], 'Cor primaria do tema');
        $this->configuracaoService->set('tema.cor_secundaria', $data['tema_cor_secundaria'], 'Cor secundaria do tema');
        $temaDestaque = (string) ($data['tema_cor_destaque'] ?? '');
        $this->configuracaoService->set('tema.cor_destaque', $temaDestaque, 'Cor de destaque');

        $removeLogo = $request->boolean('tema_logo_remover');
        if ($removeLogo) {
            $this->configuracaoService->set('tema.logo', null, 'Logo do tema');
        } elseif ($request->hasFile('tema_logo')) {
            $path = $request->file('tema_logo')->store('tema', 'public');
            $publicPath = 'storage/' . $path;
            $this->configuracaoService->set('tema.logo', $publicPath, 'Logo do tema');
        }

        $removeFavicon = $request->boolean('tema_favicon_remover');
        if ($removeFavicon) {
            $this->configuracaoService->set('tema.favicon', null, 'Favicon do tema');
        } elseif ($request->hasFile('tema_favicon')) {
            $path = $request->file('tema_favicon')->store('tema', 'public');
            $publicPath = 'storage/' . $path;
            $this->configuracaoService->set('tema.favicon', $publicPath, 'Favicon do tema');
        }

        $overlay = (string) ($data['tema_background_main_overlay'] ?? 'rgba(255,255,255,0.85)');
        $position = (string) ($data['tema_background_main_posicao'] ?? 'center');
        $size = (string) ($data['tema_background_main_tamanho'] ?? 'cover');
        $this->configuracaoService->set('tema.background_main_overlay', $overlay, 'Overlay fundo institucional');
        $this->configuracaoService->set('tema.background_main_posicao', $position, 'Posicao fundo institucional');
        $this->configuracaoService->set('tema.background_main_tamanho', $size, 'Tamanho fundo institucional');

        $removeBgImage = $request->boolean('tema_background_main_imagem_remover');
        if ($removeBgImage) {
            $this->configuracaoService->set('tema.background_main_imagem', null, 'Imagem fundo institucional');
        } elseif ($request->hasFile('tema_background_main_imagem')) {
            $path = $request->file('tema_background_main_imagem')->store('tema', 'public');
            $publicPath = 'storage/' . $path;
            $this->configuracaoService->set('tema.background_main_imagem', $publicPath, 'Imagem fundo institucional');
        }

        $this->configuracaoService->set('notificacao.email_ativo', (bool) $request->boolean('notificacao_email_ativo'), 'Notificacao por email');
        $this->configuracaoService->set('notificacao.whatsapp_ativo', (bool) $request->boolean('notificacao_whatsapp_ativo'), 'Notificacao por WhatsApp');
        $this->configuracaoService->set(
            'notificacao.destinatarios',
            (string) ($data['notificacao_destinatarios'] ?? 'alunos'),
            'Destinatários das notificações'
        );
        $this->configuracaoService->set('seguranca.2fa.ativo', (bool) $request->boolean('two_factor_ativo'), '2FA ativo');
        $this->configuracaoService->set('seguranca.2fa.perfil', (string) ($data['two_factor_perfil'] ?? 'admin'), '2FA perfis');
        $this->configuracaoService->set('seguranca.2fa.canal', (string) ($data['two_factor_canal'] ?? 'email'), '2FA canal');
        $this->configuracaoService->set(
            'seguranca.2fa.expiracao_minutos',
            (int) ($data['two_factor_expiracao_minutos'] ?? 10),
            '2FA expiração em minutos'
        );
        $this->configuracaoService->set(
            'seguranca.2fa.max_tentativas',
            (int) ($data['two_factor_max_tentativas'] ?? 5),
            '2FA max tentativas'
        );
        $whatsappProvedor = (string) ($data['whatsapp_provedor'] ?? '');
        $whatsappToken = (string) ($data['whatsapp_token'] ?? '');
        $whatsappClientToken = (string) ($data['whatsapp_client_token'] ?? '');
        $whatsappPhoneId = (string) ($data['whatsapp_phone_number_id'] ?? '');
        $whatsappWebhook = (string) ($data['whatsapp_webhook_url'] ?? '');
        $whatsappBaseUrl = (string) ($data['whatsapp_base_url'] ?? '');
        $whatsappInstance = (string) ($data['whatsapp_instance'] ?? '');

        $this->configuracaoService->set('whatsapp.provedor', $whatsappProvedor, 'Provedor WhatsApp');
        $this->configuracaoService->set('whatsapp.token', $whatsappToken, 'Token WhatsApp');
        $this->configuracaoService->set('whatsapp.client_token', $whatsappClientToken, 'Client-Token WhatsApp');
        $this->configuracaoService->set('whatsapp.phone_number_id', $whatsappPhoneId, 'WhatsApp Phone Number ID');
        $this->configuracaoService->set('whatsapp.webhook_url', $whatsappWebhook, 'WhatsApp Webhook URL');
        $this->configuracaoService->set('whatsapp.base_url', $whatsappBaseUrl, 'WhatsApp base URL');
        $this->configuracaoService->set('whatsapp.instance', $whatsappInstance, 'WhatsApp instance');
        $smtpHost = (string) ($data['smtp_host'] ?? '');
        $smtpPort = (string) ($data['smtp_port'] ?? '');
        $smtpUsername = (string) ($data['smtp_username'] ?? '');
        $smtpPassword = (string) ($data['smtp_password'] ?? '');
        $smtpEncryption = (string) ($data['smtp_encryption'] ?? '');
        $smtpFromEmail = (string) ($data['smtp_from_email'] ?? '');
        $smtpFromName = (string) ($data['smtp_from_name'] ?? '');

        $this->configuracaoService->set('smtp.host', $smtpHost, 'SMTP host');
        $this->configuracaoService->set('smtp.port', $smtpPort, 'SMTP porta');
        $this->configuracaoService->set('smtp.username', $smtpUsername, 'SMTP usuario');
        $this->configuracaoService->set('smtp.password', $smtpPassword, 'SMTP senha');
        $this->configuracaoService->set('smtp.encryption', $smtpEncryption, 'SMTP criptografia');
        $this->configuracaoService->set('smtp.from_email', $smtpFromEmail, 'SMTP remetente');
        $this->configuracaoService->set('smtp.from_name', $smtpFromName, 'SMTP nome remetente');
        $this->configuracaoService->set('site.footer.titulo', $data['footer_titulo'], 'Footer titulo');
        $this->configuracaoService->set('site.footer.descricao', $data['footer_descricao'], 'Footer descricao');
        $this->configuracaoService->set('site.footer.contato_titulo', $data['footer_contato_titulo'], 'Footer contato titulo');
        $this->configuracaoService->set('site.footer.contato_email', $data['footer_contato_email'] ?? '', 'Footer contato email');
        $this->configuracaoService->set('site.footer.contato_telefone', $data['footer_contato_telefone'] ?? '', 'Footer contato telefone');
        $this->configuracaoService->set('site.footer.endereco_titulo', $data['footer_endereco_titulo'], 'Footer endereco titulo');
        $this->configuracaoService->set('site.footer.endereco_linha1', $data['footer_endereco_linha1'], 'Footer endereco linha 1');
        $this->configuracaoService->set('site.footer.endereco_linha2', $data['footer_endereco_linha2'], 'Footer endereco linha 2');

        $this->configuracaoService->set('notificacao.auto.lembrete.ativo', (bool) $request->boolean('auto_lembrete_ativo'), 'Auto lembrete ativo');
        $this->configuracaoService->set('notificacao.auto.lembrete.canal.email', (bool) $request->boolean('auto_lembrete_email'), 'Auto lembrete email');
        $this->configuracaoService->set('notificacao.auto.lembrete.canal.whatsapp', (bool) $request->boolean('auto_lembrete_whatsapp'), 'Auto lembrete WhatsApp');
        $this->configuracaoService->set('notificacao.auto.lembrete.dias_antes', (int) ($data['auto_lembrete_dias_antes'] ?? 2), 'Auto lembrete dias antes');
        $this->configuracaoService->set('notificacao.auto.lembrete.horario', (string) ($data['auto_lembrete_horario'] ?? '08:00'), 'Auto lembrete horario');

        $this->configuracaoService->set('notificacao.auto.evento_criado.ativo', (bool) $request->boolean('auto_evento_criado_ativo'), 'Auto evento criado ativo');
        $this->configuracaoService->set('notificacao.auto.evento_criado.canal.email', (bool) $request->boolean('auto_evento_criado_email'), 'Auto evento criado email');
        $this->configuracaoService->set('notificacao.auto.evento_criado.canal.whatsapp', (bool) $request->boolean('auto_evento_criado_whatsapp'), 'Auto evento criado WhatsApp');

        $this->configuracaoService->set('notificacao.auto.inscricao_confirmacao.ativo', (bool) $request->boolean('auto_confirmacao_ativo'), 'Auto confirmacao inscricao ativo');
        $this->configuracaoService->set('notificacao.auto.inscricao_confirmacao.canal.email', (bool) $request->boolean('auto_confirmacao_email'), 'Auto confirmacao inscricao email');
        $this->configuracaoService->set('notificacao.auto.inscricao_confirmacao.canal.whatsapp', (bool) $request->boolean('auto_confirmacao_whatsapp'), 'Auto confirmacao inscricao WhatsApp');
        $this->configuracaoService->set('notificacao.auto.inscricao_confirmacao.tempo_limite_horas', (int) ($data['auto_confirmacao_tempo_limite'] ?? 24), 'Auto confirmacao inscricao tempo limite');
        $this->configuracaoService->set('notificacao.auto.inscricao_confirmacao.dias_antes', (int) ($data['auto_confirmacao_dias_antes'] ?? 0), 'Auto confirmacao inscricao dias antes');

        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.ativo', (bool) $request->boolean('auto_matricula_ativo'), 'Auto matricula confirmada ativo');
        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.canal.email', (bool) $request->boolean('auto_matricula_email'), 'Auto matricula email');
        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.canal.whatsapp', (bool) $request->boolean('auto_matricula_whatsapp'), 'Auto matricula WhatsApp');

        $this->configuracaoService->set('notificacao.auto.lista_espera.ativo', (bool) $request->boolean('auto_vaga_ativo'), 'Auto lista espera ativo');
        $this->configuracaoService->set('notificacao.auto.lista_espera.canal.email', (bool) $request->boolean('auto_vaga_email'), 'Auto lista espera email');
        $this->configuracaoService->set('notificacao.auto.lista_espera.canal.whatsapp', (bool) $request->boolean('auto_vaga_whatsapp'), 'Auto lista espera WhatsApp');
        $this->configuracaoService->set('notificacao.auto.lista_espera.modo', (string) ($data['auto_vaga_modo'] ?? 'sequencial'), 'Auto lista espera modo');
        $this->configuracaoService->set('notificacao.auto.lista_espera.intervalo_minutos', (int) ($data['auto_vaga_tempo_limite'] ?? 60), 'Auto lista espera intervalo');

        $this->configuracaoService->set('notificacao.auto.evento_cancelado.ativo', (bool) $request->boolean('auto_evento_cancelado_ativo'), 'Auto evento cancelado ativo');
        $this->configuracaoService->set('notificacao.auto.evento_cancelado.canal.email', (bool) $request->boolean('auto_evento_cancelado_email'), 'Auto evento cancelado email');
        $this->configuracaoService->set('notificacao.auto.evento_cancelado.canal.whatsapp', (bool) $request->boolean('auto_evento_cancelado_whatsapp'), 'Auto evento cancelado WhatsApp');

        $this->configuracaoService->set('notificacao.auto.curso_disponivel.ativo', (bool) $request->boolean('auto_curso_ativo'), 'Auto curso disponivel ativo');
        $this->configuracaoService->set('notificacao.auto.curso_disponivel.canal.email', (bool) $request->boolean('auto_curso_email'), 'Auto curso disponivel email');
        $this->configuracaoService->set('notificacao.auto.curso_disponivel.canal.whatsapp', (bool) $request->boolean('auto_curso_whatsapp'), 'Auto curso disponivel WhatsApp');
        $this->configuracaoService->set(
            'notificacao.auto.curso_disponivel.horario_envio',
            (string) ($data['auto_curso_horario_envio'] ?? '08:00'),
            'Auto curso disponivel horario envio'
        );
        $this->configuracaoService->set(
            'notificacao.auto.curso_disponivel.dias_antes',
            (int) ($data['auto_curso_dias_antes'] ?? 0),
            'Auto curso disponivel dias antes'
        );

        $this->configuracaoService->set('notificacao.rate_limit.ativo', (bool) $request->boolean('rate_limit_ativo'), 'Rate limit de notificacoes');
        $this->configuracaoService->set(
            'notificacao.rate_limit.limite_diario',
            (int) ($data['rate_limit_limite_diario'] ?? 2),
            'Rate limit diario de notificacoes'
        );
        $this->configuracaoService->set('bot.enabled', (bool) $request->boolean('bot_enabled'), 'BOT ativo');
        $this->configuracaoService->set('bot.provider', (string) ($data['bot_provider'] ?? 'meta'), 'Provedor ativo do BOT');
        $this->configuracaoService->set(
            'bot.session_timeout_minutes',
            (int) ($data['bot_session_timeout_minutes'] ?? 15),
            'Timeout de sessao do BOT'
        );
        $this->configuracaoService->set(
            'bot.welcome_message',
            (string) ($data['bot_welcome_message'] ?? 'Bem-vindo ao bot do Sindimir. Escolha uma opcao:'),
            'Mensagem de boas-vindas do BOT'
        );
        $this->configuracaoService->set(
            'bot.fallback_message',
            (string) ($data['bot_fallback_message'] ?? 'Nao entendi sua mensagem. Escolha uma opcao valida.'),
            'Mensagem de fallback do BOT'
        );
        $this->configuracaoService->set(
            'bot.entry_keywords',
            $this->parseBotEntryKeywords((string) ($data['bot_entry_keywords'] ?? '')),
            'Palavras-chave de entrada do BOT'
        );
        $this->configuracaoService->set(
            'bot.reset_keyword',
            (string) ($data['bot_reset_keyword'] ?? 'menu'),
            'Palavra-chave de reset do BOT'
        );
        $this->configuracaoService->set('bot.courses.limit', (int) ($data['bot_courses_limit'] ?? 10), 'Limite de cursos no BOT');
        $this->configuracaoService->set(
            'bot.courses.order',
            (string) ($data['bot_courses_order'] ?? 'asc'),
            'Ordenacao de cursos no BOT'
        );
        $this->configuracaoService->set(
            'bot.cancel.limit',
            (int) ($data['bot_cancel_limit'] ?? 10),
            'Limite de inscricoes para cancelamento no BOT'
        );
        $this->configuracaoService->set(
            'bot.cancel.order',
            (string) ($data['bot_cancel_order'] ?? 'desc'),
            'Ordenacao de inscricoes no BOT'
        );
        $this->configuracaoService->set(
            'bot.cancel.require_confirm',
            (bool) $request->boolean('bot_cancel_require_confirm'),
            'Exigir confirmacao de cancelamento no BOT'
        );
        // Compatibilidade com chave legada.
        $this->configuracaoService->set(
            'bot.cancel.require_confirmation',
            (bool) $request->boolean('bot_cancel_require_confirm'),
            'Exigir confirmacao de cancelamento no BOT (legado)'
        );
        $this->configuracaoService->set(
            'bot.cancel.require_valid_cpf',
            (bool) $request->boolean('bot_cancel_require_valid_cpf'),
            'Exigir CPF valido no BOT'
        );
        $this->configuracaoService->set(
            'bot.cancel.only_active_events',
            (bool) $request->boolean('bot_cancel_only_active_events'),
            'Cancelar apenas eventos ativos no BOT'
        );
        $this->configuracaoService->set(
            'bot.audit_log_enabled',
            (bool) $request->boolean('bot_audit_log_enabled'),
            'Auditoria de mensagens do BOT'
        );

        $this->syncNotificationTemplatesFromRequest($request, $data);
        Cache::forget(SiteSectionService::CACHE_KEY);

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', 'Configurações atualizadas com sucesso.');
    }

    public function testarWhatsapp(Request $request): RedirectResponse
    {
        if (! $this->whatsAppService->canTestSend()) {
            return redirect()
                ->route('admin.configuracoes.index')
                ->with('whatsapp_test_status', [
                    'type' => 'error',
                    'message' => 'Configure um provedor WhatsApp antes de testar o envio.',
                ]);
        }

        $data = $request->validate([
            'whatsapp_test_numero' => ['required', 'string', 'max:30'],
            'whatsapp_test_mensagem' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $payload = $this->whatsAppService->sendTest(
                $data['whatsapp_test_numero'],
                $data['whatsapp_test_mensagem']
            );
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('admin.configuracoes.index')
                ->with('whatsapp_test_status', [
                    'type' => 'error',
                    'message' => $exception->getMessage(),
                ]);
        }

        $message = 'Mensagem de teste enviada com sucesso.';
        $response = $payload['response'] ?? null;
        if ($response) {
            $message .= ' Retorno: ' . json_encode($response, JSON_UNESCAPED_UNICODE);
        }

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('whatsapp_test_status', [
                'type' => 'success',
                'message' => $message,
            ]);
    }

    private function syncNotificationTemplatesFromRequest(Request $request, array $data): void
    {
        $submitContext = (string) ($data['submit_context'] ?? 'all');

        if (
            $submitContext === 'template'
            && ! empty($data['template_type'])
            && ! empty($data['template_channel'])
        ) {
            $typeKey = (string) $data['template_type'];
            $canal = (string) $data['template_channel'];
            $payload = [
                'ativo' => $request->boolean('template_active'),
                'conteudo' => (string) ($data['template_content'] ?? ''),
            ];

            if ($canal === 'email') {
                $payload['assunto'] = (string) ($data['template_subject'] ?? '');
            }

            $this->syncNotificationTemplates(
                [
                    $typeKey => [
                        $canal => $payload,
                    ],
                ],
                $typeKey,
                $canal
            );

            return;
        }

        $legacyTemplates = $data['templates'] ?? [];
        $legacyType = isset($data['template_type']) ? (string) $data['template_type'] : null;

        if (! empty($legacyTemplates)) {
            $this->syncNotificationTemplates($legacyTemplates, $legacyType);
        }
    }

    private function syncNotificationTemplates(array $templates, ?string $onlyType = null, ?string $onlyChannel = null): void
    {
        $types = $this->getTemplateTypeValues();

        if ($onlyType) {
            $types = array_values(array_filter($types, static fn (string $type) => $type === $onlyType));
        }

        $channels = $onlyChannel ? [$onlyChannel] : ['email', 'whatsapp'];

        foreach ($types as $typeKey) {
            $typeTemplates = $templates[$typeKey] ?? [];

            foreach ($channels as $canal) {
                $payload = $typeTemplates[$canal] ?? [];
                $existing = NotificationTemplate::query()
                    ->where('notification_type', $typeKey)
                    ->where('canal', $canal)
                    ->first();

                $assunto = $payload['assunto'] ?? ($existing?->assunto ?? null);
                $conteudo = $payload['conteudo'] ?? ($existing?->conteudo ?? '');
                $ativo = array_key_exists('ativo', $payload)
                    ? (bool) $payload['ativo']
                    : ($existing?->ativo ?? true);
                if ($typeKey === LegacyNotificationType::MATRICULA_CONFIRMADA) {
                    $conteudo = $this->sanitizeMatriculaTemplate($conteudo);
                } elseif ($typeKey === LegacyNotificationType::INSCRICAO_CONFIRMAR) {
                    $conteudo = $this->sanitizeConfirmacaoTemplate($conteudo);
                } else {
                    $conteudo = $this->sanitizeLinkTemplate($conteudo);
                }

                if ($conteudo === '') {
                    continue;
                }

                NotificationTemplate::updateOrCreate(
                    [
                        'notification_type' => $typeKey,
                        'canal' => $canal,
                    ],
                    [
                        'assunto' => $assunto,
                        'conteudo' => $conteudo,
                        'ativo' => $ativo,
                    ]
                );
            }
        }
    }

    private function resolveSetting(string $chave, mixed $default = null): mixed
    {
        $value = $this->configuracaoService->get($chave, null);

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function parseBotEntryKeywords(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['oi', 'ola'];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $keywords = [];
                foreach ($decoded as $keyword) {
                    $value = trim((string) $keyword);
                    if ($value !== '') {
                        $keywords[] = $value;
                    }
                }

                if ($keywords !== []) {
                    return array_values(array_unique($keywords));
                }
            }
        }

        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $keywords = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value !== '') {
                $keywords[] = $value;
            }
        }

        if ($keywords === []) {
            return ['oi', 'ola'];
        }

        return array_values(array_unique($keywords));
    }

    private function formatBotEntryKeywordsForTextarea(mixed $value): string
    {
        $keywords = [];

        if (is_array($value)) {
            $keywords = $value;
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $keywords = $decoded;
                }
            }

            if ($keywords === []) {
                $keywords = preg_split('/[\r\n,;]+/', $value) ?: [];
            }
        }

        $normalized = [];
        foreach ($keywords as $keyword) {
            $entry = trim((string) $keyword);
            if ($entry !== '') {
                $normalized[] = $entry;
            }
        }

        if ($normalized === []) {
            $normalized = ['oi', 'ola'];
        }

        return implode("\n", array_values(array_unique($normalized)));
    }

    private function sanitizeMatriculaTemplate(string $conteudo): string
    {
        if ($conteudo === '') {
            return $conteudo;
        }

        $conteudo = $this->sanitizeLinkTemplate($conteudo);

        return str_replace(['/inscricao/token/', '/matricula/'], '{{link}}', $conteudo);
    }

    private function sanitizeConfirmacaoTemplate(string $conteudo): string
    {
        if ($conteudo === '') {
            return $conteudo;
        }

        $conteudo = $this->sanitizeLinkTemplate($conteudo);

        return str_replace(['/inscricao/confirmar/'], '{{link}}', $conteudo);
    }

    private function sanitizeLinkTemplate(string $conteudo): string
    {
        if ($conteudo === '') {
            return $conteudo;
        }

        return preg_replace('/https?:\/\/\S+/i', '{{link}}', $conteudo);
    }

    private function ensureDefaultNotificationTemplates(): void
    {
        $defaults = $this->getDefaultNotificationTemplates();

        foreach ($defaults as $template) {
            $query = NotificationTemplate::query()
                ->where('notification_type', $template['type'])
                ->where('canal', $template['canal']);
            $existing = $query->first();

            if (! $existing) {
                NotificationTemplate::create([
                    'notification_type' => $template['type'],
                    'canal' => $template['canal'],
                    'assunto' => $template['subject'],
                    'conteudo' => $template['content'],
                    'ativo' => true,
                ]);
                continue;
            }

            if (! $existing->conteudo) {
                $existing->update([
                    'assunto' => $template['subject'],
                    'conteudo' => $template['content'],
                    'ativo' => true,
                ]);
            }
        }
    }

    private function buildTemplateDefaultsPayload(): array
    {
        $payload = [];

        foreach ($this->getDefaultNotificationTemplates() as $template) {
            $typeKey = $template['type'];
            $payload[$typeKey] ??= [
                'email' => ['ativo' => true, 'assunto' => '', 'conteudo' => ''],
                'whatsapp' => ['ativo' => true, 'conteudo' => ''],
            ];

            if ($template['canal'] === 'email') {
                $payload[$typeKey]['email'] = [
                    'ativo' => true,
                    'assunto' => $template['subject'] ?? '',
                    'conteudo' => $template['content'] ?? '',
                ];
            } else {
                $payload[$typeKey]['whatsapp'] = [
                    'ativo' => true,
                    'conteudo' => $template['content'] ?? '',
                ];
            }
        }

        return $payload;
    }

    private function getDefaultNotificationTemplates(): array
    {
        $baseConteudo = "Olá {{aluno_nome}},\n\nTemos uma oportunidade no curso {{curso_nome}} ({{datas}}).\nVagas disponíveis: {{vagas}}\nGaranta sua vaga em {{link}}";
        $eventoCriadoConteudo = "Olá {{aluno_nome}}!\nO Sindicato Rural de Miranda e Bodoquena informa a abertura de um novo curso. Confira os detalhes abaixo:\n\nCurso: {{curso_nome}}\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}\nVagas disponíveis: {{vagas}}\nGaranta sua vaga: {{link}}";
        $inscricaoConfirmarConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} precisa ser confirmada.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}\nConfirme sua participação: {{link}}";
        $inscricaoCanceladaConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} foi cancelada.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}";
        $eventoCanceladoConteudo = "Olá {{aluno_nome}},\n\nO evento do curso {{curso_nome}} foi cancelado.\nDatas: {{datas}}\nHorário: {{horario}}\nCarga horária: {{carga_horaria}}\nTurno: {{turno}}";
        return [
            ['type' => NotificationType::CURSO_DISPONIVEL->value, 'canal' => 'email', 'subject' => 'Curso disponível: {{curso_nome}}', 'content' => $baseConteudo],
            ['type' => NotificationType::CURSO_DISPONIVEL->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => $baseConteudo],
            ['type' => LegacyNotificationType::EVENTO_CRIADO, 'canal' => 'email', 'subject' => 'Novo curso disponível: {{curso_nome}}', 'content' => $eventoCriadoConteudo],
            ['type' => LegacyNotificationType::EVENTO_CRIADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCriadoConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'email', 'subject' => 'Confirme sua inscrição: {{curso_nome}}', 'content' => $inscricaoConfirmarConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoConfirmarConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CANCELADA, 'canal' => 'email', 'subject' => 'Inscrição cancelada: {{curso_nome}}', 'content' => $inscricaoCanceladaConteudo],
            ['type' => LegacyNotificationType::INSCRICAO_CANCELADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoCanceladaConteudo],
            ['type' => LegacyNotificationType::EVENTO_CANCELADO, 'canal' => 'email', 'subject' => 'Evento cancelado: {{curso_nome}}', 'content' => $eventoCanceladoConteudo],
            ['type' => LegacyNotificationType::EVENTO_CANCELADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCanceladoConteudo],
            ['type' => NotificationType::VAGA_ABERTA->value, 'canal' => 'email', 'subject' => 'Vaga aberta em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nUma nova vaga foi aberta em {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::VAGA_ABERTA->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Vaga aberta: {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => LegacyNotificationType::LEMBRETE_CURSO, 'canal' => 'email', 'subject' => 'Lembrete do curso {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nLembrete: o curso {{curso_nome}} começa em {{datas}}. Confira {{link}}"],
            ['type' => LegacyNotificationType::LEMBRETE_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lembrete: {{curso_nome}} em {{datas}}. {{link}}"],
            ['type' => LegacyNotificationType::MATRICULA_CONFIRMADA, 'canal' => 'email', 'subject' => 'Matrícula confirmada em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nSua matrícula no curso {{curso_nome}} foi confirmada. Veja detalhes em {{link}}"],
            ['type' => LegacyNotificationType::MATRICULA_CONFIRMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Matrícula confirmada: {{curso_nome}}. {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA->value, 'canal' => 'email', 'subject' => 'Lista de espera para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA->value, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera: {{curso_nome}}. {{link}}"],
            ['type' => LegacyNotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'email', 'subject' => 'Lista de espera chamada para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => LegacyNotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera chamada: {{curso_nome}}. {{link}}"],
        ];
    }

    /**
     * @return list<string>
     */
    private function getTemplateTypeValues(): array
    {
        return array_values(array_unique(array_merge(
            NotificationType::values(),
            LegacyNotificationType::values()
        )));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function getTemplateTypeOptions(): array
    {
        $legacyLabels = [
            LegacyNotificationType::EVENTO_CRIADO => 'Evento criado',
            LegacyNotificationType::EVENTO_CANCELADO => 'Evento cancelado',
            LegacyNotificationType::INSCRICAO_CONFIRMAR => 'Confirmação de inscrição',
            LegacyNotificationType::INSCRICAO_CANCELADA => 'Inscrição cancelada',
            LegacyNotificationType::LEMBRETE_CURSO => 'Lembrete de curso',
            LegacyNotificationType::MATRICULA_CONFIRMADA => 'Matrícula confirmada',
            LegacyNotificationType::LISTA_ESPERA_CHAMADA => 'Lista de espera chamada',
        ];

        return array_map(function (string $value) use ($legacyLabels): array {
            $officialType = NotificationType::tryFrom($value);

            if ($officialType !== null) {
                return [
                    'value' => $value,
                    'label' => $officialType->label(),
                ];
            }

            return [
                'value' => $value,
                'label' => $legacyLabels[$value] ?? $value,
            ];
        }, $this->getTemplateTypeValues());
    }

    /**
     * @return list<array{variable: string, description: string}>
     */
    private function getTemplateVariables(): array
    {
        return [
            ['variable' => '{{aluno_nome}}', 'description' => 'Nome do aluno'],
            ['variable' => '{{curso_nome}}', 'description' => 'Nome do curso'],
            ['variable' => '{{datas}}', 'description' => 'Datas do curso/evento'],
            ['variable' => '{{horario}}', 'description' => 'Horário'],
            ['variable' => '{{carga_horaria}}', 'description' => 'Carga horária'],
            ['variable' => '{{turno}}', 'description' => 'Turno'],
            ['variable' => '{{vagas}}', 'description' => 'Quantidade de vagas'],
            ['variable' => '{{link}}', 'description' => 'Link da inscrição/detalhes'],
        ];
    }
}
