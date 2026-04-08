<?php

namespace App\Http\Middleware;

use App\Support\SystemMaintenanceState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemMaintenanceAccess
{
    private const ALLOWED_ROUTE_NAMES = [
        'landing',
        'login',
        'logout',
        'maintenance.notice',
    ];

    public function __construct(
        private readonly SystemMaintenanceState $systemMaintenanceState,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->systemMaintenanceState->isEnabled()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($this->requestIsAllowedDuringMaintenance($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'The system is currently under maintenance.',
            ], 503);
        }

        return redirect()->route('maintenance.notice');
    }

    private function requestIsAllowedDuringMaintenance(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        if (is_string($routeName) && in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return true;
        }

        return $request->is('login')
            || $request->is('up');
    }
}
