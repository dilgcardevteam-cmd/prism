<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Services\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationController extends Controller
{
    public function start(
        Request $request,
        User $user,
        UserImpersonationService $impersonationService,
    ): RedirectResponse {
        $this->authorize('impersonate-user', $user);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $impersonationService->start(
            $request,
            $request->user(),
            $user,
            isset($validated['reason']) ? trim($validated['reason']) : null,
        );

        return redirect('/dashboard')
            ->with('success', 'You are now impersonating ' . $user->fullName() . '.');
    }

    public function stop(
        Request $request,
        UserImpersonationService $impersonationService,
    ): RedirectResponse {
        $administrator = $impersonationService->stop($request);

        if (!$administrator) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_error' => 'The original administrator session could not be restored. Sign in again to continue.',
            ]);
        }

        return redirect('/dashboard')->with('success', 'Impersonation ended.');
    }
}
