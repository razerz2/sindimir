<?php

namespace App\Services\Bot\Providers;

use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZapiBotProvider implements BotProviderInterface
{
    /**
     * @param array{
     *     base_url?: string,
     *     token?: string,
     *     client_token?: string,
     *     instance?: string
     * } $credentials
     */
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly array $credentials = []
    ) {
    }

    public function sendText(string $to, string $message): void
    {
        $baseUrl = rtrim((string) (($this->credentials['base_url'] ?? null)
            ?? $this->configuracaoService->get('whatsapp.base_url')
            ?? config('services.whatsapp.zapi.base_url')), '/');
        $token = (string) (($this->credentials['token'] ?? null)
            ?? $this->configuracaoService->get('whatsapp.token')
            ?? config('services.whatsapp.zapi.token'));
        $clientToken = (string) (($this->credentials['client_token'] ?? null)
            ?? $this->configuracaoService->get('whatsapp.client_token')
            ?? config('services.whatsapp.zapi.client_token'));
        $instance = (string) (($this->credentials['instance'] ?? null)
            ?? $this->configuracaoService->get('whatsapp.instance')
            ?? config('services.whatsapp.zapi.instance'));
        $verifySsl = (bool) config('services.whatsapp.zapi.verify_ssl', true);

        if ($baseUrl === '' || $token === '' || $clientToken === '' || $instance === '') {
            throw new RuntimeException('Configuracao Z-API incompleta para envio do bot.');
        }

        $linkUrl = $this->extractLinkUrl($message);
        $endpoint = $linkUrl
            ? "{$baseUrl}/instances/{$instance}/token/{$token}/send-link"
            : "{$baseUrl}/instances/{$instance}/token/{$token}/send-text";

        $payload = [
            'phone' => $to,
            'message' => $message,
        ];

        if ($linkUrl) {
            $payload['linkUrl'] = $linkUrl;
            $payload['title'] = (string) ($this->configuracaoService->get('sistema.nome') ?? config('app.name'));
            $payload['linkDescription'] = 'Acesse o link para mais detalhes.';
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
            throw new RuntimeException('Falha ao enviar resposta do bot via Z-API.');
        }
    }

    private function extractLinkUrl(string $message): ?string
    {
        if (preg_match('~https?://\S+~i', $message, $match) !== 1) {
            return null;
        }

        return $match[0];
    }
}
