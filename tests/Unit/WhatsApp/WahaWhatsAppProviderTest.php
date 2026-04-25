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
                && ($payload['chatId'] ?? null) === '556799999999@c.us'
                && ($payload['text'] ?? null) === 'Mensagem WAHA';
        });
    }

    public function test_send_bot_text_uses_api_key_header_session_and_normalized_chat_id(): void
    {
        Http::fake([
            'https://waha.test/*' => Http::response(['id' => 'msg_bot_1'], 201),
        ]);

        $provider = new WahaWhatsAppProvider(new WahaChatIdResolver());
        $config = new WhatsAppProviderConfig('waha', [
            'scope' => 'bot',
            'base_url' => 'https://waha.test',
            'session' => 'default',
            'api_key' => 'waha-key',
            'api_key_header' => 'X-Api-Key',
            'verify_ssl' => true,
        ]);

        $provider->sendBotText($config, '5567991112222', 'Resposta bot');

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://waha.test/api/sendText'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Api-Key', 'waha-key')
                && ($payload['session'] ?? null) === 'default'
                && ($payload['chatId'] ?? null) === '556791112222@c.us'
                && ($payload['text'] ?? null) === 'Resposta bot';
        });
    }

    public function test_send_includes_x_api_key_even_with_custom_api_key_header(): void
    {
        Http::fake([
            'https://waha.test/*' => Http::response(['id' => 'msg_custom_header'], 201),
        ]);

        $provider = new WahaWhatsAppProvider(new WahaChatIdResolver());
        $config = new WhatsAppProviderConfig('waha', [
            'base_url' => 'https://waha.test',
            'session' => 'default',
            'api_key' => 'waha-key',
            'api_key_header' => 'Authorization',
            'verify_ssl' => true,
        ]);

        $provider->send($config, '5567999999999', 'Mensagem WAHA');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'waha-key')
                && $request->hasHeader('X-Api-Key', 'waha-key');
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
