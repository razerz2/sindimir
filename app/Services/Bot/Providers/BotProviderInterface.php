<?php

namespace App\Services\Bot\Providers;

interface BotProviderInterface
{
    public function sendText(string $to, string $message): void;
}

