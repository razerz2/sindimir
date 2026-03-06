<?php

namespace App\Services\WhatsApp;

use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZApiStatusService
{
    private const CACHE_PREFIX = 'whatsapp.zapi.status';
    private const CACHE_TTL_SECONDS = 45;

    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function canSend(): bool
    {
        return $this->getStatus()['can_send'];
    }

    /**
     * @return array{can_send: bool, applies: bool, reason: ?string}
     */
    public function getStatus(): array
    {
        if (! $this->shouldCheckStatus()) {
            return [
                'can_send' => true,
                'applies' => false,
                'reason' => null,
            ];
        }

        $config = $this->resolveConfig();
        if ($config === null) {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => 'Configuração Z-API incompleta.',
            ];
        }

        $cacheKey = $this->buildCacheKey($config);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($config) {
            $endpoint = "{$config['base_url']}/instances/{$config['instance']}/token/{$config['token']}/status";

            $http = Http::withHeaders([
                'Client-Token' => $config['client_token'],
                'Token' => $config['token'],
                'Content-Type' => 'application/json',
            ]);

            if (! $config['verify_ssl']) {
                $http = $http->withoutVerifying();
            }

            try {
                $response = $http->get($endpoint);
            } catch (\Throwable) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da Z-API.',
                ];
            }

            if (! $response->successful()) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da Z-API.',
                ];
            }

            $payload = $response->json() ?? [];
            $normalized = $this->normalizeStatus($payload);

            if ($normalized['connected'] && $normalized['state'] === 'CONNECTED') {
                return [
                    'can_send' => true,
                    'applies' => true,
                    'reason' => null,
                ];
            }

            return [
                'can_send' => false,
                'applies' => true,
                'reason' => 'Instância Z-API desconectada.',
            ];
        });
    }

    private function shouldCheckStatus(): bool
    {
        if (! (bool) config('services.whatsapp.zapi.enabled')) {
            return false;
        }

        return $this->getActiveProvider() === 'zapi';
    }

    /**
     * @return array{base_url: string, token: string, client_token: string, instance: string, verify_ssl: bool}|null
     */
    private function resolveConfig(): ?array
    {
        $baseUrl = rtrim((string) ($this->configuracaoService->get('whatsapp.base_url')
            ?? config('services.whatsapp.zapi.base_url')), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.zapi.token'));
        $clientToken = (string) ($this->configuracaoService->get('whatsapp.client_token') ?? config('services.whatsapp.zapi.client_token'));
        $instance = (string) ($this->configuracaoService->get('whatsapp.instance')
            ?? config('services.whatsapp.zapi.instance'));
        $verifySsl = (bool) config('services.whatsapp.zapi.verify_ssl', true);

        if ($baseUrl === '' || $token === '' || $clientToken === '' || $instance === '') {
            return null;
        }

        return [
            'base_url' => $baseUrl,
            'token' => $token,
            'client_token' => $clientToken,
            'instance' => $instance,
            'verify_ssl' => $verifySsl,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{connected: bool, state: string}
     */
    private function normalizeStatus(array $payload): array
    {
        $connected = (bool) data_get($payload, 'connected', data_get($payload, 'data.connected', false));
        $state = strtoupper((string) (data_get($payload, 'state') ?? data_get($payload, 'data.state', '')));

        return [
            'connected' => $connected,
            'state' => $state,
        ];
    }

    private function getActiveProvider(): string
    {
        $provider = $this->configuracaoService->get('whatsapp.provedor');

        if (! in_array($provider, ['zapi', 'meta'], true)) {
            return '';
        }

        return $provider;
    }

    /**
     * @param array{base_url: string, token: string, client_token: string, instance: string, verify_ssl: bool} $config
     */
    private function buildCacheKey(array $config): string
    {
        $suffix = md5(implode('|', [
            $config['base_url'],
            $config['instance'],
            $config['token'],
            $config['client_token'],
        ]));

        return self::CACHE_PREFIX . ':' . $suffix;
    }
}
