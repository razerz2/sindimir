<?php

namespace App\Services\Bot\Providers;

use InvalidArgumentException;

class BotProviderFactory
{
    public function __construct(
        private readonly MetaBotProvider $metaProvider,
        private readonly ZapiBotProvider $zapiProvider
    ) {
    }

    public function make(string $channel): BotProviderInterface
    {
        return match ($channel) {
            'meta' => $this->metaProvider,
            'zapi' => $this->zapiProvider,
            default => throw new InvalidArgumentException('Canal de bot invalido: ' . $channel),
        };
    }
}

