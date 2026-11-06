<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ModuleConfiguration;
use App\Models\User;
use App\Modules\ActivityLogs\Models\ActivityLog;
use App\Modules\ActivityLogs\Services\ActivityLogService;
use App\Support\SystemMaintenanceState;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
    )
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
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
     * Show the temporary superadmin login page used during maintenance.
     */
    public function showMaintenanceLoginForm()
    {
        if (!$this->systemMaintenanceState->isEnabled()) {
            return redirect()->route('login');
        }

        return view('auth.maintenance-login', [
            'maintenanceState' => $this->systemMaintenanceState->state(),
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        // Get credentials
        $username = $request->input('username');
        $password = $request->input('password');
        
        // Find user by username
        $user = User::where('username', $username)->first();
        
        // Check all conditions
        if (!$user) {
            return false;
        }
        
        if (strtolower($user->status) !== 'active') {
            return false;
        }
        
        if (!Hash::check($password, $user->password)) {
            return false;
        }

        if ($this->systemMaintenanceState->isEnabled() && !$user->isSuperAdmin()) {
            return false;
        }
        
        // All checks passed - login the user
        Auth::login($user, $request->filled('remember'));
        return true;
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
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

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
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
            && !$user->isSuperAdmin()
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
            'Failed login attempt for username "' . trim((string) $request->input('username')) . '".',
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
