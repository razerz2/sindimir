<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\NotificationType;
use App\Models\NotificationTemplate;
use App\Services\ConfiguracaoService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly ThemeService $themeService
    ) {
    }

    public function index(): View
    {
        $settings = [
            'sistema_nome' => $this->configuracaoService->get('sistema.nome', config('app.name', 'Sindimir')),
            'sistema_email' => $this->configuracaoService->get('sistema.email_padrao', config('mail.from.address')),
            'sistema_ativo' => (bool) $this->configuracaoService->get('sistema.ativo', true),
            'notificacao_email_ativo' => (bool) $this->configuracaoService->get('notificacao.email_ativo', true),
            'notificacao_whatsapp_ativo' => (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false),
            'tema_cor_destaque' => $this->configuracaoService->get('tema.cor_destaque', null),
            'whatsapp_provedor' => $this->configuracaoService->get('whatsapp.provedor', null),
            'whatsapp_token' => $this->configuracaoService->get('whatsapp.token', null),
            'whatsapp_phone_number_id' => $this->configuracaoService->get('whatsapp.phone_number_id', null),
            'whatsapp_webhook_url' => $this->configuracaoService->get('whatsapp.webhook_url', null),
            'smtp_host' => $this->configuracaoService->get('smtp.host', config('mail.mailers.smtp.host')),
            'smtp_port' => $this->configuracaoService->get('smtp.port', config('mail.mailers.smtp.port')),
            'smtp_username' => $this->configuracaoService->get('smtp.username', config('mail.mailers.smtp.username')),
            'smtp_password' => $this->configuracaoService->get('smtp.password', null),
            'smtp_encryption' => $this->configuracaoService->get('smtp.encryption', config('mail.mailers.smtp.encryption')),
            'smtp_from_email' => $this->configuracaoService->get('smtp.from_email', config('mail.from.address')),
            'smtp_from_name' => $this->configuracaoService->get('smtp.from_name', config('mail.from.name')),
            'auto_lembrete_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.ativo', true),
            'auto_lembrete_email' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.canal.email', true),
            'auto_lembrete_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.lembrete.canal.whatsapp', false),
            'auto_lembrete_dias_antes' => (int) $this->configuracaoService->get('notificacao.auto.lembrete.dias_antes', 2),
            'auto_lembrete_horario' => $this->configuracaoService->get('notificacao.auto.lembrete.horario', '08:00'),
            'auto_matricula_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.ativo', true),
            'auto_matricula_email' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.email', true),
            'auto_matricula_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.matricula_confirmada.canal.whatsapp', false),
            'auto_vaga_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.ativo', true),
            'auto_vaga_email' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.email', true),
            'auto_vaga_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.lista_espera.canal.whatsapp', false),
            'auto_vaga_tempo_limite' => (int) $this->configuracaoService->get('notificacao.auto.lista_espera.tempo_limite_horas', 24),
            'auto_curso_ativo' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.ativo', false),
            'auto_curso_email' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.canal.email', true),
            'auto_curso_whatsapp' => (bool) $this->configuracaoService->get('notificacao.auto.curso_disponivel.canal.whatsapp', false),
        ];

        $theme = $this->themeService->getThemeColors();
        $templates = collect();

        if (Schema::hasTable('notification_templates')) {
            $templates = NotificationTemplate::query()
                ->orderBy('notification_type')
                ->orderBy('canal')
                ->get()
                ->groupBy('notification_type');
        }

        $whatsappStatus = ($settings['whatsapp_provedor'] && $settings['whatsapp_token'])
            ? 'Ativo'
            : 'Pendente';

        return view('admin.configuracoes.index', compact('settings', 'theme', 'whatsappStatus', 'templates'));
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
            'notificacao_email_ativo' => ['nullable', 'boolean'],
            'notificacao_whatsapp_ativo' => ['nullable', 'boolean'],
            'whatsapp_provedor' => ['nullable', 'string', 'in:meta,zapi'],
            'whatsapp_token' => ['nullable', 'string', 'max:200'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:120'],
            'whatsapp_webhook_url' => ['nullable', 'url', 'max:200'],
            'smtp_host' => ['nullable', 'string', 'max:120'],
            'smtp_port' => ['nullable', 'numeric'],
            'smtp_username' => ['nullable', 'string', 'max:120'],
            'smtp_password' => ['nullable', 'string', 'max:200'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'smtp_from_email' => ['nullable', 'email', 'max:120'],
            'smtp_from_name' => ['nullable', 'string', 'max:120'],
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
            'auto_matricula_ativo' => ['nullable', 'boolean'],
            'auto_matricula_email' => ['nullable', 'boolean'],
            'auto_matricula_whatsapp' => ['nullable', 'boolean'],
            'auto_vaga_ativo' => ['nullable', 'boolean'],
            'auto_vaga_email' => ['nullable', 'boolean'],
            'auto_vaga_whatsapp' => ['nullable', 'boolean'],
            'auto_vaga_tempo_limite' => ['nullable', 'integer', 'min:1', 'max:168'],
            'auto_curso_ativo' => ['nullable', 'boolean'],
            'auto_curso_email' => ['nullable', 'boolean'],
            'auto_curso_whatsapp' => ['nullable', 'boolean'],
        ]);

        $this->configuracaoService->set('sistema.nome', $data['sistema_nome'], 'Nome do sistema');
        $this->configuracaoService->set('sistema.email_padrao', $data['sistema_email'] ?? null, 'Email padrao');
        $this->configuracaoService->set('sistema.ativo', (bool) $request->boolean('sistema_ativo'), 'Sistema ativo');
        $this->configuracaoService->set('tema.cor_primaria', $data['tema_cor_primaria'], 'Cor primaria do tema');
        $this->configuracaoService->set('tema.cor_secundaria', $data['tema_cor_secundaria'], 'Cor secundaria do tema');
        $temaDestaque = (string) ($data['tema_cor_destaque'] ?? '');
        $this->configuracaoService->set('tema.cor_destaque', $temaDestaque, 'Cor de destaque');
        $this->configuracaoService->set('notificacao.email_ativo', (bool) $request->boolean('notificacao_email_ativo'), 'Notificacao por email');
        $this->configuracaoService->set('notificacao.whatsapp_ativo', (bool) $request->boolean('notificacao_whatsapp_ativo'), 'Notificacao por WhatsApp');
        $whatsappProvedor = (string) ($data['whatsapp_provedor'] ?? '');
        $whatsappToken = (string) ($data['whatsapp_token'] ?? '');
        $whatsappPhoneId = (string) ($data['whatsapp_phone_number_id'] ?? '');
        $whatsappWebhook = (string) ($data['whatsapp_webhook_url'] ?? '');

        $this->configuracaoService->set('whatsapp.provedor', $whatsappProvedor, 'Provedor WhatsApp');
        $this->configuracaoService->set('whatsapp.token', $whatsappToken, 'Token WhatsApp');
        $this->configuracaoService->set('whatsapp.phone_number_id', $whatsappPhoneId, 'WhatsApp Phone Number ID');
        $this->configuracaoService->set('whatsapp.webhook_url', $whatsappWebhook, 'WhatsApp Webhook URL');
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

        $this->configuracaoService->set('notificacao.auto.lembrete.ativo', (bool) $request->boolean('auto_lembrete_ativo'), 'Auto lembrete ativo');
        $this->configuracaoService->set('notificacao.auto.lembrete.canal.email', (bool) $request->boolean('auto_lembrete_email'), 'Auto lembrete email');
        $this->configuracaoService->set('notificacao.auto.lembrete.canal.whatsapp', (bool) $request->boolean('auto_lembrete_whatsapp'), 'Auto lembrete WhatsApp');
        $this->configuracaoService->set('notificacao.auto.lembrete.dias_antes', (int) ($data['auto_lembrete_dias_antes'] ?? 2), 'Auto lembrete dias antes');
        $this->configuracaoService->set('notificacao.auto.lembrete.horario', (string) ($data['auto_lembrete_horario'] ?? '08:00'), 'Auto lembrete horario');

        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.ativo', (bool) $request->boolean('auto_matricula_ativo'), 'Auto matricula confirmada ativo');
        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.canal.email', (bool) $request->boolean('auto_matricula_email'), 'Auto matricula email');
        $this->configuracaoService->set('notificacao.auto.matricula_confirmada.canal.whatsapp', (bool) $request->boolean('auto_matricula_whatsapp'), 'Auto matricula WhatsApp');

        $this->configuracaoService->set('notificacao.auto.lista_espera.ativo', (bool) $request->boolean('auto_vaga_ativo'), 'Auto lista espera ativo');
        $this->configuracaoService->set('notificacao.auto.lista_espera.canal.email', (bool) $request->boolean('auto_vaga_email'), 'Auto lista espera email');
        $this->configuracaoService->set('notificacao.auto.lista_espera.canal.whatsapp', (bool) $request->boolean('auto_vaga_whatsapp'), 'Auto lista espera WhatsApp');
        $this->configuracaoService->set('notificacao.auto.lista_espera.tempo_limite_horas', (int) ($data['auto_vaga_tempo_limite'] ?? 24), 'Auto lista espera tempo limite');

        $this->configuracaoService->set('notificacao.auto.curso_disponivel.ativo', (bool) $request->boolean('auto_curso_ativo'), 'Auto curso disponivel ativo');
        $this->configuracaoService->set('notificacao.auto.curso_disponivel.canal.email', (bool) $request->boolean('auto_curso_email'), 'Auto curso disponivel email');
        $this->configuracaoService->set('notificacao.auto.curso_disponivel.canal.whatsapp', (bool) $request->boolean('auto_curso_whatsapp'), 'Auto curso disponivel WhatsApp');

        $this->syncNotificationTemplates($data['templates'] ?? [], $data['template_type'] ?? null);

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', 'Configuracoes atualizadas com sucesso.');
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
}
