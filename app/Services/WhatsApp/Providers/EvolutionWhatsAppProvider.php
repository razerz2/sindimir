<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppHealthProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use App\Services\WhatsApp\EvolutionPhoneResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EvolutionWhatsAppProvider implements
    WhatsAppBotProviderInterface,
    WhatsAppHealthProviderInterface,
    WhatsAppNotificationProviderInterface
{
    private const STATUS_CACHE_PREFIX = 'whatsapp.evolution.status';
    private const STATUS_CACHE_TTL_SECONDS = 45;

    public function __construct(private readonly EvolutionPhoneResolver $phoneResolver)
    {
    }

    public function key(): string
    {
        return 'evolution';
    }

    public function canSend(WhatsAppProviderConfig $config): bool
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $instance = $config->getString('instance');
        $apiKey = $config->getString('api_key');

        return $baseUrl !== '' && $instance !== '' && $apiKey !== '';
    }

    public function canTestSend(WhatsAppProviderConfig $config): bool
    {
        $storedBaseUrl = rtrim($config->getString('stored_base_url'), '/');
        $storedInstance = $config->getString('stored_instance');
        $storedApiKey = $config->getString('stored_api_key');

        return $storedBaseUrl !== '' && $storedInstance !== '' && $storedApiKey !== '';
    }

    public function send(WhatsAppProviderConfig $config, string $to, string $message): array
    {
        $resolved = $this->resolveRequiredConfig($config);
        $number = $this->phoneResolver->toDestinationNumber($to);

        if ($number === '') {
            throw new RuntimeException('Numero de destino invalido para Evolution API.');
        }

        $payload = [
            'number' => $number,
            'text' => $message,
        ];

        $http = Http::withHeaders([
            'apikey' => $resolved['api_key'],
            'Content-Type' => 'application/json',
        ]);

        if (! $resolved['verify_ssl']) {
            $http = $http->withoutVerifying();
        }

        try {
            $response = $http->post(
                "{$resolved['base_url']}/message/sendText/" . rawurlencode($resolved['instance']),
                $payload
            );
        } catch (\Throwable) {
            throw new RuntimeException('Falha ao enviar WhatsApp via Evolution API: erro de conexao.');
        }

        if (! $response->successful()) {
            $details = $this->extractErrorMessage($response->json(), $response->body());
            throw new RuntimeException('Falha ao enviar WhatsApp via Evolution API.' . ($details !== '' ? ' ' . $details : ''));
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
            $http = Http::withHeaders([
                'apikey' => $resolved['api_key'],
                'Content-Type' => 'application/json',
            ]);

            if (! $resolved['verify_ssl']) {
                $http = $http->withoutVerifying();
            }

            try {
                $response = $http->get(
                    "{$resolved['base_url']}/instance/connectionState/" . rawurlencode($resolved['instance'])
                );
            } catch (\Throwable) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da instancia Evolution API.',
                ];
            }

            if (! $response->successful()) {
                return [
                    'can_send' => false,
                    'applies' => true,
                    'reason' => 'Falha ao consultar status da instancia Evolution API.',
                ];
            }

            $payload = $response->json() ?? [];
            $state = mb_strtolower(trim((string) (
                data_get($payload, 'instance.state')
                ?? data_get($payload, 'state')
                ?? data_get($payload, 'data.instance.state')
                ?? ''
            )));

            if ($state === 'open') {
                return [
                    'can_send' => true,
                    'applies' => true,
                    'reason' => null,
                ];
            }

            $reason = $state === ''
                ? 'Instancia Evolution API indisponivel.'
                : 'Instancia Evolution API desconectada. Estado atual: ' . $state . '.';

            return [
                'can_send' => false,
                'applies' => true,
                'reason' => $reason,
            ];
        });
    }

    /**
     * @return array{
     *     base_url: string,
     *     instance: string,
     *     api_key: string,
     *     verify_ssl: bool
     * }
     */
    private function resolveRequiredConfig(WhatsAppProviderConfig $config): array
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $instance = $config->getString('instance');
        $apiKey = $config->getString('api_key');
        $verifySsl = $config->getBool('verify_ssl', true);

        if ($baseUrl === '' || $instance === '' || $apiKey === '') {
            $missing = [];
            if ($baseUrl === '') {
                $missing[] = 'base_url';
            }
            if ($instance === '') {
                $missing[] = 'instance';
            }
            if ($apiKey === '') {
                $missing[] = 'api_key';
            }

            $details = $missing ? (' Campos ausentes: ' . implode(', ', $missing) . '.') : '';
            throw new RuntimeException('Configuracao Evolution API incompleta.' . $details);
        }

        return [
            'base_url' => $baseUrl,
            'instance' => $instance,
            'api_key' => $apiKey,
            'verify_ssl' => $verifySsl,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function extractErrorMessage(?array $payload, string $body): string
    {
        $message = trim((string) (
            data_get($payload, 'message')
            ?? data_get($payload, 'response.message')
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
     *     base_url: string,
     *     instance: string,
     *     api_key: string
     * } $resolved
     */
    private function buildStatusCacheKey(array $resolved): string
    {
        $suffix = md5(implode('|', [
            $resolved['base_url'],
            $resolved['instance'],
            $resolved['api_key'],
        ]));

        return self::STATUS_CACHE_PREFIX . ':' . $suffix;
    }
}
