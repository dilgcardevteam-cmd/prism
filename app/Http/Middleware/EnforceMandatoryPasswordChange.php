<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceMandatoryPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionPasswordVersion = (int) $request->session()->get('auth_password_version', 0);
        $passwordVersionIsStale = $user
            && $sessionPasswordVersion < (int) $user->password_security_version;

        if (! $user || $user->isSuperAdmin() || (! $user->must_change_password && ! $passwordVersionIsStale)) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password reset is required before using the system. Sign in again to continue.',
                'requires_password_change' => true,
            ], 423);
        }

        return redirect()->route('login')->withErrors([
            'login_error' => $user->must_change_password
                ? 'A password reset is required before using the system. Sign in with your current password to continue.'
                : 'Your session was revoked after a system password reset. Sign in again to continue.',
        ]);
    }
}
