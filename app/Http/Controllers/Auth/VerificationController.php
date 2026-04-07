<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('verifyWithToken');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Mark the authenticated user's email as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $response = parent::verify($request);

        if ($request->user() && $request->user()->hasVerifiedEmail() && $request->user()->verification_token) {
            $request->user()->update(['verification_token' => null]);
        }

        return $response;
    }

    /**
     * Verify email using token from database.
     *
     * @param  string  $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyWithToken($token)
    {
        $user = \App\Models\User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'Invalid verification token.');
        }

        if ($user->hasVerifiedEmail()) {
            $message = strtolower(trim((string) $user->status)) === 'active'
                ? 'Email already verified. You can now log in.'
                : 'Email already verified. Your account is pending administrator approval.';

            return redirect('/login')->with('info', $message);
        }

        $user->markEmailAsVerified();
        $user->verification_token = null;
        $user->save();

        $message = strtolower(trim((string) $user->status)) === 'active'
            ? 'Email verified successfully. You can now log in.'
            : 'Email verified successfully. Your account is pending administrator approval.';

        return redirect('/login')->with('success', $message);
    }
}
