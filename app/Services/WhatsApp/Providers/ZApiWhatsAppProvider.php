<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppHealthProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZApiWhatsAppProvider implements
    WhatsAppBotProviderInterface,
    WhatsAppHealthProviderInterface,
    WhatsAppNotificationProviderInterface
{
    private const STATUS_CACHE_PREFIX = 'whatsapp.zapi.status';
    private const STATUS_CACHE_TTL_SECONDS = 45;

    public function key(): string
    {
        return 'zapi';
    }

    public function canSend(WhatsAppProviderConfig $config): bool
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $token = $config->getString('token');
        $clientToken = $config->getString('client_token');
        $instance = $config->getString('instance');

        return $baseUrl !== '' && $token !== '' && $clientToken !== '' && $instance !== '';
    }

    public function canTestSend(WhatsAppProviderConfig $config): bool
    {
        $token = $config->getString('stored_token');
        $clientToken = $config->getString('stored_client_token');

        return $token !== '' && $clientToken !== '';
    }

    public function send(WhatsAppProviderConfig $config, string $to, string $message): array
    {
        $resolved = $this->resolveRequiredConfig($config);
        $baseUrl = $resolved['base_url'];
        $token = $resolved['token'];
        $clientToken = $resolved['client_token'];
        $instance = $resolved['instance'];
        $verifySsl = $resolved['verify_ssl'];

        $linkUrl = $this->extractLinkUrl($message);
        $endpoint = $linkUrl
            ? "{$baseUrl}/instances/{$instance}/token/{$token}/send-link"
            : "{$baseUrl}/instances/{$instance}/token/{$token}/send-text";

        $payload = [
            'phone' => $to,
            'message' => $message,
        ];

        if ($linkUrl !== null) {
            $payload['linkUrl'] = $linkUrl;
            $payload['title'] = $config->getString('link_title');
            $payload['linkDescription'] = $config->getString('link_description', 'Acesse o link para mais detalhes.');

            if ($config->getBool('include_link_image', false)) {
                $imageUrl = $config->getString('link_image_url');
                if ($imageUrl !== '') {
                    $payload['image'] = $imageUrl;
                }
            }
        }

        $http = Http::withHeaders([
            'Client-Token' => $clientToken,
            'Content-Type' => 'application/json',
        ]);

        if (! $verifySsl) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao enviar WhatsApp via Z-API.');
        }

        return [
            'provider' => $this->key(),
            'response' => $response->json() ?? ['body' => $response->body()],
        ];
    }

    public function sendBotText(WhatsAppProviderConfig $config, string $to, string $message): void
    {
        $this->send($config, $to, $message);
    }

    public function getHealthStatus(WhatsAppProviderConfig $config): array
    {
        if (! $config->getBool('status_enabled', false)) {
            return [
                'can_send' => true,
                'applies' => false,
                'reason' => null,
            ];
        }

        try {
            $resolved = $this->resolveRequiredConfig($config, includeMessageContext: false);
        } catch (RuntimeException $exception) {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => $exception->getMessage(),
            ];
        }

        $cacheKey = $this->buildStatusCacheKey($resolved);

        return Cache::remember($cacheKey, self::STATUS_CACHE_TTL_SECONDS, function () use ($resolved) {
            $endpoint = "{$resolved['base_url']}/instances/{$resolved['instance']}/token/{$resolved['token']}/status";

            $http = Http::withHeaders([
                'Client-Token' => $resolved['client_token'],
                'Token' => $resolved['token'],
                'Content-Type' => 'application/json',
            ]);

            if (! $resolved['verify_ssl']) {
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
            $connected = (bool) data_get($payload, 'connected', data_get($payload, 'data.connected', false));
            $state = strtoupper((string) (data_get($payload, 'state') ?? data_get($payload, 'data.state', '')));

            if ($connected && $state === 'CONNECTED') {
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

    /**
     * @return array{
     *     base_url: string,
     *     token: string,
     *     client_token: string,
     *     instance: string,
     *     verify_ssl: bool,
     *     link_title?: string,
     *     link_description?: string,
     *     link_image_url?: string,
     *     include_link_image?: bool
     * }
     */
    private function resolveRequiredConfig(WhatsAppProviderConfig $config, bool $includeMessageContext = true): array
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $token = $config->getString('token');
        $clientToken = $config->getString('client_token');
        $instance = $config->getString('instance');
        $verifySsl = $config->getBool('verify_ssl', true);

        if ($baseUrl === '' || $token === '' || $clientToken === '' || $instance === '') {
            $missing = [];
            if ($baseUrl === '') {
                $missing[] = 'base_url';
            }
            if ($instance === '') {
                $missing[] = 'instance';
            }
            if ($token === '') {
                $missing[] = 'token';
            }
            if ($clientToken === '') {
                $missing[] = 'client_token';
            }

            $details = $missing ? (' Campos ausentes: ' . implode(', ', $missing) . '.') : '';
            throw new RuntimeException('Configuração Z-API incompleta.' . $details);
        }

        $resolved = [
            'base_url' => $baseUrl,
            'token' => $token,
            'client_token' => $clientToken,
            'instance' => $instance,
            'verify_ssl' => $verifySsl,
        ];

        if (! $includeMessageContext) {
            return $resolved;
        }

        $resolved['link_title'] = $config->getString('link_title');
        $resolved['link_description'] = $config->getString('link_description', 'Acesse o link para mais detalhes.');
        $resolved['link_image_url'] = $config->getString('link_image_url');
        $resolved['include_link_image'] = $config->getBool('include_link_image', false);

        return $resolved;
    }

    private function extractLinkUrl(string $message): ?string
    {
        if (preg_match('~https?://\S+~i', $message, $match) !== 1) {
            return null;
        }

        return $match[0];
    }

    /**
     * @param array{
     *     base_url: string,
     *     token: string,
     *     client_token: string,
     *     instance: string
     * } $resolved
     */
    private function buildStatusCacheKey(array $resolved): string
    {
        $suffix = md5(implode('|', [
            $resolved['base_url'],
            $resolved['instance'],
            $resolved['token'],
            $resolved['client_token'],
        ]));

        return self::STATUS_CACHE_PREFIX . ':' . $suffix;
    }
}
