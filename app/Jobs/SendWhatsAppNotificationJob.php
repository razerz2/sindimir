<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\WhatsAppSendThrottleService;
use App\Services\WhatsAppService;
use App\Services\WhatsApp\WhatsAppProviderConfigResolver;
use App\Services\WhatsApp\WhatsAppProviderStatusService;
use App\Support\WhatsAppMessageFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $destinatarioTipo,
        public readonly ?int $destinatarioId,
        public readonly string $destinatarioNome,
        public readonly string $celular,
        public readonly int $cursoId,
        public readonly ?int $eventoCursoId,
        public readonly ?int $notificacaoLinkId,
        public readonly string $notificationType,
        public readonly string $message
    ) {
    }

    public function handle(
        WhatsAppService $whatsAppService,
        WhatsAppProviderStatusService $providerStatusService,
        WhatsAppSendThrottleService $throttleService,
        WhatsAppProviderConfigResolver $providerConfigResolver
    ): void
    {
        $formattedMessage = null;

        try {
            $formattedMessage = WhatsAppMessageFormatter::format($this->message);
            $provider = $providerConfigResolver->resolveNotificationProvider();

            $status = $providerStatusService->getActiveProviderStatus();
            if ($status['applies'] && ! $status['can_send']) {
                NotificationLog::create([
                    'aluno_id' => $this->destinatarioTipo === 'aluno' ? $this->destinatarioId : null,
                    'contato_externo_id' => $this->destinatarioTipo === 'contato_externo' ? $this->destinatarioId : null,
                    'tipo_destinatario' => $this->destinatarioTipo,
                    'curso_id' => $this->cursoId,
                    'evento_curso_id' => $this->eventoCursoId,
                    'notificacao_link_id' => $this->notificacaoLinkId,
                    'notification_type' => $this->notificationType,
                    'canal' => 'whatsapp',
                    'status' => 'failed',
                    'erro' => $status['reason'] ?? 'Provedor WhatsApp indisponivel no momento.',
                    'mensagem' => $formattedMessage ?? $this->message,
                ]);

                return;
            }

            $delayDecision = $throttleService->nextDelayDecision($provider);
            if (($delayDecision['delay'] ?? 0) > 0) {
                $reason = (string) ($delayDecision['reason'] ?? 'unknown');
                $delaySeconds = max(1, (int) $delayDecision['delay']);

                $this->redispatchWithDelay($delaySeconds);

                $logMessage = match ($reason) {
                    'outside_window' => 'WhatsApp throttle: envio fora da janela permitida, reagendado.',
                    'pause' => 'WhatsApp throttle: pausa inteligente aplicada.',
                    'minute_limit', 'hour_limit' => 'WhatsApp throttle: bloqueio por rate limit, envio reagendado.',
                    default => 'WhatsApp throttle: envio reagendado.',
                };

                Log::info($logMessage, [
                    'provider' => $provider,
                    'notification_type' => $this->notificationType,
                    'delay_seconds' => $delaySeconds,
                    'reason' => $reason,
                ]);

                return;
            }

            $whatsAppService->send($this->celular, $formattedMessage);

            NotificationLog::create([
                'aluno_id' => $this->destinatarioTipo === 'aluno' ? $this->destinatarioId : null,
                'contato_externo_id' => $this->destinatarioTipo === 'contato_externo' ? $this->destinatarioId : null,
                'tipo_destinatario' => $this->destinatarioTipo,
                'curso_id' => $this->cursoId,
                'evento_curso_id' => $this->eventoCursoId,
                'notificacao_link_id' => $this->notificacaoLinkId,
                'notification_type' => $this->notificationType,
                'canal' => 'whatsapp',
                'status' => 'success',
                'erro' => null,
                'mensagem' => $formattedMessage,
            ]);
        } catch (Throwable $exception) {
            NotificationLog::create([
                'aluno_id' => $this->destinatarioTipo === 'aluno' ? $this->destinatarioId : null,
                'contato_externo_id' => $this->destinatarioTipo === 'contato_externo' ? $this->destinatarioId : null,
                'tipo_destinatario' => $this->destinatarioTipo,
                'curso_id' => $this->cursoId,
                'evento_curso_id' => $this->eventoCursoId,
                'notificacao_link_id' => $this->notificacaoLinkId,
                'notification_type' => $this->notificationType,
                'canal' => 'whatsapp',
                'status' => 'failed',
                'erro' => $exception->getMessage(),
                'mensagem' => $formattedMessage ?? $this->message,
            ]);

            throw $exception;
        }
    }

    private function redispatchWithDelay(int $seconds): void
    {
        self::dispatch(
            $this->destinatarioTipo,
            $this->destinatarioId,
            $this->destinatarioNome,
            $this->celular,
            $this->cursoId,
            $this->eventoCursoId,
            $this->notificacaoLinkId,
            $this->notificationType,
            $this->message
        )->delay(now()->addSeconds(max(1, $seconds)));
    }
}
