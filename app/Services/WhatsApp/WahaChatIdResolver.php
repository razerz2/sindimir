<?php

namespace App\Services\WhatsApp;

use App\Support\Phone;

class WahaChatIdResolver
{
    public function toChatId(string $destination): string
    {
        $value = trim($destination);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '@')) {
            return mb_strtolower($value);
        }

        $normalized = Phone::normalize($value);
        if ($normalized === '') {
            return '';
        }

        return $normalized . '@c.us';
    }

    public function toPhone(string $from): string
    {
        $chatId = trim($from);
        if ($chatId === '') {
            return '';
        }

        if (str_contains($chatId, '@')) {
            $chatId = explode('@', $chatId)[0] ?? '';
        }

        return Phone::normalize($chatId);
    }
}
