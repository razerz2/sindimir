<?php

namespace Tests\Feature;

use App\Enums\LegacyNotificationType;
use App\Enums\StatusMatricula;
use App\Enums\UserRole;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Aluno;
use App\Models\Configuracao;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\UserCourseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UserCourseNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_dispatches_internal_notifications_when_active(): void
    {
        [$matricula, $evento] = $this->createScenario();

        User::factory()->create([
            'name' => 'Admin Destino',
            'email' => 'admin-notify@example.com',
            'whatsapp' => '65999990000',
            'role' => UserRole::Admin,
        ]);
        User::factory()->create([
            'name' => 'Aluno Ignorado',
            'email' => 'aluno-ignore@example.com',
            'whatsapp' => '65999990001',
            'role' => UserRole::Aluno,
        ]);

        NotificationTemplate::create([
            'notification_type' => LegacyNotificationType::USUARIO_INSCRICAO_CURSO,
            'canal' => 'email',
            'assunto' => 'Nova inscrição: {{curso_nome}}',
            'conteudo' => 'Aluno: {{aluno_nome}} | Curso: {{curso_nome}} | Link: {{link_admin}}',
            'ativo' => true,
        ]);
        NotificationTemplate::create([
            'notification_type' => LegacyNotificationType::USUARIO_INSCRICAO_CURSO,
            'canal' => 'whatsapp',
            'assunto' => null,
            'conteudo' => 'Aluno: {{aluno_nome}} | CPF: {{aluno_cpf}}',
            'ativo' => true,
        ]);

        $this->setConfig('notificacao.auto.usuario_inscricao_curso.ativo', true);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.email', true);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.whatsapp', true);
        $this->setConfig('notificacao.email_ativo', true);
        $this->setConfig('notificacao.whatsapp_ativo', true);

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        app(UserCourseNotificationService::class)->notifyEnrollment($matricula, $evento);

        Bus::assertDispatchedTimes(SendEmailNotificationJob::class, 1);
        Bus::assertDispatched(SendEmailNotificationJob::class, function (SendEmailNotificationJob $job): bool {
            return $job->destinatarioTipo === 'usuario'
                && $job->destinatarioId === null
                && $job->email === 'admin-notify@example.com'
                && $job->notificationType === LegacyNotificationType::USUARIO_INSCRICAO_CURSO
                && str_contains($job->body, 'Aluno Exemplo');
        });

        Bus::assertDispatchedTimes(SendWhatsAppNotificationJob::class, 1);
        Bus::assertDispatched(SendWhatsAppNotificationJob::class, function (SendWhatsAppNotificationJob $job): bool {
            return $job->destinatarioTipo === 'usuario'
                && $job->destinatarioId === null
                && $job->celular === '5565999990000'
                && $job->notificationType === LegacyNotificationType::USUARIO_INSCRICAO_CURSO;
        });
    }

    public function test_cancellation_dispatches_internal_notifications_when_active(): void
    {
        [$matricula, $evento] = $this->createScenario();
        $matricula->update(['status' => StatusMatricula::Cancelada]);

        User::factory()->create([
            'name' => 'Usuario Destino',
            'email' => 'usuario-notify@example.com',
            'whatsapp' => '65999990002',
            'role' => UserRole::Usuario,
        ]);

        $this->setConfig('notificacao.auto.usuario_cancelamento_curso.ativo', true);
        $this->setConfig('notificacao.auto.usuario_cancelamento_curso.canal.email', true);
        $this->setConfig('notificacao.auto.usuario_cancelamento_curso.canal.whatsapp', false);
        $this->setConfig('notificacao.email_ativo', true);

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        app(UserCourseNotificationService::class)->notifyCancellation($matricula, $evento);

        Bus::assertDispatchedTimes(SendEmailNotificationJob::class, 1);
        Bus::assertDispatched(SendEmailNotificationJob::class, function (SendEmailNotificationJob $job): bool {
            return $job->email === 'usuario-notify@example.com'
                && $job->notificationType === LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO;
        });
        Bus::assertNotDispatched(SendWhatsAppNotificationJob::class);
    }

    public function test_it_does_not_dispatch_when_event_is_disabled(): void
    {
        [$matricula, $evento] = $this->createScenario();

        User::factory()->create([
            'email' => 'admin-disabled@example.com',
            'whatsapp' => '65999990003',
            'role' => UserRole::Admin,
        ]);

        $this->setConfig('notificacao.auto.usuario_inscricao_curso.ativo', false);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.email', true);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.whatsapp', true);
        $this->setConfig('notificacao.email_ativo', true);
        $this->setConfig('notificacao.whatsapp_ativo', true);

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        app(UserCourseNotificationService::class)->notifyEnrollment($matricula, $evento);

        Bus::assertNotDispatched(SendEmailNotificationJob::class);
        Bus::assertNotDispatched(SendWhatsAppNotificationJob::class);
    }

    public function test_it_respects_channel_configuration(): void
    {
        [$matricula, $evento] = $this->createScenario();

        User::factory()->create([
            'email' => 'admin-channel@example.com',
            'whatsapp' => '65999990004',
            'role' => UserRole::Admin,
        ]);

        $this->setConfig('notificacao.auto.usuario_inscricao_curso.ativo', true);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.email', false);
        $this->setConfig('notificacao.auto.usuario_inscricao_curso.canal.whatsapp', true);
        $this->setConfig('notificacao.email_ativo', true);
        $this->setConfig('notificacao.whatsapp_ativo', true);

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        app(UserCourseNotificationService::class)->notifyEnrollment($matricula, $evento);

        Bus::assertNotDispatched(SendEmailNotificationJob::class);
        Bus::assertDispatchedTimes(SendWhatsAppNotificationJob::class, 1);
    }

    /**
     * @return array{Matricula, EventoCurso}
     */
    private function createScenario(): array
    {
        $curso = Curso::create([
            'nome' => 'Curso de Teste',
            'descricao' => 'Curso de Teste',
            'categoria_id' => null,
            'validade' => null,
            'limite_vagas' => 30,
            'ativo' => true,
        ]);

        $evento = EventoCurso::create([
            'curso_id' => $curso->id,
            'numero_evento' => 'EVT-001',
            'data_inicio' => now()->addDays(10)->toDateString(),
            'data_fim' => now()->addDays(11)->toDateString(),
            'horario_inicio' => '08:00',
            'horario_fim' => '12:00',
            'carga_horaria' => 8,
            'municipio' => 'Miranda',
            'local_realizacao' => 'Sede',
            'turno' => null,
            'ativo' => true,
        ]);

        $aluno = Aluno::create([
            'cpf' => '12345678909',
            'nome_completo' => 'Aluno Exemplo',
            'email' => 'aluno@example.com',
            'celular' => '65999998888',
        ]);

        $matricula = Matricula::create([
            'aluno_id' => $aluno->id,
            'evento_curso_id' => $evento->id,
            'status' => StatusMatricula::Pendente,
        ]);

        return [$matricula, $evento];
    }

    private function setConfig(string $chave, mixed $valor): void
    {
        Configuracao::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
