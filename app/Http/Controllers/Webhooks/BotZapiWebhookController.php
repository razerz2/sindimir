<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotEngine;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotZapiWebhookController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotEngine $engine,
        private readonly BotProviderFactory $providerFactory
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->shouldProcess('zapi')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $payload = $request->all();
        [$from, $text] = $this->extractIncomingData($payload);

        if ($from === null || $text === null || $text === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $responseText = $this->engine->handleIncoming('zapi', $from, $text);
            if (trim($responseText) !== '') {
                $this->providerFactory->make('zapi')->sendText($from, $responseText);
            }
        } catch (Throwable $exception) {
            Log::error('Erro ao processar webhook BOT Z-API', [
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
        $activeProvider = (string) $this->configuracaoService->get('bot.provider', 'meta');

        return $enabled && $activeProvider === $channel;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0: ?string, 1: ?string}
     */
    private function extractIncomingData(array $payload): array
    {
        $fromCandidates = [
            data_get($payload, 'phone'),
            data_get($payload, 'from'),
            data_get($payload, 'sender.phone'),
            data_get($payload, 'sender.id'),
            data_get($payload, 'data.phone'),
            data_get($payload, 'data.from'),
            data_get($payload, 'data.sender.phone'),
            data_get($payload, 'message.phone'),
            data_get($payload, 'message.from'),
        ];

        $textCandidates = [
            data_get($payload, 'text.message'),
            data_get($payload, 'text.body'),
            data_get($payload, 'message'),
            data_get($payload, 'body'),
            data_get($payload, 'data.text.message'),
            data_get($payload, 'data.text.body'),
            data_get($payload, 'data.message'),
            data_get($payload, 'data.body'),
        ];

        $from = null;
        foreach ($fromCandidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $normalized = Phone::normalize((string) $candidate);
            if ($normalized !== '') {
                $from = $normalized;
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

