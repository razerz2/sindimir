<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use App\Services\WhatsApp\EvolutionPhoneResolver;
use App\Services\WhatsApp\Providers\EvolutionWhatsAppProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionWhatsAppProviderTest extends TestCase
{
    public function test_send_uses_expected_endpoint_headers_and_payload(): void
    {
        Http::fake([
            'https://evolution.test/*' => Http::response(['ok' => true], 200),
        ]);

        $provider = new EvolutionWhatsAppProvider(new EvolutionPhoneResolver());
        $config = new WhatsAppProviderConfig('evolution', [
            'base_url' => 'https://evolution.test',
            'instance' => 'inst-a',
            'api_key' => 'api-key-123',
            'verify_ssl' => true,
        ]);

        $result = $provider->send($config, '5567999999999', 'Mensagem de teste');

        $this->assertSame('evolution', $result['provider']);
        $this->assertSame(['ok' => true], $result['response']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://evolution.test/message/sendText/inst-a'
                && $request->method() === 'POST'
                && $request->hasHeader('apikey', 'api-key-123')
                && ($payload['number'] ?? null) === '5567999999999'
                && ($payload['text'] ?? null) === 'Mensagem de teste';
        });
    }

    public function test_health_status_returns_can_send_true_when_instance_is_open(): void
    {
        Cache::flush();
        Http::fake([
            'https://evolution.test/*' => Http::response([
                'instance' => [
                    'instanceName' => 'inst-b',
                    'state' => 'open',
                ],
            ], 200),
        ]);

        $provider = new EvolutionWhatsAppProvider(new EvolutionPhoneResolver());
        $config = new WhatsAppProviderConfig('evolution', [
            'base_url' => 'https://evolution.test',
            'instance' => 'inst-b',
            'api_key' => 'api-key-123',
            'status_enabled' => true,
            'verify_ssl' => true,
        ]);

        $status = $provider->getHealthStatus($config);

        $this->assertTrue($status['applies']);
        $this->assertTrue($status['can_send']);
        $this->assertNull($status['reason']);
    }

    public function test_health_status_blocks_send_when_instance_is_not_open(): void
    {
        Cache::flush();
        Http::fake([
            'https://evolution.test/*' => Http::response([
                'instance' => [
                    'instanceName' => 'inst-c',
                    'state' => 'close',
                ],
            ], 200),
        ]);

        $provider = new EvolutionWhatsAppProvider(new EvolutionPhoneResolver());
        $config = new WhatsAppProviderConfig('evolution', [
            'base_url' => 'https://evolution.test',
            'instance' => 'inst-c',
            'api_key' => 'api-key-123',
            'status_enabled' => true,
            'verify_ssl' => true,
        ]);

        $status = $provider->getHealthStatus($config);

        $this->assertTrue($status['applies']);
        $this->assertFalse($status['can_send']);
        $this->assertStringContainsString('close', (string) $status['reason']);
    }
}
