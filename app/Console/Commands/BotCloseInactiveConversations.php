<?php

namespace App\Console\Commands;

use App\Models\BotConversation;
use App\Models\BotMessageLog;
use App\Services\Bot\BotState;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BotCloseInactiveConversations extends Command
{
    protected $signature = 'bot:close-inactive {--dry-run : Lista candidatas sem encerrar} {--debug : Exibe detalhes de depuracao}';

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

        $dryRun = (bool) $this->option('dry-run');
        $debug = (bool) $this->option('debug');
        $now = $this->nowInAppTimezone();
        [$timeoutMinutes, $timeoutKey] = $this->resolveSessionTimeoutMinutes();
        $cutoff = $now->subMinutes($timeoutMinutes);
        $supportedChannels = $this->resolveSupportedChannels();

        $notEndedQuery = BotConversation::query()
            ->whereIn('channel', $supportedChannels);
        $this->applyNotEndedFilter($notEndedQuery);

        $candidateQuery = BotConversation::query()
            ->whereIn('channel', $supportedChannels)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $cutoff);
        $this->applyNotEndedFilter($candidateQuery);

        if ($this->hasConversationClosedAtColumn()) {
            $candidateQuery->whereNull('closed_at');
        }

        $totalNotEnded = (clone $notEndedQuery)->count();
        $totalsByChannel = (clone $notEndedQuery)
            ->selectRaw('channel, COUNT(*) AS total')
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();
        $totalCandidates = (clone $candidateQuery)->count();

        Log::info('BOT close-inactive started', [
            'app_timezone' => (string) config('app.timezone'),
            'now' => $now->toDateTimeString(),
            'timeout_minutes' => $timeoutMinutes,
            'timeout_key' => $timeoutKey,
            'cutoff' => $cutoff->toDateTimeString(),
            'total_not_ended' => $totalNotEnded,
            'total_by_channel' => $totalsByChannel,
            'total_candidates' => $totalCandidates,
            'dry_run' => $dryRun,
            'debug' => $debug,
        ]);

        $this->line('Timezone app: ' . (string) config('app.timezone'));
        $this->line('Agora: ' . $now->toDateTimeString());
        $this->line('Timeout (min): ' . $timeoutMinutes);
        $this->line('Cutoff: ' . $cutoff->toDateTimeString());
        $this->line('Conversas nao ENDED: ' . $totalNotEnded);
        $this->line('Conversas candidatas: ' . $totalCandidates);
        if ($totalsByChannel !== []) {
            $summary = [];
            foreach ($totalsByChannel as $channel => $total) {
                $summary[] = $channel . '=' . (int) $total;
            }
            $this->line('Totais por channel: ' . implode(', ', $summary));
        }

        $conversations = $candidateQuery
            ->orderBy('last_activity_at')
            ->get();

        if ($conversations->isEmpty()) {
            $this->info('Nenhuma conversa inativa para encerrar.');

            return self::SUCCESS;
        }

        if ($dryRun || $debug) {
            foreach ($conversations as $conversation) {
                $lastActivityAt = $conversation->last_activity_at?->setTimezone((string) config('app.timezone'));
                $diffMinutes = $lastActivityAt?->diffInMinutes($now) ?? null;
                $this->line(sprintf(
                    'Candidata id=%d channel=%s state=%s last_activity_at=%s cutoff=%s diff_min=%s',
                    (int) $conversation->id,
                    (string) $conversation->channel,
                    (string) ($conversation->state ?? 'null'),
                    $lastActivityAt?->toDateTimeString() ?? 'null',
                    $cutoff->toDateTimeString(),
                    $diffMinutes !== null ? (string) $diffMinutes : 'null'
                ));
            }
        }

        if ($dryRun) {
            $this->info('Dry-run ativo: nenhuma conversa foi encerrada.');

            return self::SUCCESS;
        }

        $closeMessage = $this->getInactiveCloseMessage();
        $auditEnabled = (bool) $this->configuracaoService->get('bot.audit_log_enabled', true);
        $canLogMessages = $auditEnabled && Schema::hasTable('bot_message_logs');
        $providerCache = [];

        $processed = 0;
        $sendFailures = 0;

        foreach ($conversations as $conversation) {
            if (trim((string) $conversation->from) === '') {
                continue;
            }

            $channel = mb_strtolower(trim((string) $conversation->channel));
            if (! in_array($channel, $supportedChannels, true)) {
                Log::warning('BOT close-inactive: canal nao suportado', [
                    'conversation_id' => $conversation->id,
                    'channel' => (string) $conversation->channel,
                ]);
                continue;
            }

            if (! isset($providerCache[$channel])) {
                try {
                    $providerCache[$channel] = $this->providerFactory->make($channel);
                } catch (Throwable $exception) {
                    Log::warning('BOT close-inactive: falha ao resolver provider', [
                        'channel' => $channel,
                        'error' => $exception->getMessage(),
                    ]);
                    $sendFailures++;
                    continue;
                }
            }

            $channelProvider = $providerCache[$channel];
            $rawContext = $conversation->context;
            $context = $this->normalizeContext($rawContext);

            Log::info('BOT close-inactive: context before normalize', [
                'conversation_id' => (int) $conversation->id,
                'channel' => (string) $conversation->channel,
                'context_raw_type' => gettype($rawContext),
                'context_raw' => $rawContext,
                'context_normalized' => $context,
                'context_normalized_summary' => $this->summarizeContextForAudit($context),
            ]);

            $destination = $this->resolveDestination($conversation, $context);
            $contextReplyChatId = (string) ($context['reply_chat_id'] ?? '');

            $sendStatus = 'ok';
            try {
                $channelProvider->sendText($destination, $closeMessage);
            } catch (Throwable $exception) {
                $sendStatus = 'error';
                $sendFailures++;
                Log::warning('BOT close-inactive: falha ao enviar mensagem de encerramento por inatividade', [
                    'conversation_id' => $conversation->id,
                    'channel' => $conversation->channel,
                    'from_masked' => $this->maskPhone((string) $conversation->from),
                    'destination_masked' => $this->maskPhone($destination),
                    'error' => $exception->getMessage(),
                ]);
            }

            Log::info('BOT close-inactive: conversa encerrada por inatividade', [
                'conversation_id' => $conversation->id,
                'channel' => $conversation->channel,
                'from_masked' => $this->maskPhone((string) $conversation->from),
                'reply_chat_id_masked' => $contextReplyChatId !== '' ? $this->maskPhone($contextReplyChatId) : null,
                'destination_masked' => $this->maskPhone($destination),
                'destination_source' => $contextReplyChatId !== '' && $destination === $contextReplyChatId
                    ? 'context.reply_chat_id'
                    : 'from',
                'send_status' => $sendStatus,
            ]);

            $payload = [
                'state' => BotState::ENDED,
                'context' => $context,
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

            Log::info('BOT close-inactive: update payload', [
                'conversation_id' => (int) $conversation->id,
                'update_payload' => $payload,
            ]);

            $this->logContextAudit(
                $conversation,
                'bot_close_inactive.handle',
                (string) $payload['state'],
                isset($payload['closed_reason']) ? (string) $payload['closed_reason'] : null,
                $rawContext,
                $context
            );

            $conversation->updateWithContextPolicy($payload, false, __METHOD__);

            if ($canLogMessages) {
                BotMessageLog::query()->create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'out',
                    'payload' => [
                        'event' => 'inactive_close',
                        'text' => $closeMessage,
                        'provider' => $channel,
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

    private function nowInAppTimezone(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('app.timezone'));
    }

    private function resolveSupportedChannels(): array
    {
        $supported = $this->providerFactory->supportedChannels();
        $normalized = [];

        foreach ($supported as $channel) {
            $value = mb_strtolower(trim((string) $channel));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));

        return $normalized !== [] ? $normalized : ['meta', 'zapi', 'waha', 'evolution'];
    }

    private function applyNotEndedFilter(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->whereNull('state')
                ->orWhere('state', '!=', BotState::ENDED);
        });
    }

    /**
     * @param array<string, mixed>|null $context
     */
    private function resolveDestination(BotConversation $conversation, ?array $context = null): string
    {
        if ($conversation->channel === 'waha') {
            $context ??= $this->normalizeContext($conversation->context);
            $replyChatId = trim((string) ($context['reply_chat_id'] ?? ''));
            if ($replyChatId !== '') {
                return $replyChatId;
            }
        }

        return (string) $conversation->from;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeContext(mixed $context): array
    {
        if (is_array($context)) {
            return $context;
        }

        if ($context === null) {
            return [];
        }

        if (! is_string($context)) {
            return [];
        }

        $context = trim($context);
        if ($context === '') {
            return [];
        }

        $decoded = json_decode($context, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function maskPhone(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '***';
        }

        $local = explode('@', $clean)[0] ?? $clean;
        $digits = preg_replace('/\D+/', '', $local) ?? '';

        if ($digits !== '') {
            return '***' . substr($digits, -4);
        }

        if (mb_strlen($local) <= 4) {
            return '***';
        }

        return mb_substr($local, 0, 2) . '***' . mb_substr($local, -2);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{
     *   keys: list<string>,
     *   size: int,
     *   has_reply_chat_id: bool,
     *   reply_chat_id_masked: ?string
     * }
     */
    private function summarizeContextForAudit(array $context): array
    {
        $replyChatId = trim((string) ($context['reply_chat_id'] ?? ''));

        return [
            'keys' => array_values(array_map(
                static fn ($key): string => (string) $key,
                array_keys($context)
            )),
            'size' => count($context),
            'has_reply_chat_id' => $replyChatId !== '',
            'reply_chat_id_masked' => $replyChatId !== '' ? $this->maskPhone($replyChatId) : null,
        ];
    }

    /**
     * @param mixed $previousContext
     * @param array<string, mixed> $newContext
     */
    private function logContextAudit(
        BotConversation $conversation,
        string $origin,
        string $newState,
        ?string $newClosedReason,
        mixed $previousContext,
        array $newContext
    ): void {
        $previousNormalized = $this->normalizeContext($previousContext);
        $payload = [
            'conversation_id' => (int) $conversation->id,
            'origin' => $origin,
            'previous_state' => (string) ($conversation->state ?? ''),
            'new_state' => $newState,
            'previous_closed_reason' => $conversation->closed_reason,
            'new_closed_reason' => $newClosedReason,
            'previous_context_type' => gettype($previousContext),
            'previous_context_summary' => $this->summarizeContextForAudit($previousNormalized),
            'new_context_summary' => $this->summarizeContextForAudit($newContext),
        ];

        if ($this->shouldIncludeAuditTrace()) {
            $payload['stack'] = $this->buildAuditStackTrace();
        }

        Log::info('BOT context audit', $payload);
    }

    private function shouldIncludeAuditTrace(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    /**
     * @return list<string>
     */
    private function buildAuditStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $frames = [];

        foreach (array_slice($trace, 1, 5) as $frame) {
            $class = (string) ($frame['class'] ?? '');
            $type = (string) ($frame['type'] ?? '');
            $function = (string) ($frame['function'] ?? '');
            $file = isset($frame['file']) ? basename((string) $frame['file']) : 'unknown';
            $line = isset($frame['line']) ? (int) $frame['line'] : 0;

            $frames[] = trim($class . $type . $function, ':') . '@' . $file . ':' . $line;
        }

        return $frames;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolveSessionTimeoutMinutes(): array
    {
        $key = 'bot.session_timeout_minutes';
        $raw = $this->configuracaoService->get('bot.session_timeout_minutes', null);
        if ($raw === null) {
            $key = 'bot_session_timeout_minutes';
            $raw = $this->configuracaoService->get('bot_session_timeout_minutes', 15);
        }

        $timeout = (int) $raw;

        if ($timeout < 1) {
            return [1, $key];
        }

        if ($timeout > 1440) {
            return [1440, $key];
        }

        return [$timeout, $key];
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
