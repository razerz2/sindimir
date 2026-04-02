<?php

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;

interface WhatsAppNotificationProviderInterface
{
    public function key(): string;

    public function canSend(WhatsAppProviderConfig $config): bool;

    public function canTestSend(WhatsAppProviderConfig $config): bool;

    /**
     * @return array{provider: string, response: array<string, mixed>}
     */
    public function send(WhatsAppProviderConfig $config, string $to, string $message): array;
}

