<?php

namespace Tests\Feature\Webhooks;

use App\Services\Bot\BotEngine;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\Bot\Providers\BotProviderInterface;
use App\Services\ConfiguracaoService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class BotWahaWebhookControllerTest extends TestCase
{
    public function test_processes_payload_with_payload_from_and_payload_body(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '5567999999999', 'Ola WAHA')
            ->andReturn('Resposta WAHA');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('5567999999999@c.us', 'Resposta WAHA');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message.any',
            'payload' => [
                'from' => '5567999999999@c.us',
                'body' => 'Ola WAHA',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_processes_payload_with_payload_id_remote_and_payload_message_body(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '5567988887777', 'Mensagem 2')
            ->andReturn('Resposta 2');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('5567988887777@c.us', 'Resposta 2');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'type' => 'messages.upsert',
            'payload' => [
                'id' => [
                    'remote' => '5567988887777@c.us',
                ],
                'message' => [
                    'body' => 'Mensagem 2',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_ignores_payload_when_from_me_is_true(): void
    {
        Log::spy();

        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldNotReceive('handleIncoming');
        $this->app->instance(BotEngine::class, $engine);

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldNotReceive('make');
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message.created',
            'payload' => [
                'fromMe' => true,
                'from' => '5567999999999@c.us',
                'body' => 'Nao deve processar',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ignored']);

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'BOT WAHA webhook ignored'
                    && ($context['reason'] ?? null) === 'from_me';
            })
            ->once();
    }

    public function test_ignores_payload_when_provider_is_not_waha(): void
    {
        Log::spy();

        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'zapi',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldNotReceive('handleIncoming');
        $this->app->instance(BotEngine::class, $engine);

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldNotReceive('make');
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message.any',
            'payload' => [
                'from' => '5567999999999@c.us',
                'body' => 'Teste',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ignored']);

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'BOT WAHA webhook ignored'
                    && ($context['reason'] ?? null) === 'provider_mismatch';
            })
            ->once();
    }

    public function test_calls_bot_engine_when_payload_uses_payload_event(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '5567991112222', 'menu')
            ->andReturn('ok');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('5567991112222@c.us', 'ok');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'payload' => [
                'event' => 'message_created',
                'from' => '5567991112222@c.us',
                'body' => 'menu',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_uses_real_reply_chat_id_when_payload_from_is_internal_identifier(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '215084110503978', 'menu')
            ->andReturn('Resposta real');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', 'Resposta real');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message',
            'payload' => [
                'from' => '215084110503978@c.us',
                'body' => 'menu',
                '_data' => [
                    'Info' => [
                        'Chat' => '556793087866@c.us',
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_uses_payload_participant_as_reply_chat_id_when_from_is_internal_identifier(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '215084110503978', 'oi')
            ->andReturn('Resposta participante');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', 'Resposta participante');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message',
            'payload' => [
                'from' => '215084110503978@c.us',
                'participant' => '556793087866@c.us',
                'body' => 'oi',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_uses_sender_alt_as_reply_chat_id_when_chat_is_internal_identifier(): void
    {
        $this->mockBotConfig([
            'bot.enabled' => true,
            'bot.provider' => 'waha',
        ]);

        $engine = Mockery::mock(BotEngine::class);
        $engine->shouldReceive('handleIncoming')
            ->once()
            ->with('waha', '215084110503978', 'menu')
            ->andReturn('Resposta SenderAlt');
        $this->app->instance(BotEngine::class, $engine);

        $provider = Mockery::mock(BotProviderInterface::class);
        $provider->shouldReceive('sendText')
            ->once()
            ->with('556793087866@c.us', 'Resposta SenderAlt');

        $factory = Mockery::mock(BotProviderFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('waha')
            ->andReturn($provider);
        $this->app->instance(BotProviderFactory::class, $factory);

        // Real WAHA payload: Chat contains an internal/LID identifier (15 digits),
        // SenderAlt contains the real user JID with device suffix (e.g. :7).
        $response = $this->postJson('/webhooks/bot/waha', [
            'event' => 'message',
            'payload' => [
                'from' => '215084110503978@c.us',
                'body' => 'menu',
                '_data' => [
                    'Info' => [
                        'Chat' => '215084110503978@c.us',
                        'SenderAlt' => '556793087866:7@s.whatsapp.net',
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function mockBotConfig(array $values): void
    {
        $configuracaoService = Mockery::mock(ConfiguracaoService::class);
        $configuracaoService->shouldReceive('get')
            ->andReturnUsing(static function (string $key, mixed $default = null) use ($values): mixed {
                return $values[$key] ?? $default;
            });

        $this->app->instance(ConfiguracaoService::class, $configuracaoService);
    }
}
