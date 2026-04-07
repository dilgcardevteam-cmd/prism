<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm(Request $request)
    {
        $request->session()->forget('otp_email');

        return view('auth.forgot-password', [
            'showOtp' => false,
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('emailaddress', $email)->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Email address not found.'])
                ->withInput();
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => now()]
        );

        try {
            Mail::send(new PasswordResetOtpMail($user, $otp, $expiresAt));
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['email' => 'Unable to send OTP right now. Please try again later.'])
                ->withInput();
        }

        $request->session()->put('otp_email', $email);

        return redirect()
            ->route('forgot-password.verify')
            ->with('success', 'OTP sent to your email. Please check your inbox.');
    }

    public function showVerifyOtpForm(Request $request)
    {
        if (!$request->session()->has('otp_email')) {
            return redirect()->route('forgot-password');
        }

        return view('auth.forgot-password', [
            'showOtp' => true,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('otp_email');
        if (!$email) {
            return redirect()->route('forgot-password');
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || !Hash::check($request->input('otp'), $record->token)) {
            return back()->withErrors(['otp' => 'Invalid OTP.']);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(5)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return back()->withErrors(['otp' => 'OTP expired. Please request a new one.']);
        }

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $request->session()->forget('otp_email');
        $request->session()->put('otp_verified_email', $email);

        return redirect()
            ->route('forgot-password.reset')
            ->with('success', 'OTP verified. Please set a new password.');
    }

    public function showResetForm(Request $request)
    {
        $email = $request->session()->get('otp_verified_email');
        if (!$email) {
            return redirect()->route('forgot-password');
        }

        return view('auth.reset-password-otp', [
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $email = $request->session()->get('otp_verified_email');
        if (!$email) {
            return redirect()->route('forgot-password');
        }

        $request->validate([
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::where('emailaddress', $email)->first();
        if (!$user) {
            return redirect()
                ->route('forgot-password')
                ->withErrors(['email' => 'User account not found.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        $request->session()->forget('otp_verified_email');

        return redirect('/login')->with('success', 'Password reset successful. You can now log in.');
    }
}
