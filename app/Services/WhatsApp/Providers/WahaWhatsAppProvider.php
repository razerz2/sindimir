<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppHealthProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use App\Services\WhatsApp\WahaChatIdResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WahaWhatsAppProvider implements
    WhatsAppBotProviderInterface,
    WhatsAppHealthProviderInterface,
    WhatsAppNotificationProviderInterface
{
    private const STATUS_CACHE_PREFIX = 'whatsapp.waha.status';
    private const STATUS_CACHE_TTL_SECONDS = 45;

    public function __construct(private readonly WahaChatIdResolver $chatIdResolver)
    {
    }

    public function key(): string
    {
        return 'waha';
    }

    public function canSend(WhatsAppProviderConfig $config): bool
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $session = $config->getString('session');

        return $baseUrl !== '' && $session !== '';
    }

    public function canTestSend(WhatsAppProviderConfig $config): bool
    {
        $storedBaseUrl = rtrim($config->getString('stored_base_url'), '/');
        $storedSession = $config->getString('stored_session');

        return $storedBaseUrl !== '' && $storedSession !== '';
    }

    public function send(WhatsAppProviderConfig $config, string $to, string $message): array
    {
        $resolved = $this->resolveRequiredConfig($config);
        $chatId = $this->chatIdResolver->toChatId($to);

        if ($chatId === '') {
            throw new RuntimeException('Numero de destino invalido para WAHA.');
        }

        $payload = [
            'session' => $resolved['session'],
            'chatId' => $chatId,
            'text' => $message,
        ];

        $http = Http::withHeaders($resolved['headers']);
        if (! $resolved['verify_ssl']) {
            $http = $http->withoutVerifying();
        }

        try {
            $response = $http->post($resolved['base_url'] . '/api/sendText', $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Falha ao enviar WhatsApp via WAHA: erro de conexao.');
        }

        if (! $response->successful()) {
            $details = $this->extractErrorMessage($response->json(), $response->body());
            throw new RuntimeException('Falha ao enviar WhatsApp via WAHA.' . ($details !== '' ? ' ' . $details : ''));
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
        if (! $config->getBool('status_enabled', true)) {
            return [
                'can_send' => true,
                'applies' => false,
                'reason' => null,
            ];
        }

        try {
            $resolved = $this->resolveRequiredConfig($config);
        } catch (RuntimeException $exception) {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => $exception->getMessage(),
            ];
        }

        $cacheKey = $this->buildStatusCacheKey($resolved);

        return Cache::remember($cacheKey, self::STATUS_CACHE_TTL_SECONDS, function () use ($resolved) {
            $http = Http::withHeaders($resolved['headers']);
            if (! $resolved['verify_ssl']) {
                $http = $http->withoutVerifying();
            }

            try {
                $response = $http->get(
                    $resolved['base_url'] . '/api/sessions/' . rawurlencode($resolved['session'])
                );
            } catch (\Throwable) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da sessao WAHA.',
                ];
            }

            if (! $response->successful()) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da sessao WAHA.',
                ];
            }

            $payload = $response->json() ?? [];
            $status = strtoupper((string) (
                data_get($payload, 'status')
                ?? data_get($payload, 'payload.status')
                ?? data_get($payload, 'data.status')
                ?? ''
            ));

            if ($status === 'WORKING') {
                return [
                    'can_send' => true,
                    'applies' => true,
                    'reason' => null,
                ];
            }

            $reason = $status === ''
                ? 'Sessao WAHA indisponivel.'
                : 'Sessao WAHA nao esta ativa. Status atual: ' . $status . '.';

            return [
                'can_send' => false,
                'applies' => true,
                'reason' => $reason,
            ];
        });
    }

    /**
     * @return array{
     *   base_url: string,
     *   session: string,
     *   api_key: string,
     *   api_key_header: string,
     *   verify_ssl: bool,
     *   headers: array<string, string>
     * }
     */
    private function resolveRequiredConfig(WhatsAppProviderConfig $config): array
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $session = $config->getString('session');
        $apiKey = $config->getString('api_key');
        $apiKeyHeader = $config->getString('api_key_header', 'X-Api-Key');
        $verifySsl = $config->getBool('verify_ssl', true);

        if ($baseUrl === '' || $session === '') {
            $missing = [];
            if ($baseUrl === '') {
                $missing[] = 'base_url';
            }
            if ($session === '') {
                $missing[] = 'session';
            }

            $details = $missing ? (' Campos ausentes: ' . implode(', ', $missing) . '.') : '';
            throw new RuntimeException('Configuracao WAHA incompleta.' . $details);
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($apiKey !== '') {
            $headers[$apiKeyHeader !== '' ? $apiKeyHeader : 'X-Api-Key'] = $apiKey;
        }

        return [
            'base_url' => $baseUrl,
            'session' => $session,
            'api_key' => $apiKey,
            'api_key_header' => $apiKeyHeader,
            'verify_ssl' => $verifySsl,
            'headers' => $headers,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function extractErrorMessage(?array $payload, string $body): string
    {
        $message = trim((string) (
            data_get($payload, 'message')
            ?? data_get($payload, 'error')
            ?? data_get($payload, 'error.message')
            ?? ''
        ));

        if ($message !== '') {
            return $message;
        }

        $body = trim($body);
        if ($body === '') {
            return '';
        }

        return mb_substr($body, 0, 300);
    }

    /**
     * @param array{
     *   base_url: string,
     *   session: string,
     *   api_key: string,
     *   api_key_header: string
     * } $resolved
     */
    private function buildStatusCacheKey(array $resolved): string
    {
        $suffix = md5(implode('|', [
            $resolved['base_url'],
            $resolved['session'],
            $resolved['api_key_header'],
            $resolved['api_key'],
        ]));

        return self::STATUS_CACHE_PREFIX . ':' . $suffix;
    }
}
