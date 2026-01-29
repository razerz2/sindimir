<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\GenericNotificationMail;
use App\Models\TwoFactorChallenge;
use App\Models\TwoFactorLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class TwoFactorService
{
    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly WhatsAppService $whatsAppService
    ) {
    }

    public function isEnabledForUser(User $user): bool
    {
        $active = (bool) $this->configuracaoService->get('seguranca.2fa.ativo', false);
        if (! $active) {
            return false;
        }

        $perfil = (string) $this->configuracaoService->get('seguranca.2fa.perfil', 'admin');
        $isAdmin = in_array($user->role, [UserRole::Admin, UserRole::Usuario], true);
        $isAluno = $user->role === UserRole::Aluno;

        return match ($perfil) {
            'admin' => $isAdmin,
            'aluno' => $isAluno,
            'ambos' => $isAdmin || $isAluno,
            default => false,
        };
    }

    public function startChallenge(User $user, Request $request): TwoFactorChallenge
    {
        $channel = $this->resolveChannel();
        $expiresAt = CarbonImmutable::now()->addMinutes($this->getExpiryMinutes());
        $maxAttempts = $this->getMaxAttempts();
        $code = $this->generateCode();

        $challenge = TwoFactorChallenge::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'sent_at' => CarbonImmutable::now(),
        ]);

        try {
            $meta = $this->sendCode($user, $code, $channel);
        } catch (RuntimeException $exception) {
            $this->log(
                $user,
                $challenge,
                $channel,
                'send',
                'failed',
                $exception->getMessage(),
                $request
            );
            throw $exception;
        }

        $this->log($user, $challenge, $channel, 'send', 'success', null, $request, $meta ?? null);

        return $challenge;
    }

    public function validateCode(User $user, TwoFactorChallenge $challenge, string $code, Request $request): bool
    {
        if ($challenge->consumed_at) {
            return false;
        }

        if (CarbonImmutable::now()->greaterThan($challenge->expires_at)) {
            $challenge->update(['consumed_at' => CarbonImmutable::now()]);
            $this->log($user, $challenge, $challenge->channel, 'expired', 'failed', 'Código expirado.', $request);

            return false;
        }

        if ($challenge->attempts >= $challenge->max_attempts) {
            $challenge->update(['consumed_at' => CarbonImmutable::now()]);
            $this->log($user, $challenge, $challenge->channel, 'blocked', 'failed', 'Limite de tentativas.', $request);

            return false;
        }

        $challenge->update([
            'attempts' => $challenge->attempts + 1,
            'last_attempt_at' => CarbonImmutable::now(),
        ]);

        if (! Hash::check($code, $challenge->code_hash)) {
            $message = 'Código inválido.';
            if ($challenge->attempts >= $challenge->max_attempts) {
                $challenge->update(['consumed_at' => CarbonImmutable::now()]);
                $this->log($user, $challenge, $challenge->channel, 'blocked', 'failed', 'Limite de tentativas.', $request);
            } else {
                $this->log($user, $challenge, $challenge->channel, 'validate', 'failed', $message, $request);
            }

            return false;
        }

        $challenge->update(['consumed_at' => CarbonImmutable::now()]);
        $this->log($user, $challenge, $challenge->channel, 'validate', 'success', null, $request);

        return true;
    }

    public function resolveChannel(): string
    {
        $channel = (string) $this->configuracaoService->get('seguranca.2fa.canal', 'email');

        return in_array($channel, ['email', 'whatsapp'], true) ? $channel : 'email';
    }

    public function getExpiryMinutes(): int
    {
        return max(1, (int) $this->configuracaoService->get('seguranca.2fa.expiracao_minutos', 10));
    }

    public function getMaxAttempts(): int
    {
        return max(1, (int) $this->configuracaoService->get('seguranca.2fa.max_tentativas', 5));
    }

    public function maskDestination(User $user, string $channel): string
    {
        if ($channel === 'email') {
            return $this->maskEmail($user->email ?? '');
        }

        $numero = $this->resolveWhatsappNumber($user);

        return $numero ? $this->maskPhone($numero) : '';
    }

    private function sendCode(User $user, string $code, string $channel): array
    {
        $mensagem = "Seu código de verificação para acesso à Área do Aluno é: {$code}. Este código é válido por alguns minutos e não deve ser compartilhado.";
        if ($channel === 'email') {
            if (! $this->isValidEmail($user->email ?? null)) {
                throw new RuntimeException('Email inválido ou não cadastrado.');
            }

            Mail::to($user->email)->send(new GenericNotificationMail('Código de verificação', $mensagem));

            return [
                'provider' => 'email',
            ];
        }

        $numero = $this->resolveWhatsappNumber($user);
        if (! $numero) {
            throw new RuntimeException('WhatsApp inválido ou não cadastrado.');
        }
        if (! $this->whatsAppService->canSend()) {
            throw new RuntimeException('WhatsApp não está configurado para envio.');
        }

        return $this->whatsAppService->sendWithResponse($numero, $mensagem);
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function resolveWhatsappNumber(User $user): ?string
    {
        $celular = $user->aluno?->celular ?? null;
        if (! $celular) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $celular);
        if ($digits === '') {
            return null;
        }

        $numeroSemPais = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
        if (! preg_match('/^\d{10,11}$/', $numeroSemPais)) {
            return null;
        }

        return '55' . $numeroSemPais;
    }

    private function isValidEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || ! str_contains($email, '@')) {
            return '';
        }

        [$user, $domain] = explode('@', $email, 2);
        $userMask = strlen($user) <= 2
            ? substr($user, 0, 1) . '***'
            : substr($user, 0, 2) . '***';

        return $userMask . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '' || strlen($digits) < 8) {
            return '';
        }

        return '***' . substr($digits, -4);
    }

    private function log(
        User $user,
        ?TwoFactorChallenge $challenge,
        string $channel,
        string $action,
        string $status,
        ?string $message,
        Request $request,
        ?array $meta = null
    ): void {
        TwoFactorLog::create([
            'user_id' => $user->id,
            'challenge_id' => $challenge?->id,
            'channel' => $channel,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => $meta,
        ]);
    }
}
