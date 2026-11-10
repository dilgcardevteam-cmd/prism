<?php

namespace App\Http\Middleware;

use App\Modules\Users\Services\UserImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidImpersonationSession
{
    public function __construct(
        private readonly UserImpersonationService $impersonationService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasSession() || !$request->session()->has(UserImpersonationService::AUDIT_SESSION_KEY)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user || !$this->impersonationService->validateActiveSession($request, $user)) {
            $this->impersonationService->expire($request);

            return redirect()->route('login')->withErrors([
                'login_error' => 'The impersonation session expired or is no longer valid. Sign in again to continue.',
            ]);
        }

        $targetId = $user->getKey();
        $audit = $this->impersonationService->auditFromSession($request);
        $response = $next($request);
        if ($audit) {
            $this->impersonationService->endIfSessionUserChanged($targetId, $audit);
        }

        return $response;
    }
}
