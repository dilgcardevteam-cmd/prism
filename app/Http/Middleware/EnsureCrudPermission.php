<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrudPermission
{
    public function handle(Request $request, Closure $next, string $aspect, string $action): Response
    {
        if (!\App\Models\ModuleConfiguration::isModuleEnabled($aspect)) {
            return response()->view('errors.restricted', [
                'message' => 'This module has been temporarily disabled by the system administrator.'
            ], 403);
        }

        $user = $request->user();

        if ($user && $user->hasCrudPermission($aspect, $action)) {
            return $next($request);
        }

        return response()->view('errors.restricted', [], 403);
    }
}
