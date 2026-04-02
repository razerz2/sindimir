<?php

namespace App\Services\WhatsApp\DTO;

final class WhatsAppProviderConfig
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly string $provider,
        private readonly array $values = []
    ) {
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? $default;

        return (bool) $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }
}

