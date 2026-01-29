<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use App\Support\WhatsAppMessageFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $alunoId,
        public readonly string $celular,
        public readonly int $cursoId,
        public readonly ?int $eventoCursoId,
        public readonly int $notificacaoLinkId,
        public readonly string $notificationType,
        public readonly string $message
    ) {
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $formattedMessage = null;

        try {
            $formattedMessage = WhatsAppMessageFormatter::format($this->message);

            $whatsAppService->send($this->celular, $formattedMessage);

            NotificationLog::create([
                'aluno_id' => $this->alunoId,
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
                'aluno_id' => $this->alunoId,
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
}
