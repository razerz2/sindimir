<?php

namespace App\Services\Bot\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;

class ConfiguredBotProvider implements BotProviderInterface
{
    public function __construct(
        private readonly WhatsAppBotProviderInterface $provider,
        private readonly WhatsAppProviderConfig $config
    ) {
    }

    public function sendText(string $to, string $message): void
    {
        $this->provider->sendBotText($this->config, $to, $message);
    }
}

