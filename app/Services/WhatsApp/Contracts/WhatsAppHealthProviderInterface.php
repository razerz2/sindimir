<?php

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;

interface WhatsAppHealthProviderInterface
{
    /**
     * @return array{can_send: bool, applies: bool, reason: ?string}
     */
    public function getHealthStatus(WhatsAppProviderConfig $config): array;
}

