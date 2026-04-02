<?php

namespace App\Services\Bot\Providers;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use App\Services\WhatsApp\WhatsAppProviderResolver;
use InvalidArgumentException;

class BotProviderFactory
{
    public function __construct(
        private readonly WhatsAppProviderResolver $providerResolver,
        private readonly WhatsAppProviderConfigResolver $configResolver
    ) {
    }

    public function make(string $channel): BotProviderInterface
    {
        $channel = mb_strtolower(trim($channel));
        $provider = $this->providerResolver->resolve($channel);

        if (! $provider instanceof WhatsAppBotProviderInterface) {
            throw new InvalidArgumentException('Canal de bot invalido: ' . $channel);
        }

        return new ConfiguredBotProvider(
            $provider,
            $this->configResolver->resolveBotConfig($channel)
        );
    }

    /**
     * @return list<string>
     */
    public function supportedChannels(): array
    {
        return $this->providerResolver->botProviderKeys();
    }
}
