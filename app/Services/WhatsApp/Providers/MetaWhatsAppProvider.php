<?php

namespace App\Services\WhatsApp\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaWhatsAppProvider implements WhatsAppBotProviderInterface, WhatsAppNotificationProviderInterface
{
    public function key(): string
    {
        return 'meta';
    }

    public function canSend(WhatsAppProviderConfig $config): bool
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $token = $config->getString('token');
        $phoneId = $config->getString('phone_number_id');

        return $baseUrl !== '' && $token !== '' && $phoneId !== '';
    }

    public function canTestSend(WhatsAppProviderConfig $config): bool
    {
        $token = $config->getString('stored_token');
        $phoneId = $config->getString('stored_phone_number_id');

        return $token !== '' && $phoneId !== '';
    }

    public function send(WhatsAppProviderConfig $config, string $to, string $message): array
    {
        [$baseUrl, $token, $phoneId] = $this->resolveRequiredConfig($config);
        $verifySsl = $config->getBool('verify_ssl', true);

        $http = Http::withToken($token);
        if (! $verifySsl) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post(
            "{$baseUrl}/{$phoneId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao enviar WhatsApp via Meta Cloud API.');
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

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveRequiredConfig(WhatsAppProviderConfig $config): array
    {
        $baseUrl = rtrim($config->getString('base_url'), '/');
        $token = $config->getString('token');
        $phoneId = $config->getString('phone_number_id');

        if ($baseUrl === '' || $token === '' || $phoneId === '') {
            throw new RuntimeException(
                'Configuração Meta incompleta. Verifique base_url e phone_number_id em services.whatsapp.meta.'
            );
        }

        return [$baseUrl, $token, $phoneId];
    }
}

