<?php

namespace App\Services;

use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppSendThrottleService
{
    private const NON_OFFICIAL_PROVIDERS = ['waha', 'evolution', 'zapi'];
    private const RATE_LIMIT_MINUTE_KEY = 'whatsapp-send-minute';
    private const RATE_LIMIT_HOUR_KEY = 'whatsapp-send-hour';
    private const PAUSE_COUNTER_KEY = 'whatsapp-send-pause-counter';

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly WhatsAppProviderConfigResolver $providerConfigResolver
    ) {
    }

    public function shouldThrottle(?string $provider = null): bool
    {
        try {
            $provider = $this->resolveProvider($provider);
            if (! in_array($provider, self::NON_OFFICIAL_PROVIDERS, true)) {
                return false;
            }

            return $this->getBoolSetting('whatsapp.unofficial_throttle_enabled', false);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao avaliar ativacao. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getRandomDelay(?string $provider = null): int
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return 0;
            }

            $minDelay = $this->getIntSetting('whatsapp.unofficial_delay_min_seconds', 2, 0);
            $maxDelay = $this->getIntSetting('whatsapp.unofficial_delay_max_seconds', 8, 0);
            if ($maxDelay < $minDelay) {
                $maxDelay = $minDelay;
            }

            return random_int($minDelay, $maxDelay);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao calcular delay aleatorio. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function canSendNow(?CarbonInterface $now = null, ?string $provider = null): bool
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return true;
            }

            $now = $this->resolveNow($now);
            [$start, $end] = $this->resolveSendWindow();

            return $this->isInsideWindow($now, $start, $end);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao validar janela de envio. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * @return array{allowed: bool, retry_after: int, reason: string|null}
     */
    public function applyRateLimit(?string $provider = null): array
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return [
                    'allowed' => true,
                    'retry_after' => 0,
                    'reason' => null,
                ];
            }

            $provider = $this->resolveProvider($provider);
            $scopeKey = $this->resolveScopeKey($provider);
            $minuteLimit = $this->getIntSetting('whatsapp.unofficial_max_per_minute', 20, 0);
            $hourLimit = $this->getIntSetting('whatsapp.unofficial_max_per_hour', 400, 0);

            $minuteRateKey = self::RATE_LIMIT_MINUTE_KEY . ':' . $scopeKey;
            $hourRateKey = self::RATE_LIMIT_HOUR_KEY . ':' . $scopeKey;

            $blockedReason = null;
            $retryAfter = 0;

            if ($minuteLimit > 0 && RateLimiter::tooManyAttempts($minuteRateKey, $minuteLimit)) {
                $blockedReason = 'minute_limit';
                $retryAfter = max($retryAfter, RateLimiter::availableIn($minuteRateKey));
            }

            if ($hourLimit > 0 && RateLimiter::tooManyAttempts($hourRateKey, $hourLimit)) {
                $blockedReason = $blockedReason ?? 'hour_limit';
                $retryAfter = max($retryAfter, RateLimiter::availableIn($hourRateKey));
            }

            if ($blockedReason !== null) {
                return [
                    'allowed' => false,
                    'retry_after' => max(1, $retryAfter),
                    'reason' => $blockedReason,
                ];
            }

            if ($minuteLimit > 0) {
                RateLimiter::hit($minuteRateKey, 60);
            }

            if ($hourLimit > 0) {
                RateLimiter::hit($hourRateKey, 3600);
            }

            return [
                'allowed' => true,
                'retry_after' => 0,
                'reason' => null,
            ];
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao aplicar rate limit. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return [
                'allowed' => true,
                'retry_after' => 0,
                'reason' => null,
            ];
        }
    }

    public function shouldPause(?string $provider = null): bool
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return false;
            }

            $pauseEvery = $this->getIntSetting('whatsapp.unofficial_pause_every', 0, 0);
            if ($pauseEvery <= 0) {
                return false;
            }

            $provider = $this->resolveProvider($provider);
            $scopeKey = $this->resolveScopeKey($provider);
            $counterKey = self::PAUSE_COUNTER_KEY . ':' . $scopeKey;

            Cache::add($counterKey, 0, now()->addDay());
            $counter = (int) Cache::increment($counterKey);

            return $counter > 0 && $counter % $pauseEvery === 0;
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao calcular pausa inteligente. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getPauseDuration(?string $provider = null): int
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return 0;
            }

            $minPause = $this->getIntSetting('whatsapp.unofficial_pause_min_seconds', 8, 0);
            $maxPause = $this->getIntSetting('whatsapp.unofficial_pause_max_seconds', 20, 0);
            if ($maxPause < $minPause) {
                $maxPause = $minPause;
            }

            return random_int($minPause, $maxPause);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao calcular duracao de pausa. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    public function secondsUntilNextWindow(?CarbonInterface $now = null, ?string $provider = null): int
    {
        try {
            if (! $this->shouldThrottle($provider)) {
                return 0;
            }

            $now = $this->resolveNow($now);
            [$start, $end] = $this->resolveSendWindow();
            if ($this->isInsideWindow($now, $start, $end)) {
                return 0;
            }

            [$startHour, $startMinute] = array_map('intval', explode(':', $start));
            $todayStart = $now->setTime($startHour, $startMinute, 0);
            $nextStart = $todayStart->greaterThan($now) ? $todayStart : $todayStart->addDay();

            return max(1, $now->diffInSeconds($nextStart));
        } catch (Throwable $exception) {
            Log::warning('WhatsApp throttle: falha ao calcular proxima janela. Aplicando fallback seguro.', [
                'erro' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @return array{delay: int, reason: string|null}
     */
    public function nextDelayDecision(?string $provider = null, ?CarbonInterface $now = null): array
    {
        if (! $this->shouldThrottle($provider)) {
            return ['delay' => 0, 'reason' => null];
        }

        if (! $this->canSendNow($now, $provider)) {
            return [
                'delay' => max(1, $this->secondsUntilNextWindow($now, $provider)),
                'reason' => 'outside_window',
            ];
        }

        $rateLimit = $this->applyRateLimit($provider);
        if (! $rateLimit['allowed']) {
            return [
                'delay' => max(1, (int) ($rateLimit['retry_after'] ?? 60)),
                'reason' => (string) ($rateLimit['reason'] ?? 'rate_limit'),
            ];
        }

        if ($this->shouldPause($provider)) {
            return [
                'delay' => max(1, $this->getPauseDuration($provider)),
                'reason' => 'pause',
            ];
        }

        return ['delay' => 0, 'reason' => null];
    }

    private function resolveProvider(?string $provider = null): string
    {
        $provider = $provider !== null
            ? mb_strtolower(trim($provider))
            : $this->providerConfigResolver->resolveNotificationProvider();

        return mb_strtolower(trim((string) $provider));
    }

    private function resolveNow(?CarbonInterface $now = null): CarbonImmutable
    {
        if ($now instanceof CarbonImmutable) {
            return $now;
        }

        if ($now !== null) {
            return CarbonImmutable::createFromInterface($now);
        }

        return CarbonImmutable::now($this->resolveTimezone());
    }

    /**
     * @return array{string, string}
     */
    private function resolveSendWindow(): array
    {
        $start = $this->normalizeTime(
            $this->getStringSetting('whatsapp.unofficial_send_window_start', '00:00'),
            '00:00'
        );
        $end = $this->normalizeTime(
            $this->getStringSetting('whatsapp.unofficial_send_window_end', '23:59'),
            '23:59'
        );

        return [$start, $end];
    }

    private function isInsideWindow(CarbonImmutable $now, string $start, string $end): bool
    {
        if ($start === $end) {
            return true;
        }

        $current = $now->format('H:i');
        if ($start < $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }

    private function resolveScopeKey(string $provider): string
    {
        $tenant = $this->resolveTenantKey();
        $session = $this->resolveSessionScope($provider);

        return "{$tenant}:{$provider}:{$session}";
    }

    private function resolveTenantKey(): string
    {
        $tenantId = trim((string) $this->configuracaoService->get('tenant.id', ''));
        if ($tenantId === '') {
            $tenantId = (string) config('app.url', config('app.name', 'app'));
        }

        return substr(sha1(mb_strtolower($tenantId)), 0, 16);
    }

    private function resolveSessionScope(string $provider): string
    {
        $config = $this->providerConfigResolver->resolveNotificationConfig($provider);

        $session = match ($provider) {
            'waha' => $config->getString('session', 'default'),
            'evolution', 'zapi' => $config->getString('instance', 'default'),
            default => 'default',
        };

        return $this->sanitizeScopeValue($session);
    }

    private function sanitizeScopeValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'default';
        }

        $sanitized = (string) preg_replace('/[^a-z0-9_-]+/i', '-', mb_strtolower($value));
        $sanitized = trim($sanitized, '-');
        if ($sanitized !== '') {
            return $sanitized;
        }

        return substr(sha1($value), 0, 12);
    }

    private function getBoolSetting(string $key, bool $default): bool
    {
        $value = $this->configuracaoService->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $value = mb_strtolower(trim($value));

            return in_array($value, ['1', 'true', 'on', 'yes', 'sim'], true);
        }

        return $default;
    }

    private function getIntSetting(string $key, int $default, int $min): int
    {
        $value = $this->configuracaoService->get($key, $default);
        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, (int) $value);
    }

    private function getStringSetting(string $key, string $default): string
    {
        $value = $this->configuracaoService->get($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    private function normalizeTime(string $value, string $default): string
    {
        return preg_match('/^(2[0-3]|[01]\d):[0-5]\d$/', $value)
            ? $value
            : $default;
    }

    private function resolveTimezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }
}
