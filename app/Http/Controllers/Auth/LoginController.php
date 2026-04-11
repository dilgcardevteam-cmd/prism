<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Support\SystemMaintenanceState;

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

        return view('auth.login');
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
        return redirect('/dashboard');
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
        $loginRoute = $this->systemMaintenanceState->isEnabled()
            ? 'maintenance.superadmin-login'
            : 'login';
        
        if ($user && strtolower($user->status) !== 'active') {
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
            return redirect()->route('maintenance.notice');
        }
        
        return redirect()->route($loginRoute)->withErrors([
            'login_error' => 'The username or password is incorrect.',
        ])->withInput($request->only('username', 'remember'));
    }
}
