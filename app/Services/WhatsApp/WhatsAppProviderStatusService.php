<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppHealthProviderInterface;

class WhatsAppProviderStatusService
{
    public function __construct(
        private readonly WhatsAppProviderResolver $providerResolver,
        private readonly WhatsAppProviderConfigResolver $configResolver
    ) {
    }

    /**
     * @return array{can_send: bool, applies: bool, reason: ?string}
     */
    public function getActiveProviderStatus(): array
    {
        $provider = $this->configResolver->resolveNotificationProvider();
        if ($provider === '') {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => 'Nenhum provedor WhatsApp ativo configurado.',
            ];
        }

        return $this->getProviderStatus($provider);
    }

    /**
     * @return array{can_send: bool, applies: bool, reason: ?string}
     */
    public function getProviderStatus(string $provider, bool $onlyWhenActive = false): array
    {
        $provider = mb_strtolower(trim($provider));
        if ($provider === '') {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => 'Nenhum provedor WhatsApp informado.',
            ];
        }

        if ($onlyWhenActive && $provider !== $this->configResolver->resolveNotificationProvider()) {
            return [
                'can_send' => true,
                'applies' => false,
                'reason' => null,
            ];
        }

        $resolvedProvider = $this->providerResolver->resolve($provider);
        if ($resolvedProvider === null) {
            return [
                'can_send' => false,
                'applies' => true,
                'reason' => 'Provedor WhatsApp não suportado: ' . $provider . '.',
            ];
        }

        if (! $resolvedProvider instanceof WhatsAppHealthProviderInterface) {
            return [
                'can_send' => true,
                'applies' => false,
                'reason' => null,
            ];
        }

        return $resolvedProvider->getHealthStatus($this->configResolver->resolveStatusConfig($provider));
    }
}

