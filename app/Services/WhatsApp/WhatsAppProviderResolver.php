<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use RuntimeException;

class WhatsAppProviderResolver
{
    /**
     * @var array<string, WhatsAppNotificationProviderInterface>|null
     */
    private ?array $providers = null;

    /**
     * @return array<string, string>
     */
    public function providerClassMap(): array
    {
        $map = config('services.whatsapp.provider_registry', []);

        return is_array($map) ? $map : [];
    }

    /**
     * @return list<string>
     */
    public function notificationProviderKeys(): array
    {
        return array_values(array_keys($this->providers()));
    }

    /**
     * @return list<string>
     */
    public function botProviderKeys(): array
    {
        $keys = [];
        foreach ($this->providers() as $key => $provider) {
            if ($provider instanceof WhatsAppBotProviderInterface) {
                $keys[] = $key;
            }
        }

        return array_values($keys);
    }

    /**
     * @return list<string>
     */
    public function plannedProviderKeys(): array
    {
        $planned = config('services.whatsapp.future_providers', []);
        if (! is_array($planned)) {
            return [];
        }

        $normalized = [];
        foreach ($planned as $provider) {
            $value = $this->normalizeProvider((string) $provider);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    public function resolve(string $provider): ?WhatsAppNotificationProviderInterface
    {
        $provider = $this->normalizeProvider($provider);
        if ($provider === '') {
            return null;
        }

        return $this->providers()[$provider] ?? null;
    }

    public function resolveOrFail(string $provider): WhatsAppNotificationProviderInterface
    {
        $resolved = $this->resolve($provider);
        if ($resolved !== null) {
            return $resolved;
        }

        throw new RuntimeException('Provedor WhatsApp inválido: ' . $provider);
    }

    /**
     * @return array<string, WhatsAppNotificationProviderInterface>
     */
    private function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $resolved = [];
        foreach ($this->providerClassMap() as $providerKey => $providerClass) {
            $providerKey = $this->normalizeProvider((string) $providerKey);
            if ($providerKey === '' || ! is_string($providerClass) || trim($providerClass) === '') {
                continue;
            }

            $provider = app($providerClass);
            if (! $provider instanceof WhatsAppNotificationProviderInterface) {
                continue;
            }

            $resolved[$providerKey] = $provider;
        }

        $this->providers = $resolved;

        return $this->providers;
    }

    private function normalizeProvider(string $provider): string
    {
        return mb_strtolower(trim($provider));
    }
}

