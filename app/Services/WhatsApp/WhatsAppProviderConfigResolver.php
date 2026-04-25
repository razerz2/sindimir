<?php

namespace App\Services\WhatsApp;

use App\Services\ConfiguracaoService;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;

class WhatsAppProviderConfigResolver
{
    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function resolveNotificationProvider(): string
    {
        return $this->normalizeProvider((string) $this->configuracaoService->get('whatsapp.provedor', ''));
    }

    public function resolveBotProvider(): string
    {
        return $this->normalizeProvider((string) $this->configuracaoService->get('bot.provider', 'meta'));
    }

    public function resolveNotificationConfig(string $provider): WhatsAppProviderConfig
    {
        return match ($this->normalizeProvider($provider)) {
            'meta' => new WhatsAppProviderConfig('meta', [
                'scope' => 'notification',
                'base_url' => rtrim((string) config('services.whatsapp.meta.base_url'), '/'),
                'token' => (string) ($this->configuracaoService->get('whatsapp.token')
                    ?? config('services.whatsapp.meta.token')),
                'phone_number_id' => (string) ($this->configuracaoService->get('whatsapp.phone_number_id')
                    ?? config('services.whatsapp.meta.phone_number_id')),
                'stored_token' => (string) ($this->configuracaoService->get('whatsapp.token') ?? ''),
                'stored_phone_number_id' => (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? ''),
                'verify_ssl' => (bool) config('services.whatsapp.meta.verify_ssl', true),
            ]),
            'zapi' => new WhatsAppProviderConfig('zapi', [
                'scope' => 'notification',
                'base_url' => rtrim((string) ($this->configuracaoService->get('whatsapp.base_url')
                    ?? config('services.whatsapp.zapi.base_url')), '/'),
                'token' => (string) ($this->configuracaoService->get('whatsapp.token')
                    ?? config('services.whatsapp.zapi.token')),
                'client_token' => (string) ($this->configuracaoService->get('whatsapp.client_token')
                    ?? config('services.whatsapp.zapi.client_token')),
                'instance' => (string) ($this->configuracaoService->get('whatsapp.instance')
                    ?? config('services.whatsapp.zapi.instance')),
                'stored_token' => (string) ($this->configuracaoService->get('whatsapp.token') ?? ''),
                'stored_client_token' => (string) ($this->configuracaoService->get('whatsapp.client_token') ?? ''),
                'verify_ssl' => (bool) config('services.whatsapp.zapi.verify_ssl', true),
                'link_title' => (string) ($this->configuracaoService->get('sistema.nome') ?? config('app.name')),
                'link_description' => 'Acesse o link para mais detalhes.',
                'link_image_url' => $this->resolveThemeLogoUrl(),
                'include_link_image' => true,
                'status_enabled' => (bool) config('services.whatsapp.zapi.enabled', false),
            ]),
            'waha' => new WhatsAppProviderConfig('waha', [
                'scope' => 'notification',
                'base_url' => rtrim((string) ($this->configuracaoService->get('whatsapp.waha_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: config('services.whatsapp.waha.base_url')), '/'),
                'session' => trim((string) ($this->configuracaoService->get('whatsapp.waha_session')
                    ?: $this->configuracaoService->get('whatsapp.instance')
                    ?: config('services.whatsapp.waha.session', 'default'))),
                'api_key' => trim((string) ($this->configuracaoService->get('whatsapp.waha_api_key')
                    ?: $this->configuracaoService->get('whatsapp.token')
                    ?: config('services.whatsapp.waha.api_key'))),
                'api_key_header' => trim((string) ($this->configuracaoService->get('whatsapp.waha_api_key_header')
                    ?: config('services.whatsapp.waha.api_key_header', 'X-Api-Key'))),
                'stored_base_url' => trim((string) ($this->configuracaoService->get('whatsapp.waha_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: '')),
                'stored_session' => trim((string) ($this->configuracaoService->get('whatsapp.waha_session')
                    ?: $this->configuracaoService->get('whatsapp.instance')
                    ?: '')),
                'stored_api_key' => trim((string) ($this->configuracaoService->get('whatsapp.waha_api_key')
                    ?: $this->configuracaoService->get('whatsapp.token')
                    ?: '')),
                'verify_ssl' => (bool) config('services.whatsapp.waha.verify_ssl', true),
                'status_enabled' => (bool) config('services.whatsapp.waha.status_enabled', true),
            ]),
            'evolution' => new WhatsAppProviderConfig('evolution', [
                'scope' => 'notification',
                'base_url' => rtrim((string) ($this->configuracaoService->get('whatsapp.evolution_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: config('services.whatsapp.evolution.base_url')), '/'),
                'instance' => trim((string) ($this->configuracaoService->get('whatsapp.evolution_instance')
                    ?: $this->configuracaoService->get('whatsapp.instance')
                    ?: config('services.whatsapp.evolution.instance'))),
                'api_key' => trim((string) ($this->configuracaoService->get('whatsapp.evolution_apikey')
                    ?: $this->configuracaoService->get('whatsapp.token')
                    ?: config('services.whatsapp.evolution.apikey'))),
                'stored_base_url' => trim((string) ($this->configuracaoService->get('whatsapp.evolution_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: '')),
                'stored_instance' => trim((string) ($this->configuracaoService->get('whatsapp.evolution_instance')
                    ?: $this->configuracaoService->get('whatsapp.instance')
                    ?: '')),
                'stored_api_key' => trim((string) ($this->configuracaoService->get('whatsapp.evolution_apikey')
                    ?: $this->configuracaoService->get('whatsapp.token')
                    ?: '')),
                'verify_ssl' => (bool) config('services.whatsapp.evolution.verify_ssl', true),
                'status_enabled' => (bool) config('services.whatsapp.evolution.status_enabled', true),
            ]),
            default => new WhatsAppProviderConfig($this->normalizeProvider($provider), []),
        };
    }

    public function resolveBotConfig(string $provider): WhatsAppProviderConfig
    {
        $provider = $this->normalizeProvider($provider);
        $mode = $this->resolveBotCredentialsMode();

        if ($mode === 'custom') {
            return $this->resolveCustomBotConfig($provider);
        }

        $notificationConfig = $this->resolveNotificationConfig($provider)->all();
        $notificationConfig['scope'] = 'bot';
        $notificationConfig['include_link_image'] = false;

        if ($provider === 'waha') {
            $notificationConfig = $this->applyInheritedWahaFallback($notificationConfig);
        }

        return new WhatsAppProviderConfig($provider, $notificationConfig);
    }

    public function resolveStatusConfig(string $provider): WhatsAppProviderConfig
    {
        $provider = $this->normalizeProvider($provider);

        if (in_array($provider, ['zapi', 'waha', 'evolution'], true)) {
            $statusConfig = $this->resolveNotificationConfig($provider)->all();
            $statusConfig['scope'] = 'status';

            return new WhatsAppProviderConfig($provider, $statusConfig);
        }

        return new WhatsAppProviderConfig($provider, []);
    }

    private function resolveCustomBotConfig(string $provider): WhatsAppProviderConfig
    {
        return match ($provider) {
            'meta' => new WhatsAppProviderConfig('meta', [
                'scope' => 'bot',
                'base_url' => rtrim((string) config('services.whatsapp.meta.base_url'), '/'),
                'token' => trim((string) $this->configuracaoService->get('bot.meta_access_token', '')),
                'phone_number_id' => trim((string) $this->configuracaoService->get('bot.meta_phone_number_id', '')),
                'verify_ssl' => (bool) config('services.whatsapp.meta.verify_ssl', true),
            ]),
            'zapi' => new WhatsAppProviderConfig('zapi', [
                'scope' => 'bot',
                'base_url' => rtrim((string) ($this->configuracaoService->get('bot.zapi_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: config('services.whatsapp.zapi.base_url')), '/'),
                'instance' => trim((string) $this->configuracaoService->get('bot.zapi_instance_id', '')),
                'token' => trim((string) $this->configuracaoService->get('bot.zapi_token', '')),
                'client_token' => $this->resolveCustomBotZapiClientToken(),
                'verify_ssl' => (bool) config('services.whatsapp.zapi.verify_ssl', true),
                'link_title' => (string) ($this->configuracaoService->get('sistema.nome') ?? config('app.name')),
                'link_description' => 'Acesse o link para mais detalhes.',
                'include_link_image' => false,
            ]),
            'waha' => new WhatsAppProviderConfig('waha', [
                'scope' => 'bot',
                'base_url' => rtrim((string) ($this->configuracaoService->get('bot.waha_base_url')
                    ?: config('services.whatsapp.waha.base_url')), '/'),
                'session' => trim((string) ($this->configuracaoService->get('bot.waha_session')
                    ?: config('services.whatsapp.waha.session', 'default'))),
                'api_key' => trim((string) $this->configuracaoService->get('bot.waha_api_key', '')),
                'api_key_header' => trim((string) ($this->configuracaoService->get('bot.waha_api_key_header')
                    ?: config('services.whatsapp.waha.api_key_header', 'X-Api-Key'))),
                'verify_ssl' => (bool) config('services.whatsapp.waha.verify_ssl', true),
                'status_enabled' => (bool) config('services.whatsapp.waha.status_enabled', true),
            ]),
            'evolution' => new WhatsAppProviderConfig('evolution', [
                'scope' => 'bot',
                'base_url' => rtrim((string) ($this->configuracaoService->get('bot.evolution_base_url')
                    ?: config('services.whatsapp.evolution.base_url')), '/'),
                'instance' => trim((string) ($this->configuracaoService->get('bot.evolution_instance')
                    ?: config('services.whatsapp.evolution.instance'))),
                'api_key' => trim((string) ($this->configuracaoService->get('bot.evolution_apikey')
                    ?: config('services.whatsapp.evolution.apikey'))),
                'verify_ssl' => (bool) config('services.whatsapp.evolution.verify_ssl', true),
                'status_enabled' => (bool) config('services.whatsapp.evolution.status_enabled', true),
            ]),
            default => new WhatsAppProviderConfig($provider, []),
        };
    }

    private function resolveCustomBotZapiClientToken(): string
    {
        $token = trim((string) $this->configuracaoService->get('bot.zapi_token', ''));
        $clientToken = trim((string) $this->configuracaoService->get('bot.zapi_client_token', ''));

        return $clientToken !== '' ? $clientToken : $token;
    }

    private function resolveBotCredentialsMode(): string
    {
        $mode = mb_strtolower(trim((string) $this->configuracaoService->get(
            'bot.credentials_mode',
            'inherit_notifications'
        )));

        return in_array($mode, ['inherit_notifications', 'custom'], true)
            ? $mode
            : 'inherit_notifications';
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function applyInheritedWahaFallback(array $config): array
    {
        $baseUrl = trim((string) ($config['base_url'] ?? ''));
        if ($baseUrl === '') {
            $baseUrl = trim((string) ($this->configuracaoService->get('whatsapp.waha_base_url')
                ?: $this->configuracaoService->get('whatsapp.base_url')
                ?: config('services.whatsapp.waha.base_url', '')));
        }

        $session = trim((string) ($config['session'] ?? ''));
        if ($session === '') {
            $session = trim((string) ($this->configuracaoService->get('whatsapp.waha_session')
                ?: $this->configuracaoService->get('whatsapp.instance')
                ?: config('services.whatsapp.waha.session', 'default')));
        }

        $config['base_url'] = rtrim($baseUrl, '/');
        $config['session'] = $session;

        return $config;
    }

    private function resolveThemeLogoUrl(): string
    {
        $logoPath = (string) ($this->configuracaoService->get('tema.logo') ?? '');
        if ($logoPath === '') {
            return '';
        }

        return url($logoPath);
    }

    private function normalizeProvider(string $provider): string
    {
        return mb_strtolower(trim($provider));
    }
}
