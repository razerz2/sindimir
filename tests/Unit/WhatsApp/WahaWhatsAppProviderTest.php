<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use App\Services\WhatsApp\Providers\WahaWhatsAppProvider;
use App\Services\WhatsApp\WahaChatIdResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WahaWhatsAppProviderTest extends TestCase
{
    public function test_send_uses_expected_endpoint_headers_and_payload(): void
    {
        Http::fake([
            'https://waha.test/*' => Http::response(['id' => 'msg_1'], 200),
        ]);

        $provider = new WahaWhatsAppProvider(new WahaChatIdResolver());
        $config = new WhatsAppProviderConfig('waha', [
            'base_url' => 'https://waha.test',
            'session' => 'default',
            'api_key' => 'waha-key',
            'api_key_header' => 'X-Api-Key',
            'verify_ssl' => true,
        ]);

        $result = $provider->send($config, '5567999999999', 'Mensagem WAHA');

        $this->assertSame('waha', $result['provider']);
        $this->assertSame(['id' => 'msg_1'], $result['response']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://waha.test/api/sendText'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Api-Key', 'waha-key')
                && ($payload['session'] ?? null) === 'default'
                && ($payload['chatId'] ?? null) === '5567999999999@c.us'
                && ($payload['text'] ?? null) === 'Mensagem WAHA';
        });
    }

    public function test_health_status_returns_can_send_true_when_session_is_working(): void
    {
        Cache::flush();
        Http::fake([
            'https://waha.test/*' => Http::response([
                'name' => 'default',
                'status' => 'WORKING',
            ], 200),
        ]);

        $provider = new WahaWhatsAppProvider(new WahaChatIdResolver());
        $config = new WhatsAppProviderConfig('waha', [
            'base_url' => 'https://waha.test',
            'session' => 'default',
            'api_key' => 'waha-key',
            'status_enabled' => true,
            'verify_ssl' => true,
        ]);

        $status = $provider->getHealthStatus($config);

        $this->assertTrue($status['applies']);
        $this->assertTrue($status['can_send']);
        $this->assertNull($status['reason']);
    }

    public function test_health_status_blocks_send_when_session_is_not_working(): void
    {
        Cache::flush();
        Http::fake([
            'https://waha.test/*' => Http::response([
                'name' => 'default',
                'status' => 'STOPPED',
            ], 200),
        ]);

        $provider = new WahaWhatsAppProvider(new WahaChatIdResolver());
        $config = new WhatsAppProviderConfig('waha', [
            'base_url' => 'https://waha.test',
            'session' => 'default',
            'api_key' => 'waha-key',
            'status_enabled' => true,
            'verify_ssl' => true,
        ]);

        $status = $provider->getHealthStatus($config);

        $this->assertTrue($status['applies']);
        $this->assertFalse($status['can_send']);
        $this->assertStringContainsString('STOPPED', (string) $status['reason']);
    }
}
