<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\LocallyFundedProject;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\RlipLimeProjectController;
use App\Http\Controllers\SglgifProjectController;
use App\Http\Controllers\SystemManagementController;

Auth::routes(['reset' => false, 'register' => false]); // Disable default register routes

// Custom register routes
Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

// Email verification routes - Token route FIRST (more specific)
Route::get('/email/verify/token/{token}', [App\Http\Controllers\Auth\VerificationController::class, 'verifyWithToken'])->name('verification.verify.token');
// Then general verification routes
Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
Route::post('/email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.resend');

Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('forgot-password');
Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendOtp'])->name('forgot-password.send-otp');
Route::get('/verify-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showVerifyOtpForm'])->name('forgot-password.verify');
Route::post('/verify-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'verifyOtp'])->name('forgot-password.verify-otp');
Route::get('/reset-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showResetForm'])->name('forgot-password.reset');
Route::post('/reset-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'resetPassword'])->name('forgot-password.reset-submit');

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/admin/login', [App\Http\Controllers\Auth\LoginController::class, 'showMaintenanceLoginForm'])
    ->name('maintenance.superadmin-login');

Route::get('/system-under-maintenance', function (\App\Support\SystemMaintenanceState $systemMaintenanceState) {
    if (!$systemMaintenanceState->isEnabled()) {
        return redirect()->route('landing');
    }

    return response()->view('errors.maintenance', [
        'maintenanceState' => $systemMaintenanceState->state(),
    ], 503);
})->withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
])->name('maintenance.notice');

// Public API endpoint for municipality projects
Route::get('/api/municipality-projects', function () {
    try {
        if (!Schema::hasTable('subay_project_profiles')) {
            return response()->json(['features' => [], 'maxCount' => 0]);
        }

        $municipalityCoords = [
            // Abra
            'Bangued' => ['lat' => 17.0667, 'lng' => 120.6167],
            'Boliney' => ['lat' => 17.0833, 'lng' => 120.65],
            'Bucay' => ['lat' => 16.85, 'lng' => 120.6],
            'Daguioman' => ['lat' => 16.9, 'lng' => 120.7167],
            'Danglas' => ['lat' => 16.9667, 'lng' => 120.8],
            'Dolores' => ['lat' => 17.1667, 'lng' => 120.75],
            'La Paz' => ['lat' => 17.0, 'lng' => 120.5],
            'Lacub' => ['lat' => 17.05, 'lng' => 120.8],
            'Lagangilang' => ['lat' => 16.8667, 'lng' => 120.75],
            'Lagayan' => ['lat' => 16.9167, 'lng' => 120.65],
            'Langiden' => ['lat' => 17.0833, 'lng' => 120.9],
            'Licuan-Baay' => ['lat' => 17.0, 'lng' => 120.85],
            'Malibcong' => ['lat' => 16.95, 'lng' => 120.6333],
            'Manabo' => ['lat' => 16.8833, 'lng' => 120.65],
            'Peñarrubia' => ['lat' => 16.9333, 'lng' => 120.5333],
            'Pidcal' => ['lat' => 17.0167, 'lng' => 120.7333],
            'Pilar' => ['lat' => 16.9667, 'lng' => 120.5667],
            'Sallapadan' => ['lat' => 16.95, 'lng' => 120.85],
            'San Isidro' => ['lat' => 17.0667, 'lng' => 120.6833],
            'San Juan' => ['lat' => 17.1333, 'lng' => 120.7833],
            'San Quintin' => ['lat' => 17.1167, 'lng' => 120.6667],
            'Tayum' => ['lat' => 17.1083, 'lng' => 120.8333],
            // Apayao
            'Calanasan' => ['lat' => 17.95, 'lng' => 121.1667],
            'Conner' => ['lat' => 17.75, 'lng' => 121.05],
            'Flora' => ['lat' => 17.95, 'lng' => 121.0],
            'Kabugao' => ['lat' => 17.8333, 'lng' => 120.95],
            'Pudtol' => ['lat' => 17.85, 'lng' => 121.1667],
            'Santa Marcela' => ['lat' => 18.0333, 'lng' => 121.2167],
            // Benguet
            'Atok' => ['lat' => 16.4167, 'lng' => 120.6],
            'Bakun' => ['lat' => 16.35, 'lng' => 120.9],
            'Bokod' => ['lat' => 16.3833, 'lng' => 120.8167],
            'Buguias' => ['lat' => 16.5167, 'lng' => 120.8],
            'City Of Baguio' => ['lat' => 16.408, 'lng' => 120.594],
            'Itogon' => ['lat' => 16.4833, 'lng' => 120.85],
            'Kabayan' => ['lat' => 16.3833, 'lng' => 120.7],
            'Kapangan' => ['lat' => 16.45, 'lng' => 120.7667],
            'Kibungan' => ['lat' => 16.4833, 'lng' => 120.7667],
            'La Trinidad' => ['lat' => 16.3667, 'lng' => 120.55],
            'Mankayan' => ['lat' => 16.5333, 'lng' => 120.65],
            'Sablan' => ['lat' => 16.35, 'lng' => 120.65],
            'Tuba' => ['lat' => 16.3333, 'lng' => 120.5333],
            'Tublay' => ['lat' => 16.3667, 'lng' => 120.6667],
            'Tubo' => ['lat' => 16.4, 'lng' => 120.6],
            // Ifugao
            'Aguinaldo' => ['lat' => 16.95, 'lng' => 121.3],
            'Alfonso Lista' => ['lat' => 17.0333, 'lng' => 121.3833],
            'Asipulo' => ['lat' => 16.85, 'lng' => 121.2667],
            'Banaue' => ['lat' => 16.95, 'lng' => 121.1667],
            'Hingyon' => ['lat' => 16.8667, 'lng' => 121.3333],
            'Hungduan' => ['lat' => 16.8333, 'lng' => 121.2],
            'Kiangan' => ['lat' => 16.9833, 'lng' => 121.2667],
            'Lagawe' => ['lat' => 16.9333, 'lng' => 121.2167],
            'Mayoyao' => ['lat' => 16.85, 'lng' => 121.45],
            'Tinoc' => ['lat' => 16.9, 'lng' => 121.4167],
            // Kalinga
            'Balbalan' => ['lat' => 17.4333, 'lng' => 121.45],
            'City Of Tabuk' => ['lat' => 17.3667, 'lng' => 121.4167],
            'Dagupagsan' => ['lat' => 17.35, 'lng' => 121.3833],
            'Lubuagan' => ['lat' => 17.3833, 'lng' => 121.3167],
            'Luna' => ['lat' => 17.2, 'lng' => 121.2167],
            'Mabunguran' => ['lat' => 17.3667, 'lng' => 121.5167],
            'Pasil' => ['lat' => 17.2333, 'lng' => 121.3667],
            'Pinukpuk' => ['lat' => 17.2667, 'lng' => 121.45],
            'Rizal' => ['lat' => 17.2833, 'lng' => 121.2333],
            'Tanudan' => ['lat' => 17.2333, 'lng' => 121.2667],
            'Tinglayan' => ['lat' => 17.2167, 'lng' => 121.5333],
            // Mountain Province
            'Amlang' => ['lat' => 16.65, 'lng' => 121.3333],
            'Amtan' => ['lat' => 16.7, 'lng' => 121.2667],
            'Barlig' => ['lat' => 16.75, 'lng' => 121.1667],
            'Bauko' => ['lat' => 16.8, 'lng' => 121.1833],
            'Besao' => ['lat' => 16.7167, 'lng' => 121.2667],
            'Bontoc' => ['lat' => 16.7667, 'lng' => 121.3],
            'Bucloc' => ['lat' => 16.6667, 'lng' => 121.4],
            'Cervantes' => ['lat' => 16.7667, 'lng' => 121.35],
            'Luba' => ['lat' => 16.8333, 'lng' => 121.25],
            'Natonin' => ['lat' => 16.6667, 'lng' => 121.2667],
            'Paracelis' => ['lat' => 16.7833, 'lng' => 121.4833],
            'Sabangan' => ['lat' => 16.8167, 'lng' => 121.35],
            'Sadanga' => ['lat' => 16.7, 'lng' => 121.3167],
            'Sagada' => ['lat' => 16.7333, 'lng' => 121.2167],
            'Tadian' => ['lat' => 16.7667, 'lng' => 121.3167],
            'Tineg' => ['lat' => 16.7333, 'lng' => 121.35]
        ];

        // Get all municipalities with subay project counts
        $municipalityProjects = DB::table('subay_project_profiles')
            ->select(
                DB::raw('UPPER(TRIM(city_municipality)) as municipality'),
                DB::raw('count(*) as project_count')
            )
            ->whereNotNull('city_municipality')
            ->where('city_municipality', '<>', '')
            ->groupBy(DB::raw('UPPER(TRIM(city_municipality))'))
            ->get();

        $maxCount = 0;
        $features = [];
        
        // Create a lowercase version of coords for matching
        $coordsLowercase = [];
        foreach ($municipalityCoords as $name => $coord) {
            $coordsLowercase[strtolower($name)] = ['name' => $name, 'coord' => $coord];
        }

        foreach ($municipalityProjects as $row) {
            $municipality = $row->municipality;
            // Remove anything in parentheses and lowercase for matching
            $municipalityKey = strtolower(trim(preg_replace('/\s*\([^)]*\)\s*/', '', $municipality)));
            $count = $row->project_count;
            $maxCount = max($maxCount, $count);

            if (isset($coordsLowercase[$municipalityKey])) {
                $matchedData = $coordsLowercase[$municipalityKey];
                $coords = $matchedData['coord'];
                $displayName = $matchedData['name'];
                $features[] = [
                    'type' => 'Feature',
                    'properties' => [
                        'name' => $displayName,
                        'project_count' => $count
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$coords['lng'], $coords['lat']]
                    ]
                ];
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'maxCount' => $maxCount
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.municipality-projects');

//======================MOBILE ENDPOINTS=================
Route::get('/api/mobile/locally-funded', [App\Http\Controllers\LocallyFundedProjectController::class, 'mobileIndex'])
    ->name('api.mobile.locally-funded');
Route::get('/api/mobile/dashboard/aggregate', [App\Http\Controllers\LocallyFundedProjectController::class, 'mobileAggregatedDashboard'])
    ->name('api.mobile.dashboard.aggregate');
Route::get('/api/mobile/locally-funded/dashboard-summary', [App\Http\Controllers\LocallyFundedProjectController::class, 'mobileDashboardSummary'])
    ->name('api.mobile.locally-funded.dashboard-summary');
Route::get('/api/mobile/locally-funded/expected-completion', [App\Http\Controllers\LocallyFundedProjectController::class, 'mobileExpectedCompletionThisMonth'])
    ->name('api.mobile.locally-funded.expected-completion');
Route::get('/api/mobile/project-at-risk/slippage-summary', [App\Http\Controllers\ProjectAtRiskController::class, 'mobileSlippageSummary'])
    ->name('api.mobile.project-at-risk.slippage-summary');
Route::get('/api/mobile/project-at-risk/aging-summary', [App\Http\Controllers\ProjectAtRiskController::class, 'mobileAgingSummary'])
    ->name('api.mobile.project-at-risk.aging-summary');
Route::get('/api/mobile/project-at-risk/project-update-status-summary', [App\Http\Controllers\ProjectAtRiskController::class, 'mobileProjectUpdateStatusSummary'])
    ->name('api.mobile.project-at-risk.project-update-status-summary');
Route::get('/api/mobile/locally-funded/{project}/gallery/{galleryImage}', [App\Http\Controllers\LocallyFundedProjectController::class, 'viewMobileGalleryImage'])
    ->whereNumber('project')
    ->whereNumber('galleryImage')
    ->name('api.mobile.locally-funded.gallery-image');

Route::post('/api/mobile/locally-funded/{project}/gallery', [App\Http\Controllers\LocallyFundedProjectController::class, 'mobileUploadGalleryImage'])
    ->whereNumber('project')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.mobile.locally-funded.gallery-upload');

Route::post('/api/mobile/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('username', $credentials['username'])->first();

    if (!$user || strtolower((string) $user->status) !== 'active' || !Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'The username or password is incorrect.',
        ], 422);
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    return response()->json([
        'message' => 'Login successful.',
        'user' => [
            'id' => $user->idno,
            'username' => $user->username,
            'first_name' => $user->fname ?? null,
            'last_name' => $user->lname ?? null,
            'email' => $user->emailaddress ?? null,
            'phone' => $user->mobileno ?? null,
            'agency' => $user->agency ?? null,
            'position' => $user->position ?? null,
            'region' => $user->region ?? null,
            'province' => $user->province ?? null,
            'office' => $user->office ?? null,
            'role' => $user->role ?? null,
            'status' => $user->status,
        ],
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

Route::get('/api/mobile/user/profile', function (Request $request) {
    if (!Auth::check()) {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $user = Auth::user();

    return response()->json([
        'user' => [
            'id' => $user->idno,
            'username' => $user->username,
            'first_name' => $user->fname ?? null,
            'last_name' => $user->lname ?? null,
            'email' => $user->emailaddress ?? null,
            'phone' => $user->mobileno ?? null,
            'agency' => $user->agency ?? null,
            'position' => $user->position ?? null,
            'region' => $user->region ?? null,
            'province' => $user->province ?? null,
            'office' => $user->office ?? null,
            'role' => $user->role ?? null,
            'status' => $user->status,
        ],
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

Route::get('/api/mobile/messages', [App\Http\Controllers\MessageController::class, 'mobileIndex'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.mobile.messages.index');

Route::post('/api/mobile/messages', [App\Http\Controllers\MessageController::class, 'store'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.mobile.messages.store');

Route::get('/api/mobile/notifications', function (Request $request) {
    $authUser = Auth::user();
    $userId = $authUser?->idno ?? (int) $request->query('user_id');

    if (!$userId) {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    if ($authUser && (int) $authUser->idno !== (int) $userId) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 403);
    }

    $notifications = DB::table('tbnotifications')
        ->where('user_id', $userId)
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->limit(50)
        ->get()
        ->map(function ($notification) {
            return [
                'id' => $notification->id,
                'message' => $notification->message,
                'url' => $notification->url,
                'document_type' => $notification->document_type,
                'quarter' => $notification->quarter,
                'sender_name' => $notification->sender_name ?? null,
                'sender_user_id' => $notification->sender_user_id ?? null,
                'read_at' => $notification->read_at,
                'is_read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at,
                'updated_at' => $notification->updated_at,
            ];
        });

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $notifications->where('is_read', false)->count(),
        'total_count' => $notifications->count(),
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

Route::post('/api/mobile/user/profile/update', function (Request $request) {
    $validated = $request->validate([
        'user_id' => ['required', 'integer'],
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'position' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string'],
    ]);

    $sanitizedPhone = preg_replace('/\D+/', '', (string) $validated['phone']);

    if (!preg_match('/^09\d{9}$/', $sanitizedPhone)) {
        return response()->json([
            'message' => 'Mobile number must be 11 digits and start with 09.',
        ], 422);
    }

    $user = User::where('idno', $validated['user_id'])->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found.',
        ], 404);
    }

    $user->update([
        'fname' => trim((string) $validated['first_name']),
        'lname' => trim((string) $validated['last_name']),
        'position' => trim((string) $validated['position']),
        'mobileno' => $sanitizedPhone,
    ]);

    return response()->json([
        'message' => 'Profile updated successfully.',
        'user' => [
            'id' => $user->idno,
            'username' => $user->username,
            'first_name' => $user->fname ?? null,
            'last_name' => $user->lname ?? null,
            'email' => $user->emailaddress ?? null,
            'phone' => $user->mobileno ?? null,
            'agency' => $user->agency ?? null,
            'position' => $user->position ?? null,
            'region' => $user->region ?? null,
            'province' => $user->province ?? null,
            'office' => $user->office ?? null,
            'role' => $user->role ?? null,
            'status' => $user->status,
        ],
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

Route::post('/api/mobile/user/password/update', function (Request $request) {
    if (!Auth::check()) {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $validated = $request->validate([
        'user_id' => ['required', 'integer'],
        'current_password' => ['required', 'string'],
        'new_password' => [
            'required',
            'string',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
            'different:current_password',
            'confirmed',
        ],
    ], [
        'new_password.min' => 'The new password must be at least 8 characters.',
        'new_password.regex' => 'The new password does not meet all required complexity rules.',
        'new_password.confirmed' => 'The password confirmation does not match.',
        'new_password.different' => 'The new password must be different from the current password.',
    ]);

    $authUser = Auth::user();

    if ((int) $authUser->idno !== (int) $validated['user_id']) {
        return response()->json([
            'message' => 'Unauthorized password update request.',
        ], 403);
    }

    $user = User::where('idno', (int) $validated['user_id'])->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found.',
        ], 404);
    }

    if (!Hash::check($validated['current_password'], $user->password)) {
        return response()->json([
            'message' => 'Current password is incorrect.',
        ], 422);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->save();

    return response()->json([
        'message' => 'Password updated successfully.',
    ]);
})->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);



Route::middleware(['auth'])->group(function () {
    // PAGASA time endpoint for live clock display
    Route::get('/api/pagasa-time/current', [App\Http\Controllers\PagasaTimeController::class, 'current'])->name('pagasa-time.current');

    Route::get('/notifications/{id}/read', function ($id) {
        $notification = \Illuminate\Support\Facades\DB::table('tbnotifications')
            ->where('id', $id)
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->first();

        if (!$notification) {
            return redirect()->back();
        }

        \Illuminate\Support\Facades\DB::table('tbnotifications')
            ->where('id', $id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        $notificationUrl = \App\Support\NotificationUrl::normalizeForRedirect($notification->url);

        return redirect($notificationUrl ?: route('fund-utilization.index'));
    })->name('notifications.read');

    Route::post('/notifications/clear', function () {
        \Illuminate\Support\Facades\DB::table('tbnotifications')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->whereNotNull('read_at')
            ->where(function ($query) {
                $query->whereNull('document_type')
                    ->orWhere('document_type', '!=', 'bulk-notification');
            })
            ->delete();

        return redirect()->back();
    })->name('notifications.clear');

    Route::get('/messages', [App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{thread}/mark-unread', [App\Http\Controllers\MessageController::class, 'markThreadUnread'])->name('messages.mark-unread');
    Route::post('/messages/{thread}/mark-read', [App\Http\Controllers\MessageController::class, 'markThreadRead'])->name('messages.mark-read');
    Route::post('/messages/{thread}/delete', [App\Http\Controllers\MessageController::class, 'deleteConversation'])->name('messages.delete');
    Route::post('/messages/{thread}/rename-group', [App\Http\Controllers\MessageController::class, 'renameGroup'])->name('messages.rename-group');
    Route::get('/messages/poll', [App\Http\Controllers\MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/messages/conversation', [App\Http\Controllers\MessageController::class, 'conversation'])->name('messages.conversation');

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    $renderProjectDashboard = function (string $activeProjectTab = 'locally-funded') {
        try {
            if ($activeProjectTab === 'rssa') {
                return app(\App\Http\Controllers\RssaDashboardController::class)->index(request());
            }

            $subayUploadDateLabel = 'No SubayBAYAN upload yet';
            if (Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'created_at')) {
                $latestSubayUploadAt = DB::table('subay_project_profiles')->max('created_at');
                if ($latestSubayUploadAt) {
                    try {
                        $subayUploadDateLabel = \Illuminate\Support\Carbon::parse($latestSubayUploadAt)->format('F d, Y h:i A');
                    } catch (\Throwable $error) {
                        $subayUploadDateLabel = (string) $latestSubayUploadAt;
                    }
                }
            }

            $user = Auth::user();
            $province = trim((string) $user->province);
            $office = trim((string) $user->office);
            $region = trim((string) $user->region);
            $provinceLower = $user->normalizedProvince();
            $officeLower = $user->normalizedOffice();
            $regionLower = $user->normalizedRegion();
            $officeComparableLower = $user->normalizedOfficeComparable();
            $isLguScopedUser = $user->isLguScopedUser();
            $isDilgUser = $user->isDilgUser();
            $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();

            $normalizeFilterValue = function ($value): string {
                $normalizedValue = trim((string) $value);
                $normalizedValue = preg_replace('/\s+/u', ' ', $normalizedValue) ?? $normalizedValue;

                return trim($normalizedValue);
            };

            $parseRequestedFilterValues = function (string $inputName) use ($normalizeFilterValue): array {
                $requestedValues = request()->input($inputName, []);
                if (!is_array($requestedValues)) {
                    $requestedValues = $requestedValues === null ? [] : [$requestedValues];
                }

                return collect($requestedValues)
                    ->map($normalizeFilterValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();
            };

            $filters = [
                'search' => $normalizeFilterValue(request()->input('search', '')),
                'province' => $parseRequestedFilterValues('province'),
                'city_municipality' => $parseRequestedFilterValues('city_municipality'),
                'barangay' => $parseRequestedFilterValues('barangay'),
                'programs' => $parseRequestedFilterValues('program'),
                'fund_source' => $parseRequestedFilterValues('fund_source'),
                'funding_year' => $parseRequestedFilterValues('funding_year'),
                'project_type' => $parseRequestedFilterValues('project_type'),
                'project_status' => $parseRequestedFilterValues('project_status'),
            ];

            if (empty($filters['province'])) {
                $filters['city_municipality'] = [];
            }

            if (empty($filters['city_municipality'])) {
                $filters['barangay'] = [];
            }

            $filterOptions = [
                'provinces' => collect(),
                'cities' => collect(),
                'province_city_map' => [],
                'barangays' => collect(),
                'city_barangay_map' => [],
                'programs' => collect(),
                'fund_sources' => collect(),
                'funding_years' => collect(),
                'project_types' => collect(),
                'project_statuses' => collect(),
            ];

            $normalizeComparableLocationValue = function ($value): string {
                return \App\Support\ProjectLocationFilterHelper::normalizeComparableLocationLabel($value);
            };

            $subayCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
            $applyOfficeScopeToSubay = function ($query) use ($officeLower, $officeComparableLower, $subayCityComparableExpression) {
                if ($officeLower === '') {
                    return;
                }

                $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

                $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $subayCityComparableExpression) {
                    $subQuery->whereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) = ?', [$officeLower])
                        ->orWhereRaw("{$subayCityComparableExpression} = ?", [$officeNeedle]);
                });
            };

            $applyRoleScopeToSubay = function ($query) use (
                $province,
                $office,
                $region,
                $provinceLower,
                $regionLower,
                $isLguScopedUser,
                $isDilgUser,
                $isRegionalOfficeUser,
                $applyOfficeScopeToSubay
            ) {
                if ($isLguScopedUser) {
                    if ($office !== '') {
                        if ($province !== '') {
                            $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
                            $applyOfficeScopeToSubay($query);
                        } else {
                            $applyOfficeScopeToSubay($query);
                        }
                    } elseif ($province !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
                    }
                } elseif ($isDilgUser) {
                    if ($isRegionalOfficeUser) {
                        // Regional Office users can see all projects.
                    } elseif ($province !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
                    } elseif ($region !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(spp.region, ""))) = ?', [$regionLower]);
                    }
                }
            };

            $normalizedSqlComparable = function (string $column): string {
                return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' '), '  ', ' '), '  ', ' '), '  ', ' ')))";
            };

            $normalizedDelimitedSqlComparable = function (string $column): string {
                return "LOWER(CONCAT('|', TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), '|'), CHAR(10), '|'), CHAR(9), ' '), ' |', '|'), '| ', '|'), '||', '|'), '||', '|')), '|'))";
            };

            $normalizedComparableLocationSql = function (string $column): string {
                return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' ')))";
            };

            $applyExactMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedSqlComparable, $normalizeFilterValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeFilterValue)
                    ->map(function ($value) {
                        return strtolower($value);
                    })
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
                $query->whereRaw($normalizedSqlComparable($column) . " IN ({$placeholders})", $normalizedValues);
            };

            $applyDelimitedMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedDelimitedSqlComparable, $normalizeFilterValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeFilterValue)
                    ->map(function ($value) {
                        return strtolower($value);
                    })
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $normalizedExpression = $normalizedDelimitedSqlComparable($column);
                $query->where(function ($subQuery) use ($normalizedValues, $normalizedExpression) {
                    foreach ($normalizedValues as $normalizedValue) {
                        $subQuery->orWhereRaw($normalizedExpression . ' LIKE ?', ['%|' . $normalizedValue . '|%']);
                    }
                });
            };

            $applyComparableLocationMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedComparableLocationSql, $normalizeComparableLocationValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeComparableLocationValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
                $query->whereRaw($normalizedComparableLocationSql($column) . " IN ({$placeholders})", $normalizedValues);
            };

            $fundSourceFromProjectCodeExpr = "
                CASE
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'SBDP%' THEN 'SBDP'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'FA-%' THEN 'FALGU'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'FALGU%' THEN 'FALGU'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'CMGP%' THEN 'CMGP'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'GEF%' THEN 'GEF'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'SAFPB%' THEN 'SAFPB'
                    WHEN UPPER(TRIM(spp.project_code)) LIKE 'SGLGIF%' THEN 'SGLGIF'
                    WHEN TRIM(COALESCE(spp.program, '')) <> '' THEN UPPER(TRIM(COALESCE(spp.program, '')))
                    ELSE 'UNSPECIFIED'
                END
            ";

            $excludeSglgifFromSubay = function ($query) {
                $query->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
                    ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF']);
            };

            $excludeSglgifFromFallback = function ($query) {
                $query->whereRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
                    ->whereRaw('UPPER(TRIM(COALESCE(fund_source, ""))) <> ?', ['SGLGIF']);
            };

            $applyDashboardFiltersToSubay = function ($query) use ($filters, $applyExactMultiFilterToSubay, $applyDelimitedMultiFilterToSubay, $applyComparableLocationMultiFilterToSubay, $fundSourceFromProjectCodeExpr) {
                $applyExactMultiFilterToSubay($query, 'spp.province', $filters['province']);
                $applyComparableLocationMultiFilterToSubay($query, 'spp.city_municipality', $filters['city_municipality']);
                $applyDelimitedMultiFilterToSubay($query, 'spp.barangay', $filters['barangay']);
                $applyExactMultiFilterToSubay($query, 'spp.program', $filters['programs']);
                $applyExactMultiFilterToSubay($query, $fundSourceFromProjectCodeExpr, $filters['fund_source']);
                $applyExactMultiFilterToSubay($query, 'spp.funding_year', $filters['funding_year']);
                $applyExactMultiFilterToSubay($query, 'spp.type_of_project', $filters['project_type']);
                $applyExactMultiFilterToSubay($query, 'spp.status', $filters['project_status']);

                if ($filters['search'] !== '') {
                    $searchNeedle = '%' . strtolower($filters['search']) . '%';
                    $query->where(function ($searchQuery) use ($searchNeedle) {
                        $searchQuery
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.project_code, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.project_title, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.barangay, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.program, ""))) LIKE ?', [$searchNeedle]);
                    });
                }
            };

            $totalProjects = 0;
            $fundSourceOptions = ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB'];
            $filterOptions['fund_sources'] = collect($fundSourceOptions);
            $fundSourceCountsMap = [];
            $fundSourceProjectsMap = [];
            $totalObligationAmount = 0.0;
            $totalDisbursementAmount = 0.0;
            $totalBalanceAmount = 0.0;
            $totalLgsfAllocationAmount = 0.0;
            $projectsWithBalance = collect();
            $financialStatusProjects = collect();
            $utilizationPercentage = 0.0;
            $expectedCompletionMonthLabel = now()->format('F Y');
            $currentYear = now()->year;
            $currentMonth = now()->month;
            $currentMonthStart = now()->copy()->startOfMonth()->toDateString();
            $currentMonthEnd = now()->copy()->endOfMonth()->toDateString();
            $projectsExpectedCompletionThisMonth = collect();
            $projectAtRiskOrder = ['Ahead', 'No Risk', 'On Schedule', 'High Risk', 'Moderate Risk', 'Low Risk'];
            $projectAtRiskAgingOrder = ['High Risk', 'Low Risk', 'No Risk'];
            $projectUpdateStatusOrder = ['High Risk', 'Low Risk', 'No Risk'];
            $projectAtRiskCounts = array_fill_keys($projectAtRiskOrder, 0);
            $projectAtRiskAgingCounts = array_fill_keys($projectAtRiskAgingOrder, 0);
            $projectUpdateStatusCounts = array_fill_keys($projectUpdateStatusOrder, 0);
            $projectAtRiskSlippageProjects = array_fill_keys($projectAtRiskOrder, collect());
            $projectUpdateRiskProjects = [
                'High Risk' => collect(),
                'Low Risk' => collect(),
                'No Risk' => collect(),
            ];
            $projectAtRiskAgingProjects = [
                'High Risk' => collect(),
                'Low Risk' => collect(),
                'No Risk' => collect(),
            ];

            $statusLabels = [
                'COMPLETED' => 'Completed',
                'ONGOING' => 'On-going',
                'BID EVALUATION/OPENING' => 'Bid Evaluation/Opening',
                'NOA ISSUANCE' => 'NOA Issuance',
                'DED PREPARATION' => 'DED Preparation',
                'NOT YET STARTED' => 'Not Yet Started',
                'ITB/AD POSTED' => 'ITB/AD Posted',
                'TERMINATED' => 'Terminated',
                'CANCELLED' => 'Cancelled',
            ];
            $statusAliases = [
                'ON-GOING' => 'ONGOING',
                'NOT STARTED' => 'NOT YET STARTED',
            ];

            $normalizeStatus = function ($status) use ($statusLabels, $statusAliases) {
                $raw = trim((string) $status);
                if ($raw === '') {
                    return null;
                }

                $upper = strtoupper($raw);
                if (array_key_exists($upper, $statusAliases)) {
                    $upper = $statusAliases[$upper];
                }
                if (array_key_exists($upper, $statusLabels)) {
                    return $upper;
                }

                return null;
            };

            $labelForStatus = function ($normalized) use ($statusLabels) {
                if ($normalized && array_key_exists($normalized, $statusLabels)) {
                    return $statusLabels[$normalized];
                }
                return null;
            };

            $statusDisplayOrder = array_values($statusLabels);
            $statusDisplayOrderPositions = array_flip($statusDisplayOrder);
            $statusActualCounts = array_fill_keys($statusDisplayOrder, 0);
            $statusSubaybayanCounts = array_fill_keys($statusDisplayOrder, 0);
            $statusSubaybayanProjectsMap = [];
            $statusSubaybayanLocationReport = [];
            $provinceFundingYearProgramStatusReport = [];
            $provinceFundingYearProgramStatusSourceRows = [];
            foreach ($statusDisplayOrder as $statusLabel) {
                $statusSubaybayanProjectsMap[$statusLabel] = [];
            }

            $carProvinceDisplayOrder = [
                'Abra',
                'Apayao',
                'Benguet',
                'Ifugao',
                'Kalinga',
                'Mountain Province',
            ];
            $carProvinceProjectCounts = array_fill_keys($carProvinceDisplayOrder, 0);
            $carProvinceProjectMaxCount = 0;

            $normalizeCarProvince = function ($province) {
                $raw = trim((string) $province);
                if ($raw === '') {
                    return null;
                }

                $compact = preg_replace('/[^A-Z]/', '', strtoupper($raw)) ?? '';
                if ($compact === '') {
                    return null;
                }

                $provinceAliases = [
                    'ABRA' => 'Abra',
                    'APAYAO' => 'Apayao',
                    'BENGUET' => 'Benguet',
                    'IFUGAO' => 'Ifugao',
                    'KALINGA' => 'Kalinga',
                    'MOUNTAINPROVINCE' => 'Mountain Province',
                    'MOUNTAINPROV' => 'Mountain Province',
                    'MTPROVINCE' => 'Mountain Province',
                ];

                if (array_key_exists($compact, $provinceAliases)) {
                    return $provinceAliases[$compact];
                }

                return null;
            };

            $normalizeRiskLevel = function ($riskLevel) {
                $raw = strtoupper(trim((string) $riskLevel));
                if ($raw === '') {
                    return null;
                }

                $compact = preg_replace('/[^A-Z]/', '', $raw) ?? '';
                if ($compact === '') {
                    return null;
                }

                if (str_contains($compact, 'AHEAD')) {
                    return 'Ahead';
                }
                if (str_contains($compact, 'ONSCHEDULE')) {
                    return 'On Schedule';
                }
                if (str_contains($compact, 'NORISK')) {
                    return 'No Risk';
                }
                if (str_contains($compact, 'HIGHRISK')) {
                    return 'High Risk';
                }
                if (str_contains($compact, 'MODERATERISK')) {
                    return 'Moderate Risk';
                }
                if (str_contains($compact, 'LOWRISK')) {
                    return 'Low Risk';
                }

                return null;
            };

            $computeProjectAtRiskCounts = function ($projectCodesQuery, string $riskColumn, array &$targetCounts) use ($normalizeRiskLevel) {
                if (!Schema::hasTable('project_at_risks')) {
                    return;
                }

                if (!in_array($riskColumn, ['risk_level'], true)) {
                    return;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw("TRIM(COALESCE(par.{$riskColumn}, \"\")) as risk_level_value")
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select('risk_base.project_code', 'risk_base.id', 'risk_base.risk_level_value');

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalRiskRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select('risk_base.risk_level_value')
                    ->get();

                foreach ($finalRiskRows as $row) {
                    $riskLabel = $normalizeRiskLevel($row->risk_level_value ?? null);
                    if ($riskLabel !== null && array_key_exists($riskLabel, $targetCounts)) {
                        $targetCounts[$riskLabel] += 1;
                    }
                }
            };

            $computeProjectAtRiskAgingCounts = function ($projectCodesQuery, array &$targetCounts) {
                if (!Schema::hasTable('project_at_risks')) {
                    return;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select('risk_base.project_code', 'risk_base.id', 'risk_base.aging_value');

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalAgingRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select('risk_base.aging_value')
                    ->get();

                foreach ($finalAgingRows as $row) {
                    $rawAging = trim((string) ($row->aging_value ?? ''));
                    if ($rawAging === '') {
                        continue;
                    }

                    if (is_numeric($rawAging)) {
                        $agingValue = (float) $rawAging;
                    } else {
                        $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                        if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                            continue;
                        }
                        $agingValue = (float) $cleanedAging;
                    }

                    if ($agingValue >= 60) {
                        $riskLabel = 'High Risk';
                    } elseif ($agingValue > 30 && $agingValue < 60) {
                        $riskLabel = 'Low Risk';
                    } else {
                        $riskLabel = 'No Risk';
                    }

                    if (array_key_exists($riskLabel, $targetCounts)) {
                        $targetCounts[$riskLabel] += 1;
                    }
                }
            };

            $fetchProjectAtRiskAgingProjects = function ($projectCodesQuery) {
                $projectsByRisk = [
                    'High Risk' => collect(),
                    'Low Risk' => collect(),
                    'No Risk' => collect(),
                ];

                if (!Schema::hasTable('project_at_risks')) {
                    return $projectsByRisk;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(par.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(par.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.aging_value',
                        'risk_base.extraction_date',
                        'risk_base.id'
                    );

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalAgingRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.aging_value',
                        'risk_base.extraction_date'
                    )
                    ->get();

                $rowsByRisk = [
                    'High Risk' => [],
                    'Low Risk' => [],
                    'No Risk' => [],
                ];

                foreach ($finalAgingRows as $row) {
                    $rawAging = trim((string) ($row->aging_value ?? ''));
                    if ($rawAging === '') {
                        continue;
                    }

                    if (is_numeric($rawAging)) {
                        $agingValue = (float) $rawAging;
                    } else {
                        $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                        if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                            continue;
                        }
                        $agingValue = (float) $cleanedAging;
                    }

                    if ($agingValue >= 60) {
                        $riskLabel = 'High Risk';
                    } elseif ($agingValue > 30 && $agingValue < 60) {
                        $riskLabel = 'Low Risk';
                    } else {
                        $riskLabel = 'No Risk';
                    }

                    $rowsByRisk[$riskLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'latest_update_date' => $row->extraction_date ?? null,
                        'aging_days' => (fmod($agingValue, 1.0) === 0.0) ? (int) $agingValue : round($agingValue, 2),
                    ];
                }

                foreach (array_keys($rowsByRisk) as $riskLabel) {
                    $projectsByRisk[$riskLabel] = collect($rowsByRisk[$riskLabel])
                        ->sortByDesc('aging_days')
                        ->values();
                }

                return $projectsByRisk;
            };

            $fetchProjectAtRiskSlippageProjects = function ($projectCodesQuery) use ($normalizeRiskLevel, $projectAtRiskOrder) {
                $projectsByRisk = array_fill_keys($projectAtRiskOrder, collect());

                if (!Schema::hasTable('project_at_risks')) {
                    return $projectsByRisk;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(par.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(par.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(par.risk_level, "")) as risk_level_value')
                    ->selectRaw('TRIM(COALESCE(par.slippage, "")) as slippage_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.risk_level_value',
                        'risk_base.slippage_value',
                        'risk_base.extraction_date',
                        'risk_base.id'
                    );

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalRiskRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.risk_level_value',
                        'risk_base.slippage_value',
                        'risk_base.extraction_date'
                    )
                    ->get();

                $rowsByRisk = array_fill_keys($projectAtRiskOrder, []);

                foreach ($finalRiskRows as $row) {
                    $riskLabel = $normalizeRiskLevel($row->risk_level_value ?? null);
                    if ($riskLabel === null || !array_key_exists($riskLabel, $rowsByRisk)) {
                        continue;
                    }

                    $rawSlippage = trim((string) ($row->slippage_value ?? ''));
                    $normalizedSlippage = null;
                    if ($rawSlippage !== '') {
                        if (is_numeric($rawSlippage)) {
                            $normalizedSlippage = (float) $rawSlippage;
                        } else {
                            $cleanedSlippage = preg_replace('/[^0-9\.\-]/', '', $rawSlippage);
                            if ($cleanedSlippage !== null && $cleanedSlippage !== '' && is_numeric($cleanedSlippage)) {
                                $normalizedSlippage = (float) $cleanedSlippage;
                            }
                        }
                    }

                    $rowsByRisk[$riskLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'latest_update_date' => $row->extraction_date ?? null,
                        'slippage_value' => $normalizedSlippage,
                        'slippage_display' => $rawSlippage,
                        'risk_level' => $riskLabel,
                    ];
                }

                foreach (array_keys($rowsByRisk) as $riskLabel) {
                    $projectsByRisk[$riskLabel] = collect($rowsByRisk[$riskLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftSlippage = $leftRow->slippage_value;
                            $rightSlippage = $rightRow->slippage_value;

                            if ($leftSlippage !== null && $rightSlippage !== null && $leftSlippage !== $rightSlippage) {
                                return $leftSlippage <=> $rightSlippage;
                            }
                            if ($leftSlippage === null && $rightSlippage !== null) {
                                return 1;
                            }
                            if ($leftSlippage !== null && $rightSlippage === null) {
                                return -1;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode !== $rightCode) {
                                return $leftCode < $rightCode ? -1 : 1;
                            }

                            $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                            $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                            if ($leftTitle === $rightTitle) {
                                return 0;
                            }

                            return $leftTitle < $rightTitle ? -1 : 1;
                        })
                        ->values();
                }

                return $projectsByRisk;
            };

            $projectUpdateStatusParsedDateExpression = "
                COALESCE(
                    IF(
                        TRIM(COALESCE(spp.date, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                        DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.date, '')) AS DECIMAL(12,4))) DAY),
                        NULL
                    ),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %h:%i:%s %p'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d/%m/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%m-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%b-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%b %e, %Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%M %e, %Y')
                )
            ";

            $expectedCompletionParsedDateExpression = "
                COALESCE(
                    IF(
                        TRIM(COALESCE(spp.intended_completion_date_2, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                        DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.intended_completion_date_2, '')) AS DECIMAL(12,4))) DAY),
                        NULL
                    ),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%Y-%m-%d'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%Y-%m-%d %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %H:%i'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %h:%i:%s %p'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d/%m/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d-%m-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d-%b-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%b %e, %Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%M %e, %Y')
                )
            ";

            $computeProjectUpdateStatusCountsFromSubay = function ($subayQuery, array &$targetCounts) use ($projectUpdateStatusParsedDateExpression) {

                $projectUpdateRowsQuery = (clone $subayQuery)
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("LOWER(TRIM(COALESCE(spp.status, ''))) as status_raw")
                    ->selectRaw("{$projectUpdateStatusParsedDateExpression} as latest_update_date");

                $counts = DB::query()
                    ->fromSub($projectUpdateRowsQuery, 'project_updates')
                    ->where('project_updates.status_raw', '!=', 'completed')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60 THEN 1 ELSE 0 END) as high_risk_total')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60 THEN 1 ELSE 0 END) as low_risk_total')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30 THEN 1 ELSE 0 END) as no_risk_total')
                    ->first();

                $targetCounts['High Risk'] = (int) ($counts->high_risk_total ?? 0);
                $targetCounts['Low Risk'] = (int) ($counts->low_risk_total ?? 0);
                $targetCounts['No Risk'] = (int) ($counts->no_risk_total ?? 0);
            };

            $fetchProjectUpdateProjectsFromSubay = function ($subayQuery, string $riskLabel) use ($projectUpdateStatusParsedDateExpression) {
                $projectUpdateRowsQuery = (clone $subayQuery)
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("LOWER(TRIM(COALESCE(spp.status, ''))) as status_raw")
                    ->selectRaw("{$projectUpdateStatusParsedDateExpression} as latest_update_date");

                $statusRowsQuery = DB::query()
                    ->fromSub($projectUpdateRowsQuery, 'project_updates')
                    ->select(
                        'project_updates.project_code',
                        'project_updates.project_title',
                        'project_updates.province',
                        'project_updates.city_municipality',
                        'project_updates.latest_update_date'
                    )
                    ->selectRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) as aging_days')
                    ->where('project_updates.status_raw', '!=', 'completed')
                    ->whereNotNull('project_updates.latest_update_date');

                if ($riskLabel === 'High Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60');
                } elseif ($riskLabel === 'Low Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60');
                } elseif ($riskLabel === 'No Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30');
                } else {
                    return collect();
                }

                return $statusRowsQuery
                    ->orderByDesc('aging_days')
                    ->orderBy('project_updates.project_code')
                    ->orderBy('project_updates.project_title')
                    ->get();
            };

            if (Schema::hasTable('subay_project_profiles')) {
                $subayBaseQuery = DB::table('subay_project_profiles as spp')
                    ->whereNotNull('spp.project_code')
                    ->whereRaw('TRIM(spp.project_code) <> ""');

                $excludeSglgifFromSubay($subayBaseQuery);
                $applyRoleScopeToSubay($subayBaseQuery);

                $configuredProvinceLabels = collect(\App\Support\ProjectLocationFilterHelper::buildConfiguredProvinceLabels())
                    ->map($normalizeFilterValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->values();

                if ($configuredProvinceLabels->isNotEmpty()) {
                    if (($isLguScopedUser || ($isDilgUser && !$isRegionalOfficeUser)) && $province !== '') {
                        $normalizedScopedProvince = $normalizeComparableLocationValue($province);
                        $configuredProvinceLabels = $configuredProvinceLabels
                            ->filter(function ($provinceLabel) use ($normalizedScopedProvince, $normalizeComparableLocationValue) {
                                return $normalizeComparableLocationValue($provinceLabel) === $normalizedScopedProvince;
                            })
                            ->values();
                    }

                    $filterOptions['provinces'] = $configuredProvinceLabels;

                    $provinceCityMap = \App\Support\ProjectLocationFilterHelper::buildConfiguredProvinceCityMapFromHierarchy(
                        $configuredProvinceLabels->all()
                    );

                    if ($isLguScopedUser && $office !== '') {
                        $normalizedOfficeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $normalizeComparableLocationValue($office);
                        foreach ($provinceCityMap as $provinceLabel => $cityLabels) {
                            $provinceCityMap[$provinceLabel] = array_values(array_filter(
                                $cityLabels,
                                function ($cityLabel) use ($normalizedOfficeNeedle, $normalizeComparableLocationValue, $officeLower) {
                                    $normalizedCityLabel = strtolower(trim((string) $cityLabel));

                                    return $normalizedCityLabel === $officeLower
                                        || $normalizeComparableLocationValue($cityLabel) === $normalizedOfficeNeedle;
                                }
                            ));
                        }
                    }

                    $filterOptions['province_city_map'] = $provinceCityMap;
                    $filterOptions['cities'] = collect($filters['province'])
                        ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                            return $provinceCityMap[$provinceLabel] ?? [];
                        })
                        ->unique(function ($cityLabel) {
                            return strtolower(trim((string) $cityLabel));
                        })
                        ->values();

                    $cityBarangayMap = \App\Support\ProjectLocationFilterHelper::buildConfiguredCityBarangayMapFromHierarchy(
                        $configuredProvinceLabels->all()
                    );

                    if ($isLguScopedUser && $office !== '') {
                        $normalizedOfficeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $normalizeComparableLocationValue($office);
                        $cityBarangayMap = array_filter(
                            $cityBarangayMap,
                            function ($cityLabel) use ($normalizedOfficeNeedle, $normalizeComparableLocationValue, $officeLower) {
                                $normalizedCityLabel = strtolower(trim((string) $cityLabel));

                                return $normalizedCityLabel === $officeLower
                                    || $normalizeComparableLocationValue($cityLabel) === $normalizedOfficeNeedle;
                            },
                            ARRAY_FILTER_USE_KEY
                        );
                    }

                    if (!empty($filters['province'])) {
                        $allowedCityKeys = collect($filters['province'])
                            ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                                return $provinceCityMap[$provinceLabel] ?? [];
                            })
                            ->map(function ($cityLabel) {
                                return strtolower(trim((string) $cityLabel));
                            })
                            ->unique()
                            ->values()
                            ->all();

                        if (!empty($allowedCityKeys)) {
                            $cityBarangayMap = array_filter(
                                $cityBarangayMap,
                                function ($cityLabel) use ($allowedCityKeys) {
                                    return in_array(strtolower(trim((string) $cityLabel)), $allowedCityKeys, true);
                                },
                                ARRAY_FILTER_USE_KEY
                            );
                        }
                    }

                    $filterOptions['city_barangay_map'] = $cityBarangayMap;
                    $filterOptions['barangays'] = collect($filters['city_municipality'])
                        ->flatMap(function ($cityLabel) use ($cityBarangayMap) {
                            return $cityBarangayMap[$cityLabel] ?? [];
                        })
                        ->unique(function ($barangayLabel) {
                            return strtolower(trim((string) $barangayLabel));
                        })
                        ->values();
                } else {
                    $filterOptions['provinces'] = (clone $subayBaseQuery)
                        ->select('spp.province')
                        ->whereNotNull('spp.province')
                        ->whereRaw('TRIM(spp.province) <> ""')
                        ->distinct()
                        ->orderBy('spp.province')
                        ->pluck('spp.province');

                    $provinceCityMap = [];
                    $provinceOptionLabels = collect($filterOptions['provinces'] ?? [])
                        ->map($normalizeFilterValue)
                        ->filter(function ($value) {
                            return $value !== '';
                        })
                        ->values();

                    if (
                        Schema::hasTable('location_provinces')
                        && Schema::hasTable('location_city_municipalities')
                    ) {
                        $configuredProvinceCityRows = DB::table('location_city_municipalities as lcm')
                            ->join('location_provinces as lp', 'lp.id', '=', 'lcm.province_id')
                            ->selectRaw('TRIM(COALESCE(lp.province_name, "")) as province')
                            ->selectRaw('TRIM(COALESCE(lcm.citymun_name, "")) as city_municipality')
                            ->whereNotNull('lp.province_name')
                            ->whereRaw('TRIM(lp.province_name) <> ""')
                            ->whereNotNull('lcm.citymun_name')
                            ->whereRaw('TRIM(lcm.citymun_name) <> ""')
                            ->orderBy('lp.province_name')
                            ->orderBy('lcm.citymun_name')
                            ->get();

                        $configuredProvinceCityIndex = [];
                        foreach ($configuredProvinceCityRows as $row) {
                            $provinceLabel = $normalizeFilterValue($row->province ?? '');
                            $cityLabel = $normalizeFilterValue($row->city_municipality ?? '');

                            if ($provinceLabel === '' || $cityLabel === '') {
                                continue;
                            }

                            $configuredProvinceKey = strtolower($provinceLabel);
                            $configuredProvinceCityIndex[$configuredProvinceKey] ??= [];
                            if (!in_array($cityLabel, $configuredProvinceCityIndex[$configuredProvinceKey], true)) {
                                $configuredProvinceCityIndex[$configuredProvinceKey][] = $cityLabel;
                            }
                        }

                        foreach ($provinceOptionLabels as $provinceOptionLabel) {
                            $provinceCityMap[$provinceOptionLabel] = $configuredProvinceCityIndex[strtolower($provinceOptionLabel)] ?? [];
                        }
                    }

                    if (empty(array_filter($provinceCityMap))) {
                        $provinceCityRows = (clone $subayBaseQuery)
                            ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                            ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                            ->whereNotNull('spp.province')
                            ->whereRaw('TRIM(spp.province) <> ""')
                            ->whereNotNull('spp.city_municipality')
                            ->whereRaw('TRIM(spp.city_municipality) <> ""')
                            ->distinct()
                            ->orderBy('spp.province')
                            ->orderBy('spp.city_municipality')
                            ->get();

                        foreach ($provinceCityRows as $row) {
                            $provinceLabel = trim((string) ($row->province ?? ''));
                            $cityLabel = trim((string) ($row->city_municipality ?? ''));

                            if ($provinceLabel === '' || $cityLabel === '') {
                                continue;
                            }

                            $provinceCityMap[$provinceLabel] ??= [];
                            if (!in_array($cityLabel, $provinceCityMap[$provinceLabel], true)) {
                                $provinceCityMap[$provinceLabel][] = $cityLabel;
                            }
                        }
                    }

                    $filterOptions['province_city_map'] = $provinceCityMap;
                    $filterOptions['cities'] = collect($filters['province'])
                        ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                            return $provinceCityMap[$provinceLabel] ?? [];
                        })
                        ->unique(function ($cityLabel) {
                            return strtolower(trim((string) $cityLabel));
                        })
                        ->values();

                    $cityBarangayRows = (clone $subayBaseQuery)
                        ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                        ->selectRaw('TRIM(COALESCE(spp.barangay, "")) as barangay')
                        ->whereNotNull('spp.city_municipality')
                        ->whereRaw('TRIM(spp.city_municipality) <> ""')
                        ->whereNotNull('spp.barangay')
                        ->whereRaw('TRIM(spp.barangay) <> ""')
                        ->orderBy('spp.city_municipality')
                        ->get();

                    $cityBarangayMap = [];
                    $cityBarangaySeenMap = [];
                    foreach ($cityBarangayRows as $row) {
                        $cityLabel = trim((string) ($row->city_municipality ?? ''));
                        if ($cityLabel === '') {
                            continue;
                        }

                        $barangayItems = preg_split('/\r\n|\r|\n/u', (string) ($row->barangay ?? '')) ?: [];
                        foreach ($barangayItems as $barangayValue) {
                            $barangayLabel = $normalizeFilterValue($barangayValue);
                            if ($barangayLabel === '') {
                                continue;
                            }

                            $cityBarangayMap[$cityLabel] ??= [];
                            $cityBarangaySeenMap[$cityLabel] ??= [];
                            $dedupeKey = strtolower($barangayLabel);
                            if (!array_key_exists($dedupeKey, $cityBarangaySeenMap[$cityLabel])) {
                                $cityBarangayMap[$cityLabel][] = $barangayLabel;
                                $cityBarangaySeenMap[$cityLabel][$dedupeKey] = true;
                            }
                        }
                    }

                    $filterOptions['city_barangay_map'] = $cityBarangayMap;
                    $filterOptions['barangays'] = collect($filters['city_municipality'])
                        ->flatMap(function ($cityLabel) use ($cityBarangayMap) {
                            return $cityBarangayMap[$cityLabel] ?? [];
                        })
                        ->unique(function ($barangayLabel) {
                            return strtolower(trim((string) $barangayLabel));
                        })
                        ->values();
                }

                $programOptionsQuery = clone $subayBaseQuery;
                $applyExactMultiFilterToSubay($programOptionsQuery, 'spp.province', $filters['province']);
                $applyComparableLocationMultiFilterToSubay($programOptionsQuery, 'spp.city_municipality', $filters['city_municipality']);
                $applyExactMultiFilterToSubay($programOptionsQuery, 'spp.barangay', $filters['barangay']);
                $filterOptions['programs'] = $programOptionsQuery
                    ->select('spp.program')
                    ->whereNotNull('spp.program')
                    ->whereRaw('TRIM(spp.program) <> ""')
                    ->distinct()
                    ->orderBy('spp.program')
                    ->pluck('spp.program');

                $filterOptions['fund_sources'] = (clone $subayBaseQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->whereRaw("{$fundSourceFromProjectCodeExpr} <> 'SGLGIF'")
                    ->distinct()
                    ->orderBy('fund_source')
                    ->pluck('fund_source');

                $filterOptions['funding_years'] = (clone $subayBaseQuery)
                    ->select('spp.funding_year')
                    ->whereNotNull('spp.funding_year')
                    ->whereRaw('TRIM(spp.funding_year) <> ""')
                    ->distinct()
                    ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) DESC')
                    ->pluck('spp.funding_year');

                $filterOptions['project_types'] = (clone $subayBaseQuery)
                    ->select('spp.type_of_project')
                    ->whereNotNull('spp.type_of_project')
                    ->whereRaw('TRIM(spp.type_of_project) <> ""')
                    ->distinct()
                    ->orderBy('spp.type_of_project')
                    ->pluck('spp.type_of_project');

                $filterOptions['project_statuses'] = (clone $subayBaseQuery)
                    ->select('spp.status')
                    ->whereNotNull('spp.status')
                    ->whereRaw('TRIM(spp.status) <> ""')
                    ->distinct()
                    ->orderBy('spp.status')
                    ->pluck('spp.status');

                $subayDashboardQuery = clone $subayBaseQuery;
                $applyDashboardFiltersToSubay($subayDashboardQuery);

                $totalProjects = (int) (clone $subayDashboardQuery)->count();

                $subayProvinceProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw('TRIM(COALESCE(spp.province, ""))'))
                    ->get();

                foreach ($subayProvinceProjectRows as $row) {
                    $normalizedProvince = $normalizeCarProvince($row->province ?? null);
                    if ($normalizedProvince === null || !array_key_exists($normalizedProvince, $carProvinceProjectCounts)) {
                        continue;
                    }

                    $carProvinceProjectCounts[$normalizedProvince] += (int) ($row->total ?? 0);
                }

                $balanceByProjectQuery = (clone $subayDashboardQuery)
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('MAX(TRIM(COALESCE(spp.project_title, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(spp.status, ""))) as status')
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.national_subsidy_original_allocation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as original_allocation")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.lgu_counterpart_original_allocation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as lgu_counterpart")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.obligation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as obligation")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.disbursement, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as disbursement")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.national_subsidy_reverted_amount, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as reverted_allocation")
                    ->groupBy(DB::raw('UPPER(TRIM(spp.project_code))'));

                $balanceFormulaExpression = 'COALESCE(balance_projects.original_allocation, 0) - (COALESCE(balance_projects.disbursement, 0) + COALESCE(balance_projects.reverted_allocation, 0))';
                $revertedAllocationExpression = 'COALESCE(balance_projects.reverted_allocation, 0)';

                $financialStatusProjectsBaseQuery = DB::query()
                    ->fromSub($balanceByProjectQuery, 'balance_projects')
                    ->select(
                        'balance_projects.project_code',
                        'balance_projects.project_title',
                        'balance_projects.status',
                        'balance_projects.original_allocation',
                        'balance_projects.lgu_counterpart',
                        'balance_projects.obligation',
                        'balance_projects.disbursement'
                    )
                    ->selectRaw("{$revertedAllocationExpression} as reverted_allocation")
                    ->selectRaw("{$balanceFormulaExpression} as balance");

                $financialStatusProjects = (clone $financialStatusProjectsBaseQuery)
                    ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(balance_projects.status, ''))) = 'completed' THEN 1 ELSE 0 END")
                    ->orderBy('balance_projects.project_code')
                    ->get();

                $projectsWithBalance = (clone $financialStatusProjectsBaseQuery)
                    ->whereRaw("{$balanceFormulaExpression} > 0")
                    ->orderByRaw("{$balanceFormulaExpression} DESC")
                    ->orderBy('balance_projects.project_code')
                    ->get();

                $financialTotals = DB::query()
                    ->fromSub($balanceByProjectQuery, 'balance_projects')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.original_allocation, 0)), 0) as total_lgsf_allocation')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.obligation, 0)), 0) as total_obligation')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.disbursement, 0)), 0) as total_disbursement')
                    ->selectRaw("COALESCE(SUM({$balanceFormulaExpression}), 0) as total_balance")
                    ->first();

                $totalLgsfAllocationAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0);
                $totalObligationAmount = (float) ($financialTotals->total_obligation ?? 0);
                $totalDisbursementAmount = (float) ($financialTotals->total_disbursement ?? 0);
                $totalBalanceAmount = (float) ($financialTotals->total_balance ?? 0);

                $utilizationPercentage = $totalObligationAmount > 0
                    ? (($totalDisbursementAmount / $totalObligationAmount) * 100)
                    : 0.0;

                $projectsExpectedCompletionBaseQuery = (clone $subayDashboardQuery)
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("{$expectedCompletionParsedDateExpression} as expected_completion_date");

                $projectsExpectedCompletionThisMonth = DB::query()
                    ->fromSub($projectsExpectedCompletionBaseQuery, 'due_projects')
                    ->whereNotNull('due_projects.expected_completion_date')
                    ->whereYear('due_projects.expected_completion_date', $currentYear)
                    ->whereMonth('due_projects.expected_completion_date', $currentMonth)
                    ->orderBy('due_projects.expected_completion_date')
                    ->orderBy('due_projects.project_code')
                    ->orderBy('due_projects.project_title')
                    ->get();

                $fundSourceCountsMap = (clone $subayDashboardQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw($fundSourceFromProjectCodeExpr))
                    ->get()
                    ->reduce(function ($carry, $row) {
                        $label = strtoupper(trim((string) $row->fund_source));
                        $label = $label !== '' && $label !== 'UNSPECIFIED' ? $label : 'Unspecified';
                        $carry[$label] = (int) $row->total;
                        return $carry;
                    }, []);

                $fundSourceProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(spp.status, "")) as status')
                    ->orderByRaw("{$fundSourceFromProjectCodeExpr}")
                    ->orderByRaw('UPPER(TRIM(spp.project_code))')
                    ->orderByRaw('TRIM(COALESCE(spp.project_title, ""))')
                    ->get();

                foreach ($fundSourceProjectRows as $row) {
                    $label = strtoupper(trim((string) ($row->fund_source ?? '')));
                    $label = $label !== '' && $label !== 'UNSPECIFIED' ? $label : 'Unspecified';

                    if (!array_key_exists($label, $fundSourceProjectsMap)) {
                        $fundSourceProjectsMap[$label] = [];
                    }

                    $fundSourceProjectsMap[$label][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'status' => $row->status ?? null,
                    ];
                }

                foreach (array_keys($fundSourceProjectsMap) as $sourceLabel) {
                    $fundSourceProjectsMap[$sourceLabel] = collect($fundSourceProjectsMap[$sourceLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftIsCompleted = strtolower(trim((string) ($leftRow->status ?? ''))) === 'completed' ? 1 : 0;
                            $rightIsCompleted = strtolower(trim((string) ($rightRow->status ?? ''))) === 'completed' ? 1 : 0;

                            if ($leftIsCompleted !== $rightIsCompleted) {
                                return $leftIsCompleted <=> $rightIsCompleted;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode !== $rightCode) {
                                return $leftCode < $rightCode ? -1 : 1;
                            }

                            $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                            $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                            if ($leftTitle === $rightTitle) {
                                return 0;
                            }

                            return $leftTitle < $rightTitle ? -1 : 1;
                        })
                        ->values();
                }

                $subayStatusRows = (clone $subayDashboardQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(spp.status, ""))) as status_raw')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw('UPPER(TRIM(COALESCE(spp.status, "")))'))
                    ->get();

                foreach ($subayStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel !== null) {
                        $statusSubaybayanCounts[$statusLabel] += (int) $row->total;
                    }
                }

                $subayStatusLocationRows = (clone $subayDashboardQuery)
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw('UPPER(TRIM(COALESCE(spp.status, ""))) as status_raw')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(
                        DB::raw('TRIM(COALESCE(spp.province, ""))'),
                        DB::raw('TRIM(COALESCE(spp.city_municipality, ""))'),
                        DB::raw('UPPER(TRIM(COALESCE(spp.status, "")))')
                    )
                    ->get();

                $statusByProvince = [];
                foreach ($subayStatusLocationRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceLabel = trim((string) ($row->province ?? ''));
                    $provinceLabel = $provinceLabel !== '' ? $provinceLabel : 'Unspecified Province';
                    $cityLabel = trim((string) ($row->city_municipality ?? ''));
                    $cityLabel = $cityLabel !== '' ? $cityLabel : 'Unspecified City/Municipality';
                    $countValue = (int) ($row->total ?? 0);

                    if (!array_key_exists($provinceLabel, $statusByProvince)) {
                        $statusByProvince[$provinceLabel] = [
                            'province_totals' => array_fill_keys($statusDisplayOrder, 0),
                            'cities' => [],
                        ];
                    }

                    if (!array_key_exists($cityLabel, $statusByProvince[$provinceLabel]['cities'])) {
                        $statusByProvince[$provinceLabel]['cities'][$cityLabel] = array_fill_keys($statusDisplayOrder, 0);
                    }

                    $statusByProvince[$provinceLabel]['province_totals'][$statusLabel] += $countValue;
                    $statusByProvince[$provinceLabel]['cities'][$cityLabel][$statusLabel] += $countValue;
                }

                if (!empty($statusByProvince)) {
                    $provinceLabels = array_keys($statusByProvince);
                    natcasesort($provinceLabels);

                    foreach ($provinceLabels as $provinceLabel) {
                        $provinceData = $statusByProvince[$provinceLabel];

                        $statusSubaybayanLocationReport[] = [
                            'row_type' => 'province',
                            'province' => $provinceLabel,
                            'city_municipality' => '',
                            'counts' => $provinceData['province_totals'],
                        ];

                        $cityLabels = array_keys($provinceData['cities']);
                        natcasesort($cityLabels);

                        foreach ($cityLabels as $cityLabel) {
                            $statusSubaybayanLocationReport[] = [
                                'row_type' => 'city',
                                'province' => $provinceLabel,
                                'city_municipality' => $cityLabel,
                                'counts' => $provinceData['cities'][$cityLabel],
                            ];
                        }
                    }
                }

                $subayProvinceFundingYearProgramStatusRows = (clone $subayDashboardQuery)
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.funding_year, "")) as funding_year')
                    ->selectRaw('TRIM(COALESCE(spp.program, "")) as program')
                    ->selectRaw('UPPER(TRIM(COALESCE(spp.status, ""))) as status_raw')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(
                        DB::raw('TRIM(COALESCE(spp.province, ""))'),
                        DB::raw('TRIM(COALESCE(spp.funding_year, ""))'),
                        DB::raw('TRIM(COALESCE(spp.program, ""))'),
                        DB::raw('UPPER(TRIM(COALESCE(spp.status, "")))')
                    )
                    ->get();

                $provinceFundingYearProgramStatusRows = [];
                foreach ($subayProvinceFundingYearProgramStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceLabel = trim((string) ($row->province ?? ''));
                    $provinceLabel = $provinceLabel !== '' ? $provinceLabel : 'Unspecified Province';
                    $fundingYearLabel = trim((string) ($row->funding_year ?? ''));
                    $fundingYearLabel = $fundingYearLabel !== '' ? $fundingYearLabel : 'Unspecified Funding Year';
                    $programLabel = trim((string) ($row->program ?? ''));
                    $programLabel = $programLabel !== '' ? $programLabel : 'Unspecified Program';
                    $groupKey = implode('||', [$provinceLabel, $fundingYearLabel, $programLabel, $statusLabel]);

                    if (!array_key_exists($groupKey, $provinceFundingYearProgramStatusRows)) {
                        $provinceFundingYearProgramStatusRows[$groupKey] = [
                            'province' => $provinceLabel,
                            'funding_year' => $fundingYearLabel,
                            'program' => $programLabel,
                            'project_status' => $statusLabel,
                            'total' => 0,
                        ];
                    }

                    $provinceFundingYearProgramStatusRows[$groupKey]['total'] += (int) ($row->total ?? 0);
                }

                $provinceFundingYearProgramStatusReport = array_values($provinceFundingYearProgramStatusRows);
                usort($provinceFundingYearProgramStatusReport, function (array $leftRow, array $rightRow) use ($statusDisplayOrderPositions) {
                    $provinceCompare = strnatcasecmp((string) ($leftRow['province'] ?? ''), (string) ($rightRow['province'] ?? ''));
                    if ($provinceCompare !== 0) {
                        return $provinceCompare;
                    }

                    $leftFundingYear = trim((string) ($leftRow['funding_year'] ?? ''));
                    $rightFundingYear = trim((string) ($rightRow['funding_year'] ?? ''));
                    $leftFundingYearIsNumeric = is_numeric($leftFundingYear);
                    $rightFundingYearIsNumeric = is_numeric($rightFundingYear);
                    if ($leftFundingYearIsNumeric && $rightFundingYearIsNumeric) {
                        $fundingYearCompare = (int) $rightFundingYear <=> (int) $leftFundingYear;
                    } else {
                        $fundingYearCompare = strnatcasecmp($leftFundingYear, $rightFundingYear);
                    }
                    if ($fundingYearCompare !== 0) {
                        return $fundingYearCompare;
                    }

                    $programCompare = strnatcasecmp((string) ($leftRow['program'] ?? ''), (string) ($rightRow['program'] ?? ''));
                    if ($programCompare !== 0) {
                        return $programCompare;
                    }

                    $leftStatus = (string) ($leftRow['project_status'] ?? '');
                    $rightStatus = (string) ($rightRow['project_status'] ?? '');
                    $leftStatusPosition = $statusDisplayOrderPositions[$leftStatus] ?? PHP_INT_MAX;
                    $rightStatusPosition = $statusDisplayOrderPositions[$rightStatus] ?? PHP_INT_MAX;

                    if ($leftStatusPosition !== $rightStatusPosition) {
                        return $leftStatusPosition <=> $rightStatusPosition;
                    }

                    return strnatcasecmp($leftStatus, $rightStatus);
                });

                $subayStatusProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(spp.status, ""))) as status_raw')
                    ->selectRaw('UPPER(TRIM(spp.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(spp.funding_year, "")) as funding_year')
                    ->selectRaw('TRIM(COALESCE(spp.program, "")) as program')
                    ->orderByRaw('UPPER(TRIM(COALESCE(spp.status, "")))')
                    ->orderByRaw('UPPER(TRIM(spp.project_code))')
                    ->orderByRaw('TRIM(COALESCE(spp.project_title, ""))')
                    ->get();

                foreach ($subayStatusProjectRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    if (!array_key_exists($statusLabel, $statusSubaybayanProjectsMap)) {
                        $statusSubaybayanProjectsMap[$statusLabel] = [];
                    }

                    $statusSubaybayanProjectsMap[$statusLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'funding_year' => $row->funding_year ?? null,
                        'program' => $row->program ?? null,
                        'status' => $statusLabel,
                    ];

                    $provinceFundingYearProgramStatusSourceRows[] = [
                        'province' => trim((string) ($row->province ?? '')) ?: 'Unspecified Province',
                        'funding_year' => trim((string) ($row->funding_year ?? '')) ?: 'Unspecified Funding Year',
                        'program' => trim((string) ($row->program ?? '')) ?: 'Unspecified Program',
                        'project_status' => $statusLabel,
                    ];
                }

                $filteredProjectCodesQuery = (clone $subayDashboardQuery)
                    ->selectRaw('DISTINCT UPPER(TRIM(spp.project_code)) as project_code');
                $computeProjectAtRiskCounts(clone $filteredProjectCodesQuery, 'risk_level', $projectAtRiskCounts);
                $computeProjectAtRiskAgingCounts(clone $filteredProjectCodesQuery, $projectAtRiskAgingCounts);
                $projectAtRiskSlippageProjects = $fetchProjectAtRiskSlippageProjects(clone $filteredProjectCodesQuery);
                $projectAtRiskAgingProjects = $fetchProjectAtRiskAgingProjects(clone $filteredProjectCodesQuery);
                $computeProjectUpdateStatusCountsFromSubay(clone $subayDashboardQuery, $projectUpdateStatusCounts);
                foreach ($projectUpdateStatusOrder as $riskLabel) {
                    $projectUpdateRiskProjects[$riskLabel] = $fetchProjectUpdateProjectsFromSubay(clone $subayDashboardQuery, $riskLabel);
                }
            } else {
                $fallbackQuery = LocallyFundedProject::query();
                $excludeSglgifFromFallback($fallbackQuery);
                $fallbackCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $fallbackOfficeComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(office, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $applyOfficeScopeToFallback = function ($query) use (
                    $officeLower,
                    $officeComparableLower,
                    $fallbackCityComparableExpression,
                    $fallbackOfficeComparableExpression
                ) {
                    if ($officeLower === '') {
                        return;
                    }

                    $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

                    $query->where(function ($subQuery) use (
                        $officeLower,
                        $officeNeedle,
                        $fallbackCityComparableExpression,
                        $fallbackOfficeComparableExpression
                    ) {
                        $subQuery->whereRaw('LOWER(TRIM(COALESCE(office, ""))) = ?', [$officeLower])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(city_municipality, ""))) = ?', [$officeLower])
                            ->orWhereRaw("{$fallbackOfficeComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$fallbackCityComparableExpression} = ?", [$officeNeedle]);
                    });
                };

                if ($isLguScopedUser) {
                    if ($office !== '') {
                        if ($province !== '') {
                            $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                            $applyOfficeScopeToFallback($fallbackQuery);
                        } else {
                            $applyOfficeScopeToFallback($fallbackQuery);
                        }
                    } elseif ($province !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                    }
                } elseif ($isDilgUser) {
                    if ($isRegionalOfficeUser) {
                        // Regional Office users can see all projects
                    } elseif ($province !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                    } elseif ($region !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(region, ""))) = ?', [$regionLower]);
                    }
                }

                $applyExactMultiFilterToSubay($fallbackQuery, 'province', $filters['province']);
                $applyComparableLocationMultiFilterToSubay($fallbackQuery, 'city_municipality', $filters['city_municipality']);
                $applyDelimitedMultiFilterToSubay($fallbackQuery, 'barangay', $filters['barangay']);
                if (!empty($filters['programs'])) {
                    $normalizedPrograms = collect($filters['programs'])
                        ->map(function ($value) {
                            return strtolower(trim((string) $value));
                        })
                        ->filter(function ($value) {
                            return $value !== '';
                        })
                        ->unique()
                        ->values()
                        ->all();

                    if (!empty($normalizedPrograms)) {
                        $placeholders = implode(', ', array_fill(0, count($normalizedPrograms), '?'));
                        $fallbackQuery->whereRaw("LOWER(TRIM(COALESCE(fund_source, \"\"))) IN ({$placeholders})", $normalizedPrograms);
                    }
                }
                $applyExactMultiFilterToSubay($fallbackQuery, 'fund_source', $filters['fund_source']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'funding_year', $filters['funding_year']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'project_type', $filters['project_type']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'status', $filters['project_status']);

                if ($filters['search'] !== '') {
                    $searchNeedle = '%' . strtolower($filters['search']) . '%';
                    $fallbackQuery->where(function ($searchQuery) use ($searchNeedle) {
                        $searchQuery
                            ->orWhereRaw('LOWER(TRIM(COALESCE(subaybayan_project_code, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(project_name, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(province, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(city_municipality, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(barangay, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(fund_source, ""))) LIKE ?', [$searchNeedle]);
                    });
                }

                $totalProjects = (int) (clone $fallbackQuery)->count();

                $fallbackProvinceProjectRows = (clone $fallbackQuery)
                    ->selectRaw('TRIM(COALESCE(province, "")) as province')
                    ->selectRaw('COUNT(DISTINCT NULLIF(UPPER(TRIM(COALESCE(subaybayan_project_code, ""))), "")) as code_total')
                    ->selectRaw('COUNT(*) as row_total')
                    ->groupBy(DB::raw('TRIM(COALESCE(province, ""))'))
                    ->get();

                foreach ($fallbackProvinceProjectRows as $row) {
                    $normalizedProvince = $normalizeCarProvince($row->province ?? null);
                    if ($normalizedProvince === null || !array_key_exists($normalizedProvince, $carProvinceProjectCounts)) {
                        continue;
                    }

                    $countValue = (int) ($row->code_total ?? 0);
                    if ($countValue < 1) {
                        $countValue = (int) ($row->row_total ?? 0);
                    }

                    $carProvinceProjectCounts[$normalizedProvince] += $countValue;
                }

                $financialTotals = (clone $fallbackQuery)
                    ->selectRaw('COALESCE(SUM(COALESCE(obligation, 0)), 0) as total_obligation')
                    ->selectRaw('COALESCE(SUM(COALESCE(disbursed_amount, 0)), 0) as total_disbursement')
                    ->selectRaw('COALESCE(SUM(COALESCE(lgsf_allocation, 0)), 0) as total_lgsf_allocation')
                    ->selectRaw('COALESCE(SUM(COALESCE(reverted_amount, 0)), 0) as total_reverted_amount')
                    ->first();

                $totalObligationAmount = (float) ($financialTotals->total_obligation ?? 0);
                $totalDisbursementAmount = (float) ($financialTotals->total_disbursement ?? 0);
                $totalLgsfAllocationAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0);
                $totalBalanceAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0)
                    - (
                        (float) ($financialTotals->total_disbursement ?? 0)
                        + (float) ($financialTotals->total_reverted_amount ?? 0)
                    );
                $utilizationPercentage = $totalObligationAmount > 0
                    ? (($totalDisbursementAmount / $totalObligationAmount) * 100)
                    : 0.0;

                $projectsExpectedCompletionThisMonth = (clone $fallbackQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('MAX(TRIM(COALESCE(project_name, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(province, ""))) as province')
                    ->selectRaw('MAX(TRIM(COALESCE(city_municipality, ""))) as city_municipality')
                    ->selectRaw('MAX(target_date_completion) as expected_completion_date')
                    ->groupBy(DB::raw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))'))
                    ->havingRaw('MAX(target_date_completion) BETWEEN ? AND ?', [$currentMonthStart, $currentMonthEnd])
                    ->orderByRaw('MAX(target_date_completion) ASC')
                    ->orderByRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    ->get();

                $financialStatusProjects = (clone $fallbackQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('TRIM(COALESCE(project_name, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(status, "")) as status')
                    ->selectRaw('COALESCE(lgsf_allocation, 0) as original_allocation')
                    ->selectRaw('COALESCE(lgu_counterpart, 0) as lgu_counterpart')
                    ->selectRaw('COALESCE(obligation, 0) as obligation')
                    ->selectRaw('COALESCE(disbursed_amount, 0) as disbursement')
                    ->selectRaw('COALESCE(reverted_amount, 0) as reverted_allocation')
                    ->selectRaw('COALESCE(lgsf_allocation, 0) - (COALESCE(disbursed_amount, 0) + COALESCE(reverted_amount, 0)) as balance')
                    ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'completed' THEN 1 ELSE 0 END")
                    ->orderByRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    ->get();

                $fundSourceCountsMap = (clone $fallbackQuery)
                    ->select('fund_source', DB::raw('COUNT(*) as total'))
                    ->groupBy('fund_source')
                    ->get()
                    ->reduce(function ($carry, $row) {
                        $label = strtoupper(trim((string) $row->fund_source));
                        $label = $label !== '' ? $label : 'Unspecified';
                        $carry[$label] = ($carry[$label] ?? 0) + (int) $row->total;
                        return $carry;
                    }, collect())
                    ->toArray();

                $fundSourceProjectRows = (clone $fallbackQuery)
                    ->selectRaw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified") as fund_source')
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('MAX(TRIM(COALESCE(project_name, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(province, ""))) as province')
                    ->selectRaw('MAX(TRIM(COALESCE(city_municipality, ""))) as city_municipality')
                    ->groupBy(
                        DB::raw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified")'),
                        DB::raw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    )
                    ->orderBy('fund_source')
                    ->orderBy('project_code')
                    ->get();

                foreach ($fundSourceProjectRows as $row) {
                    $label = strtoupper(trim((string) ($row->fund_source ?? '')));
                    $label = $label !== '' ? $label : 'Unspecified';

                    if (!array_key_exists($label, $fundSourceProjectsMap)) {
                        $fundSourceProjectsMap[$label] = [];
                    }

                    $fundSourceProjectsMap[$label][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'status' => null,
                    ];
                }

                foreach (array_keys($fundSourceProjectsMap) as $sourceLabel) {
                    $fundSourceProjectsMap[$sourceLabel] = collect($fundSourceProjectsMap[$sourceLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftIsCompleted = strtolower(trim((string) ($leftRow->status ?? ''))) === 'completed' ? 1 : 0;
                            $rightIsCompleted = strtolower(trim((string) ($rightRow->status ?? ''))) === 'completed' ? 1 : 0;

                            if ($leftIsCompleted !== $rightIsCompleted) {
                                return $leftIsCompleted <=> $rightIsCompleted;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode === $rightCode) {
                                return 0;
                            }

                            return $leftCode < $rightCode ? -1 : 1;
                        })
                        ->values();
                }

                $fallbackProjectCodesQuery = (clone $fallbackQuery)
                    ->whereNotNull('subaybayan_project_code')
                    ->whereRaw('TRIM(subaybayan_project_code) <> ""')
                    ->selectRaw('DISTINCT UPPER(TRIM(subaybayan_project_code)) as project_code');
                $computeProjectAtRiskCounts(clone $fallbackProjectCodesQuery, 'risk_level', $projectAtRiskCounts);
                $computeProjectAtRiskAgingCounts(clone $fallbackProjectCodesQuery, $projectAtRiskAgingCounts);
                $projectAtRiskSlippageProjects = $fetchProjectAtRiskSlippageProjects(clone $fallbackProjectCodesQuery);
                $projectAtRiskAgingProjects = $fetchProjectAtRiskAgingProjects(clone $fallbackProjectCodesQuery);

                $fallbackProvinceFundingYearProgramStatusRows = (clone $fallbackQuery)
                    ->selectRaw('TRIM(COALESCE(province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(funding_year, "")) as funding_year')
                    ->selectRaw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified Program") as program')
                    ->selectRaw('TRIM(COALESCE(status, "")) as status_raw')
                    ->get();

                foreach ($fallbackProvinceFundingYearProgramStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceFundingYearProgramStatusSourceRows[] = [
                        'province' => trim((string) ($row->province ?? '')) ?: 'Unspecified Province',
                        'funding_year' => trim((string) ($row->funding_year ?? '')) ?: 'Unspecified Funding Year',
                        'program' => trim((string) ($row->program ?? '')) ?: 'Unspecified Program',
                        'project_status' => $statusLabel,
                    ];
                }
            }

            $fundSourceCounts = collect();
            foreach ($fundSourceOptions as $source) {
                $fundSourceCounts[$source] = (int) ($fundSourceCountsMap[$source] ?? 0);
            }

            foreach ($fundSourceCountsMap as $source => $count) {
                if (!in_array($source, $fundSourceOptions, true)) {
                    $fundSourceCounts[$source] = (int) $count;
                }
            }

            foreach (array_keys($statusSubaybayanProjectsMap) as $statusLabel) {
                $statusSubaybayanProjectsMap[$statusLabel] = collect($statusSubaybayanProjectsMap[$statusLabel])
                    ->sort(function ($leftRow, $rightRow) {
                        $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                        $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                        if ($leftCode !== $rightCode) {
                            return $leftCode < $rightCode ? -1 : 1;
                        }

                        $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                        $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                        if ($leftTitle === $rightTitle) {
                            return 0;
                        }

                        return $leftTitle < $rightTitle ? -1 : 1;
                    })
                    ->values();
            }

            $carProvinceProjectMaxCount = !empty($carProvinceProjectCounts)
                ? (int) max($carProvinceProjectCounts)
                : 0;

            return view('dashboard.index', compact(
                'totalProjects',
                'statusActualCounts',
                'statusSubaybayanCounts',
                'statusSubaybayanProjectsMap',
                'statusSubaybayanLocationReport',
                'provinceFundingYearProgramStatusReport',
                'provinceFundingYearProgramStatusSourceRows',
                'statusDisplayOrder',
                'subayUploadDateLabel',
                'fundSourceCounts',
                'filters',
                'filterOptions',
                'totalLgsfAllocationAmount',
                'totalObligationAmount',
                'totalDisbursementAmount',
                'totalBalanceAmount',
                'utilizationPercentage',
                'expectedCompletionMonthLabel',
                'projectsExpectedCompletionThisMonth',
                'projectAtRiskCounts',
                'projectAtRiskAgingCounts',
                'projectAtRiskSlippageProjects',
                'projectAtRiskAgingProjects',
                'projectUpdateStatusCounts',
                'projectUpdateRiskProjects',
                'projectsWithBalance',
                'financialStatusProjects',
                'fundSourceProjectsMap',
                'carProvinceProjectCounts',
                'carProvinceProjectMaxCount',
                'activeProjectTab'
            ));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Dashboard view error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    };

    Route::get('/dashboard', function () use ($renderProjectDashboard) {
        return $renderProjectDashboard(request()->query('tab', 'locally-funded'));
    })->name('dashboard');
    
    // Profile routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
    // Change password routes
    Route::get('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'show'])->name('password.show');
    Route::put('/change-password', [App\Http\Controllers\ChangePasswordController::class, 'update'])->name('password.update');

    Route::prefix('ticketing')->name('ticketing.')->middleware('crud_permission:ticketing_system,view')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/tickets/{ticket}', [App\Http\Controllers\TicketController::class, 'show'])->name('show');
        Route::get('/tickets/{ticket}/attachments/{attachment}', [App\Http\Controllers\TicketController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/tickets/{ticket}/history', [App\Http\Controllers\TicketHistoryController::class, 'index'])->name('history.index');
        Route::post('/tickets/{ticket}/comments', [App\Http\Controllers\TicketCommentController::class, 'store'])->name('comments.store');

        Route::middleware('role:lgu')->group(function () {
            Route::get('/submit', [App\Http\Controllers\TicketController::class, 'create'])->name('create');
            Route::post('/submit', [App\Http\Controllers\TicketController::class, 'store'])->name('store');
            Route::get('/my-tickets', [App\Http\Controllers\TicketController::class, 'myTickets'])->name('my-tickets');
            Route::get('/track', [App\Http\Controllers\TicketController::class, 'track'])->name('track');
        });

        Route::prefix('province')->name('province.')->middleware('role:province')->group(function () {
            Route::get('/tickets', [App\Http\Controllers\TicketController::class, 'provincialIndex'])->name('index');
            Route::post('/tickets/{ticket}/accept', [App\Http\Controllers\TicketController::class, 'provinceAccept'])->name('accept');
            Route::post('/tickets/{ticket}/start-review', [App\Http\Controllers\TicketController::class, 'provinceStartReview'])->name('start-review');
            Route::post('/tickets/{ticket}/resolve', [App\Http\Controllers\TicketController::class, 'provinceResolve'])->name('resolve');
            Route::post('/tickets/{ticket}/escalate', [App\Http\Controllers\TicketController::class, 'provinceEscalate'])->name('escalate');
        });

        Route::prefix('region')->name('region.')->middleware('role:region')->group(function () {
            Route::get('/tickets', [App\Http\Controllers\TicketController::class, 'regionalIndex'])->name('index');
            Route::post('/tickets/{ticket}/accept', [App\Http\Controllers\TicketController::class, 'regionAccept'])->name('accept');
            Route::post('/tickets/{ticket}/start-review', [App\Http\Controllers\TicketController::class, 'regionStartReview'])->name('start-review');
            Route::post('/tickets/{ticket}/resolve', [App\Http\Controllers\TicketController::class, 'regionResolve'])->name('resolve');
            Route::post('/tickets/{ticket}/forward', [App\Http\Controllers\TicketController::class, 'regionForward'])->name('forward');
        });

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/tickets', [App\Http\Controllers\AdminController::class, 'index'])->name('index');
            Route::post('/categories', [App\Http\Controllers\AdminController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}', [App\Http\Controllers\AdminController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [App\Http\Controllers\AdminController::class, 'destroyCategory'])->name('categories.destroy');
            Route::post('/tickets/{ticket}/close', [App\Http\Controllers\AdminController::class, 'closeTicket'])->name('close');
        });
    });
    
    // User Management routes (superadmin only)
    Route::middleware('superadmin')->group(function () {
        Route::resource('users', App\Http\Controllers\UserManagementController::class);
        Route::put('users/{user}/block', [App\Http\Controllers\UserManagementController::class, 'block'])
            ->name('users.block');
        Route::put('users/{user}/access', [App\Http\Controllers\UserManagementController::class, 'updateAccess'])
            ->name('users.access.update');
        Route::get('/utilities/role-configuration', [App\Http\Controllers\DatabaseUtilityController::class, 'roleConfiguration'])
            ->name('utilities.role-configuration.index');
        Route::post('/utilities/role-configuration/role-definitions', [App\Http\Controllers\DatabaseUtilityController::class, 'storeRoleDefinition'])
            ->name('utilities.role-configuration.role-definitions.store');
        Route::put('/utilities/role-configuration/role-definitions/{role}', [App\Http\Controllers\DatabaseUtilityController::class, 'updateRoleDefinition'])
            ->name('utilities.role-configuration.role-definitions.update');
        Route::delete('/utilities/role-configuration/role-definitions/{role}', [App\Http\Controllers\DatabaseUtilityController::class, 'destroyRoleDefinition'])
            ->name('utilities.role-configuration.role-definitions.destroy');
        Route::put('/utilities/role-configuration/roles/{role}', [App\Http\Controllers\DatabaseUtilityController::class, 'updateRoleConfiguration'])
            ->name('utilities.role-configuration.roles.update');
        Route::delete('/utilities/role-configuration/roles/{role}', [App\Http\Controllers\DatabaseUtilityController::class, 'resetRoleConfiguration'])
            ->name('utilities.role-configuration.roles.reset');
    });

    Route::prefix('utilities')->name('utilities.')->group(function () {
        Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])
            ->middleware('superadmin')
            ->name('activity-logs.index');
        Route::get('/activity-logs/export', [App\Http\Controllers\ActivityLogController::class, 'export'])
            ->middleware('superadmin')
            ->name('activity-logs.export');
        Route::get('/system-setup', [App\Http\Controllers\DatabaseUtilityController::class, 'systemSetup'])
            ->middleware('crud_permission:utilities_system_setup,view')
            ->name('system-setup.index');
        Route::get('/system-maintenance', [App\Http\Controllers\DatabaseUtilityController::class, 'systemMaintenance'])
            ->middleware('superadmin')
            ->name('system-maintenance.index');
        Route::post('/system-maintenance/toggle', [App\Http\Controllers\DatabaseUtilityController::class, 'toggleSystemMaintenance'])
            ->middleware('superadmin')
            ->name('system-maintenance.toggle');
        Route::get('/notifications', [App\Http\Controllers\DatabaseUtilityController::class, 'notifications'])
            ->middleware('crud_permission:utilities_bulk_notifications,view')
            ->name('notifications.index');
        Route::post('/notifications', [App\Http\Controllers\DatabaseUtilityController::class, 'sendBulkNotification'])
            ->middleware('crud_permission:utilities_bulk_notifications,add')
            ->name('notifications.broadcast');
        Route::get('/deadlines-configuration', [App\Http\Controllers\DatabaseUtilityController::class, 'deadlinesConfiguration'])
            ->middleware('crud_permission:utilities_deadlines_configuration,view')
            ->name('deadlines-configuration.index');
        Route::get('/deadlines-configuration/lgu-reportorial-requirements', [App\Http\Controllers\DatabaseUtilityController::class, 'lguReportorialRequirements'])
            ->middleware('crud_permission:utilities_deadlines_configuration,view')
            ->name('deadlines-configuration.lgu-reportorial');
        Route::post('/deadlines-configuration/lgu-reportorial-requirements', [App\Http\Controllers\DatabaseUtilityController::class, 'storeLguReportorialDeadline'])
            ->middleware('crud_permission:utilities_deadlines_configuration,update')
            ->name('deadlines-configuration.lgu-reportorial.store');
        Route::get('/deadlines-configuration/dilg-reportorial-requirements', [App\Http\Controllers\DatabaseUtilityController::class, 'dilgReportorialRequirements'])
            ->middleware('crud_permission:utilities_deadlines_configuration,view')
            ->name('deadlines-configuration.dilg-reportorial');
        Route::get('/location-configuration', [App\Http\Controllers\DatabaseUtilityController::class, 'locationConfiguration'])
            ->middleware('crud_permission:utilities_location_configuration,view')
            ->name('location-configuration.index');
        Route::post('/location-configuration/import/{dataset}', [App\Http\Controllers\DatabaseUtilityController::class, 'importLocationDataset'])
            ->middleware('crud_permission:utilities_location_configuration,add')
            ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
            ->name('location-configuration.import');
        Route::post('/location-configuration/import/{dataset}/{importId}/load', [App\Http\Controllers\DatabaseUtilityController::class, 'loadLocationDatasetImport'])
            ->middleware('crud_permission:utilities_location_configuration,update')
            ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
            ->whereNumber('importId')
            ->name('location-configuration.load');
        Route::get('/location-configuration/import/{dataset}/{importId}/download', [App\Http\Controllers\DatabaseUtilityController::class, 'downloadLocationDatasetImport'])
            ->middleware('crud_permission:utilities_location_configuration,view')
            ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
            ->whereNumber('importId')
            ->name('location-configuration.download');
        Route::delete('/location-configuration/import/{dataset}/{importId}', [App\Http\Controllers\DatabaseUtilityController::class, 'deleteLocationDatasetImport'])
            ->middleware('crud_permission:utilities_location_configuration,delete')
            ->whereIn('dataset', ['regions', 'provinces', 'city-municipalities'])
            ->whereNumber('importId')
            ->name('location-configuration.delete');
        Route::get('/backup-and-restore', [App\Http\Controllers\DatabaseUtilityController::class, 'index'])
            ->middleware('crud_permission:utilities_backup_restore,view')
            ->name('backup-and-restore.index');
        Route::get('/backup-and-restore/download', [App\Http\Controllers\DatabaseUtilityController::class, 'downloadBackup'])
            ->middleware('crud_permission:utilities_backup_restore,view')
            ->name('backup-and-restore.download');
        Route::post('/backup-and-restore/restore', [App\Http\Controllers\DatabaseUtilityController::class, 'restore'])
            ->middleware('crud_permission:utilities_backup_restore,update')
            ->name('backup-and-restore.restore');
        Route::post('/backup-and-restore/schedule', [App\Http\Controllers\DatabaseUtilityController::class, 'saveSchedule'])
            ->middleware('crud_permission:utilities_backup_restore,update')
            ->name('backup-and-restore.schedule');
        Route::post('/backup-and-restore/test-now', [App\Http\Controllers\DatabaseUtilityController::class, 'sendTestBackupNow'])
            ->middleware('crud_permission:utilities_backup_restore,update')
            ->name('backup-and-restore.test-now');
    });

    // Fund Utilization Report routes
    Route::prefix('fund-utilization')->group(function () {
        Route::get('/', [App\Http\Controllers\FundUtilizationReportController::class, 'index'])->name('fund-utilization.index');
        Route::get('/export', [App\Http\Controllers\FundUtilizationReportController::class, 'export'])->name('fund-utilization.export');
        Route::get('/create', [App\Http\Controllers\FundUtilizationReportController::class, 'create'])->name('fund-utilization.create');
        Route::get('/get-municipalities/{province}', [App\Http\Controllers\FundUtilizationReportController::class, 'getMunicipalities'])->name('fund-utilization.get-municipalities');
        Route::post('/', [App\Http\Controllers\FundUtilizationReportController::class, 'store'])->name('fund-utilization.store');
        Route::get('/{projectCode}', [App\Http\Controllers\FundUtilizationReportController::class, 'show'])->name('fund-utilization.show');
        Route::get('/{projectCode}/edit', [App\Http\Controllers\FundUtilizationReportController::class, 'edit'])->name('fund-utilization.edit');
        Route::put('/{projectCode}', [App\Http\Controllers\FundUtilizationReportController::class, 'update'])->name('fund-utilization.update');
        Route::delete('/{projectCode}', [App\Http\Controllers\FundUtilizationReportController::class, 'deleteProject'])->name('fund-utilization.delete-project');
        Route::post('/{projectCode}/upload-mov', [App\Http\Controllers\FundUtilizationReportController::class, 'uploadMOV'])->name('fund-utilization.upload-mov');
        Route::post('/{projectCode}/upload-written-notice', [App\Http\Controllers\FundUtilizationReportController::class, 'uploadWrittenNotice'])->name('fund-utilization.upload-written-notice');
        Route::post('/{projectCode}/upload-fdp', [App\Http\Controllers\FundUtilizationReportController::class, 'uploadFDP'])->name('fund-utilization.upload-fdp');
        Route::post('/{projectCode}/save-posting-link', [App\Http\Controllers\FundUtilizationReportController::class, 'savePostingLink'])->name('fund-utilization.save-posting-link');
        Route::post('/{projectCode}/add-remark', [App\Http\Controllers\FundUtilizationReportController::class, 'addRemark'])->name('fund-utilization.add-remark');
        Route::post('/{projectCode}/approve/{uploadType}/{quarter}', [App\Http\Controllers\FundUtilizationReportController::class, 'approveUpload'])->name('fund-utilization.approve-upload');
        Route::post('/{projectCode}/save-remarks/{uploadType}/{quarter}', [App\Http\Controllers\FundUtilizationReportController::class, 'saveUserRemarks'])->name('fund-utilization.save-remarks');
        Route::get('/{projectCode}/view-document/{docType}/{quarter}', [App\Http\Controllers\FundUtilizationReportController::class, 'viewDocument'])->name('fund-utilization.view-document');
        Route::post('/{projectCode}/delete-document/{docType}/{quarter}', [App\Http\Controllers\FundUtilizationReportController::class, 'deleteDocument'])->name('fund-utilization.delete-document');
    });

    Route::get('/pre-implementation-documents/projects', [App\Http\Controllers\PreImplementationDocumentController::class, 'index'])
        ->name('pre-implementation-documents.index');
    Route::get('/pre-implementation-documents/projects/{projectCode}', [App\Http\Controllers\PreImplementationDocumentController::class, 'show'])
        ->name('pre-implementation-documents.show');
    Route::get('/pre-implementation-documents/projects/{projectCode}/document/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'viewDocument'])
        ->name('pre-implementation-documents.document');
    Route::get('/pre-implementation-documents/projects/{projectCode}/document-file/{fileId}', [App\Http\Controllers\PreImplementationDocumentController::class, 'viewDocumentFile'])
        ->name('pre-implementation-documents.document-file');
    Route::post('/pre-implementation-documents/projects/{projectCode}', [App\Http\Controllers\PreImplementationDocumentController::class, 'save'])
        ->name('pre-implementation-documents.save');
    Route::post('/pre-implementation-documents/projects/{projectCode}/upload/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'uploadMultiDocument'])
        ->name('pre-implementation-documents.upload-multi');
    Route::post('/pre-implementation-documents/projects/{projectCode}/validate/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'validateDocument'])
        ->name('pre-implementation-documents.validate');
    Route::post('/pre-implementation-documents/projects/{projectCode}/validate-file/{fileId}', [App\Http\Controllers\PreImplementationDocumentController::class, 'validateDocumentFile'])
        ->name('pre-implementation-documents.validate-file');
    Route::get('/pre-implementation-documents/sbdp-projects', function () {
        return redirect()->route('pre-implementation-documents.index', request()->query());
    });
    Route::get('/pre-implementation-documents/sbdp-projects/{projectCode}', function (string $projectCode) {
        return redirect()->route('pre-implementation-documents.show', array_merge(
            ['projectCode' => $projectCode],
            request()->query()
        ));
    });
    Route::post('/pre-implementation-documents/sbdp-projects/{projectCode}', [App\Http\Controllers\PreImplementationDocumentController::class, 'save']);
    Route::post('/pre-implementation-documents/sbdp-projects/{projectCode}/validate/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'validateDocument']);

    Route::get('/initial-project-documents/projects', [App\Http\Controllers\PreImplementationDocumentController::class, 'index'])
        ->name('initial-project-documents.index');
    Route::get('/initial-project-documents/projects/{projectCode}', [App\Http\Controllers\PreImplementationDocumentController::class, 'show'])
        ->name('initial-project-documents.show');
    Route::get('/initial-project-documents/projects/{projectCode}/document/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'viewDocument'])
        ->name('initial-project-documents.document');
    Route::get('/initial-project-documents/projects/{projectCode}/document-file/{fileId}', [App\Http\Controllers\PreImplementationDocumentController::class, 'viewDocumentFile'])
        ->name('initial-project-documents.document-file');
    Route::post('/initial-project-documents/projects/{projectCode}', [App\Http\Controllers\PreImplementationDocumentController::class, 'save'])
        ->name('initial-project-documents.save');
    Route::post('/initial-project-documents/projects/{projectCode}/upload/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'uploadMultiDocument'])
        ->name('initial-project-documents.upload-multi');
    Route::post('/initial-project-documents/projects/{projectCode}/validate/{documentType}', [App\Http\Controllers\PreImplementationDocumentController::class, 'validateDocument'])
        ->name('initial-project-documents.validate');
    Route::post('/initial-project-documents/projects/{projectCode}/validate-file/{fileId}', [App\Http\Controllers\PreImplementationDocumentController::class, 'validateDocumentFile'])
        ->name('initial-project-documents.validate-file');

    // Projects routes
    Route::get('/projects/locally-funded', [App\Http\Controllers\LocallyFundedProjectController::class, 'index'])->name('projects.locally-funded');
    Route::get('/projects/locally-funded/subay/{projectCode}', [App\Http\Controllers\LocallyFundedProjectController::class, 'showSubaybayan'])->name('locally-funded-project.subay-show');
    Route::get('/projects/locally-funded/create', [App\Http\Controllers\LocallyFundedProjectController::class, 'create'])->name('locally-funded-project.create');
    Route::get('/projects/locally-funded/ensure/{projectCode}', [App\Http\Controllers\LocallyFundedProjectController::class, 'ensureFromSubay'])
        ->name('locally-funded-project.ensure');
    Route::post('/projects/locally-funded', [App\Http\Controllers\LocallyFundedProjectController::class, 'store'])->name('locally-funded-project.store');
    Route::get('/projects/locally-funded/{project}', [App\Http\Controllers\LocallyFundedProjectController::class, 'show'])->name('locally-funded-project.show');
    Route::get('/projects/locally-funded/{project}/pcr-mov', [App\Http\Controllers\LocallyFundedProjectController::class, 'viewPcrMov'])->name('locally-funded-project.view-pcr-mov');
    Route::get('/projects/locally-funded/{project}/edit', [App\Http\Controllers\LocallyFundedProjectController::class, 'edit'])->name('locally-funded-project.edit');
    Route::put('/projects/locally-funded/{project}', [App\Http\Controllers\LocallyFundedProjectController::class, 'update'])->name('locally-funded-project.update');
    Route::delete('/projects/locally-funded/{project}', [App\Http\Controllers\LocallyFundedProjectController::class, 'destroy'])->name('locally-funded-project.destroy');
    
    // API routes for location data

    Route::get('/project-at-risk', [App\Http\Controllers\ProjectAtRiskController::class, 'index'])
        ->name('projects.at-risk');
    Route::get('/project-at-risk/export', [App\Http\Controllers\ProjectAtRiskController::class, 'export'])
        ->name('projects.at-risk.export');
    Route::post('/project-at-risk/import', [App\Http\Controllers\ProjectAtRiskController::class, 'import'])
        ->middleware('regional_dilg')
        ->name('projects.at-risk.import');

    Route::get('/projects/rssa', [App\Http\Controllers\RssaProjectController::class, 'index'])
        ->name('projects.rssa');

    Route::get('/projects/sglgif', [SglgifProjectController::class, 'dashboard'])
        ->name('projects.sglgif');
    Route::get('/projects/sglgif/table', [SglgifProjectController::class, 'table'])
        ->name('projects.sglgif.table');

    Route::get('/projects/rlip-lime/dashboard', [RlipLimeProjectController::class, 'dashboard'])
        ->name('projects.rlip-lime.dashboard');
    Route::get('/projects/rlip-lime', [RlipLimeProjectController::class, 'index'])->name('projects.rlip-lime');
    Route::get('/projects/rlip-lime/{rowNumber}', [RlipLimeProjectController::class, 'show'])
        ->whereNumber('rowNumber')
        ->name('projects.rlip-lime.show');

    Route::middleware('regional_dilg')->group(function () {
        Route::get('/system-management', function () {
            $user = request()->user();
            $hasAccess = $user
                && (
                    $user->hasCrudPermission('subaybayan_data_uploads', 'view')
                    || $user->hasCrudPermission('rlip_lime_data_uploads', 'view')
                    || $user->hasCrudPermission('project_at_risk_data_uploads', 'view')
                    || $user->hasCrudPermission('sglgif_data_uploads', 'view')
                );

            if (!$hasAccess) {
                return response()->view('errors.restricted', [], 403);
            }

            return view('system-management.index');
        })->name('system-management.index');
        Route::get('/system-management/upload-subaybayan', [SystemManagementController::class, 'uploadSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan');
        Route::get('/system-management/upload-subaybayan/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan.template');
        Route::get('/system-management/upload-subaybayan-2025', [SystemManagementController::class, 'uploadSubaybayan2025'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025');
        Route::get('/system-management/upload-subaybayan-2025/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025.template');
        Route::get('/system-management/upload-rssa', [SystemManagementController::class, 'uploadRssa'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa');
        Route::get('/system-management/upload-rssa/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa.template');
        Route::get('/system-management/upload-sglgif', [SystemManagementController::class, 'uploadSglgif'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif');
        Route::get('/system-management/upload-sglgif/template', [SystemManagementController::class, 'downloadSubaybayanTemplate'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif.template');
        Route::get('/system-management/upload-rlip-lime', [SystemManagementController::class, 'uploadRlipLime'])
            ->middleware('crud_permission:rlip_lime_data_uploads,view')
            ->name('system-management.upload-rlip-lime');
        Route::get('/system-management/upload-project-at-risk', [App\Http\Controllers\ProjectAtRiskController::class, 'uploadManager'])
            ->name('system-management.upload-project-at-risk');
        Route::get('/system-management/upload-project-at-risk/template', [App\Http\Controllers\ProjectAtRiskController::class, 'downloadTemplate'])
            ->name('system-management.upload-project-at-risk.template');
        Route::post('/system-management/upload-project-at-risk/import', [App\Http\Controllers\ProjectAtRiskController::class, 'import'])
            ->name('system-management.upload-project-at-risk.import');
        Route::post('/system-management/upload-project-at-risk/import/{importId}/load', [App\Http\Controllers\ProjectAtRiskController::class, 'loadImport'])
            ->name('system-management.upload-project-at-risk.load');
        Route::get('/system-management/upload-project-at-risk/import/{importId}/download', [App\Http\Controllers\ProjectAtRiskController::class, 'downloadImport'])
            ->name('system-management.upload-project-at-risk.download');
        Route::delete('/system-management/upload-project-at-risk/import/{importId}', [App\Http\Controllers\ProjectAtRiskController::class, 'deleteImport'])
            ->name('system-management.upload-project-at-risk.delete');
        Route::post('/system-management/upload-subaybayan/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-subaybayan.import');
        Route::post('/system-management/upload-subaybayan/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-subaybayan.load');
        Route::get('/system-management/upload-subaybayan/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan.download');
        Route::delete('/system-management/upload-subaybayan/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-subaybayan.delete');
        Route::post('/system-management/upload-subaybayan-2025/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-subaybayan-2025.import');
        Route::post('/system-management/upload-subaybayan-2025/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-subaybayan-2025.load');
        Route::get('/system-management/upload-subaybayan-2025/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-subaybayan-2025.download');
        Route::delete('/system-management/upload-subaybayan-2025/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-subaybayan-2025.delete');
        Route::post('/system-management/upload-rssa/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:subaybayan_data_uploads,add')
            ->name('system-management.upload-rssa.import');
        Route::post('/system-management/upload-rssa/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,update')
            ->name('system-management.upload-rssa.load');
        Route::get('/system-management/upload-rssa/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,view')
            ->name('system-management.upload-rssa.download');
        Route::delete('/system-management/upload-rssa/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:subaybayan_data_uploads,delete')
            ->name('system-management.upload-rssa.delete');
        Route::post('/system-management/upload-sglgif/import', [SystemManagementController::class, 'importSubaybayan'])
            ->middleware('crud_permission:sglgif_data_uploads,add')
            ->name('system-management.upload-sglgif.import');
        Route::post('/system-management/upload-sglgif/import/{importId}/load', [SystemManagementController::class, 'loadSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,update')
            ->name('system-management.upload-sglgif.load');
        Route::get('/system-management/upload-sglgif/import/{importId}/download', [SystemManagementController::class, 'downloadSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,view')
            ->name('system-management.upload-sglgif.download');
        Route::delete('/system-management/upload-sglgif/import/{importId}', [SystemManagementController::class, 'deleteSubaybayanImport'])
            ->middleware('crud_permission:sglgif_data_uploads,delete')
            ->name('system-management.upload-sglgif.delete');
        Route::post('/system-management/upload-rlip-lime/import', [SystemManagementController::class, 'importRlipLime'])
            ->middleware('crud_permission:rlip_lime_data_uploads,add')
            ->name('system-management.upload-rlip-lime.import');
        Route::post('/system-management/upload-rlip-lime/import/{importId}/load', [SystemManagementController::class, 'loadRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,update')
            ->name('system-management.upload-rlip-lime.load');
        Route::get('/system-management/upload-rlip-lime/import/{importId}/download', [SystemManagementController::class, 'downloadRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,view')
            ->name('system-management.upload-rlip-lime.download');
        Route::delete('/system-management/upload-rlip-lime/import/{importId}', [SystemManagementController::class, 'deleteRlipLimeImport'])
            ->middleware('crud_permission:rlip_lime_data_uploads,delete')
            ->name('system-management.upload-rlip-lime.delete');
    });

    // Local Project Monitoring Committee routes
    Route::post('local-project-monitoring-committee/{lpmc}/upload', [App\Http\Controllers\LocalProjectMonitoringCommitteeController::class, 'upload'])
        ->name('local-project-monitoring-committee.upload');
    Route::post('local-project-monitoring-committee/{lpmc}/approve/{docId}', [App\Http\Controllers\LocalProjectMonitoringCommitteeController::class, 'approveDocument'])
        ->name('local-project-monitoring-committee.approve');
    Route::get('local-project-monitoring-committee/{lpmc}/document/{docId}', [App\Http\Controllers\LocalProjectMonitoringCommitteeController::class, 'viewDocument'])
        ->name('local-project-monitoring-committee.document');
    Route::delete('local-project-monitoring-committee/{lpmc}/document/{docId}', [App\Http\Controllers\LocalProjectMonitoringCommitteeController::class, 'deleteDocument'])
        ->name('local-project-monitoring-committee.delete-document');
    Route::resource('local-project-monitoring-committee', App\Http\Controllers\LocalProjectMonitoringCommitteeController::class)
        ->parameters(['local-project-monitoring-committee' => 'lpmc']);

    // Road Maintenance Status Report routes
    Route::post('road-maintenance-status/{roadMaintenance}/upload', [App\Http\Controllers\RoadMaintenanceStatusReportController::class, 'upload'])
        ->name('road-maintenance-status.upload');
    Route::post('road-maintenance-status/{roadMaintenance}/approve/{docId}', [App\Http\Controllers\RoadMaintenanceStatusReportController::class, 'approveDocument'])
        ->name('road-maintenance-status.approve');
    Route::get('road-maintenance-status/{roadMaintenance}/document/{docId}', [App\Http\Controllers\RoadMaintenanceStatusReportController::class, 'viewDocument'])
        ->name('road-maintenance-status.document');
    Route::delete('road-maintenance-status/{roadMaintenance}/document/{docId}', [App\Http\Controllers\RoadMaintenanceStatusReportController::class, 'deleteDocument'])
        ->name('road-maintenance-status.delete-document');
    Route::resource('road-maintenance-status', App\Http\Controllers\RoadMaintenanceStatusReportController::class)
        ->parameters(['road-maintenance-status' => 'roadMaintenance']);

    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'index'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573');
    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/edit', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'edit'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.edit');
    Route::post('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/upload', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'upload'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.upload');
    Route::post('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/approve/{docId}', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'approveDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.approve');
    Route::get('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/document/{docId}', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'viewDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.document');
    Route::delete('/reports/monthly/pd-no-pbbm-2025-1572-1573/{office}/document/{docId}', [App\Http\Controllers\PdNoPbbmMonthlyReportController::class, 'deleteDocument'])
        ->name('reports.monthly.pd-no-pbbm-2025-1572-1573.delete-document');

    Route::get('/reports/monthly/swa-annex-f', [App\Http\Controllers\SwaAnnexFReportController::class, 'index'])
        ->name('reports.monthly.swa-annex-f');
    Route::get('/reports/monthly/swa-annex-f/{office}/edit', [App\Http\Controllers\SwaAnnexFReportController::class, 'edit'])
        ->name('reports.monthly.swa-annex-f.edit');
    Route::post('/reports/monthly/swa-annex-f/{office}/upload', [App\Http\Controllers\SwaAnnexFReportController::class, 'upload'])
        ->name('reports.monthly.swa-annex-f.upload');
    Route::post('/reports/monthly/swa-annex-f/{office}/approve/{docId}', [App\Http\Controllers\SwaAnnexFReportController::class, 'approveDocument'])
        ->name('reports.monthly.swa-annex-f.approve');
    Route::get('/reports/monthly/swa-annex-f/{office}/document/{docId}', [App\Http\Controllers\SwaAnnexFReportController::class, 'viewDocument'])
        ->name('reports.monthly.swa-annex-f.document');
    Route::delete('/reports/monthly/swa-annex-f/{office}/document/{docId}', [App\Http\Controllers\SwaAnnexFReportController::class, 'deleteDocument'])
        ->name('reports.monthly.swa-annex-f.delete-document');

    Route::get('/reports/quarterly/rpmes/form-2', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-2');
    Route::post('/reports/quarterly/rpmes/form-2/{projectCode}/upload', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-2.upload');
    Route::post('/reports/quarterly/rpmes/form-2/{projectCode}/approve/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-2.approve');
    Route::get('/reports/quarterly/rpmes/form-2/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-2.document');
    Route::delete('/reports/quarterly/rpmes/form-2/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-2.delete-document');
    Route::get('/reports/quarterly/rpmes/form-2/{projectCode}', [App\Http\Controllers\QuarterlyRpmesForm2Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-2.show');
    Route::get('/reports/quarterly/rpmes/form-5', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-5');
    Route::post('/reports/quarterly/rpmes/form-5/{projectCode}/upload', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-5.upload');
    Route::post('/reports/quarterly/rpmes/form-5/{projectCode}/approve/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-5.approve');
    Route::get('/reports/quarterly/rpmes/form-5/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-5.document');
    Route::delete('/reports/quarterly/rpmes/form-5/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-5.delete-document');
    Route::get('/reports/quarterly/rpmes/form-5/{projectCode}', [App\Http\Controllers\QuarterlyRpmesForm5Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-5.show');
    Route::get('/reports/quarterly/rpmes/form-6', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'index'])
        ->name('reports.quarterly.rpmes.form-6');
    Route::post('/reports/quarterly/rpmes/form-6/{projectCode}/upload', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'upload'])
        ->name('reports.quarterly.rpmes.form-6.upload');
    Route::post('/reports/quarterly/rpmes/form-6/{projectCode}/approve/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'approveDocument'])
        ->name('reports.quarterly.rpmes.form-6.approve');
    Route::get('/reports/quarterly/rpmes/form-6/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'viewDocument'])
        ->name('reports.quarterly.rpmes.form-6.document');
    Route::delete('/reports/quarterly/rpmes/form-6/{projectCode}/document/{quarter}', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'deleteDocument'])
        ->name('reports.quarterly.rpmes.form-6.delete-document');
    Route::get('/reports/quarterly/rpmes/form-6/{projectCode}', [App\Http\Controllers\QuarterlyRpmesForm6Controller::class, 'show'])
        ->name('reports.quarterly.rpmes.form-6.show');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'index']
    )->name('reports.quarterly.dilg-mc-2018-19');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'edit']
    )->name('reports.quarterly.dilg-mc-2018-19.edit');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'saveEncoding']
    )->name('reports.quarterly.dilg-mc-2018-19.save-encoding');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/edit/{quarter}/export',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'exportEncoding']
    )->name('reports.quarterly.dilg-mc-2018-19.export-encoding');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/upload',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'upload']
    )->name('reports.quarterly.dilg-mc-2018-19.upload');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}/document/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'viewDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.document');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-19/{office}/approve/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'approveDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.approve');
    Route::delete(
        '/reports/quarterly/dilg-mc-2018-19/{office}/document/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'deleteDocument']
    )->name('reports.quarterly.dilg-mc-2018-19.delete-document');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-19/{office}',
        [App\Http\Controllers\QuarterlyDilgMc201819Controller::class, 'show']
    )->name('reports.quarterly.dilg-mc-2018-19.show');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'index']
    )->name('reports.quarterly.dilg-mc-2018-30');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'edit']
    )->name('reports.quarterly.dilg-mc-2018-30.edit');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'saveEncoding']
    )->name('reports.quarterly.dilg-mc-2018-30.save-encoding');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/edit/{quarter}/export',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'exportEncoding']
    )->name('reports.quarterly.dilg-mc-2018-30.export-encoding');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/upload',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'upload']
    )->name('reports.quarterly.dilg-mc-2018-30.upload');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}/document/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'viewDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.document');
    Route::post(
        '/reports/quarterly/dilg-mc-2018-30/{office}/approve/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'approveDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.approve');
    Route::delete(
        '/reports/quarterly/dilg-mc-2018-30/{office}/document/{docId}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'deleteDocument']
    )->name('reports.quarterly.dilg-mc-2018-30.delete-document');
    Route::get(
        '/reports/quarterly/dilg-mc-2018-30/{office}',
        [App\Http\Controllers\QuarterlyDilgMc201830Controller::class, 'show']
    )->name('reports.quarterly.dilg-mc-2018-30.show');
    Route::get('/reports/annual/rpmes/form-4', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'index'])
        ->name('reports.annual.rpmes.form-4');
    Route::post('/reports/annual/rpmes/form-4/{projectCode}/upload', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'upload'])
        ->name('reports.annual.rpmes.form-4.upload');
    Route::post('/reports/annual/rpmes/form-4/{projectCode}/approve/{quarter}', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'approveDocument'])
        ->name('reports.annual.rpmes.form-4.approve');
    Route::get('/reports/annual/rpmes/form-4/{projectCode}/document/{quarter}', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'viewDocument'])
        ->name('reports.annual.rpmes.form-4.document');
    Route::delete('/reports/annual/rpmes/form-4/{projectCode}/document/{quarter}', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'deleteDocument'])
        ->name('reports.annual.rpmes.form-4.delete-document');
    Route::get('/reports/annual/rpmes/form-4/{projectCode}', [App\Http\Controllers\AnnualRpmesForm4Controller::class, 'show'])
        ->name('reports.annual.rpmes.form-4.show');
    Route::get('/reports/annual/amwp', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'index'])
        ->name('reports.annual.amwp');
    Route::get('/reports/annual/amwp/{office}/edit', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'edit'])
        ->name('reports.annual.amwp.edit');
    Route::post('/reports/annual/amwp/{office}/upload', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'upload'])
        ->name('reports.annual.amwp.upload');
    Route::post('/reports/annual/amwp/{office}/approve/{docId}', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'approveDocument'])
        ->name('reports.annual.amwp.approve');
    Route::get('/reports/annual/amwp/{office}/document/{docId}', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'viewDocument'])
        ->name('reports.annual.amwp.document');
    Route::delete('/reports/annual/amwp/{office}/document/{docId}', [App\Http\Controllers\AnnualMaintenanceWorkProgramController::class, 'deleteDocument'])
        ->name('reports.annual.amwp.delete-document');
    Route::get('/reports/one-time/confirmation-of-fund-receipt', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'index'])
        ->name('reports.one-time.confirmation-of-fund-receipt.index');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'show'])
        ->name('reports.one-time.confirmation-of-fund-receipt.show');
    Route::post('/reports/one-time/confirmation-of-fund-receipt/{office}/accept/{docId}', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'acceptDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.accept');
    Route::post('/reports/one-time/confirmation-of-fund-receipt/{office}/upload/{docId}', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'store'])
        ->name('reports.one-time.confirmation-of-fund-receipt.upload');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}/document/{docId}', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'viewDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.document');
    Route::get('/reports/one-time/confirmation-of-fund-receipt/{office}/attachment/{attachmentId}', [App\Http\Controllers\ConfirmationOfFundReceiptController::class, 'viewConfirmationDocument'])
        ->name('reports.one-time.confirmation-of-fund-receipt.attachment');
    Route::get('/reports/one-time/project-initial-documents', function () {
        return redirect()->route('initial-project-documents.index', request()->query());
    })->name('reports.one-time.project-initial-documents');
    Route::view(
        '/reports/one-time/project-completion-reports/falgu-gef-sbdp',
        'reports.one-time.shared.show',
        [
            'pageTitle' => 'Project Completion Reports - FALGU, GEF, SBDP',
            'pageSubtitle' => 'Dedicated one-time report page for Project Completion Reports covering FALGU, GEF, and SBDP.',
        ]
    )->name('reports.one-time.project-completion-reports.falgu-gef-sbdp');
    Route::view(
        '/reports/one-time/project-completion-reports/safpb',
        'reports.one-time.shared.show',
        [
            'pageTitle' => 'Project Completion Reports - SAFPB',
            'pageSubtitle' => 'Dedicated one-time report page for Project Completion Reports covering SAFPB.',
        ]
    )->name('reports.one-time.project-completion-reports.safpb');
    Route::view(
        '/reports/one-time/project-completion-reports/sglgif',
        'reports.one-time.shared.show',
        [
            'pageTitle' => 'Project Completion Reports - SGLGIF',
            'pageSubtitle' => 'Dedicated one-time report page for Project Completion Reports covering SGLGIF.',
        ]
    )->name('reports.one-time.project-completion-reports.sglgif');
    Route::view(
        '/reports/one-time/pisat',
        'reports.one-time.shared.show',
        [
            'pageTitle' => 'PISAT',
            'pageSubtitle' => 'Dedicated one-time report page for PISAT.',
        ]
    )->name('reports.one-time.pisat');

    Route::get('/reports/rbis-annual-certification', [App\Http\Controllers\RbisAnnualCertificationController::class, 'index'])
        ->name('rbis-annual-certification.index');
    Route::get('/reports/rbis-annual-certification/{office}/edit', [App\Http\Controllers\RbisAnnualCertificationController::class, 'edit'])
        ->name('rbis-annual-certification.edit');
    Route::post('/reports/rbis-annual-certification/{office}/upload', [App\Http\Controllers\RbisAnnualCertificationController::class, 'upload'])
        ->name('rbis-annual-certification.upload');
    Route::post('/reports/rbis-annual-certification/{office}/approve/{docId}', [App\Http\Controllers\RbisAnnualCertificationController::class, 'approveDocument'])
        ->name('rbis-annual-certification.approve');
    Route::get('/reports/rbis-annual-certification/{office}/document/{docId}', [App\Http\Controllers\RbisAnnualCertificationController::class, 'viewDocument'])
        ->name('rbis-annual-certification.document');
    Route::delete('/reports/rbis-annual-certification/{office}/document/{docId}', [App\Http\Controllers\RbisAnnualCertificationController::class, 'deleteDocument'])
        ->name('rbis-annual-certification.delete-document');
    Route::get('/nadai-management', [App\Http\Controllers\NadaiManagementController::class, 'index'])
        ->name('nadai-management.index');
    Route::get('/nadai-management/{office}', [App\Http\Controllers\NadaiManagementController::class, 'show'])
        ->name('nadai-management.show');
    Route::post('/nadai-management/{office}/upload', [App\Http\Controllers\NadaiManagementController::class, 'store'])
        ->name('nadai-management.store');
    Route::get('/nadai-management/{office}/document/{docId}', [App\Http\Controllers\NadaiManagementController::class, 'viewDocument'])
        ->name('nadai-management.document');
    Route::get('/nadai-management/{office}/document/{docId}/open', [App\Http\Controllers\NadaiManagementController::class, 'openDocumentAndRedirect'])
        ->name('nadai-management.open-document');
    Route::get('/nadai-management/{office}/document/{docId}/download', [App\Http\Controllers\NadaiManagementController::class, 'downloadDocument'])
        ->name('nadai-management.download-document');
    Route::delete('/nadai-management/{office}/document/{docId}', [App\Http\Controllers\NadaiManagementController::class, 'deleteDocument'])
        ->name('nadai-management.delete-document');
});
