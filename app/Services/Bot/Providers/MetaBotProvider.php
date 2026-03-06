<?php

namespace App\Services\Bot\Providers;

use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaBotProvider implements BotProviderInterface
{
    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function sendText(string $to, string $message): void
    {
        $baseUrl = rtrim((string) config('services.whatsapp.meta.base_url'), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.meta.token'));
        $phoneId = (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? config('services.whatsapp.meta.phone_number_id'));
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

