<?php

namespace App\Jobs;

use App\Mail\GenericNotificationMail;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
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
        public readonly string $email,
        public readonly int $cursoId,
        public readonly ?int $eventoCursoId,
        public readonly ?int $notificacaoLinkId,
        public readonly string $notificationType,
        public readonly string $subject,
        public readonly string $body
    ) {
    }

    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new GenericNotificationMail($this->subject, $this->body));

            NotificationLog::create([
                'aluno_id' => $this->destinatarioTipo === 'aluno' ? $this->destinatarioId : null,
                'contato_externo_id' => $this->destinatarioTipo === 'contato_externo' ? $this->destinatarioId : null,
                'tipo_destinatario' => $this->destinatarioTipo,
                'curso_id' => $this->cursoId,
                'evento_curso_id' => $this->eventoCursoId,
                'notificacao_link_id' => $this->notificacaoLinkId,
                'notification_type' => $this->notificationType,
                'canal' => 'email',
                'status' => 'success',
                'erro' => null,
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
                'canal' => 'email',
                'status' => 'failed',
                'erro' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
