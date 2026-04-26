<?php

namespace Tests\Feature;

use App\Enums\LegacyNotificationType;
use App\Enums\StatusMatricula;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Services\MatriculaService;
use App\Services\NotificationService;
use App\Services\UserCourseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MatriculaServiceInternalNotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_solicitar_inscricao_dispara_interna_e_mantem_notificacao_do_aluno(): void
    {
        [$aluno, $evento] = $this->createScenarioData();

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('disparar')
            ->once()
            ->withArgs(function (...$args) use ($evento): bool {
                return count($args) >= 3
                    && $args[1] instanceof EventoCurso
                    && $args[1]->id === $evento->id
                    && $args[2] === LegacyNotificationType::INSCRICAO_CONFIRMAR;
            });

        $userCourseNotificationService = Mockery::mock(UserCourseNotificationService::class);
        $userCourseNotificationService
            ->shouldReceive('notifyEnrollment')
            ->once()
            ->withArgs(function ($matricula, $eventoNotificado): bool {
                return $matricula instanceof Matricula
                    && $eventoNotificado instanceof EventoCurso;
            });

        $this->app->instance(NotificationService::class, $notificationService);
        $this->app->instance(UserCourseNotificationService::class, $userCourseNotificationService);

        $service = $this->app->make(MatriculaService::class);
        $resultado = $service->solicitarInscricao($aluno->id, $evento->id);

        $this->assertSame('created', $resultado['status']);
        $this->assertSame('matricula', $resultado['tipo']);
    }

    public function test_cancelamento_dispara_interna_apenas_uma_vez_na_transicao_de_status(): void
    {
        [$aluno, $evento] = $this->createScenarioData();

        $matricula = Matricula::create([
            'aluno_id' => $aluno->id,
            'evento_curso_id' => $evento->id,
            'status' => StatusMatricula::Pendente,
        ]);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldIgnoreMissing();

        $userCourseNotificationService = Mockery::mock(UserCourseNotificationService::class);
        $userCourseNotificationService
            ->shouldReceive('notifyCancellation')
            ->once()
            ->withArgs(function ($matriculaNotificada, $eventoNotificado): bool {
                return $matriculaNotificada instanceof Matricula
                    && $eventoNotificado instanceof EventoCurso;
            });

        $this->app->instance(NotificationService::class, $notificationService);
        $this->app->instance(UserCourseNotificationService::class, $userCourseNotificationService);

        $service = $this->app->make(MatriculaService::class);
        $service->cancelarMatricula($matricula);

        $matricula = $matricula->fresh();
        $service->cancelarMatriculaEEnviarParaListaEspera($matricula);

        $this->assertSame(StatusMatricula::Cancelada, $matricula->fresh()->status);
    }

    /**
     * @return array{Aluno, EventoCurso}
     */
    private function createScenarioData(): array
    {
        $curso = Curso::create([
            'nome' => 'Curso de Fluxo',
            'descricao' => 'Curso de Fluxo',
            'categoria_id' => null,
            'validade' => null,
            'limite_vagas' => 10,
            'ativo' => true,
        ]);

        $evento = EventoCurso::create([
            'curso_id' => $curso->id,
            'numero_evento' => 'EVT-TRIGGER',
            'data_inicio' => now()->addDays(5)->toDateString(),
            'data_fim' => now()->addDays(6)->toDateString(),
            'horario_inicio' => '08:00',
            'horario_fim' => '12:00',
            'carga_horaria' => 8,
            'municipio' => 'Miranda',
            'local_realizacao' => 'Sede',
            'turno' => null,
            'ativo' => true,
        ]);

        $aluno = Aluno::create([
            'cpf' => '12345678901',
            'nome_completo' => 'Aluno Fluxo',
            'email' => 'aluno.fluxo@example.com',
            'celular' => '65999997777',
        ]);

        return [$aluno, $evento];
    }
}
