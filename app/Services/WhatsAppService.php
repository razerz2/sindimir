<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function canSend(): bool
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'zapi') {
            $baseUrl = rtrim((string) ($this->configuracaoService->get('whatsapp.base_url')
                ?? config('services.whatsapp.zapi.base_url')), '/');
            $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.zapi.token'));
            $clientToken = (string) ($this->configuracaoService->get('whatsapp.client_token') ?? config('services.whatsapp.zapi.client_token'));
            $instance = (string) ($this->configuracaoService->get('whatsapp.instance')
                ?? config('services.whatsapp.zapi.instance'));

            return $baseUrl !== '' && $token !== '' && $clientToken !== '' && $instance !== '';
        }

        if ($provider === 'meta') {
            $baseUrl = rtrim((string) config('services.whatsapp.meta.base_url'), '/');
            $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.meta.token'));
            $phoneId = (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? config('services.whatsapp.meta.phone_number_id'));

            return $baseUrl !== '' && $token !== '' && $phoneId !== '';
        }

        return false;
    }

    public function canTestSend(): bool
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'zapi') {
            $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? '');
            $clientToken = (string) ($this->configuracaoService->get('whatsapp.client_token') ?? '');

            return $token !== '' && $clientToken !== '';
        }

        if ($provider === 'meta') {
            $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? '');
            $phoneId = (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? '');

            return $token !== '' && $phoneId !== '';
        }

        return false;
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

    public function sendTest(string $to, string $message): array
    {
        $provider = $this->getActiveProvider();

        if ($provider === 'zapi') {
            return $this->sendViaZapi($to, $message, true);
        }

        if ($provider === 'meta') {
            return $this->sendViaMeta($to, $message, true);
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

    private function sendViaZapi(string $to, string $message, bool $returnResponse = false): array
    {
        $baseUrl = rtrim((string) ($this->configuracaoService->get('whatsapp.base_url')
            ?? config('services.whatsapp.zapi.base_url')), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.zapi.token'));
        $clientToken = (string) ($this->configuracaoService->get('whatsapp.client_token') ?? config('services.whatsapp.zapi.client_token'));
        $instance = (string) ($this->configuracaoService->get('whatsapp.instance')
            ?? config('services.whatsapp.zapi.instance'));
        $verifySsl = (bool) config('services.whatsapp.zapi.verify_ssl', true);

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

        $http = Http::withHeaders([
            'Client-Token' => $clientToken,
            'Content-Type' => 'application/json',
        ]);

        if (! $verifySsl) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post(
            "{$baseUrl}/instances/{$instance}/token/{$token}/send-text",
            [
                'phone' => $to,
                'message' => $message,
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao enviar WhatsApp via Z-API.');
        }

        if (! $returnResponse) {
            return [];
        }

        return [
            'provider' => 'zapi',
            'response' => $response->json() ?? ['body' => $response->body()],
        ];
    }

    private function sendViaMeta(string $to, string $message, bool $returnResponse = false): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp.meta.base_url'), '/');
        $token = (string) ($this->configuracaoService->get('whatsapp.token') ?? config('services.whatsapp.meta.token'));
        $phoneId = (string) ($this->configuracaoService->get('whatsapp.phone_number_id') ?? config('services.whatsapp.meta.phone_number_id'));
        $verifySsl = (bool) config('services.whatsapp.meta.verify_ssl', true);

        if ($baseUrl === '' || $token === '' || $phoneId === '') {
            throw new RuntimeException(
                'Configuração Meta incompleta. Verifique base_url e phone_number_id em services.whatsapp.meta.'
            );
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
            throw new RuntimeException('Falha ao enviar WhatsApp via Meta Cloud API.');
        }

        if (! $returnResponse) {
            return [];
        }

        return [
            'provider' => 'meta',
            'response' => $response->json() ?? ['body' => $response->body()],
        ];
    }
}
