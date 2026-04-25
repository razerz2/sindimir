<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotEngine;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Services\WhatsApp\WahaChatIdResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotWahaWebhookController extends Controller
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_EVENTS = [
        'message',
        'message.any',
        'message.created',
        'message.upsert',
        'messages.upsert',
    ];

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotEngine $engine,
        private readonly BotProviderFactory $providerFactory,
        private readonly WahaChatIdResolver $chatIdResolver
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $incoming = $this->extractIncomingData($payload);

        $this->logIncomingPayload($payload, $incoming);

        $enabled = (bool) $this->configuracaoService->get('bot.enabled', false);
        if (! $enabled) {
            return $this->ignored('bot_disabled', $incoming);
        }

        $activeProvider = mb_strtolower(trim((string) $this->configuracaoService->get('bot.provider', 'meta')));
        if ($activeProvider !== 'waha') {
            return $this->ignored('provider_mismatch', $incoming, ['active_provider' => $activeProvider]);
        }

        if (! $this->isSupportedEvent($incoming['event'])) {
            return $this->ignored('unsupported_event', $incoming);
        }

        if ($incoming['from_me']) {
            return $this->ignored('from_me', $incoming);
        }

        if ($incoming['group_message']) {
            return $this->ignored('group_message', $incoming);
        }

        $fromIdentity = $incoming['from_identity'];
        if ($fromIdentity === null || $fromIdentity === '') {
            return $this->ignored('missing_from', $incoming);
        }

        $text = $incoming['text'];
        if ($text === null || $text === '') {
            return $this->ignored('missing_text', $incoming);
        }

        $replyChatId = $incoming['reply_chat_id'];
        if ($replyChatId === null || $replyChatId === '') {
            return $this->ignored('missing_reply_chat_id', $incoming);
        }

        try {
            $responseText = $this->engine->handleIncoming('waha', $fromIdentity, $text);
            if (trim($responseText) !== '') {
                $this->providerFactory->make('waha')->sendText($replyChatId, $responseText);
            }
        } catch (Throwable $exception) {
            Log::error('Erro ao processar webhook BOT WAHA', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        // Persist the verified reply JID so commands like bot:close-inactive can use it.
        try {
            $this->engine->persistReplyChatId('waha', $fromIdentity, $replyChatId);
        } catch (Throwable $exception) {
            Log::warning('BOT WAHA: falha ao persistir reply_chat_id no contexto da conversa', [
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *   event: string,
     *   event_raw: ?string,
     *   from_identity: ?string,
     *   from_identity_raw: ?string,
     *   from_masked: ?string,
     *   reply_chat_id: ?string,
     *   reply_chat_id_source: ?string,
     *   reply_chat_id_masked: ?string,
     *   text: ?string,
     *   has_text: bool,
     *   text_length: int,
     *   from_me: bool,
     *   group_message: bool
     * }
     */
    private function extractIncomingData(array $payload): array
    {
        $eventRaw = $this->firstScalarValue([
            data_get($payload, 'event'),
            data_get($payload, 'type'),
            data_get($payload, 'payload.event'),
            data_get($payload, 'data.event'),
        ]);
        $event = $this->normalizeEvent($eventRaw);

        $fromIdentityCandidates = [
            data_get($payload, 'payload.from'),
            data_get($payload, 'payload.chatId'),
            data_get($payload, 'payload.author'),
            data_get($payload, 'payload.participant'),
            data_get($payload, 'payload.id.remote'),
            data_get($payload, 'payload._data.from'),
            data_get($payload, 'payload._data.id.remote'),
            data_get($payload, 'data.from'),
            data_get($payload, 'data.chatId'),
            data_get($payload, 'from'),
            data_get($payload, 'chatId'),
        ];

        $replyChatIdCandidates = [
            // SenderAlt carries the real user JID (may include device suffix like :7).
            ['source' => 'payload._data.Info.SenderAlt', 'value' => data_get($payload, 'payload._data.Info.SenderAlt')],
            ['source' => 'payload._data.info.senderAlt', 'value' => data_get($payload, 'payload._data.info.senderAlt')],
            ['source' => 'payload._data.Info.SenderAlt.User', 'value' => data_get($payload, 'payload._data.Info.SenderAlt.User')],
            ['source' => 'payload._data.Info.SenderAlt.user', 'value' => data_get($payload, 'payload._data.Info.SenderAlt.user')],
            ['source' => 'payload._data.Info.Sender', 'value' => data_get($payload, 'payload._data.Info.Sender')],
            ['source' => 'payload._data.Info.Chat', 'value' => data_get($payload, 'payload._data.Info.Chat')],
            ['source' => 'payload._data.Info.Sender.User', 'value' => data_get($payload, 'payload._data.Info.Sender.User')],
            ['source' => 'payload._data.Info.Chat.User', 'value' => data_get($payload, 'payload._data.Info.Chat.User')],
            ['source' => 'payload._data.info.chat', 'value' => data_get($payload, 'payload._data.info.chat')],
            ['source' => 'payload._data.key.remoteJid', 'value' => data_get($payload, 'payload._data.key.remoteJid')],
            ['source' => 'payload._data.key.participant', 'value' => data_get($payload, 'payload._data.key.participant')],
            ['source' => 'payload._data.id.remote', 'value' => data_get($payload, 'payload._data.id.remote')],
            ['source' => 'payload._data.from', 'value' => data_get($payload, 'payload._data.from')],
            ['source' => 'payload._data.to', 'value' => data_get($payload, 'payload._data.to')],
            ['source' => 'payload.id.remote', 'value' => data_get($payload, 'payload.id.remote')],
            ['source' => 'payload.participant', 'value' => data_get($payload, 'payload.participant')],
            ['source' => 'payload.to', 'value' => data_get($payload, 'payload.to')],
            ['source' => 'payload.chatId', 'value' => data_get($payload, 'payload.chatId')],
            ['source' => 'payload.from', 'value' => data_get($payload, 'payload.from')],
        ];

        $textCandidates = [
            data_get($payload, 'payload.body'),
            data_get($payload, 'payload.text'),
            data_get($payload, 'payload.caption'),
            data_get($payload, 'payload.message.body'),
            data_get($payload, 'payload.message.text'),
            data_get($payload, 'payload.data.body'),
            data_get($payload, 'payload.data.text'),
            data_get($payload, 'payload._data.body'),
            data_get($payload, 'payload._data.caption'),
            data_get($payload, 'data.body'),
            data_get($payload, 'data.text'),
            data_get($payload, 'body'),
            data_get($payload, 'text'),
            data_get($payload, 'message.body'),
            data_get($payload, 'message.text'),
        ];

        $fromMeCandidates = [
            data_get($payload, 'payload.fromMe'),
            data_get($payload, 'payload.me'),
            data_get($payload, 'payload._data.id.fromMe'),
            data_get($payload, 'payload.id.fromMe'),
            data_get($payload, 'data.fromMe'),
            data_get($payload, 'fromMe'),
        ];

        $fromMe = false;
        foreach ($fromMeCandidates as $candidate) {
            $candidateBool = $this->toBool($candidate);
            if ($candidateBool !== null) {
                $fromMe = $candidateBool;
                break;
            }
        }

        $fromIdentity = null;
        $fromIdentityRaw = null;
        $groupMessage = false;

        foreach ($fromIdentityCandidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $raw = trim((string) $candidate);
            $lower = mb_strtolower($raw);
            if ($raw === '') {
                continue;
            }

            if (str_contains($lower, '@g.us') || str_contains($lower, 'status@broadcast')) {
                $groupMessage = true;
                continue;
            }

            $phone = $this->chatIdResolver->toPhone($raw);
            if ($phone !== '') {
                $fromIdentity = $phone;
                $fromIdentityRaw = $raw;
                break;
            }
        }

        $replyChatId = null;
        $replyChatIdSource = null;

        // First pass: prefer explicit individual JID candidates (@c.us / @s.whatsapp.net).
        foreach ($replyChatIdCandidates as $candidate) {
            $candidateValue = $candidate['value'] ?? null;
            if (! is_scalar($candidateValue)) {
                continue;
            }

            $raw = trim((string) $candidateValue);
            $lower = mb_strtolower($raw);
            if ($raw === '' || str_contains($lower, '@lid')) {
                continue;
            }

            $isPreferredJid = str_ends_with($lower, '@c.us') || str_ends_with($lower, '@s.whatsapp.net');
            if (! $isPreferredJid) {
                continue;
            }

            $normalized = $this->chatIdResolver->normalizeReplyChatId($raw);
            if ($normalized === '') {
                continue;
            }

            $replyChatId = $normalized;
            $replyChatIdSource = (string) ($candidate['source'] ?? 'unknown');
            break;
        }

        // Second pass: accept other candidates that can be normalized to a valid reply chat id.
        if ($replyChatId === null) {
            foreach ($replyChatIdCandidates as $candidate) {
                $candidateValue = $candidate['value'] ?? null;
                if (! is_scalar($candidateValue)) {
                    continue;
                }

                $raw = trim((string) $candidateValue);
                $lower = mb_strtolower($raw);
                if ($raw === '' || str_contains($lower, '@lid')) {
                    continue;
                }

                $normalized = $this->chatIdResolver->normalizeReplyChatId($raw);
                if ($normalized === '') {
                    continue;
                }

                $replyChatId = $normalized;
                $replyChatIdSource = (string) ($candidate['source'] ?? 'unknown');
                break;
            }
        }

        // Controlled fallbacks requested for real payloads.
        if ($replyChatId === null) {
            $participantFallback = data_get($payload, 'payload.participant');
            if (is_scalar($participantFallback)) {
                $participantRaw = trim((string) $participantFallback);
                if ($this->chatIdResolver->toPhone($participantRaw) !== '') {
                    $normalized = $this->chatIdResolver->normalizeReplyChatId($participantRaw);
                    if ($normalized !== '') {
                        $replyChatId = $normalized;
                        $replyChatIdSource = 'payload.participant_fallback';
                    }
                }
            }
        }

        if ($replyChatId === null) {
            $toFallback = data_get($payload, 'payload.to');
            if (is_scalar($toFallback)) {
                $toRaw = trim((string) $toFallback);
                if ($this->chatIdResolver->toPhone($toRaw) !== '') {
                    $normalized = $this->chatIdResolver->normalizeReplyChatId($toRaw);
                    if ($normalized !== '') {
                        $replyChatId = $normalized;
                        $replyChatIdSource = 'payload.to_fallback';
                    }
                }
            }
        }

        if ($replyChatId !== null) {
            $replyLower = mb_strtolower($replyChatId);
            if (str_contains($replyLower, '@g.us') || str_contains($replyLower, 'status@broadcast')) {
                $groupMessage = true;
            }
        }

        $text = null;
        foreach ($textCandidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $value = trim((string) $candidate);
            if ($value !== '') {
                $text = $value;
                break;
            }
        }

        return [
            'event' => $event,
            'event_raw' => $eventRaw,
            'from_identity' => $fromIdentity,
            'from_identity_raw' => $fromIdentityRaw,
            'from_masked' => $fromIdentityRaw !== null ? $this->maskIdentifier($fromIdentityRaw) : null,
            'reply_chat_id' => $replyChatId,
            'reply_chat_id_source' => $replyChatIdSource,
            'reply_chat_id_masked' => $replyChatId !== null ? $this->maskIdentifier($replyChatId) : null,
            'text' => $text,
            'has_text' => $text !== null && $text !== '',
            'text_length' => $text !== null ? mb_strlen($text) : 0,
            'from_me' => $fromMe,
            'group_message' => $groupMessage,
        ];
    }

    private function isSupportedEvent(string $event): bool
    {
        if ($event === '') {
            return false;
        }

        if (in_array($event, self::SUPPORTED_EVENTS, true)) {
            return true;
        }

        return preg_match('/^messages?(\.(any|created|upsert))?$/', $event) === 1;
    }

    private function normalizeEvent(?string $event): string
    {
        $normalized = mb_strtolower(trim((string) $event));
        if ($normalized === '') {
            return '';
        }

        $normalized = str_replace(['-', '_', ' '], '.', $normalized);
        $normalized = preg_replace('/\.+/', '.', $normalized) ?? $normalized;

        return trim($normalized, '.');
    }

    private function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        return null;
    }

    /**
     * @param list<mixed> $candidates
     */
    private function firstScalarValue(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{
     *   event: string,
     *   event_raw: ?string,
     *   from_identity: ?string,
     *   from_identity_raw: ?string,
     *   from_masked: ?string,
     *   reply_chat_id: ?string,
     *   reply_chat_id_source: ?string,
     *   reply_chat_id_masked: ?string,
     *   text: ?string,
     *   has_text: bool,
     *   text_length: int,
     *   from_me: bool,
     *   group_message: bool
     * } $incoming
     */
    private function logIncomingPayload(array $payload, array $incoming): void
    {
        Log::info('BOT WAHA webhook received', [
            'event' => $incoming['event'] !== '' ? $incoming['event'] : null,
            'event_raw' => $incoming['event_raw'],
            'top_level_keys' => array_values(array_keys($payload)),
            'payload_keys' => $this->arrayKeys(data_get($payload, 'payload')),
            'data_keys' => $this->arrayKeys(data_get($payload, 'data')),
            'message_keys' => $this->arrayKeys(data_get($payload, 'message')),
            'payload_data_keys' => $this->arrayKeys(data_get($payload, 'payload._data')),
            'payload_data_identifier_candidates' => $this->collectMaskedIdentifierCandidates(
                data_get($payload, 'payload._data'),
                'payload._data'
            ),
            'payload_preview' => [
                'has_payload_node' => is_array(data_get($payload, 'payload')),
                'has_data_node' => is_array(data_get($payload, 'data')),
                'has_message_node' => is_array(data_get($payload, 'message')),
                'from_masked' => $incoming['from_masked'],
                'reply_chat_id_masked' => $incoming['reply_chat_id_masked'],
                'reply_chat_id_source' => $incoming['reply_chat_id_source'],
                'has_text' => $incoming['has_text'],
                'from_me' => $incoming['from_me'],
            ],
            'from_masked' => $incoming['from_masked'],
            'reply_chat_id_masked' => $incoming['reply_chat_id_masked'],
            'reply_chat_id_source' => $incoming['reply_chat_id_source'],
            'has_text' => $incoming['has_text'],
            'text_length' => $incoming['text_length'],
            'from_me' => $incoming['from_me'],
            'group_message' => $incoming['group_message'],
        ]);
    }

    /**
     * @param array{
     *   event: string,
     *   event_raw: ?string,
     *   from_identity: ?string,
     *   from_identity_raw: ?string,
     *   from_masked: ?string,
     *   reply_chat_id: ?string,
     *   reply_chat_id_source: ?string,
     *   reply_chat_id_masked: ?string,
     *   text: ?string,
     *   has_text: bool,
     *   text_length: int,
     *   from_me: bool,
     *   group_message: bool
     * } $incoming
     * @param array<string, mixed> $extra
     */
    private function ignored(string $reason, array $incoming, array $extra = []): JsonResponse
    {
        Log::info('BOT WAHA webhook ignored', array_merge([
            'reason' => $reason,
            'event' => $incoming['event'] !== '' ? $incoming['event'] : null,
            'event_raw' => $incoming['event_raw'],
            'from_masked' => $incoming['from_masked'],
            'reply_chat_id_masked' => $incoming['reply_chat_id_masked'],
            'reply_chat_id_source' => $incoming['reply_chat_id_source'],
            'has_text' => $incoming['has_text'],
            'text_length' => $incoming['text_length'],
            'from_me' => $incoming['from_me'],
            'group_message' => $incoming['group_message'],
        ], $extra));

        return response()->json(['status' => 'ignored'], 200);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function arrayKeys(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn ($key): string => (string) $key,
            array_keys($value)
        ));
    }

    /**
     * @param mixed $value
     * @return list<array{path: string, masked: string}>
     */
    private function collectMaskedIdentifierCandidates(mixed $value, string $path): array
    {
        $result = [];
        $this->walkIdentifierCandidates($value, $path, $result);

        return $result;
    }

    /**
     * @param mixed $value
     * @param list<array{path: string, masked: string}> $result
     */
    private function walkIdentifierCandidates(mixed $value, string $path, array &$result): void
    {
        if (count($result) >= 30) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->walkIdentifierCandidates($item, $path . '.' . (string) $key, $result);
                if (count($result) >= 30) {
                    break;
                }
            }

            return;
        }

        if (! is_scalar($value)) {
            return;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return;
        }

        $lower = mb_strtolower($raw);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $looksLikeIdentifier = str_contains($lower, '@c.us')
            || str_contains($lower, '@s.whatsapp.net')
            || str_contains($lower, '@lid')
            || str_contains($lower, '@g.us')
            || strlen($digits) >= 10;

        if (! $looksLikeIdentifier) {
            return;
        }

        $result[] = [
            'path' => $path,
            'masked' => $this->maskIdentifier($raw),
        ];
    }

    private function maskIdentifier(string $identifier): string
    {
        $value = trim($identifier);
        if ($value === '') {
            return '***';
        }

        $local = explode('@', $value)[0] ?? $value;
        $digits = preg_replace('/\D+/', '', $local) ?? '';

        if ($digits !== '') {
            $suffix = substr($digits, -4);

            return '***' . $suffix;
        }

        if (mb_strlen($local) <= 4) {
            return '***';
        }

        return mb_substr($local, 0, 2) . '***' . mb_substr($local, -2);
    }
}
