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

class BotMetaWebhookController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotEngine $engine,
        private readonly BotProviderFactory $providerFactory
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->shouldProcess('meta')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $payload = $request->all();
        [$from, $text] = $this->extractIncomingData($payload);

        if ($from === null || $text === null || $text === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $responseText = $this->engine->handleIncoming('meta', $from, $text);
            if (trim($responseText) !== '') {
                $this->providerFactory->make('meta')->sendText($from, $responseText);
            }
        } catch (Throwable $exception) {
            Log::error('Erro ao processar webhook BOT Meta', [
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
        $message = data_get($payload, 'entry.0.changes.0.value.messages.0');
        if (! is_array($message)) {
            return [null, null];
        }

        $from = Phone::normalize((string) data_get($message, 'from', ''));
        $text = trim((string) (
            data_get($message, 'text.body')
            ?? data_get($message, 'button.text')
            ?? data_get($message, 'interactive.button_reply.title')
            ?? data_get($message, 'interactive.list_reply.title')
            ?? ''
        ));

        if ($from === '' || $text === '') {
            return [null, null];
        }

        return [$from, $text];
    }
}

