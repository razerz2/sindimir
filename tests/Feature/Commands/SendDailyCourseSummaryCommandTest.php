<?php

namespace Tests\Feature\Commands;

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
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendDailyCourseSummaryCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_envia_resumo_quando_ativo(): void
    {
        $now = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, config('app.timezone'));
        CarbonImmutable::setTestNow($now);

        $this->createAdminUser();
        $this->createActiveEventWithMatriculas();
        $this->seedSummaryConfig(active: true, email: true, whatsapp: true, horario: '08:00');

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(SendEmailNotificationJob::class, 1);
        Bus::assertDispatched(SendEmailNotificationJob::class, function (SendEmailNotificationJob $job): bool {
            return $job->notificationType === LegacyNotificationType::USUARIO_RESUMO_DIARIO_CURSOS
                && str_contains($job->body, 'Resumo diário de cursos ativos');
        });

        Bus::assertDispatchedTimes(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_nao_envia_quando_desativado(): void
    {
        $now = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, config('app.timezone'));
        CarbonImmutable::setTestNow($now);

        $this->createAdminUser();
        $this->createActiveEventWithMatriculas();
        $this->seedSummaryConfig(active: false, email: true, whatsapp: true, horario: '08:00');

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);

        Bus::assertNotDispatched(SendEmailNotificationJob::class);
        Bus::assertNotDispatched(SendWhatsAppNotificationJob::class);
    }

    public function test_respeita_canais_configurados(): void
    {
        $now = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, config('app.timezone'));
        CarbonImmutable::setTestNow($now);

        $this->createAdminUser();
        $this->createActiveEventWithMatriculas();
        $this->seedSummaryConfig(active: true, email: false, whatsapp: true, horario: '08:00');

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);

        Bus::assertNotDispatched(SendEmailNotificationJob::class);
        Bus::assertDispatchedTimes(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_nao_duplica_envio_no_mesmo_dia(): void
    {
        $now = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, config('app.timezone'));
        CarbonImmutable::setTestNow($now);

        $this->createAdminUser();
        $this->createActiveEventWithMatriculas();
        $this->seedSummaryConfig(active: true, email: true, whatsapp: false, horario: '08:00');

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);
        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(SendEmailNotificationJob::class, 1);
        Bus::assertNotDispatched(SendWhatsAppNotificationJob::class);
    }

    public function test_nao_quebra_sem_cursos_ativos(): void
    {
        $now = CarbonImmutable::create(2026, 4, 26, 8, 0, 0, config('app.timezone'));
        CarbonImmutable::setTestNow($now);

        $this->createAdminUser();
        $this->seedSummaryConfig(active: true, email: true, whatsapp: true, horario: '08:00');

        Bus::fake([SendEmailNotificationJob::class, SendWhatsAppNotificationJob::class]);

        $this->artisan('cursos:enviar-resumo-diario')
            ->assertExitCode(0);

        Bus::assertNotDispatched(SendEmailNotificationJob::class);
        Bus::assertNotDispatched(SendWhatsAppNotificationJob::class);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'name' => 'Admin Resumo',
            'email' => 'admin.resumo@example.com',
            'whatsapp' => '65999887766',
            'role' => UserRole::Admin,
        ]);
    }

    private function createActiveEventWithMatriculas(): EventoCurso
    {
        $curso = Curso::create([
            'nome' => 'Curso Ativo',
            'descricao' => 'Curso para resumo diário',
            'categoria_id' => null,
            'validade' => null,
            'limite_vagas' => 10,
            'ativo' => true,
        ]);

        $evento = EventoCurso::create([
            'curso_id' => $curso->id,
            'numero_evento' => 'RES-001',
            'data_inicio' => now()->addDays(2)->toDateString(),
            'data_fim' => now()->addDays(3)->toDateString(),
            'horario_inicio' => '08:00',
            'horario_fim' => '12:00',
            'carga_horaria' => 8,
            'municipio' => 'Miranda',
            'local_realizacao' => 'Sindicato',
            'turno' => null,
            'ativo' => true,
        ]);

        $alunoPendente = Aluno::create([
            'cpf' => '12345678909',
            'nome_completo' => 'Aluno Pendente',
            'email' => 'pendente@example.com',
            'celular' => '65911112222',
        ]);
        Matricula::create([
            'aluno_id' => $alunoPendente->id,
            'evento_curso_id' => $evento->id,
            'status' => StatusMatricula::Pendente,
        ]);

        $alunoCancelado = Aluno::create([
            'cpf' => '98765432100',
            'nome_completo' => 'Aluno Cancelado',
            'email' => 'cancelado@example.com',
            'celular' => '65933334444',
        ]);
        $matriculaCancelada = Matricula::create([
            'aluno_id' => $alunoCancelado->id,
            'evento_curso_id' => $evento->id,
            'status' => StatusMatricula::Cancelada,
        ]);
        $matriculaCancelada->update([
            'updated_at' => now(),
        ]);

        return $evento;
    }

    private function seedSummaryConfig(bool $active, bool $email, bool $whatsapp, string $horario): void
    {
        $this->setConfig('notificacao.auto.usuario_resumo_diario_cursos.ativo', $active);
        $this->setConfig('notificacao.auto.usuario_resumo_diario_cursos.canal.email', $email);
        $this->setConfig('notificacao.auto.usuario_resumo_diario_cursos.canal.whatsapp', $whatsapp);
        $this->setConfig('notificacao.auto.usuario_resumo_diario_cursos.horario_envio', $horario);
        $this->setConfig('notificacao.email_ativo', true);
        $this->setConfig('notificacao.whatsapp_ativo', true);
    }

    private function setConfig(string $chave, mixed $valor): void
    {
        Configuracao::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
