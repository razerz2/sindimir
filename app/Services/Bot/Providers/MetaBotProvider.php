<?php

namespace App\Services\Bot\Providers;

use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaBotProvider implements BotProviderInterface
{
    /**
     * @param array{base_url?: string, access_token?: string, phone_number_id?: string} $credentials
     */
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly array $credentials = []
    ) {
    }

    public function sendText(string $to, string $message): void
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? config('services.whatsapp.meta.base_url')), '/');
        $token = (string) ($this->credentials['access_token']
            ?? $this->configuracaoService->get('whatsapp.token')
            ?? config('services.whatsapp.meta.token'));
        $phoneId = (string) ($this->credentials['phone_number_id']
            ?? $this->configuracaoService->get('whatsapp.phone_number_id')
            ?? config('services.whatsapp.meta.phone_number_id'));
        $verifySsl = (bool) config('services.whatsapp.meta.verify_ssl', true);

        if ($baseUrl === '' || $token === '' || $phoneId === '') {
            throw new RuntimeException('Configuracao Meta incompleta para envio do bot.');
        }

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
            throw new RuntimeException('Falha ao enviar resposta do bot via Meta Cloud API.');
        }
    }
}
