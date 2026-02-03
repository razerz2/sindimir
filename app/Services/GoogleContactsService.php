<?php

namespace App\Services;

use Google\Client;
use Google\Service\Oauth2;
use Google\Service\PeopleService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleContactsService
{
    private const CONFIG_KEY = 'integracoes.google.contacts';

    public function __construct(private readonly ConfiguracaoService $configuracaoService)
    {
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.google.client_id')
            && (bool) config('services.google.client_secret')
            && (bool) config('services.google.redirect');
    }

    public function isConnected(): bool
    {
        return $this->getStoredToken() !== null;
    }

    public function getConnectedAccount(): ?array
    {
        $payload = $this->getStoredPayload();

        if (! $payload) {
            return null;
        }

        return [
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null,
        ];
    }

    public function getAuthUrl(): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Credenciais Google não configuradas.');
        }

        $client = $this->buildClient();

        return $client->createAuthUrl();
    }

    public function handleCallback(string $code): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Credenciais Google não configuradas.');
        }

        $client = $this->buildClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException('Não foi possível autenticar com o Google.');
        }

        $client->setAccessToken($token);
        $oauth = new Oauth2($client);
        $profile = $oauth->userinfo->get();

        $this->storeToken($token, $profile->getEmail(), $profile->getName());

        return [
            'email' => $profile->getEmail(),
            'name' => $profile->getName(),
        ];
    }

    public function disconnect(): void
    {
        $token = $this->getStoredToken();

        if ($token) {
            try {
                $client = $this->buildClient();
                $client->revokeToken($token['refresh_token'] ?? $token['access_token'] ?? null);
            } catch (\Throwable $exception) {
                Log::warning('Falha ao revogar token Google.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->configuracaoService->set(self::CONFIG_KEY, null, 'Tokens Google Contacts');
    }

    public function getAuthorizedClient(): Client
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Credenciais Google não configuradas.');
        }

        $token = $this->getStoredToken();
        if (! $token) {
            throw new RuntimeException('Conta Google não conectada.');
        }

        $client = $this->buildClient();
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $token['refresh_token'] ?? null;
            if (! $refreshToken) {
                throw new RuntimeException('Token expirado; reconecte a conta Google.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($newToken['error'])) {
                throw new RuntimeException('Falha ao renovar o token Google.');
            }

            $token = array_merge($token, $newToken);
            $this->storeToken($token);
            $client->setAccessToken($token);
        }

        return $client;
    }

    public function fetchContacts(): array
    {
        $client = $this->getAuthorizedClient();
        $service = new PeopleService($client);

        $contacts = [];
        $pageToken = null;

        do {
            $response = $service->people_connections->listPeopleConnections('people/me', [
                'personFields' => 'names,phoneNumbers',
                'pageSize' => 1000,
                'pageToken' => $pageToken,
                'sortOrder' => 'FIRST_NAME_ASCENDING',
            ]);

            foreach ($response->getConnections() ?? [] as $person) {
                $name = null;
                $names = $person->getNames();
                if ($names && isset($names[0])) {
                    $name = $names[0]->getDisplayName();
                }

                foreach ($person->getPhoneNumbers() ?? [] as $phoneNumber) {
                    $value = $phoneNumber->getValue();
                    if (! $value) {
                        continue;
                    }

                    $contacts[] = [
                        'id' => $person->getResourceName(),
                        'nome' => $name ?: 'Contato sem nome',
                        'telefone' => $value,
                    ];
                }
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken);

        return $contacts;
    }

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            PeopleService::CONTACTS_READONLY,
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ]);

        return $client;
    }

    private function getStoredPayload(): ?array
    {
        $payload = $this->configuracaoService->get(self::CONFIG_KEY, null);

        return is_array($payload) ? $payload : null;
    }

    private function getStoredToken(): ?array
    {
        $payload = $this->getStoredPayload();
        if (! $payload || empty($payload['token'])) {
            return null;
        }

        $decoded = json_decode(Crypt::decryptString($payload['token']), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function storeToken(array $token, ?string $email = null, ?string $name = null): void
    {
        $payload = $this->getStoredPayload() ?? [];
        $email ??= $payload['email'] ?? null;
        $name ??= $payload['name'] ?? null;

        $this->configuracaoService->set(self::CONFIG_KEY, [
            'token' => Crypt::encryptString(json_encode($token)),
            'email' => $email,
            'name' => $name,
        ], 'Tokens Google Contacts');
    }
}
