<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrudPermission
{
    public function handle(Request $request, Closure $next, string $aspect, string $action): Response
    {
        $user = $request->user();

        if ($user && $user->hasCrudPermission($aspect, $action)) {
            return $next($request);
        }

        return response()->view('errors.restricted', [], 403);
    }
}
