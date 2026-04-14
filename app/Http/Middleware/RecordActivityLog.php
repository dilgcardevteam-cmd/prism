<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordActivityLog
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    /**
     * Record successful authenticated page and mutation access after the
     * application has already handled the request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $actingUser */
        $actingUser = $request->user();

        /** @var Response $response */
        $response = $next($request);

        $this->activityLogService->logRequest($request, $response, $actingUser);

        return $response;
    }
}
