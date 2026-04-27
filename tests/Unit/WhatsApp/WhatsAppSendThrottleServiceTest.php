<?php

namespace Tests\Unit\WhatsApp;

use App\Services\ConfiguracaoService;
use App\Services\WhatsApp\DTO\WhatsAppProviderConfig;
use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use App\Services\WhatsAppSendThrottleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class WhatsAppSendThrottleServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_delay_respects_bounds_and_randomization(): void
    {
        $service = $this->makeService([
            'whatsapp.unofficial_throttle_enabled' => true,
            'whatsapp.unofficial_delay_min_seconds' => 1,
            'whatsapp.unofficial_delay_max_seconds' => 2,
        ], provider: 'waha', session: 'session-random');

        $samples = [];
        for ($i = 0; $i < 30; $i++) {
            $samples[] = $service->getRandomDelay('waha');
        }

        foreach ($samples as $delay) {
            $this->assertGreaterThanOrEqual(1, $delay);
            $this->assertLessThanOrEqual(2, $delay);
        }

        $this->assertGreaterThan(1, count(array_unique($samples)));
    }

    public function test_rate_limit_blocks_excess_per_minute(): void
    {
        $service = $this->makeService([
            'whatsapp.unofficial_throttle_enabled' => true,
            'whatsapp.unofficial_max_per_minute' => 1,
            'whatsapp.unofficial_max_per_hour' => 100,
        ], provider: 'waha', session: 'session-minute-limit');

        $first = $service->applyRateLimit('waha');
        $second = $service->applyRateLimit('waha');

        $this->assertTrue($first['allowed']);
        $this->assertFalse($second['allowed']);
        $this->assertSame('minute_limit', $second['reason']);
        $this->assertGreaterThanOrEqual(1, $second['retry_after']);
    }

    public function test_send_window_is_respected(): void
    {
        $service = $this->makeService([
            'whatsapp.unofficial_throttle_enabled' => true,
            'whatsapp.unofficial_send_window_start' => '08:00',
            'whatsapp.unofficial_send_window_end' => '18:00',
        ], provider: 'waha', session: 'session-window');

        $outsideWindow = CarbonImmutable::create(2026, 4, 26, 7, 59, 0, 'UTC');
        $insideWindow = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, 'UTC');

        $this->assertFalse($service->canSendNow($outsideWindow, 'waha'));
        $this->assertSame(60, $service->secondsUntilNextWindow($outsideWindow, 'waha'));
        $this->assertTrue($service->canSendNow($insideWindow, 'waha'));
    }

    public function test_pause_is_applied_every_configured_batch(): void
    {
        $service = $this->makeService([
            'whatsapp.unofficial_throttle_enabled' => true,
            'whatsapp.unofficial_pause_every' => 3,
            'whatsapp.unofficial_pause_min_seconds' => 5,
            'whatsapp.unofficial_pause_max_seconds' => 5,
        ], provider: 'waha', session: 'session-pause');

        $this->assertFalse($service->shouldPause('waha'));
        $this->assertFalse($service->shouldPause('waha'));
        $this->assertTrue($service->shouldPause('waha'));
        $this->assertSame(5, $service->getPauseDuration('waha'));
    }

    public function test_meta_provider_is_not_throttled(): void
    {
        $service = $this->makeService([
            'whatsapp.unofficial_throttle_enabled' => true,
            'whatsapp.unofficial_delay_min_seconds' => 10,
            'whatsapp.unofficial_delay_max_seconds' => 10,
        ], provider: 'meta', session: 'meta-session');

        $this->assertFalse($service->shouldThrottle('meta'));
        $this->assertSame(0, $service->getRandomDelay('meta'));
        $this->assertTrue($service->canSendNow(provider: 'meta'));
        $this->assertSame(['delay' => 0, 'reason' => null], $service->nextDelayDecision('meta'));
    }

    private function makeService(
        array $values,
        string $provider,
        string $session
    ): WhatsAppSendThrottleService {
        $configuracaoService = Mockery::mock(ConfiguracaoService::class);
        $configuracaoService->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null) use ($values): mixed {
                return $values[$key] ?? $default;
            });

        $providerConfigResolver = Mockery::mock(WhatsAppProviderConfigResolver::class);
        $providerConfigResolver->shouldReceive('resolveNotificationProvider')
            ->andReturn($provider);
        $providerConfigResolver->shouldReceive('resolveNotificationConfig')
            ->andReturnUsing(static function (string $provider) use ($session): WhatsAppProviderConfig {
                return new WhatsAppProviderConfig($provider, [
                    'session' => $session,
                    'instance' => $session,
                ]);
            });

        return new WhatsAppSendThrottleService($configuracaoService, $providerConfigResolver);
    }
}
