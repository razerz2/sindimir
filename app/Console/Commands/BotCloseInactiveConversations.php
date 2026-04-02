<?php

namespace App\Console\Commands;

use App\Models\BotConversation;
use App\Models\BotMessageLog;
use App\Services\Bot\BotState;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BotCloseInactiveConversations extends Command
{
    protected $signature = 'bot:close-inactive';

    protected $description = 'Encerra conversas do BOT por inatividade e envia mensagem de encerramento.';

    private ?bool $hasConversationIsOpenColumn = null;

    private ?bool $hasConversationClosedAtColumn = null;

    private ?bool $hasConversationClosedReasonColumn = null;

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotProviderFactory $providerFactory
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('bot_conversations')) {
            $this->info('Tabela bot_conversations nao encontrada. Nada a processar.');

            return self::SUCCESS;
        }

        if (! (bool) $this->configuracaoService->get('bot.enabled', false)) {
            $this->info('BOT desabilitado. Encerramento por inatividade ignorado.');

            return self::SUCCESS;
        }

        $provider = $this->resolveActiveProvider();
        $timeoutMinutes = $this->getSessionTimeoutMinutes();
        $cutoff = now()->subMinutes($timeoutMinutes);

        $query = BotConversation::query()
            ->where('channel', $provider)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<', $cutoff)
            ->where(function ($builder) {
                $builder->whereNull('state')
                    ->orWhere('state', '!=', BotState::ENDED);
            });

        if ($this->hasConversationClosedAtColumn()) {
            $query->whereNull('closed_at');
        }

        $conversations = $query->get();

        if ($conversations->isEmpty()) {
            $this->info('Nenhuma conversa inativa para encerrar.');

            return self::SUCCESS;
        }

        $closeMessage = $this->getInactiveCloseMessage();
        $channelProvider = $this->providerFactory->make($provider);
        $auditEnabled = (bool) $this->configuracaoService->get('bot.audit_log_enabled', true);
        $canLogMessages = $auditEnabled && Schema::hasTable('bot_message_logs');

        $processed = 0;
        $sendFailures = 0;

        foreach ($conversations as $conversation) {
            if (trim((string) $conversation->from) === '') {
                continue;
            }

            try {
                $channelProvider->sendText((string) $conversation->from, $closeMessage);
            } catch (Throwable $exception) {
                $sendFailures++;
                Log::warning('Falha ao enviar mensagem de encerramento por inatividade do BOT.', [
                    'conversation_id' => $conversation->id,
                    'channel' => $conversation->channel,
                    'from' => $conversation->from,
                    'error' => $exception->getMessage(),
                ]);
            }

            $payload = [
                'state' => BotState::ENDED,
                'context' => [],
                'last_activity_at' => now(),
            ];

            if ($this->hasConversationIsOpenColumn()) {
                $payload['is_open'] = false;
            }

            if ($this->hasConversationClosedAtColumn()) {
                $payload['closed_at'] = now();
            }

            if ($this->hasConversationClosedReasonColumn()) {
                $payload['closed_reason'] = 'inactive';
            }

            $conversation->update($payload);

            if ($canLogMessages) {
                BotMessageLog::query()->create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'out',
                    'payload' => [
                        'event' => 'inactive_close',
                        'text' => $closeMessage,
                        'provider' => $provider,
                    ],
                ]);
            }

            $processed++;
        }

        $this->info(
            sprintf(
                'Conversas encerradas por inatividade: %d. Falhas de envio: %d.',
                $processed,
                $sendFailures
            )
        );

        return self::SUCCESS;
    }

    private function resolveActiveProvider(): string
    {
        $provider = mb_strtolower(trim((string) $this->configuracaoService->get('bot.provider', 'meta')));
        $supported = $this->providerFactory->supportedChannels();

        if (in_array($provider, $supported, true)) {
            return $provider;
        }

        return $supported[0] ?? 'meta';
    }

    private function getSessionTimeoutMinutes(): int
    {
        $timeout = (int) $this->configuracaoService->get('bot.session_timeout_minutes', 15);

        if ($timeout < 1) {
            return 1;
        }

        if ($timeout > 1440) {
            return 1440;
        }

        return $timeout;
    }

    private function getInactiveCloseMessage(): string
    {
        $message = trim((string) $this->configuracaoService->get(
            'bot.inactive_close_message',
            '⏳ Sessão encerrada por inatividade. Quando precisar, digite *menu* para iniciar novamente.'
        ));

        if ($message === '') {
            return '⏳ Sessão encerrada por inatividade. Quando precisar, digite *menu* para iniciar novamente.';
        }

        return $message;
    }

    private function hasConversationIsOpenColumn(): bool
    {
        if ($this->hasConversationIsOpenColumn !== null) {
            return $this->hasConversationIsOpenColumn;
        }

        $this->hasConversationIsOpenColumn = Schema::hasColumn('bot_conversations', 'is_open');

        return $this->hasConversationIsOpenColumn;
    }

    private function hasConversationClosedAtColumn(): bool
    {
        if ($this->hasConversationClosedAtColumn !== null) {
            return $this->hasConversationClosedAtColumn;
        }

        $this->hasConversationClosedAtColumn = Schema::hasColumn('bot_conversations', 'closed_at');

        return $this->hasConversationClosedAtColumn;
    }

    private function hasConversationClosedReasonColumn(): bool
    {
        if ($this->hasConversationClosedReasonColumn !== null) {
            return $this->hasConversationClosedReasonColumn;
        }

        $this->hasConversationClosedReasonColumn = Schema::hasColumn('bot_conversations', 'closed_reason');

        return $this->hasConversationClosedReasonColumn;
    }
}
