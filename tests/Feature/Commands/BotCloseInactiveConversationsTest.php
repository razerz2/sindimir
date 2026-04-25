<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\BotCloseInactiveConversations;
use App\Models\BotConversation;
use App\Models\BotMessageLog;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\Bot\BotState;
use App\Services\Bot\Providers\BotProviderInterface;
use App\Services\ConfiguracaoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class BotCloseInactiveConversationsTest extends TestCase
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

    public function test_sends_closing_message_to_waha_reply_chat_id_from_context(): void
    {
        // Simulates a WAHA conversation where `from` holds an internal LID identifier
        // but the correct reply JID is stored in context.reply_chat_id.
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '215084110503978',
            'state' => 'MENU',
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'last_activity_at' => now()->subHours(2),
        ]);

        $this->assertDatabaseCount('bot_conversations', 1);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
            'bot.session_timeout_minutes' => 15,
            'bot.audit_log_enabled' => true,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);

        $conversation->refresh();
        $this->assertSame(BotState::ENDED, $conversation->state);
        $this->assertSame('inactive', $conversation->closed_reason);
        $this->assertNotNull($conversation->closed_at);
        // reply_chat_id must survive the close so subsequent reopens and close cycles still reach the user.
        $this->assertSame('556793087866@c.us', $conversation->context['reply_chat_id'] ?? null);

        $log = BotMessageLog::query()->where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('out', $log->direction);
        $this->assertSame('inactive_close', $log->payload['event'] ?? null);
        $this->assertSame('waha', $log->payload['provider'] ?? null);
    }

    public function test_sends_closing_message_using_from_for_non_waha_channels(): void
    {
        // For meta/zapi/evolution, the existing `from` field must still be used unchanged.
        BotConversation::query()->create([
            'channel' => 'meta',
            'from' => '5567999999999',
            'state' => 'MENU',
            'context' => [],
            'last_activity_at' => now()->subHours(2),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('5567999999999', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('meta')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.provider' => 'meta',
            'bot.session_timeout_minutes' => 15,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);
    }

    public function test_waha_falls_back_to_from_when_no_reply_chat_id_in_context(): void
    {
        // When context.reply_chat_id is absent, `from` is used as the destination.
        BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => 'MENU',
            'context' => [],
            'last_activity_at' => now()->subHours(2),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
            'bot.session_timeout_minutes' => 15,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);
    }

    public function test_closes_waha_conversation_when_inactive_for_more_than_timeout(): void
    {
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => 'MENU',
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'last_activity_at' => now()->subMinutes(6),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 5,
            'bot.audit_log_enabled' => true,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);
        $conversation->refresh();
        $this->assertSame(BotState::ENDED, $conversation->state);
        $this->assertNotNull($conversation->closed_at);
        $this->assertSame('inactive', $conversation->closed_reason);
    }

    public function test_does_not_close_waha_conversation_before_timeout(): void
    {
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => 'MENU',
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'last_activity_at' => now()->subMinutes(4),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldNotReceive('sendText');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldNotReceive('make');
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 5,
            'bot.audit_log_enabled' => true,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);
        $conversation->refresh();
        $this->assertSame('MENU', $conversation->state);
        $this->assertNull($conversation->closed_at);
        $this->assertNull($conversation->closed_reason);
    }

    public function test_dry_run_lists_candidates_without_closing_conversation(): void
    {
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '556793087866',
            'state' => 'MENU',
            'context' => ['reply_chat_id' => '556793087866@c.us'],
            'last_activity_at' => now()->subMinutes(30),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldNotReceive('sendText');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldNotReceive('make');
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 5,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(
            new BotCloseInactiveConversations($configuracaoService, $factory),
            ['--dry-run' => true]
        );

        $this->assertSame(0, $exitCode);
        $conversation->refresh();
        $this->assertSame('MENU', $conversation->state);
        $this->assertNull($conversation->closed_at);
        $this->assertNull($conversation->closed_reason);
    }

    public function test_context_reply_chat_id_is_preserved_after_inactive_close(): void
    {
        // Core invariant: closing for inactivity must NOT wipe context.reply_chat_id because:
        // - The next close cycle still needs it to reach the user via WAHA.
        // - After the user reopens the conversation (Fix 3), reply_chat_id must already be in context
        //   so reopenConversation() can carry it forward before persistReplyChatId() runs again.
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '215084110503978',
            'state' => 'MENU',
            'context' => [
                'reply_chat_id' => '556793087866@c.us',
                'some_other_key' => 'should_be_preserved_too',
            ],
            'last_activity_at' => now()->subHours(1),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 15,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);

        $conversation->refresh();
        $this->assertSame(BotState::ENDED, $conversation->state);
        $this->assertSame('inactive', $conversation->closed_reason);
        $this->assertNotNull($conversation->closed_at);
        $this->assertSame('556793087866@c.us', $conversation->context['reply_chat_id'] ?? null);
        $this->assertSame('should_be_preserved_too', $conversation->context['some_other_key'] ?? null);
    }

    public function test_late_empty_context_update_after_inactive_close_does_not_wipe_context_without_clear_flag(): void
    {
        $conversation = BotConversation::query()->create([
            'channel' => 'waha',
            'from' => '215084110503978',
            'state' => 'MENU',
            'context' => [
                'reply_chat_id' => '556793087866@c.us',
                'some_other_key' => 'must_survive',
            ],
            'last_activity_at' => now()->subHours(1),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 15,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));
        $this->assertSame(0, $exitCode);

        $conversation->refresh();
        $this->assertSame(BotState::ENDED, $conversation->state);
        $this->assertSame('556793087866@c.us', $conversation->context['reply_chat_id'] ?? null);

        // Simula um update tardio de outro fluxo tentando zerar context.
        $conversation->update([
            'state' => BotState::MENU,
            'context' => [],
            'last_activity_at' => now(),
        ]);

        $conversation->refresh();
        $this->assertSame('556793087866@c.us', $conversation->context['reply_chat_id'] ?? null);
        $this->assertSame('must_survive', $conversation->context['some_other_key'] ?? null);

        // O clear só é permitido quando explicitamente solicitado.
        $conversation->updateWithContextPolicy(['context' => []], true, __METHOD__);
        $conversation->refresh();
        $this->assertSame([], $conversation->context);
    }

    public function test_json_string_context_from_database_is_preserved_after_inactive_close(): void
    {
        $conversationId = DB::table('bot_conversations')->insertGetId([
            'channel' => 'waha',
            'from' => '215084110503978',
            'state' => 'MENU',
            'context' => json_encode(['reply_chat_id' => '556793087866@c.us']),
            'last_activity_at' => now()->subMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 5,
            'bot.audit_log_enabled' => true,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);

        $conversation = BotConversation::query()->findOrFail($conversationId);
        $this->assertSame(BotState::ENDED, $conversation->state);
        $this->assertSame('inactive', $conversation->closed_reason);
        $this->assertNotNull($conversation->closed_at);
        $this->assertSame('556793087866@c.us', $conversation->context['reply_chat_id'] ?? null);
        $this->assertNotSame([], $conversation->context);

        $log = BotMessageLog::query()
            ->where('conversation_id', $conversationId)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('out', $log->direction);
        $this->assertSame('inactive_close', $log->payload['event'] ?? null);
        $this->assertSame('waha', $log->payload['provider'] ?? null);
    }

    public function test_null_and_empty_context_fall_back_to_from_without_error(): void
    {
        DB::table('bot_conversations')->insert([
            [
                'channel' => 'waha',
                'from' => '556793087866',
                'state' => 'MENU',
                'context' => null,
                'last_activity_at' => now()->subMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'channel' => 'waha',
                'from' => '556793087867',
                'state' => 'MENU',
                'context' => '',
                'last_activity_at' => now()->subMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866', Mockery::type('string'));
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087867', Mockery::type('string'));

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')->with('waha')->andReturn($provider);
        $factory->shouldReceive('supportedChannels')->andReturn(['meta', 'zapi', 'waha', 'evolution']);

        $configuracaoService = $this->makeConfigMock([
            'bot.enabled' => true,
            'bot.session_timeout_minutes' => 5,
            'bot.audit_log_enabled' => false,
        ]);

        $exitCode = $this->runCommand(new BotCloseInactiveConversations($configuracaoService, $factory));

        $this->assertSame(0, $exitCode);
        $this->assertSame(2, BotConversation::query()->where('state', BotState::ENDED)->count());
    }

    public function test_normalize_context_accepts_json_string_null_and_empty_values(): void
    {
        $factory = Mockery::mock(BotProviderFactory::class);
        $configuracaoService = $this->makeConfigMock([]);
        $command = new BotCloseInactiveConversations($configuracaoService, $factory);
        $method = new ReflectionMethod($command, 'normalizeContext');
        $method->setAccessible(true);

        $this->assertSame(
            ['reply_chat_id' => '556793087866@c.us'],
            $method->invoke($command, '{"reply_chat_id":"556793087866@c.us"}')
        );
        $this->assertSame([], $method->invoke($command, null));
        $this->assertSame([], $method->invoke($command, ''));
        $this->assertSame([], $method->invoke($command, '{invalid-json'));
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function runCommand(BotCloseInactiveConversations $command, array $arguments = []): int
    {
        $command->setLaravel($this->app);

        return $command->run(new ArrayInput($arguments), new NullOutput());
    }

    /**
     * @param array<string, mixed> $values
     */
    private function makeConfigMock(array $values): ConfiguracaoService
    {
        $mock = Mockery::mock(ConfiguracaoService::class);
        $mock->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null) use ($values): mixed {
                return $values[$key] ?? $default;
            });

        return $mock;
    }
}
