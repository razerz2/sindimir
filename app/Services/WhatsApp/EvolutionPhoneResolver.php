<?php

namespace App\Services\WhatsApp;

use App\Support\Phone;

class EvolutionPhoneResolver
{
    public function toDestinationNumber(string $destination): string
    {
        return $this->toPhone($destination);
    }

    public function toPhone(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, '@')) {
            $raw = explode('@', $raw)[0] ?? '';
        }

        return Phone::normalizeForBrazil($raw, false);
    }
}
