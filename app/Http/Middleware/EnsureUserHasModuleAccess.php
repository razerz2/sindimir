<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasModuleAccess
{
    /**
     * @param  array<int, string>  $allowedRouteNames
     */
    private const ALLOWED_ROUTE_NAMES = [
        'admin.index',
        'admin.dashboard',
        'admin.logout',
    ];

    /**
     * @var array<string, string>
     */
    private const MODULE_ROUTE_PREFIXES = [
        'admin.cursos.' => 'cursos',
        'admin.eventos.' => 'eventos',
        'admin.alunos.' => 'alunos',
        'admin.relatorios.' => 'relatorios',
        'admin.notificacoes.' => 'notificacoes',
        'admin.site.' => 'cms',
        'admin.matriculas.' => 'eventos',
        'admin.lista-espera.' => 'eventos',
        'admin.auditoria.' => 'relatorios',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role === UserRole::Admin) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! $routeName) {
            abort(403);
        }

        if (in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        foreach (self::MODULE_ROUTE_PREFIXES as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                if (! $user->hasModuleAccess($module)) {
                    abort(403);
                }

                return $next($request);
            }
        }

        abort(403);
    }
}
