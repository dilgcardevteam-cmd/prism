<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();
        if (!$plainTextToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = DB::table('mobile_access_tokens')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->where('expires_at', '>', now())
            ->first(['user_id']);

        $user = $accessToken ? User::query()->find($accessToken->user_id) : null;
        if (!$user || strtolower(trim((string) $user->status)) !== 'active') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
