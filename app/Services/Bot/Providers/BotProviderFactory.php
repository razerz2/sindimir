<?php

namespace App\Services\Bot\Providers;

use App\Services\ConfiguracaoService;
use InvalidArgumentException;

class BotProviderFactory
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService
    ) {
    }

    public function make(string $channel): BotProviderInterface
    {
        $channel = mb_strtolower(trim($channel));
        $credentials = $this->resolveCredentials($channel);

        return match ($channel) {
            'meta' => new MetaBotProvider($this->configuracaoService, $credentials),
            'zapi' => new ZapiBotProvider($this->configuracaoService, $credentials),
            default => throw new InvalidArgumentException('Canal de bot invalido: ' . $channel),
        };
    }

    /**
     * @return array<string, string>
     */
    private function resolveCredentials(string $channel): array
    {
        $mode = mb_strtolower(trim((string) $this->configuracaoService->get(
            'bot.credentials_mode',
            'inherit_notifications'
        )));

        if ($mode === 'custom') {
            return $this->resolveCustomCredentials($channel);
        }

        return $this->resolveNotificationCredentials($channel);
    }

    /**
     * @return array<string, string>
     */
    private function resolveNotificationCredentials(string $channel): array
    {
        if ($channel === 'meta') {
            return [
                'base_url' => (string) config('services.whatsapp.meta.base_url'),
                'phone_number_id' => (string) ($this->configuracaoService->get('whatsapp.phone_number_id')
                    ?? config('services.whatsapp.meta.phone_number_id')),
                'access_token' => (string) ($this->configuracaoService->get('whatsapp.token')
                    ?? config('services.whatsapp.meta.token')),
            ];
        }

        if ($channel === 'zapi') {
            return [
                'base_url' => (string) ($this->configuracaoService->get('whatsapp.base_url')
                    ?? config('services.whatsapp.zapi.base_url')),
                'instance' => (string) ($this->configuracaoService->get('whatsapp.instance')
                    ?? config('services.whatsapp.zapi.instance')),
                'token' => (string) ($this->configuracaoService->get('whatsapp.token')
                    ?? config('services.whatsapp.zapi.token')),
                'client_token' => (string) ($this->configuracaoService->get('whatsapp.client_token')
                    ?? config('services.whatsapp.zapi.client_token')),
            ];
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function resolveCustomCredentials(string $channel): array
    {
        if ($channel === 'meta') {
            return [
                'base_url' => (string) config('services.whatsapp.meta.base_url'),
                'phone_number_id' => trim((string) $this->configuracaoService->get('bot.meta_phone_number_id', '')),
                'access_token' => trim((string) $this->configuracaoService->get('bot.meta_access_token', '')),
            ];
        }

        if ($channel === 'zapi') {
            $zapiToken = trim((string) $this->configuracaoService->get('bot.zapi_token', ''));
            $zapiClientToken = trim((string) $this->configuracaoService->get('bot.zapi_client_token', ''));

            return [
                'base_url' => (string) ($this->configuracaoService->get('bot.zapi_base_url')
                    ?: $this->configuracaoService->get('whatsapp.base_url')
                    ?: config('services.whatsapp.zapi.base_url')),
                'instance' => trim((string) $this->configuracaoService->get('bot.zapi_instance_id', '')),
                'token' => $zapiToken,
                'client_token' => $zapiClientToken !== '' ? $zapiClientToken : $zapiToken,
            ];
        }

        return [];
    }
}
