<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Observação: o "intended" é salvo na sessão pelo fluxo de auth do Laravel.
        // Como a sessão é compartilhada entre guards, evitamos depender dele no login/2FA.
        if ($request->routeIs('admin.*') || $request->is('admin', 'admin/*')) {
            return route('admin.login');
        }

        if ($request->routeIs('aluno.*') || $request->is('aluno', 'aluno/*')) {
            return route('aluno.login');
        }

        return route('public.home');
    }
}
