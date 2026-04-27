<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\NotificationType;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Aluno;
use App\Models\Configuracao;
use App\Models\Curso;
use App\Services\NotificationService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WhatsAppThrottleDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_applies_initial_delay_for_unofficial_provider(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 10, 0, 0, config('app.timezone')));

        $curso = $this->createCurso();
        $aluno = $this->createAluno();

        $this->setConfig('notificacao.email_ativo', false);
        $this->setConfig('notificacao.whatsapp_ativo', true);
        $this->setConfig('whatsapp.provedor', 'waha');
        $this->setConfig('whatsapp.unofficial_throttle_enabled', true);
        $this->setConfig('whatsapp.unofficial_delay_min_seconds', 7);
        $this->setConfig('whatsapp.unofficial_delay_max_seconds', 7);

        Bus::fake([SendWhatsAppNotificationJob::class]);

        app(NotificationService::class)->disparar([$aluno], $curso, NotificationType::CURSO_DISPONIVEL);

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, function (SendWhatsAppNotificationJob $job): bool {
            if (! $job->delay instanceof DateTimeInterface) {
                return false;
            }

            return $job->delay->format('Y-m-d H:i:s') === now()->addSeconds(7)->format('Y-m-d H:i:s');
        });
    }

    public function test_meta_provider_does_not_apply_throttle_delay(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 10, 0, 0, config('app.timezone')));

        $curso = $this->createCurso();
        $aluno = $this->createAluno();

        $this->setConfig('notificacao.email_ativo', false);
        $this->setConfig('notificacao.whatsapp_ativo', true);
        $this->setConfig('whatsapp.provedor', 'meta');
        $this->setConfig('whatsapp.unofficial_throttle_enabled', true);
        $this->setConfig('whatsapp.unofficial_delay_min_seconds', 9);
        $this->setConfig('whatsapp.unofficial_delay_max_seconds', 9);

        Bus::fake([SendWhatsAppNotificationJob::class]);

        app(NotificationService::class)->disparar([$aluno], $curso, NotificationType::CURSO_DISPONIVEL);

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, function (SendWhatsAppNotificationJob $job): bool {
            return $job->delay === null;
        });
    }

    private function createCurso(): Curso
    {
        return Curso::create([
            'nome' => 'Curso Throttle',
            'descricao' => 'Curso para teste de throttle',
            'categoria_id' => null,
            'validade' => null,
            'limite_vagas' => 20,
            'ativo' => true,
        ]);
    }

    private function createAluno(): Aluno
    {
        return Aluno::create([
            'cpf' => '12345678909',
            'nome_completo' => 'Aluno Throttle',
            'email' => 'aluno.throttle@example.com',
            'celular' => '65999998888',
        ]);
    }

    private function setConfig(string $chave, mixed $valor): void
    {
        Configuracao::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
