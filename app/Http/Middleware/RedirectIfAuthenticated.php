<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * @param  array<int, string|null>  $guards
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect()->to($this->redirectTo($guard));
            }
        }

        return $next($request);
    }

    private function redirectTo(?string $guard): string
    {
        // Evita redirecionar para area publica quando o guard e especifico.
        return match ($guard) {
            'admin' => route('admin.dashboard'),
            'aluno' => route('aluno.dashboard'),
            default => route('public.home'),
        };
    }
}
