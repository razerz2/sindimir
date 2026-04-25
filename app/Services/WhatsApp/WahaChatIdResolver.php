<?php

namespace App\Services\WhatsApp;

use App\Support\Phone;

class WahaChatIdResolver
{
    public function toChatId(string $destination): string
    {
        return $this->normalizeReplyChatId($destination);
    }

    public function normalizeReplyChatId(string $candidate): string
    {
        $value = trim($candidate);
        if ($value === '') {
            return '';
        }

        $lower = mb_strtolower($value);
        if (str_contains($lower, 'status@broadcast') || str_contains($lower, '@lid')) {
            return '';
        }

        if (str_ends_with($lower, '@g.us')) {
            return $lower;
        }

        $localPart = $this->extractLocalPart($lower);
        $normalized = Phone::normalizeForBrazil($localPart, true);
        // Reject internal/LID identifiers that are too long to be valid phone numbers.
        // Real WhatsApp phones have at most 13 digits (e.g. Brazil 55+DDD+9digits=13).
        if (strlen($normalized) < 10 || strlen($normalized) > 13) {
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

        if (str_contains($chatId, ':')) {
            $chatId = explode(':', $chatId)[0] ?? $chatId;
        }

        return Phone::normalize($chatId);
    }

    private function extractLocalPart(string $value): string
    {
        $local = $value;
        if (str_contains($local, '@')) {
            $local = explode('@', $local)[0] ?? '';
        }

        if (str_contains($local, ':')) {
            $local = explode(':', $local)[0] ?? $local;
        }

        return trim($local);
    }
}
