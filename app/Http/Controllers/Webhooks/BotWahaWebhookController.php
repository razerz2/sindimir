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
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotEngine $engine,
        private readonly BotProviderFactory $providerFactory,
        private readonly WahaChatIdResolver $chatIdResolver
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->shouldProcess('waha')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $payload = $request->all();
        [$from, $text] = $this->extractIncomingData($payload);

        if ($from === null || $text === null || $text === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $responseText = $this->engine->handleIncoming('waha', $from, $text);
            if (trim($responseText) !== '') {
                $this->providerFactory->make('waha')->sendText($from, $responseText);
            }
        } catch (Throwable $exception) {
            Log::error('Erro ao processar webhook BOT WAHA', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function shouldProcess(string $channel): bool
    {
        $enabled = (bool) $this->configuracaoService->get('bot.enabled', false);
        $activeProvider = mb_strtolower(trim((string) $this->configuracaoService->get('bot.provider', 'meta')));
        $channel = mb_strtolower(trim($channel));

        return $enabled && $activeProvider === $channel;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0: ?string, 1: ?string}
     */
    private function extractIncomingData(array $payload): array
    {
        $event = mb_strtolower(trim((string) data_get($payload, 'event', '')));
        if ($event === '' || ! str_starts_with($event, 'message')) {
            return [null, null];
        }

        $fromMe = data_get($payload, 'payload.fromMe', null);
        if ($fromMe === true) {
            return [null, null];
        }

        $fromCandidates = [
            data_get($payload, 'payload.from'),
            data_get($payload, 'payload.author'),
            data_get($payload, 'payload.participant'),
            data_get($payload, 'payload.chatId'),
            data_get($payload, 'payload.to'),
            data_get($payload, 'from'),
        ];

        $textCandidates = [
            data_get($payload, 'payload.body'),
            data_get($payload, 'payload.text'),
            data_get($payload, 'payload.caption'),
            data_get($payload, 'payload._data.body'),
            data_get($payload, 'payload._data.caption'),
        ];

        $from = null;
        foreach ($fromCandidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $raw = trim((string) $candidate);
            $lower = mb_strtolower($raw);
            if ($raw === '' || str_contains($lower, '@g.us') || str_contains($lower, 'status@broadcast')) {
                continue;
            }

            $phone = $this->chatIdResolver->toPhone($raw);
            if ($phone !== '') {
                $from = $phone;
                break;
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

        if ($from === null || $text === null) {
            return [null, null];
        }

        return [$from, $text];
    }
}
