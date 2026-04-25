<?php

namespace Tests\Unit\Bot;

use App\Services\AlunoService;
use App\Services\Bot\BotEngine;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Services\EventoCursoService;
use App\Services\MatriculaService;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BotEngineWahaConversationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('bot_message_logs');
        Schema::dropIfExists('bot_conversations');

        Schema::create('bot_conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 30);
            $table->string('from', 30);
            $table->string('state', 120)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->unique(['channel', 'from']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bot_message_logs');
        Schema::dropIfExists('bot_conversations');

        parent::tearDown();
    }

    public function test_bot_engine_creates_conversation_with_waha_channel(): void
    {
        $configuracaoService = Mockery::mock(ConfiguracaoService::class);
        $configuracaoService->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null): mixed {
                if ($key === 'bot.audit_log_enabled') {
                    return false;
                }

                return $default;
            });

        $providerFactory = Mockery::mock(BotProviderFactory::class);
        $providerFactory->shouldReceive('supportedChannels')
            ->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $engine = new BotEngine(
            $configuracaoService,
            Mockery::mock(AlunoService::class),
            Mockery::mock(MatriculaService::class),
            Mockery::mock(EventoCursoService::class),
            Mockery::mock(NotificationService::class),
            $providerFactory
        );

        $response = $engine->handleIncoming('waha', '5567999999999', 'oi');

        $this->assertNotSame('', trim($response));
        $this->assertDatabaseHas('bot_conversations', [
            'channel' => 'waha',
            'from' => '5567999999999',
        ]);
    }
}
