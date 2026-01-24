<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function send(string $to, string $message): void
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'zapi') {
            $this->sendViaZapi($to, $message);

            return;
        }

        if ($provider === 'meta') {
            $this->sendViaMeta($to, $message);

            return;
        }

        throw new RuntimeException('Nenhum provedor WhatsApp ativo configurado.');
    }

    private function getActiveProvider(): string
    {
        $provider = $this->configuracaoService->get('whatsapp.provedor');

        if (! in_array($provider, ['zapi', 'meta'], true)) {
            return '';
        }

        return $provider;
    }

    private function sendViaZapi(string $to, string $message): void
    {
        $baseUrl = rtrim((string) config('services.whatsapp.zapi.base_url'), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.zapi.token'));
        $instance = (string) config('services.whatsapp.zapi.instance');

        if ($baseUrl === '' || $token === '' || $instance === '') {
            throw new RuntimeException('Configuração Z-API incompleta.');
        }

        $response = Http::withToken($token)->post(
            "{$baseUrl}/instances/{$instance}/messages",
            [
                'phone' => $to,
                'message' => $message,
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao enviar WhatsApp via Z-API.');
        }
    }

    private function sendViaMeta(string $to, string $message): void
    {
        $baseUrl = rtrim((string) config('services.whatsapp.meta.base_url'), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.meta.token'));
        $phoneId = (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? config('services.whatsapp.meta.phone_number_id'));

        if ($baseUrl === '' || $token === '' || $phoneId === '') {
            throw new RuntimeException('Configuração Meta WhatsApp incompleta.');
        }

        $response = Http::withToken($token)->post(
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
    }
}
