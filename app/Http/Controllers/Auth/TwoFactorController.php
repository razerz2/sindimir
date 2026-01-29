<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TwoFactorChallenge;
use App\Models\User;
use App\Services\TwoFactorService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $authenticatedGuard = $this->getAuthenticatedGuard();
        if ($authenticatedGuard) {
            return $this->redirectForGuard($authenticatedGuard, Auth::guard($authenticatedGuard)->user(), $request);
        }

        $pendingUserId = $request->session()->get('2fa.pending_user_id');
        if (! $pendingUserId) {
            return $this->redirectToLogin($request);
        }

        $channel = (string) $request->session()->get('2fa.channel', 'email');
        $destination = (string) $request->session()->get('2fa.destination', '');

        return view('auth.two-factor', compact('channel', 'destination'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $guard = (string) $request->session()->get('2fa.guard', 'admin');
        if (! in_array($guard, ['admin', 'aluno'], true)) {
            return $this->redirectToLogin($request);
        }

        $pendingUserId = $request->session()->get('2fa.pending_user_id');
        $challengeId = $request->session()->get('2fa.challenge_id');

        if (! $pendingUserId || ! $challengeId) {
            return $this->redirectToLogin($request);
        }

        $user = User::query()->find($pendingUserId);
        if (! $user) {
            $this->clearSession($request);

            return $this->redirectToLogin($request);
        }

        $challenge = TwoFactorChallenge::query()
            ->whereKey($challengeId)
            ->where('user_id', $user->id)
            ->first();

        if (! $challenge) {
            $this->clearSession($request);

            return $this->redirectToLogin($request);
        }

        $valid = $this->twoFactorService->validateCode($user, $challenge, $data['code'], $request);
        $request->session()->put('2fa.channel', $challenge->channel);

        if (! $valid) {
            $challenge->refresh();
            if ($challenge->attempts >= $challenge->max_attempts) {
                $this->clearSession($request);

                return $this->redirectToLogin($request)
                    ->withErrors(['code' => 'Limite de tentativas atingido. Faça login novamente.']);
            }
            if (CarbonImmutable::now()->greaterThan($challenge->expires_at)) {
                $this->clearSession($request);

                return $this->redirectToLogin($request)
                    ->withErrors(['code' => 'Código expirado. Faça login novamente.']);
            }

            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $remember = (bool) $request->session()->get('2fa.remember', false);
        if ($guard === 'admin' && ! in_array($user->role, [UserRole::Admin, UserRole::Usuario], true)) {
            $this->clearSession($request);

            return $this->redirectToLogin($request)
                ->withErrors(['code' => 'Credenciais inválidas.']);
        }
        Auth::guard($guard)->loginUsingId($user->id, $remember);
        $this->clearSession($request);

        return $this->redirectForGuard($guard, $user, $request);
    }

    public function resend(Request $request): RedirectResponse
    {
        $pendingUserId = $request->session()->get('2fa.pending_user_id');
        if (! $pendingUserId) {
            return $this->redirectToLogin($request);
        }

        $user = User::query()->find($pendingUserId);
        if (! $user) {
            $this->clearSession($request);

            return $this->redirectToLogin($request);
        }

        try {
            $challenge = $this->twoFactorService->startChallenge($user, $request);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->put([
            '2fa.challenge_id' => $challenge->id,
            '2fa.channel' => $challenge->channel,
            '2fa.destination' => $this->twoFactorService->maskDestination($user, $challenge->channel),
        ]);

        return back()->with('status', 'Novo código enviado.');
    }

    private function redirectToLogin(Request $request): RedirectResponse
    {
        $loginRoute = $request->session()->get('2fa.login_route', 'admin.login');

        return redirect()->route($loginRoute);
    }

    private function redirectForGuard(string $guard, User $user, Request $request): RedirectResponse
    {
        $fallback = $guard === 'admin'
            ? route('admin.dashboard')
            : route('aluno.dashboard');

        // Evita reaproveitar o "intended" de outro guard (login cruzado).
        $request->session()->forget('url.intended');

        return redirect()->to($fallback);
    }

    private function clearSession(Request $request): void
    {
        $request->session()->forget([
            '2fa.pending_user_id',
            '2fa.challenge_id',
            '2fa.remember',
            '2fa.channel',
            '2fa.destination',
            '2fa.login_route',
            '2fa.guard',
        ]);
    }

    private function getAuthenticatedGuard(): ?string
    {
        if (Auth::guard('admin')->check()) {
            return 'admin';
        }

        if (Auth::guard('aluno')->check()) {
            return 'aluno';
        }

        return null;
    }
}
