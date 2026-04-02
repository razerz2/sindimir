<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppBotProviderInterface;
use App\Services\WhatsApp\Contracts\WhatsAppNotificationProviderInterface;
use App\Services\WhatsApp\WhatsAppProviderResolver;
use Tests\TestCase;

class WhatsAppProviderResolverTest extends TestCase
{
    public function test_resolver_exposes_registered_notification_and_bot_providers(): void
    {
        $resolver = app(WhatsAppProviderResolver::class);

        $notificationKeys = $resolver->notificationProviderKeys();
        $botKeys = $resolver->botProviderKeys();

        foreach (['meta', 'zapi', 'waha', 'evolution'] as $provider) {
            $this->assertContains($provider, $notificationKeys);
            $this->assertContains($provider, $botKeys);

            $resolved = $resolver->resolve($provider);
            $this->assertInstanceOf(WhatsAppNotificationProviderInterface::class, $resolved);
            $this->assertInstanceOf(WhatsAppBotProviderInterface::class, $resolved);
        }
    }

    public function test_resolver_returns_null_for_unknown_provider(): void
    {
        $resolver = app(WhatsAppProviderResolver::class);

        $this->assertNull($resolver->resolve('desconhecido'));
    }
}
