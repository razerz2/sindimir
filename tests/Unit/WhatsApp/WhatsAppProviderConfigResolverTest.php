<?php

namespace Tests\Unit\WhatsApp;

use App\Services\ConfiguracaoService;
use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use Mockery;
use Tests\TestCase;

class WhatsAppProviderConfigResolverTest extends TestCase
{
    public function test_waha_bot_config_inherit_mode_uses_notification_waha_base_url_and_session_when_bot_values_are_empty(): void
    {
        $values = [
            'bot.credentials_mode' => 'inherit_notifications',
            'bot.waha_base_url' => '',
            'bot.waha_session' => '',
            'whatsapp.waha_base_url' => 'https://waha-notify.test',
            'whatsapp.waha_session' => 'default-notify',
            'whatsapp.waha_api_key' => 'notify-key',
            'whatsapp.waha_api_key_header' => 'X-Api-Key',
        ];

        $configuracaoService = Mockery::mock(ConfiguracaoService::class);
        $configuracaoService->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null) use ($values): mixed {
                return $values[$key] ?? $default;
            });

        $resolver = new WhatsAppProviderConfigResolver($configuracaoService);
        $config = $resolver->resolveBotConfig('waha');

        $this->assertSame('waha', $config->provider);
        $this->assertSame('bot', $config->getString('scope'));
        $this->assertSame('https://waha-notify.test', $config->getString('base_url'));
        $this->assertSame('default-notify', $config->getString('session'));
        $this->assertSame('notify-key', $config->getString('api_key'));
        $this->assertFalse($config->getBool('include_link_image', true));
    }
}
