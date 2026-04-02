<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotEngine;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Services\WhatsApp\EvolutionPhoneResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotEvolutionWebhookController extends Controller
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly BotEngine $engine,
        private readonly BotProviderFactory $providerFactory,
        private readonly EvolutionPhoneResolver $phoneResolver
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->shouldProcess('evolution')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $payload = $request->all();
        [$from, $text] = $this->extractIncomingData($payload);

        if ($from === null || $text === null || $text === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $responseText = $this->engine->handleIncoming('evolution', $from, $text);
            if (trim($responseText) !== '') {
                $this->providerFactory->make('evolution')->sendText($from, $responseText);
            }
        } catch (Throwable $exception) {
            Log::error('Erro ao processar webhook BOT Evolution', [
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
        $event = trim((string) data_get($payload, 'event', ''));
        if ($event !== '' && ! $this->isMessageEvent($event)) {
            return [null, null];
        }

        $data = data_get($payload, 'data');
        if (! is_array($data)) {
            $data = $payload;
        }

        $fromMe = data_get($data, 'key.fromMe', data_get($payload, 'key.fromMe', null));
        if ($fromMe === true) {
            return [null, null];
        }

        $fromCandidates = [
            data_get($data, 'key.remoteJid'),
            data_get($payload, 'key.remoteJid'),
            data_get($data, 'key.participant'),
            data_get($payload, 'key.participant'),
            data_get($data, 'remoteJid'),
            data_get($payload, 'remoteJid'),
        ];

        $textCandidates = [
            data_get($data, 'message.conversation'),
            data_get($payload, 'message.conversation'),
            data_get($data, 'message.extendedTextMessage.text'),
            data_get($payload, 'message.extendedTextMessage.text'),
            data_get($data, 'message.imageMessage.caption'),
            data_get($payload, 'message.imageMessage.caption'),
            data_get($data, 'message.videoMessage.caption'),
            data_get($payload, 'message.videoMessage.caption'),
            data_get($data, 'message.documentMessage.caption'),
            data_get($payload, 'message.documentMessage.caption'),
            data_get($data, 'message.buttonsResponseMessage.selectedDisplayText'),
            data_get($payload, 'message.buttonsResponseMessage.selectedDisplayText'),
            data_get($data, 'message.listResponseMessage.title'),
            data_get($payload, 'message.listResponseMessage.title'),
            data_get($data, 'message.ephemeralMessage.message.conversation'),
            data_get($payload, 'message.ephemeralMessage.message.conversation'),
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

            $phone = $this->phoneResolver->toPhone($raw);
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

    private function isMessageEvent(string $event): bool
    {
        $normalized = mb_strtolower(trim($event));
        $normalized = str_replace(['-', '_'], '.', $normalized);

        return str_contains($normalized, 'message');
    }
}
