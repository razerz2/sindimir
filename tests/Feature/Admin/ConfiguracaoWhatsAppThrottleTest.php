<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendWhatsAppTestMessageJob;
use App\Models\Configuracao;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class ConfiguracaoWhatsAppThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_testar_whatsapp_queues_message_with_delay_when_throttle_is_enabled(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 10, 0, 0, config('app.timezone')));

        $this->withoutMiddleware();
        $this->setConfig('whatsapp.provedor', 'waha');
        $this->setConfig('whatsapp.unofficial_throttle_enabled', true);
        $this->setConfig('whatsapp.unofficial_delay_min_seconds', 6);
        $this->setConfig('whatsapp.unofficial_delay_max_seconds', 6);
        $this->setConfig('whatsapp.unofficial_send_window_start', '00:00');
        $this->setConfig('whatsapp.unofficial_send_window_end', '23:59');

        $whatsAppService = Mockery::mock(WhatsAppService::class);
        $whatsAppService->shouldReceive('canTestSend')->once()->andReturn(true);
        $whatsAppService->shouldReceive('sendTest')->never();
        $this->app->instance(WhatsAppService::class, $whatsAppService);

        Bus::fake([SendWhatsAppTestMessageJob::class]);

        $response = $this->post(route('admin.configuracoes.whatsapp.testar'), [
            'whatsapp_test_numero' => '5567999999999',
            'whatsapp_test_mensagem' => 'Mensagem teste',
        ]);

        $response->assertRedirect(route('admin.configuracoes.index'));
        $response->assertSessionHas('whatsapp_test_status', function ($status): bool {
            return ($status['type'] ?? null) === 'success'
                && str_contains((string) ($status['message'] ?? ''), 'agendada');
        });

        Bus::assertDispatched(SendWhatsAppTestMessageJob::class, function (SendWhatsAppTestMessageJob $job): bool {
            if (! $job->delay instanceof DateTimeInterface) {
                return false;
            }

            return $job->delay->format('Y-m-d H:i:s') === now()->addSeconds(6)->format('Y-m-d H:i:s');
        });
    }

    private function setConfig(string $chave, mixed $valor): void
    {
        Configuracao::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
