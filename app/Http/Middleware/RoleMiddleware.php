<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->view('errors.restricted', [], 403);
        }

        foreach ($roles as $role) {
            if ($user->matchesRoleAlias($role)) {
                return $next($request);
            }
        }

        return response()->view('errors.restricted', [], 403);
    }
}
