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
        if (! $plainTextToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = DB::table('mobile_access_tokens')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->where('expires_at', '>', now())
            ->first(['id', 'user_id', 'updated_at']);

        $user = $accessToken ? User::query()->find($accessToken->user_id) : null;
        if (! $user || strtolower(trim((string) $user->status)) !== 'active') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokenHash = hash('sha256', $plainTextToken);
        if (now()->subMinutes(120)->greaterThanOrEqualTo($accessToken->updated_at)) {
            DB::table('mobile_access_tokens')->where('token_hash', $tokenHash)->delete();

            return response()->json([
                'message' => 'Your session expired due to inactivity. Please sign in again.',
            ], 401);
        }

        DB::table('mobile_access_tokens')
            ->where('id', $accessToken->id)
            ->update(['updated_at' => now()]);

        Auth::setUser($user);

        return $next($request);
    }
}
