<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = $request->user();
            $fallback = $user?->role === UserRole::Admin
                ? route('admin.index')
                : route('aluno.dashboard');

            return redirect()->intended($fallback);
        }

        throw ValidationException::withMessages([
            'email' => 'Credenciais invalidas.',
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
