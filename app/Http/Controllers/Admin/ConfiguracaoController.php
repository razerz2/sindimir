<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use App\Services\ConfiguracaoService;
use App\Services\WhatsAppService;
use App\Services\ThemeService;
use App\Services\SiteSectionService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly ThemeService $themeService,
        private readonly WhatsAppService $whatsAppService
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
                'Solucoes digitais para capacitacao, eventos e desenvolvimento do setor metal mecanico.'
            ),
            'footer_contato_titulo' => $this->configuracaoService->get('site.footer.contato_titulo', 'Contato'),
            'footer_contato_email' => $this->configuracaoService->get('site.footer.contato_email', 'contato@sindimir.org'),
            'footer_contato_telefone' => $this->configuracaoService->get('site.footer.contato_telefone', '(00) 0000-0000'),
            'footer_endereco_titulo' => $this->configuracaoService->get('site.footer.endereco_titulo', 'Endereco'),
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
            'rate_limit_ativo' => (bool) $this->configuracaoService->get('notificacao.rate_limit.ativo', true),
            'rate_limit_limite_diario' => (int) $this->configuracaoService->get('notificacao.rate_limit.limite_diario', 2),
        ];

        $theme = $this->themeService->getThemeColors();
        $templates = collect();
        $templateDefaults = $this->buildTemplateDefaultsPayload();

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

        return view('admin.configuracoes.index', compact(
            'settings',
            'theme',
            'whatsappStatus',
            'templates',
            'whatsappReady',
            'templateDefaults'
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
            'templates' => ['array'],
            'templates.*.email.assunto' => ['nullable', 'string', 'max:200'],
            'templates.*.email.conteudo' => ['nullable', 'string', 'max:4000'],
            'templates.*.email.ativo' => ['nullable', 'boolean'],
            'templates.*.whatsapp.conteudo' => ['nullable', 'string', 'max:4000'],
            'templates.*.whatsapp.ativo' => ['nullable', 'boolean'],
            'template_type' => ['nullable', 'string'],
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
            'rate_limit_ativo' => ['nullable', 'boolean'],
            'rate_limit_limite_diario' => ['nullable', 'integer', 'min:1', 'max:100'],
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

        $this->configuracaoService->set('notificacao.rate_limit.ativo', (bool) $request->boolean('rate_limit_ativo'), 'Rate limit de notificacoes');
        $this->configuracaoService->set(
            'notificacao.rate_limit.limite_diario',
            (int) ($data['rate_limit_limite_diario'] ?? 2),
            'Rate limit diario de notificacoes'
        );

        $this->syncNotificationTemplates($data['templates'] ?? [], $data['template_type'] ?? null);
        Cache::forget(SiteSectionService::CACHE_KEY);

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', 'Configuracoes atualizadas com sucesso.');
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

    private function syncNotificationTemplates(array $templates, ?string $onlyType = null): void
    {
        $types = NotificationType::cases();

        if ($onlyType) {
            $types = array_filter($types, fn (NotificationType $type) => $type->value === $onlyType);
        }

        foreach ($types as $type) {
            $typeKey = $type->value;
            $typeTemplates = $templates[$typeKey] ?? [];

            foreach (['email', 'whatsapp'] as $canal) {
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
                if ($typeKey === NotificationType::MATRICULA_CONFIRMADA->value) {
                    $conteudo = $this->sanitizeMatriculaTemplate($conteudo);
                } elseif ($typeKey === NotificationType::INSCRICAO_CONFIRMAR->value) {
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
                ->where('notification_type', $template['type']->value)
                ->where('canal', $template['canal']);
            $existing = $query->first();

            if (! $existing) {
                NotificationTemplate::create([
                    'notification_type' => $template['type']->value,
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
            $typeKey = $template['type']->value;
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
        $eventoCriadoConteudo = "Olá {{aluno_nome}}!\nO Sindicato Rural de Miranda e Bodoquena informa a abertura de um novo curso. Confira os detalhes abaixo:\n\nCurso: {{curso_nome}}\nDatas: {{datas}}\nHorario: {{horario}}\nCarga horaria: {{carga_horaria}}\nTurno: {{turno}}\nVagas disponíveis: {{vagas}}\nGaranta sua vaga: {{link}}";
        $inscricaoConfirmarConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} precisa ser confirmada.\nDatas: {{datas}}\nHorario: {{horario}}\nCarga horaria: {{carga_horaria}}\nTurno: {{turno}}\nConfirme sua participação: {{link}}";
        $inscricaoCanceladaConteudo = "Olá {{aluno_nome}},\n\nSua inscrição no curso {{curso_nome}} foi cancelada.\nDatas: {{datas}}\nHorario: {{horario}}\nCarga horaria: {{carga_horaria}}\nTurno: {{turno}}";
        $eventoCanceladoConteudo = "Olá {{aluno_nome}},\n\nO evento do curso {{curso_nome}} foi cancelado.\nDatas: {{datas}}\nHorario: {{horario}}\nCarga horaria: {{carga_horaria}}\nTurno: {{turno}}";

        return [
            ['type' => NotificationType::CURSO_DISPONIVEL, 'canal' => 'email', 'subject' => 'Curso disponível: {{curso_nome}}', 'content' => $baseConteudo],
            ['type' => NotificationType::CURSO_DISPONIVEL, 'canal' => 'whatsapp', 'subject' => null, 'content' => $baseConteudo],
            ['type' => NotificationType::EVENTO_CRIADO, 'canal' => 'email', 'subject' => 'Novo curso disponível: {{curso_nome}}', 'content' => $eventoCriadoConteudo],
            ['type' => NotificationType::EVENTO_CRIADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCriadoConteudo],
            ['type' => NotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'email', 'subject' => 'Confirme sua inscrição: {{curso_nome}}', 'content' => $inscricaoConfirmarConteudo],
            ['type' => NotificationType::INSCRICAO_CONFIRMAR, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoConfirmarConteudo],
            ['type' => NotificationType::INSCRICAO_CANCELADA, 'canal' => 'email', 'subject' => 'Inscrição cancelada: {{curso_nome}}', 'content' => $inscricaoCanceladaConteudo],
            ['type' => NotificationType::INSCRICAO_CANCELADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => $inscricaoCanceladaConteudo],
            ['type' => NotificationType::EVENTO_CANCELADO, 'canal' => 'email', 'subject' => 'Evento cancelado: {{curso_nome}}', 'content' => $eventoCanceladoConteudo],
            ['type' => NotificationType::EVENTO_CANCELADO, 'canal' => 'whatsapp', 'subject' => null, 'content' => $eventoCanceladoConteudo],
            ['type' => NotificationType::VAGA_ABERTA, 'canal' => 'email', 'subject' => 'Vaga aberta em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nUma nova vaga foi aberta em {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::VAGA_ABERTA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Vaga aberta: {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LEMBRETE_CURSO, 'canal' => 'email', 'subject' => 'Lembrete do curso {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nLembrete: o curso {{curso_nome}} começa em {{datas}}. Confira {{link}}"],
            ['type' => NotificationType::LEMBRETE_CURSO, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lembrete: {{curso_nome}} em {{datas}}. {{link}}"],
            ['type' => NotificationType::MATRICULA_CONFIRMADA, 'canal' => 'email', 'subject' => 'Matricula confirmada em {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nSua matricula no curso {{curso_nome}} foi confirmada. Veja detalhes em {{link}}"],
            ['type' => NotificationType::MATRICULA_CONFIRMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Matricula confirmada: {{curso_nome}}. {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'email', 'subject' => 'Lista de espera chamada para {{curso_nome}}', 'content' => "Olá {{aluno_nome}},\nVocê foi chamado da lista de espera para {{curso_nome}} ({{datas}}). Acesse {{link}}"],
            ['type' => NotificationType::LISTA_ESPERA_CHAMADA, 'canal' => 'whatsapp', 'subject' => null, 'content' => "Lista de espera chamada: {{curso_nome}}. {{link}}"],
        ];
    }
}
