<?php

namespace App\Services\WhatsApp;

class ZApiStatusService
{
    public function __construct(private readonly WhatsAppProviderStatusService $providerStatusService)
    {
    }

    public function canSend(): bool
    {
        return $this->getStatus()['can_send'];
    }

    /**
     * @return array{can_send: bool, applies: bool, reason: ?string}
     */
    public function getStatus(): array
    {
        return $this->providerStatusService->getProviderStatus('zapi', onlyWhenActive: true);
    }
}

