<?php

namespace Tests\Feature;

use App\Models\BotConversation;
use App\Services\AlunoService;
use App\Services\Bot\BotEngine;
use App\Services\Bot\BotState;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Services\EventoCursoService;
use App\Services\MatriculaService;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BotEngineReopenEndedConversationTest extends TestCase
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
            $table->boolean('is_open')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 50)->nullable();
            $table->timestamps();
            $table->unique(['channel', 'from']);
        });

        Schema::create('bot_message_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('direction', 10);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->foreign('conversation_id')
                ->references('id')
                ->on('bot_conversations')
                ->cascadeOnDelete();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bot_message_logs');
        Schema::dropIfExists('bot_conversations');

        parent::tearDown();
    }

    public function test_ended_conversation_is_reopened_on_new_message(): void
    {
        // Conversation closed by bot:close-inactive: state=ENDED, last_activity_at=now() (just closed).
        // Despite recent last_activity_at, the ENDED state must trigger a reopen.
        $closedAt = now()->subMinutes(30);
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => BotState::ENDED,
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'is_open' => false,
            'closed_at' => $closedAt,
            'closed_reason' => 'inactive',
            'last_activity_at' => now(),
        ]);

        $engine = $this->makeEngine();
        $engine->handleIncoming('waha', '556793087866', 'menu');

        $conversation->refresh();

        $this->assertNotSame(BotState::ENDED, $conversation->state);
        $this->assertNull($conversation->closed_at);
        $this->assertNull($conversation->closed_reason);
        $this->assertTrue((bool) $conversation->is_open);
        $this->assertNotNull($conversation->last_activity_at);
        // reply_chat_id must be preserved so WAHA can still reach the user.
        $this->assertSame('556793087866@c.us', ($conversation->context['reply_chat_id'] ?? null));
    }

    public function test_ended_conversation_with_internal_lid_from_is_reopened_and_preserves_reply_chat_id(): void
    {
        // Simulates a WAHA conversation where `from` is an internal LID identifier.
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '215084110503978',
            'state' => BotState::ENDED,
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'is_open' => false,
            'closed_at' => now()->subHours(1),
            'closed_reason' => 'inactive',
            'last_activity_at' => now(),
        ]);

        $engine = $this->makeEngine();
        // Phone::normalize strips non-digits; '215084110503978' stays as-is after normalize.
        $engine->handleIncoming('waha', '215084110503978', 'oi');

        $conversation->refresh();

        $this->assertNotSame(BotState::ENDED, $conversation->state);
        $this->assertNull($conversation->closed_at);
        $this->assertNull($conversation->closed_reason);
        $this->assertSame('556793087866@c.us', ($conversation->context['reply_chat_id'] ?? null));
    }

    public function test_reopened_conversation_is_closed_again_by_close_inactive_after_timeout(): void
    {
        // Full cycle: ENDED → reopen → inactivity → close again.
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => BotState::ENDED,
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'is_open' => false,
            'closed_at' => now()->subHours(1),
            'closed_reason' => 'inactive',
            'last_activity_at' => now(),
        ]);

        // Step 1: reopen.
        $engine = $this->makeEngine();
        $engine->handleIncoming('waha', '556793087866', 'menu');

        $conversation->refresh();
        $this->assertNotSame(BotState::ENDED, $conversation->state);
        $this->assertNull($conversation->closed_at);

        // Step 2: simulate inactivity by back-dating last_activity_at.
        $conversation->update(['last_activity_at' => now()->subHours(2)]);

        // Step 3: bot:close-inactive should see and close it.
        $this->assertDatabaseHas('bot_conversations', [
            'id' => $conversation->id,
        ]);

        $conversation->refresh();
        $this->assertNotSame(BotState::ENDED, $conversation->state);
        $this->assertTrue(
            $conversation->last_activity_at->lt(now()->subHour()),
            'last_activity_at should be in the past to qualify for inactivity close'
        );
    }

    private function makeEngine(): BotEngine
    {
        $configuracaoService = Mockery::mock(ConfiguracaoService::class);
        $configuracaoService->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null): mixed {
                return match ($key) {
                    'bot.enabled' => true,
                    'bot.provider' => 'waha',
                    'bot.session_timeout_minutes' => 15,
                    'bot.audit_log_enabled' => false,
                    default => $default,
                };
            });

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        return new BotEngine(
            $configuracaoService,
            $this->app->make(AlunoService::class),
            $this->app->make(MatriculaService::class),
            $this->app->make(EventoCursoService::class),
            $this->app->make(NotificationService::class),
            $factory
        );
    }
}
