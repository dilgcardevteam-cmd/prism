<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ModuleConfiguration;
use App\Models\User;
use App\Modules\ActivityLogs\Models\ActivityLog;
use App\Modules\ActivityLogs\Services\ActivityLogService;
use App\Modules\Auth\Jobs\SendLoginOtpEmail;
use App\Support\SystemMaintenanceState;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    public function __construct(
        private readonly SystemMaintenanceState $systemMaintenanceState,
    ) {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
        $this->middleware('throttle:6,1')->only('login');
        $this->middleware('throttle:6,1')->only('verifyLoginOtp');
        $this->middleware('throttle:3,1')->only('resendLoginOtp');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Show the application's public login page.
     */
    public function showLoginForm()
    {
        if ($this->systemMaintenanceState->isEnabled()) {
            return redirect()->route('maintenance.notice');
        }

        return view('auth.login', [
            'registrationEnabled' => ModuleConfiguration::isModuleEnabled('user_registration'),
        ]);
    }

    /**
     * Authenticate with a password, then require a daily OTP unless this
     * browser has already been trusted for the current local calendar day.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember_today' => ['sometimes', 'boolean'],
        ]);

        $throttleKey = Str::transliterate(
            Str::lower((string) $request->input('username')).'|'.$request->ip()
        );

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return redirect()->back()
                ->withErrors([
                    'login_error' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
                ])
                ->withInput($request->only('username', 'remember_today'));
        }

        $user = User::where('username', $request->input('username'))->first();
        $isValidLogin = $user
            && strtolower((string) $user->status) === 'active'
            && Hash::check((string) $request->input('password'), (string) $user->password)
            && (! $this->systemMaintenanceState->isEnabled() || $user->isSuperAdmin());

        if (! $isValidLogin) {
            RateLimiter::hit($throttleKey, 60);
            $request->session()->forget('login_otp_challenge');

            return $this->sendFailedLoginResponse($request);
        }

        RateLimiter::clear($throttleKey);

        if ($user->must_change_password && ! $user->isSuperAdmin()) {
            $request->session()->forget(['login_otp_challenge', 'mandatory_password_otp']);
            $request->session()->put('mandatory_password_user_id', $user->getKey());
            $request->session()->put('mandatory_password_remember', $request->boolean('remember'));

            return redirect()->route('password.mandatory.show');
        }

        if ($this->hasDailyOtpCookie($request, $user)) {
            return $this->completeLogin($request, $user, $request->boolean('remember'));
        }

        $challenge = $request->session()->get('login_otp_challenge');
        if (
            is_array($challenge)
            && (string) ($challenge['user_id'] ?? '') === (string) $user->getKey()
            && isset($challenge['sent_at'])
            && (int) $challenge['sent_at'] + 300 > now()->timestamp
        ) {
            return redirect()->route('login.otp');
        }

        return $this->sendLoginOtp($request, $user, [
            'user_id' => $user->getKey(),
            'attempts' => 0,
            'remember' => $request->boolean('remember'),
            'remember_today' => $request->boolean('remember_today'),
        ]);
    }

    public function showLoginOtpForm(Request $request)
    {
        $challenge = $request->session()->get('login_otp_challenge');

        if (! $this->isLoginOtpChallengeValid($challenge)) {
            $request->session()->forget('login_otp_challenge');

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your verification code expired. Please sign in again.',
            ]);
        }

        $user = User::find($challenge['user_id']);
        if (! $user) {
            $request->session()->forget('login_otp_challenge');

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your verification request is no longer valid. Please sign in again.',
            ]);
        }

        if (empty($challenge['email_dispatched']) && ! empty($challenge['encrypted_otp'])) {
            $challenge['email_dispatched'] = true;
            $encryptedOtp = $challenge['encrypted_otp'];
            unset($challenge['encrypted_otp']);
            $request->session()->put('login_otp_challenge', $challenge);

            SendLoginOtpEmail::dispatch(
                (int) $user->getKey(),
                Crypt::decryptString($encryptedOtp),
                now()->setTimestamp((int) $challenge['expires_at']),
            )->afterResponse();
        }

        return view('auth.login-otp', [
            'emailAddress' => $this->maskEmailAddress($user->emailaddress),
            'canResendAt' => (int) $challenge['sent_at'] + 300,
        ]);
    }

    public function resendLoginOtp(Request $request)
    {
        $challenge = $request->session()->get('login_otp_challenge');

        if (! $this->isLoginOtpChallengeValid($challenge)) {
            $request->session()->forget('login_otp_challenge');

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your verification request expired. Please sign in again.',
            ]);
        }

        if ((int) $challenge['sent_at'] + 300 > now()->timestamp) {
            return back()->withErrors([
                'resend' => 'Please wait five minutes between verification code requests.',
            ]);
        }

        $user = User::find($challenge['user_id']);
        if (! $user || strtolower((string) $user->status) !== 'active') {
            $request->session()->forget('login_otp_challenge');

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your account cannot sign in right now. Please contact an administrator.',
            ]);
        }

        return $this->sendLoginOtp($request, $user, $challenge, true);
    }

    public function showMandatoryPasswordForm(Request $request)
    {
        $user = $this->pendingMandatoryPasswordUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.mandatory-password', ['user' => $user]);
    }

    public function updateMandatoryPassword(Request $request): RedirectResponse
    {
        $user = $this->pendingMandatoryPasswordUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
        ], [
            'new_password.regex' => 'The new password must include uppercase and lowercase letters, a number, and a symbol.',
            'new_password.confirmed' => 'The password confirmation does not match.',
        ]);

        if (Hash::check($validated['new_password'], $user->password)) {
            return back()->withErrors([
                'new_password' => 'Choose a password different from your current password.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $request->session()->put('mandatory_password_otp', [
            'user_id' => $user->getKey(),
            'password_hash' => Hash::make($validated['new_password']),
            'otp_hash' => Hash::make($otp),
            'encrypted_otp' => Crypt::encryptString($otp),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'otp_expires_at' => now()->addMinutes(5)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
            'email_dispatched' => false,
        ]);

        return redirect()->route('password.mandatory.otp');
    }

    public function showMandatoryPasswordOtpForm(Request $request)
    {
        $challenge = $request->session()->get('mandatory_password_otp');
        $user = $this->pendingMandatoryPasswordUser($request);

        if (! $user || ! is_array($challenge) || (int) ($challenge['user_id'] ?? 0) !== (int) $user->getKey()) {
            return redirect()->route('password.mandatory.show')->withErrors([
                'new_password' => 'Choose your new password before verifying the code.',
            ]);
        }

        if ((int) ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('mandatory_password_otp');

            return redirect()->route('password.mandatory.show')->withErrors([
                'new_password' => 'The verification code expired. Submit your new password again to request another code.',
            ]);
        }

        if (empty($challenge['email_dispatched']) && ! empty($challenge['encrypted_otp'])) {
            $challenge['email_dispatched'] = true;
            $encryptedOtp = $challenge['encrypted_otp'];
            unset($challenge['encrypted_otp']);
            $request->session()->put('mandatory_password_otp', $challenge);

            SendLoginOtpEmail::dispatch(
                (int) $user->getKey(),
                Crypt::decryptString($encryptedOtp),
                now()->setTimestamp((int) $challenge['otp_expires_at']),
            )->afterResponse();
        }

        return view('auth.mandatory-password-otp', [
            'emailAddress' => $this->maskEmailAddress($user->emailaddress),
            'canResendAt' => (int) $challenge['sent_at'] + 300,
        ]);
    }

    public function verifyMandatoryPasswordOtp(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $challenge = $request->session()->get('mandatory_password_otp');
        $user = $this->pendingMandatoryPasswordUser($request);
        if (
            ! $user
            || ! is_array($challenge)
            || (int) ($challenge['user_id'] ?? 0) !== (int) $user->getKey()
            || (int) ($challenge['expires_at'] ?? 0) <= now()->timestamp
        ) {
            $request->session()->forget('mandatory_password_otp');

            return redirect()->route('password.mandatory.show')->withErrors([
                'new_password' => 'Your verification request expired. Submit your new password again.',
            ]);
        }

        if ((int) ($challenge['otp_expires_at'] ?? 0) <= now()->timestamp) {
            return back()->withErrors([
                'otp' => 'The verification code expired. Request a new code to continue.',
            ]);
        }

        $challenge['attempts'] = (int) ($challenge['attempts'] ?? 0) + 1;
        if (! Hash::check((string) $request->input('otp'), (string) ($challenge['otp_hash'] ?? ''))) {
            if ($challenge['attempts'] >= 5) {
                $request->session()->forget('mandatory_password_otp');

                return redirect()->route('password.mandatory.show')->withErrors([
                    'new_password' => 'Too many incorrect verification attempts. Submit your new password again.',
                ]);
            }

            $request->session()->put('mandatory_password_otp', $challenge);

            return back()->withErrors(['otp' => 'The verification code is incorrect.']);
        }

        $updated = DB::transaction(function () use ($user, $challenge): ?User {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            if (
                ! $lockedUser
                || ! $lockedUser->must_change_password
                || $lockedUser->isSuperAdmin()
                || strtolower((string) $lockedUser->status) !== 'active'
            ) {
                return null;
            }

            $lockedUser->forceFill([
                'password' => $challenge['password_hash'],
                'must_change_password' => false,
            ])->save();
            DB::table('mobile_access_tokens')->where('user_id', $lockedUser->getKey())->delete();

            return $lockedUser;
        });

        if (! $updated) {
            $request->session()->forget(['mandatory_password_user_id', 'mandatory_password_otp']);

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your account cannot complete the password reset. Contact a system administrator.',
            ]);
        }

        $remember = (bool) $request->session()->get('mandatory_password_remember', false);
        $request->session()->forget([
            'mandatory_password_user_id',
            'mandatory_password_remember',
            'mandatory_password_otp',
        ]);

        app(ActivityLogService::class)->log(
            $updated,
            ActivityLog::ACTION_PASSWORD_RESET,
            'Completed a system-mandated password change and email verification.',
            ['request' => $request],
        );

        return $this->completeLogin($request, $updated, $remember);
    }

    public function resendMandatoryPasswordOtp(Request $request): RedirectResponse
    {
        $user = $this->pendingMandatoryPasswordUser($request);
        $challenge = $request->session()->get('mandatory_password_otp');
        if (! $user || ! is_array($challenge) || (int) ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            return redirect()->route('password.mandatory.show')->withErrors([
                'new_password' => 'Submit your new password again to request a verification code.',
            ]);
        }

        if ((int) ($challenge['sent_at'] ?? 0) + 300 > now()->timestamp) {
            return back()->withErrors([
                'otp' => 'Please wait five minutes between verification code requests.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $challenge['otp_hash'] = Hash::make($otp);
        $challenge['encrypted_otp'] = Crypt::encryptString($otp);
        $challenge['expires_at'] = now()->addMinutes(10)->timestamp;
        $challenge['otp_expires_at'] = now()->addMinutes(5)->timestamp;
        $challenge['sent_at'] = now()->timestamp;
        $challenge['attempts'] = 0;
        $challenge['email_dispatched'] = false;
        $request->session()->put('mandatory_password_otp', $challenge);

        return redirect()->route('password.mandatory.otp')
            ->with('otp_notice', 'A new verification code is being sent.');
    }

    private function pendingMandatoryPasswordUser(Request $request): ?User
    {
        $userId = $request->session()->get('mandatory_password_user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if (
            ! $user
            || ! $user->must_change_password
            || $user->isSuperAdmin()
            || strtolower((string) $user->status) !== 'active'
        ) {
            $request->session()->forget([
                'mandatory_password_user_id',
                'mandatory_password_remember',
                'mandatory_password_otp',
            ]);

            return null;
        }

        return $user;
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $challenge = $request->session()->get('login_otp_challenge');

        if (! $this->isLoginOtpChallengeActive($challenge)) {
            return redirect()->route('login.otp')->withErrors([
                'otp' => 'Your verification code expired. Request a new code to continue.',
            ]);
        }

        $challenge['attempts']++;

        if (! Hash::check((string) $request->input('otp'), $challenge['otp_hash'])) {
            if ($challenge['attempts'] >= 5) {
                $request->session()->forget('login_otp_challenge');

                return redirect()->route('login')->withErrors([
                    'login_error' => 'Too many incorrect verification attempts. Please sign in again.',
                ]);
            }

            $request->session()->put('login_otp_challenge', $challenge);

            return back()->withErrors([
                'otp' => 'The verification code is incorrect.',
            ]);
        }

        $user = User::find($challenge['user_id']);
        if (
            ! $user
            || strtolower((string) $user->status) !== 'active'
            || ($this->systemMaintenanceState->isEnabled() && ! $user->isSuperAdmin())
        ) {
            $request->session()->forget('login_otp_challenge');

            return redirect()->route('login')->withErrors([
                'login_error' => 'Your account cannot sign in right now. Please contact an administrator.',
            ]);
        }

        if ($user->must_change_password && ! $user->isSuperAdmin()) {
            $request->session()->forget('login_otp_challenge');
            $request->session()->put('mandatory_password_user_id', $user->getKey());
            $request->session()->put('mandatory_password_remember', (bool) ($challenge['remember'] ?? false));

            return redirect()->route('password.mandatory.show');
        }

        $request->session()->forget('login_otp_challenge');
        $response = $this->completeLogin($request, $user, (bool) $challenge['remember']);

        if ($challenge['remember_today']) {
            $today = now()->toDateString();
            $userId = (string) $user->getKey();
            $cookieValue = $this->dailyOtpCookieValue($user, $today);
            $minutesUntilMidnight = max(1, (int) ceil(now()->diffInSeconds(now()->endOfDay()) / 60));

            $response->withCookie(cookie(
                'daily_otp_verified',
                $cookieValue,
                $minutesUntilMidnight,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }

    /**
     * Show the temporary superadmin login page used during maintenance.
     */
    public function showMaintenanceLoginForm()
    {
        if (! $this->systemMaintenanceState->isEnabled()) {
            return redirect()->route('login');
        }

        return view('auth.maintenance-login', [
            'maintenanceState' => $this->systemMaintenanceState->state(),
        ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $request->session()->put('announcements_login_popup_pending', true);
        $request->session()->forget('announcements_popup_shown_in_session');

        if (ModuleConfiguration::isModuleEnabled('dashboard')) {
            return redirect('/dashboard');
        }

        $landingModules = [
            ['projects.locally-funded.modern', 'locally_funded_projects', 'locally_funded_projects'],
            ['projects.rssa', 'rssa_projects', 'locally_funded_projects'],
            ['projects.rlip-lime', 'rlip_lime_projects', 'rlip_lime_projects'],
            ['projects.at-risk', 'project_at_risk_projects', 'project_at_risk_projects'],
            ['projects.sglgif.table', 'sglgif_portal', 'sglgif_portal'],
            ['reports.annual.amwp', 'annual_maintenance_work_program', 'annual_maintenance_work_program'],
            ['fund-utilization.index', 'fund_utilization_reports', 'fund_utilization_reports'],
            ['task-management.index', 'approval_management', 'approval_management'],
            ['ticketing.dashboard', 'ticketing_system', 'ticketing_system'],
            ['messages.index', 'communications', 'communications'],
            ['announcements.index', 'announcement_management', 'announcement_management'],
            ['profile.show', 'user_profile', 'user_profile'],
        ];

        foreach ($landingModules as [$route, $module, $permission]) {
            if (
                ModuleConfiguration::isModuleEnabled($module)
                && $user->hasCrudPermission($permission, 'view')
            ) {
                return redirect()->route($route);
            }
        }

        return redirect()->route('landing');
    }

    private function completeLogin(Request $request, User $user, bool $remember): Response
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('auth_password_version', (int) $user->password_security_version);

        return $this->authenticated($request, $user);
    }

    private function isLoginOtpChallengeValid(mixed $challenge): bool
    {
        if (
            ! is_array($challenge)
            || ! isset($challenge['user_id'], $challenge['otp_hash'], $challenge['expires_at'], $challenge['attempts'], $challenge['sent_at'])
        ) {
            return false;
        }

        return is_numeric($challenge['expires_at'])
            && is_numeric($challenge['sent_at'])
            && $challenge['sent_at'] <= now()->timestamp;
    }

    private function isLoginOtpChallengeActive(mixed $challenge): bool
    {
        return $this->isLoginOtpChallengeValid($challenge)
            && (int) $challenge['expires_at'] > now()->timestamp;
    }

    private function sendLoginOtp(
        Request $request,
        User $user,
        array $challenge,
        bool $isResend = false,
    ): RedirectResponse {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);
        $challenge['otp_hash'] = Hash::make($otp);
        $challenge['expires_at'] = $expiresAt->timestamp;
        $challenge['sent_at'] = now()->timestamp;
        $challenge['attempts'] = 0;
        $challenge['email_dispatched'] = $isResend;

        if ($isResend) {
            unset($challenge['encrypted_otp']);
        } else {
            $challenge['encrypted_otp'] = Crypt::encryptString($otp);
        }

        $request->session()->put('login_otp_challenge', $challenge);

        if ($isResend) {
            SendLoginOtpEmail::dispatch((int) $user->getKey(), $otp, $expiresAt)->afterResponse();
        }

        return redirect()->route('login.otp')
            ->with($isResend ? 'otp_notice' : 'login_otp_sent', true);
    }

    private function hasDailyOtpCookie(Request $request, User $user): bool
    {
        $parts = explode('|', (string) $request->cookie('daily_otp_verified'), 3);
        if (count($parts) !== 3) {
            return false;
        }

        [$userId, $date, $signature] = $parts;
        if ($userId !== (string) $user->getKey() || $date !== now()->toDateString()) {
            return false;
        }

        return hash_equals($this->dailyOtpCookieSignature($user, $date), $signature);
    }

    private function dailyOtpCookieValue(User $user, string $date): string
    {
        $userId = (string) $user->getKey();

        return $userId.'|'.$date.'|'.$this->dailyOtpCookieSignature($user, $date);
    }

    private function dailyOtpCookieSignature(User $user, string $date): string
    {
        $payload = $user->getKey().'|'.$date.'|'.$user->getAuthPassword();

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    private function maskEmailAddress(string $emailAddress): string
    {
        [$localPart, $domain] = array_pad(explode('@', $emailAddress, 2), 2, '');
        if ($localPart === '' || $domain === '') {
            return 'your registered email address';
        }

        return substr($localPart, 0, 1).str_repeat('*', max(3, strlen($localPart) - 1)).'@'.$domain;
    }

    /**
     * Get the failed login response instance.
     *
     * @return Response
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where('username', $request->input('username'))->first();
        $failureReason = 'Invalid username or password.';
        $loginRoute = $this->systemMaintenanceState->isEnabled()
            ? 'maintenance.superadmin-login'
            : 'login';

        if ($user && strtolower($user->status) !== 'active') {
            $failureReason = 'Attempted login with an inactive account.';
            app(ActivityLogService::class)->log(
                $user,
                ActivityLog::ACTION_FAILED_LOGIN,
                'Failed login attempt: inactive account.',
                [
                    'request' => $request,
                    'properties' => [
                        'reason' => $failureReason,
                    ],
                ],
            );

            return redirect()->route($loginRoute)->withErrors([
                'login_error' => 'Your account is inactive. Please contact an administrator.',
            ])->withInput($request->only('username', 'remember'));
        }

        if (
            $user
            && strtolower((string) $user->status) === 'active'
            && Hash::check((string) $request->input('password'), (string) $user->password)
            && $this->systemMaintenanceState->isEnabled()
            && ! $user->isSuperAdmin()
        ) {
            $failureReason = 'Attempted login during maintenance without superadmin access.';
            app(ActivityLogService::class)->log(
                $user,
                ActivityLog::ACTION_FAILED_LOGIN,
                'Failed login attempt during maintenance mode.',
                [
                    'request' => $request,
                    'properties' => [
                        'reason' => $failureReason,
                    ],
                ],
            );

            return redirect()->route('maintenance.notice');
        }

        app(ActivityLogService::class)->log(
            $user,
            ActivityLog::ACTION_FAILED_LOGIN,
            'Failed login attempt for username "'.trim((string) $request->input('username')).'".',
            [
                'request' => $request,
                'username' => trim((string) $request->input('username')),
                'properties' => [
                    'reason' => $failureReason,
                ],
            ],
        );

        return redirect()->route($loginRoute)->withErrors([
            'login_error' => 'The username or password is incorrect.',
        ])->withInput($request->only('username', 'remember'));
    }
}
