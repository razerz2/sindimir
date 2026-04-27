<?php

namespace App\Jobs;

use App\Services\WhatsAppSendThrottleService;
use App\Services\WhatsAppService;
use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppTestMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $to,
        public readonly string $message
    ) {
    }

    public function handle(
        WhatsAppService $whatsAppService,
        WhatsAppSendThrottleService $throttleService,
        WhatsAppProviderConfigResolver $providerConfigResolver
    ): void {
        $provider = $providerConfigResolver->resolveNotificationProvider();
        $delayDecision = $throttleService->nextDelayDecision($provider);
        if (($delayDecision['delay'] ?? 0) > 0) {
            $reason = (string) ($delayDecision['reason'] ?? 'unknown');
            $delaySeconds = max(1, (int) $delayDecision['delay']);

            $this->redispatchWithDelay($delaySeconds);

            $logMessage = match ($reason) {
                'outside_window' => 'WhatsApp throttle: teste fora da janela permitida, reagendado.',
                'pause' => 'WhatsApp throttle: pausa inteligente aplicada ao teste.',
                'minute_limit', 'hour_limit' => 'WhatsApp throttle: teste bloqueado por rate limit, reagendado.',
                default => 'WhatsApp throttle: teste reagendado.',
            };

            Log::info($logMessage, [
                'provider' => $provider,
                'delay_seconds' => $delaySeconds,
                'reason' => $reason,
            ]);

            return;
        }

        $whatsAppService->sendTest($this->to, $this->message);
    }

    private function redispatchWithDelay(int $seconds): void
    {
        self::dispatch(
            $this->to,
            $this->message
        )->delay(now()->addSeconds(max(1, $seconds)));
    }
}
