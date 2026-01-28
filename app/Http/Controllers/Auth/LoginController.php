<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Support\Cpf;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(Request $request): View
    {
        $routeName = $request->route()?->getName();
        $view = $routeName === 'aluno.login'
            ? 'auth.login-aluno'
            : 'auth.login-admin';

        return view($view);
    }

    public function store(Request $request, TwoFactorService $twoFactorService): RedirectResponse
    {
        $isAlunoLogin = $request->route()?->getName() === 'aluno.login.store';
        if ($isAlunoLogin) {
            $request->merge([
                'cpf' => Cpf::normalize($request->input('cpf')),
            ]);
        }
        $credentials = $request->validate($isAlunoLogin
            ? [
                'cpf' => [
                    'required',
                    'string',
                    'digits:11',
                    function (string $attribute, mixed $value, $fail) {
                        if (! Cpf::isValid($value)) {
                            $fail('CPF inválido.');
                        }
                    },
                ],
            ]
            : [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

        $remember = $request->boolean('remember');

        if ($isAlunoLogin) {
            $cpf = Cpf::normalize($credentials['cpf'] ?? '');
            $aluno = $cpf !== '' ? Aluno::query()->whereCpf($cpf)->first() : null;
            $user = $aluno?->user;

            if (! $aluno) {
                throw ValidationException::withMessages([
                    'cpf' => 'Credenciais invalidas.',
                ]);
            }

            if (! $user) {
                $email = (string) ($aluno->email ?? '');
                if ($email === '') {
                    throw ValidationException::withMessages([
                        'cpf' => 'Aluno sem e-mail cadastrado.',
                    ]);
                }

                $user = User::query()->where('email', $email)->first();
                if ($user && $user->role !== UserRole::Aluno) {
                    throw ValidationException::withMessages([
                        'cpf' => 'CPF associado a um usuário inválido.',
                    ]);
                }

                if (! $user) {
                    $user = User::create([
                        'name' => $aluno->nome_completo ?? 'Aluno',
                        'email' => $email,
                        'password' => Hash::make(bin2hex(random_bytes(16))),
                        'role' => UserRole::Aluno,
                    ]);
                }

                $aluno->update(['user_id' => $user->id]);
            }

            if ($user->role !== UserRole::Aluno) {
                throw ValidationException::withMessages([
                    'cpf' => 'Credenciais invalidas.',
                ]);
            }

            if ($twoFactorService->isEnabledForUser($user)) {
                $request->session()->regenerate();

                try {
                    $challenge = $twoFactorService->startChallenge($user, $request);
                } catch (\RuntimeException $exception) {
                    return back()->withErrors([
                        'cpf' => $exception->getMessage(),
                    ]);
                }

                $request->session()->put([
                    '2fa.pending_user_id' => $user->id,
                    '2fa.challenge_id' => $challenge->id,
                    '2fa.remember' => $remember,
                    '2fa.channel' => $challenge->channel,
                    '2fa.destination' => $twoFactorService->maskDestination($user, $challenge->channel),
                    '2fa.login_route' => 'aluno.login',
                ]);

                return redirect()->route('2fa.show');
            }

            Auth::loginUsingId($user->id, $remember);

            return redirect()->intended(route('aluno.dashboard'));
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = $request->user();
            if ($user && $twoFactorService->isEnabledForUser($user)) {
                Auth::logout();

                try {
                    $challenge = $twoFactorService->startChallenge($user, $request);
                } catch (\RuntimeException $exception) {
                    return back()->withErrors([
                        'email' => $exception->getMessage(),
                    ]);
                }

                $loginRoute = $request->route()?->getName() === 'aluno.login' ? 'aluno.login' : 'login';
                $request->session()->put([
                    '2fa.pending_user_id' => $user->id,
                    '2fa.challenge_id' => $challenge->id,
                    '2fa.remember' => $remember,
                    '2fa.channel' => $challenge->channel,
                    '2fa.destination' => $twoFactorService->maskDestination($user, $challenge->channel),
                    '2fa.login_route' => $loginRoute,
                ]);

                return redirect()->route('2fa.show');
            }

            $fallback = in_array($user?->role, [UserRole::Admin, UserRole::Usuario], true)
                ? route('admin.index')
                : route('aluno.dashboard');

            return redirect()->intended($fallback);
        }

        $errorKey = $isAlunoLogin ? 'cpf' : 'email';
        throw ValidationException::withMessages([
            $errorKey => 'Credenciais invalidas.',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.home');
    }
}
