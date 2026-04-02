<?php

namespace App\Services;

use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use App\Services\WhatsApp\WhatsAppProviderResolver;
use RuntimeException;

class WhatsAppService
{
    public function __construct(
        private readonly WhatsAppProviderResolver $providerResolver,
        private readonly WhatsAppProviderConfigResolver $configResolver
    )
    {
    }

    public function canSend(): bool
    {
        $provider = $this->providerResolver->resolve($this->configResolver->resolveNotificationProvider());
        if ($provider === null) {
            return false;
        }

        return $provider->canSend(
            $this->configResolver->resolveNotificationConfig($provider->key())
        );
    }

    public function canTestSend(): bool
    {
        $provider = $this->providerResolver->resolve($this->configResolver->resolveNotificationProvider());
        if ($provider === null) {
            return false;
        }

        return $provider->canTestSend(
            $this->configResolver->resolveNotificationConfig($provider->key())
        );
    }

    public function send(string $to, string $message): void
    {
        $this->sendWithResponse($to, $message);
    }

    public function sendWithResponse(string $to, string $message): array
    {
        return $this->sendUsingActiveProvider($to, $message);
    }

    public function sendTest(string $to, string $message): array
    {
        return $this->sendUsingActiveProvider($to, $message);
    }

    /**
     * @return array{provider: string, response: array<string, mixed>}
     */
    private function sendUsingActiveProvider(string $to, string $message): array
    {
        $providerKey = $this->configResolver->resolveNotificationProvider();
        $provider = $this->providerResolver->resolve($providerKey);
        if ($provider === null) {
            throw new RuntimeException('Nenhum provedor WhatsApp ativo configurado.');
        }

        return $provider->send(
            $this->configResolver->resolveNotificationConfig($provider->key()),
            $to,
            $message
        );
    }
}
