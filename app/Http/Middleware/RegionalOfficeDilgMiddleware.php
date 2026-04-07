<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegionalOfficeDilgMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $agency = strtoupper(trim((string) $user->agency));
        $province = strtolower(trim((string) $user->province));

        if ($agency !== 'DILG' || $province !== 'regional office') {
            abort(403);
        }

        return $next($request);
    }
}

