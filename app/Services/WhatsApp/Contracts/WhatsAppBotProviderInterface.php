<?php

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;

interface WhatsAppBotProviderInterface
{
    public function sendBotText(WhatsAppProviderConfig $config, string $to, string $message): void;
}

