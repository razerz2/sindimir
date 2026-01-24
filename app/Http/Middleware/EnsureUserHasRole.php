<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom($role)?->value)
            ->filter()
            ->all();

        if (empty($allowed) || ! in_array($user->role->value, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
