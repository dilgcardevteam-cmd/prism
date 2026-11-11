<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PagasaTimeController;
use App\Models\User;
use App\Modules\ActivityLogs\Models\ActivityLog;
use App\Modules\ActivityLogs\Services\ActivityLogService;
use App\Modules\Auth\Jobs\SendLoginOtpEmail;
use App\Services\RegionalApprovalPoolService;
use App\Support\NotificationCenter;
use App\Support\NotificationUrl;
use App\Support\SystemMaintenanceState;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;

// Authentication Module
require base_path('routes/modules/auth.php');

// Projects & Data Ingestion Module
require base_path('routes/modules/projects.php');

// Communications & Messaging Module
require base_path('routes/modules/communications.php');

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

Route::get('/system-under-maintenance', function (SystemMaintenanceState $systemMaintenanceState) {
    if (! $systemMaintenanceState->isEnabled()) {
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
        if (! Schema::hasTable('subay_project_profiles')) {
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
            'Tineg' => ['lat' => 16.7333, 'lng' => 121.35],
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
                        'project_count' => $count,
                    ],
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$coords['lng'], $coords['lat']],
                    ],
                ];
            }
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'maxCount' => $maxCount,
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->name('api.municipality-projects');

// ======================MOBILE ENDPOINTS=================
Route::post('/api/mobile/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $user = User::where('username', $credentials['username'])->first();

    if (! $user || strtolower((string) $user->status) !== 'active' || ! Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'The username or password is incorrect.',
        ], 422);
    }

    if ($user->must_change_password && ! $user->isSuperAdmin()) {
        $resetChallenge = DB::transaction(function () use ($user): array {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $existing = DB::table('mobile_password_reset_challenges')
                ->where('user_id', $user->idno)
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return [
                    'token' => Crypt::decryptString($existing->encrypted_token),
                    'otp_pending' => $existing->pending_password_hash !== null,
                    'otp_sent_at' => $existing->otp_sent_at
                        ? Carbon\Carbon::parse($existing->otp_sent_at)
                        : null,
                ];
            }

            $challengeToken = Str::random(80);
            DB::table('mobile_password_reset_challenges')->insert([
                'user_id' => $user->idno,
                'challenge_hash' => hash('sha256', $challengeToken),
                'encrypted_token' => Crypt::encryptString($challengeToken),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'token' => $challengeToken,
                'otp_pending' => false,
                'otp_sent_at' => null,
            ];
        });

        return response()->json([
            'message' => $resetChallenge['otp_pending']
                ? 'Complete the email verification for your password change.'
                : 'A password reset is required before you can sign in.',
            'requires_password_change' => true,
            'requires_password_change_otp' => $resetChallenge['otp_pending'],
            'challenge_token' => $resetChallenge['token'],
            'resend_available_at' => $resetChallenge['otp_sent_at']?->copy()->addMinutes(5)->toISOString(),
        ], 202);
    }

    $challenge = DB::transaction(function () use ($user): array {
        User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

        $existing = DB::table('mobile_login_challenges')
            ->where('user_id', $user->idno)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if (! $existing->encrypted_token) {
                DB::table('mobile_login_challenges')->where('id', $existing->id)->delete();
            } else {
                return [
                    'token' => $existing->encrypted_token
                        ? Crypt::decryptString($existing->encrypted_token)
                        : null,
                    'otp' => null,
                    'expires_at' => Carbon\Carbon::parse($existing->expires_at),
                    'sent_at' => Carbon\Carbon::parse($existing->created_at),
                    'existing' => true,
                ];
            }
        }

        $otp = (string) random_int(100000, 999999);
        $challengeToken = Str::random(80);
        $expiresAt = now()->addMinutes(5);
        DB::table('mobile_login_challenges')->insert([
            'user_id' => $user->idno,
            'challenge_hash' => hash('sha256', $challengeToken),
            'encrypted_token' => Crypt::encryptString($challengeToken),
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'token' => $challengeToken,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'sent_at' => now(),
            'existing' => false,
        ];
    });

    if (! $challenge['existing']) {
        SendLoginOtpEmail::dispatch((int) $user->idno, $challenge['otp'], $challenge['expires_at'])->afterResponse();
    }

    return response()->json([
        'message' => $challenge['existing']
            ? 'A verification code is already active. Use the existing code or resend after five minutes.'
            : 'A verification code has been sent to your registered email address.',
        'requires_otp' => true,
        'challenge_token' => $challenge['token'],
        'expires_at' => $challenge['expires_at']->toISOString(),
        'resend_available_at' => $challenge['sent_at']->copy()->addMinutes(5)->toISOString(),
    ], 202);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:10,1');

Route::post('/api/mobile/password/reset', function (Request $request) {
    $validated = $request->validate([
        'challenge_token' => ['required', 'string', 'size:80'],
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

    $challengeHash = hash('sha256', $validated['challenge_token']);
    $challenge = DB::table('mobile_password_reset_challenges')
        ->where('challenge_hash', $challengeHash)
        ->where('expires_at', '>', now())
        ->first();
    $user = $challenge ? User::query()->find($challenge->user_id) : null;

    if (! $challenge || ! $user || ! $user->must_change_password || $user->isSuperAdmin() || strtolower((string) $user->status) !== 'active') {
        return response()->json([
            'message' => 'Your password reset request expired. Sign in again to continue.',
        ], 422);
    }

    if (Hash::check($validated['new_password'], $user->password)) {
        return response()->json([
            'message' => 'Choose a password different from your current password.',
        ], 422);
    }

    $otp = (string) random_int(100000, 999999);
    $otpExpiresAt = now()->addMinutes(5);
    DB::table('mobile_password_reset_challenges')
        ->where('id', $challenge->id)
        ->update([
            'pending_password_hash' => Hash::make($validated['new_password']),
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'otp_expires_at' => $otpExpiresAt,
            'otp_sent_at' => now(),
            'updated_at' => now(),
        ]);

    SendLoginOtpEmail::dispatch((int) $user->idno, $otp, $otpExpiresAt)->afterResponse();

    return response()->json([
        'message' => 'A verification code is being sent to your registered email address.',
        'requires_otp' => true,
        'resend_available_at' => now()->addMinutes(5)->toISOString(),
    ], 202);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:5,1');

Route::post('/api/mobile/password/reset/otp/resend', function (Request $request) {
    $validated = $request->validate([
        'challenge_token' => ['required', 'string', 'size:80'],
    ]);
    $challenge = DB::table('mobile_password_reset_challenges')
        ->where('challenge_hash', hash('sha256', $validated['challenge_token']))
        ->where('expires_at', '>', now())
        ->first();

    if (! $challenge || ! $challenge->pending_password_hash) {
        return response()->json([
            'message' => 'Your password reset request expired. Sign in again to continue.',
        ], 422);
    }

    $resendAvailableAt = $challenge->otp_sent_at
        ? Carbon\Carbon::parse($challenge->otp_sent_at)->addMinutes(5)
        : now();
    if ($resendAvailableAt->isFuture()) {
        return response()->json([
            'message' => 'Please wait five minutes between verification code requests.',
            'resend_available_at' => $resendAvailableAt->toISOString(),
        ], 429);
    }

    $user = User::query()->find($challenge->user_id);
    if (! $user || ! $user->must_change_password || $user->isSuperAdmin() || strtolower((string) $user->status) !== 'active') {
        DB::table('mobile_password_reset_challenges')->where('id', $challenge->id)->delete();

        return response()->json([
            'message' => 'This account cannot complete the password reset.',
        ], 422);
    }

    $otp = (string) random_int(100000, 999999);
    $otpExpiresAt = now()->addMinutes(5);
    DB::table('mobile_password_reset_challenges')
        ->where('id', $challenge->id)
        ->update([
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'otp_expires_at' => $otpExpiresAt,
            'otp_sent_at' => now(),
            'updated_at' => now(),
        ]);
    SendLoginOtpEmail::dispatch((int) $user->idno, $otp, $otpExpiresAt)->afterResponse();

    return response()->json([
        'message' => 'A new verification code is being sent.',
        'resend_available_at' => $otpExpiresAt->toISOString(),
    ]);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:3,1');

Route::post('/api/mobile/password/reset/otp', function (Request $request) {
    $validated = $request->validate([
        'challenge_token' => ['required', 'string', 'size:80'],
        'otp' => ['required', 'digits:6'],
    ]);
    $challengeHash = hash('sha256', $validated['challenge_token']);
    $challenge = DB::table('mobile_password_reset_challenges')
        ->where('challenge_hash', $challengeHash)
        ->where('expires_at', '>', now())
        ->where('otp_expires_at', '>', now())
        ->first();

    if (! $challenge || ! $challenge->pending_password_hash || ! $challenge->otp_hash) {
        return response()->json([
            'message' => 'The verification code expired. Request a new code to continue.',
        ], 422);
    }

    if (! Hash::check($validated['otp'], $challenge->otp_hash)) {
        $attempts = $challenge->attempts + 1;
        if ($attempts >= 5) {
            DB::table('mobile_password_reset_challenges')->where('id', $challenge->id)->delete();

            return response()->json([
                'message' => 'Too many incorrect verification attempts. Sign in again to restart the password reset.',
            ], 422);
        }

        DB::table('mobile_password_reset_challenges')
            ->where('id', $challenge->id)
            ->update(['attempts' => $attempts, 'updated_at' => now()]);

        return response()->json([
            'message' => 'The verification code is incorrect.',
        ], 422);
    }

    $plainTextToken = Str::random(80);
    $tokenExpiresAt = now()->addDays(30);
    $updatedUser = DB::transaction(function () use ($challenge, $challengeHash, $plainTextToken, $tokenExpiresAt, $validated) {
        $lockedChallenge = DB::table('mobile_password_reset_challenges')
            ->where('id', $challenge->id)
            ->where('challenge_hash', $challengeHash)
            ->where('expires_at', '>', now())
            ->where('otp_expires_at', '>', now())
            ->lockForUpdate()
            ->first();
        if (! $lockedChallenge || ! Hash::check($validated['otp'], (string) $lockedChallenge->otp_hash)) {
            return null;
        }

        $user = User::query()->whereKey($lockedChallenge->user_id)->lockForUpdate()->first();
        if (
            ! $user
            || ! $user->must_change_password
            || $user->isSuperAdmin()
            || strtolower((string) $user->status) !== 'active'
        ) {
            return null;
        }

        $user->forceFill([
            'password' => $lockedChallenge->pending_password_hash,
            'must_change_password' => false,
        ])->save();
        DB::table('mobile_password_reset_challenges')->where('user_id', $user->idno)->delete();
        DB::table('mobile_login_challenges')->where('user_id', $user->idno)->delete();
        DB::table('mobile_access_tokens')->where('user_id', $user->idno)->delete();
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $tokenExpiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    });

    if (! $updatedUser) {
        return response()->json([
            'message' => 'The verification request expired. Sign in again to continue.',
        ], 422);
    }

    app(ActivityLogService::class)->log(
        $updatedUser,
        ActivityLog::ACTION_PASSWORD_RESET,
        'Completed a system-mandated password change and email verification.',
        ['request' => $request],
    );

    return response()->json([
        'message' => 'Password changed and verified successfully.',
        'access_token' => $plainTextToken,
        'token_type' => 'Bearer',
        'expires_at' => $tokenExpiresAt->toISOString(),
        'user' => [
            'id' => $updatedUser->idno,
            'username' => $updatedUser->username,
            'first_name' => $updatedUser->fname ?? null,
            'last_name' => $updatedUser->lname ?? null,
            'email' => $updatedUser->emailaddress ?? null,
            'phone' => $updatedUser->mobileno ?? null,
            'agency' => $updatedUser->agency ?? null,
            'position' => $updatedUser->position ?? null,
            'region' => $updatedUser->region ?? null,
            'province' => $updatedUser->province ?? null,
            'office' => $updatedUser->office ?? null,
            'role' => $updatedUser->role ?? null,
            'status' => $updatedUser->status,
        ],
    ]);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:6,1');

Route::post('/api/mobile/login/otp/resend', function (Request $request) {
    $validated = $request->validate([
        'challenge_token' => ['required', 'string', 'size:80'],
    ]);

    $challengeHash = hash('sha256', $validated['challenge_token']);
    $challenge = DB::table('mobile_login_challenges')
        ->where('challenge_hash', $challengeHash)
        ->first();

    if (! $challenge) {
        return response()->json([
            'message' => 'Your verification request expired. Please sign in again.',
        ], 422);
    }

    $resend = DB::transaction(function () use ($challenge): ?array {
        $lockedChallenge = DB::table('mobile_login_challenges')
            ->where('id', $challenge->id)
            ->lockForUpdate()
            ->first();

        if (! $lockedChallenge) {
            return null;
        }

        $sentAt = Carbon\Carbon::parse($lockedChallenge->created_at);
        if ($sentAt->copy()->addMinutes(5)->isFuture()) {
            return [
                'available_at' => $sentAt->copy()->addMinutes(5),
            ];
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);
        DB::table('mobile_login_challenges')
            ->where('id', $lockedChallenge->id)
            ->update([
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ];
    });

    if (! $resend) {
        return response()->json([
            'message' => 'Your verification request expired. Please sign in again.',
        ], 422);
    }

    if (isset($resend['available_at'])) {
        return response()->json([
            'message' => 'Please wait five minutes between verification code requests.',
            'resend_available_at' => $resend['available_at']->toISOString(),
        ], 429);
    }

    $user = User::find($challenge->user_id);
    if (! $user || strtolower((string) $user->status) !== 'active') {
        DB::table('mobile_login_challenges')->where('id', $challenge->id)->delete();

        return response()->json([
            'message' => 'This account cannot sign in right now.',
        ], 422);
    }

    SendLoginOtpEmail::dispatch((int) $user->idno, $resend['otp'], $resend['expires_at'])->afterResponse();

    return response()->json([
        'message' => 'A new verification code has been sent.',
        'expires_at' => $resend['expires_at']->toISOString(),
        'resend_available_at' => $resend['expires_at']->toISOString(),
    ]);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:3,1');

Route::post('/api/mobile/login/otp', function (Request $request) {
    $validated = $request->validate([
        'challenge_token' => ['required', 'string', 'size:80'],
        'otp' => ['required', 'digits:6'],
    ]);

    $challengeHash = hash('sha256', $validated['challenge_token']);
    $challenge = DB::table('mobile_login_challenges')
        ->where('challenge_hash', $challengeHash)
        ->first();

    if (! $challenge || Carbon\Carbon::parse($challenge->expires_at)->isPast()) {
        return response()->json([
            'message' => 'The verification code expired. Request a new code to continue.',
        ], 422);
    }

    if (! Hash::check($validated['otp'], $challenge->otp_hash)) {
        $attempts = $challenge->attempts + 1;
        if ($attempts >= 5) {
            DB::table('mobile_login_challenges')->where('id', $challenge->id)->delete();

            return response()->json([
                'message' => 'Too many incorrect verification attempts. Please sign in again.',
            ], 422);
        }

        DB::table('mobile_login_challenges')
            ->where('id', $challenge->id)
            ->update(['attempts' => $attempts, 'updated_at' => now()]);

        return response()->json([
            'message' => 'The verification code is incorrect.',
        ], 422);
    }

    $user = User::find($challenge->user_id);
    if (! $user || strtolower((string) $user->status) !== 'active') {
        DB::table('mobile_login_challenges')->where('id', $challenge->id)->delete();

        return response()->json([
            'message' => 'This account cannot sign in right now.',
        ], 422);
    }

    if ($user->must_change_password && ! $user->isSuperAdmin()) {
        DB::table('mobile_login_challenges')->where('id', $challenge->id)->delete();

        return response()->json([
            'message' => 'A password reset is required before using the system.',
            'requires_password_change' => true,
        ], 423);
    }

    $plainTextToken = Str::random(80);
    $tokenExpiresAt = now()->addDays(30);
    $issued = DB::transaction(function () use ($challenge, $challengeHash, $user, $plainTextToken, $tokenExpiresAt): bool {
        $deleted = DB::table('mobile_login_challenges')
            ->where('id', $challenge->id)
            ->where('challenge_hash', $challengeHash)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->delete();

        if ($deleted !== 1) {
            return false;
        }

        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $tokenExpiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    });

    if (! $issued) {
        return response()->json([
            'message' => 'The verification code is invalid or expired. Please sign in again.',
        ], 422);
    }

    return response()->json([
        'message' => 'Login successful.',
        'access_token' => $plainTextToken,
        'token_type' => 'Bearer',
        'expires_at' => $tokenExpiresAt->toISOString(),
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
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('throttle:6,1');

Route::post('/api/mobile/logout', function (Request $request) {
    DB::table('mobile_access_tokens')
        ->where('token_hash', hash('sha256', (string) $request->bearerToken()))
        ->delete();

    return response()->json(['message' => 'Logged out successfully.']);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('mobile.auth');

Route::get('/api/mobile/user/profile', function (Request $request) {
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
})->middleware('mobile.auth');

Route::post('/api/mobile/user/profile/update', function (Request $request) {
    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'position' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string'],
    ]);

    $sanitizedPhone = preg_replace('/\D+/', '', (string) $validated['phone']);

    if (! preg_match('/^09\d{9}$/', $sanitizedPhone)) {
        return response()->json([
            'message' => 'Mobile number must be 11 digits and start with 09.',
        ], 422);
    }

    $user = Auth::user();
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
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('mobile.auth');

Route::post('/api/mobile/user/password/update', function (Request $request) {
    $validated = $request->validate([
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

    $user = Auth::user();

    if (! Hash::check($validated['current_password'], $user->password)) {
        return response()->json([
            'message' => 'Current password is incorrect.',
        ], 422);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->save();
    DB::table('mobile_access_tokens')->where('user_id', $user->idno)->delete();

    return response()->json([
        'message' => 'Password updated successfully.',
    ]);
})->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware('mobile.auth');

Route::middleware(['auth'])->group(function () {
    // PAGASA time endpoint for live clock display
    Route::get('/api/pagasa-time/current', [PagasaTimeController::class, 'current'])->name('pagasa-time.current');

    Route::get('/notifications/{id}/read', function ($id) {
        $notificationQuery = DB::table('tbnotifications')->where('id', $id);
        $authUser = Auth::user();

        if ($authUser instanceof User && $authUser->isRegionalUser()) {
            $notificationQuery->whereIn('user_id', NotificationCenter::regionalPoolUserIds($authUser));
        } else {
            $notificationQuery->where('user_id', Auth::id());
        }

        $notification = $notificationQuery->first();

        if (! $notification) {
            if (is_string($id) && str_starts_with($id, 'returned-fund-utilization-')) {
                $workflowId = (int) Str::after($id, 'returned-fund-utilization-');
                $workflow = DB::table('fund_utilization_approval_workflows')->where('id', $workflowId)->first();
                if ($workflow) {
                    DB::table('tbnotifications')
                        ->where('user_id', Auth::id())
                        ->where(function ($q) use ($workflow) {
                            $q->where('url', 'like', '%'.$workflow->project_code.'%')
                                ->orWhere('message', 'like', '%'.$workflow->project_code.'%');
                        })
                        ->update(['read_at' => now(), 'updated_at' => now()]);

                    return redirect()->route('fund-utilization.show', [
                        'projectCode' => $workflow->project_code,
                        'quarter' => $workflow->quarter,
                        'document' => $workflow->document_type,
                    ]);
                }
            } elseif (is_string($id) && str_starts_with($id, 'returned-')) {
                $remainder = Str::after($id, 'returned-');
                $lastDashPos = strrpos($remainder, '-');
                if ($lastDashPos !== false) {
                    $table = substr($remainder, 0, $lastDashPos);
                    $recordId = (int) substr($remainder, $lastDashPos + 1);
                    if (Schema::hasTable($table)) {
                        $record = DB::table($table)->where('id', $recordId)->first();
                        if ($record) {
                            $poolService = app(RegionalApprovalPoolService::class);
                            $refClass = new ReflectionClass($poolService);
                            $docSourcesMethod = $refClass->getMethod('documentSources');
                            $docSourcesMethod->setAccessible(true);
                            $sources = $docSourcesMethod->invoke($poolService);

                            foreach ($sources as $source) {
                                if ($source['table'] === $table) {
                                    $officeOrProject = trim((string) ($record->{$source['project'] ?? 'office'} ?? ''));
                                    $period = $source['period'] ? trim((string) ($record->{$source['period']} ?? '')) : '';
                                    $year = trim((string) ($record->year ?? $record->document_year ?? ''));
                                    $label = trim((string) ($record->{$source['document'] ?? 'doc_type' ?? 'document_name'} ?? ''));

                                    $buildMethod = $refClass->getMethod('buildTaskUrl');
                                    $buildMethod->setAccessible(true);
                                    $targetUrl = $buildMethod->invoke($poolService, $source, $record, $officeOrProject, $year, $period, $label);

                                    DB::table('tbnotifications')
                                        ->where('user_id', Auth::id())
                                        ->where('url', 'like', '%'.$officeOrProject.'%')
                                        ->update(['read_at' => now(), 'updated_at' => now()]);

                                    return redirect($targetUrl);
                                }
                            }
                        }
                    }
                }
            }

            return redirect()->back();
        }

        $sharedReadQuery = DB::table('tbnotifications')
            ->where('url', $notification->url)
            ->where('document_type', $notification->document_type);

        if ($notification->quarter === null) {
            $sharedReadQuery->whereNull('quarter');
        } else {
            $sharedReadQuery->where('quarter', $notification->quarter);
        }

        if ($authUser instanceof User && $authUser->isRegionalUser()) {
            $sharedReadQuery->whereIn('user_id', NotificationCenter::regionalPoolUserIds($authUser));
        } else {
            $sharedReadQuery->where('user_id', Auth::id());
        }

        $sharedReadQuery->update(['read_at' => now(), 'updated_at' => now()]);

        $notificationUrl = NotificationUrl::normalizeForRedirect($notification->url);
        $documentType = trim((string) ($notification->document_type ?? ''));
        $quarter = trim((string) ($notification->quarter ?? ''));

        $notificationPath = is_string($notificationUrl)
            ? parse_url($notificationUrl, PHP_URL_PATH)
            : null;
        $notificationSegments = is_string($notificationPath)
            ? array_values(array_filter(explode('/', trim($notificationPath, '/')), static fn ($segment) => $segment !== ''))
            : [];

        $fundUtilizationActionDocumentMap = [
            'upload-mov' => 'mov',
            'upload-batch-document' => 'batch-document',
            'upload-written-notice' => 'written-notice',
            'upload-fdp' => 'fdp',
            'save-posting-link' => 'posting-link',
            'approve' => $documentType !== '' ? $documentType : null,
            'save-remarks' => $documentType !== '' ? $documentType : null,
            'delete-document' => $documentType !== '' ? $documentType : null,
        ];

        if (
            count($notificationSegments) >= 3
            && ($notificationSegments[0] ?? null) === 'fund-utilization'
            && array_key_exists((string) ($notificationSegments[2] ?? ''), $fundUtilizationActionDocumentMap)
        ) {
            $redirectParameters = ['projectCode' => urldecode((string) $notificationSegments[1])];
            $actionSegment = (string) ($notificationSegments[2] ?? '');

            if (in_array($actionSegment, ['approve', 'save-remarks', 'delete-document'], true) && $documentType === '' && ! empty($notificationSegments[3] ?? null)) {
                $documentType = urldecode((string) $notificationSegments[3]);
            }

            if ($quarter !== '') {
                $redirectParameters['quarter'] = $quarter;
            } elseif (in_array($actionSegment, ['approve', 'save-remarks', 'delete-document'], true) && ! empty($notificationSegments[4] ?? null)) {
                $redirectParameters['quarter'] = urldecode((string) $notificationSegments[4]);
            }

            $redirectDocumentType = $documentType !== ''
                ? $documentType
                : ($fundUtilizationActionDocumentMap[$actionSegment] ?? null);

            if ($redirectDocumentType !== null && $redirectDocumentType !== '') {
                if ($redirectDocumentType === 'written-notice') {
                    $redirectDocumentType = 'written-notice-dbm';
                }

                $redirectParameters['document'] = $redirectDocumentType;
            }

            return redirect()->route('fund-utilization.show', $redirectParameters);
        }

        return redirect($notificationUrl ?: route('fund-utilization.index'));
    })->name('notifications.read');

    Route::post('/notifications/reset-counter', function () {
        $userId = Auth::id();
        if ($userId) {
            Cache::forever('notifications.bell-counter-reset.'.$userId, now()->toIso8601String());
        }

        return response()->json([
            'success' => true,
        ]);
    })->name('notifications.reset-counter');

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Project Dashboard Module
    require base_path('routes/modules/dashboard.php');
    // Users & User Management Module
    require base_path('routes/modules/users.php');

    // Ticketing Module
    require base_path('routes/modules/ticketing.php');

    // Activity Logs Module
    require base_path('routes/modules/activity_logs.php');

    // System Settings & Utilities Module
    require base_path('routes/modules/system_settings.php');

    // DILG Deliverables Module
    require base_path('routes/modules/dilg_deliverables.php');

    // Fund Utilization Module
    require base_path('routes/modules/fund_utilization.php');

    // LGU Reportorial Requirements Module
    require base_path('routes/modules/reportorial.php');

    // Documents (NADAI) Module
    require base_path('routes/modules/documents.php');

    // Task Management Module
    require base_path('routes/modules/task_management.php');

    // Announcement Management Module
    require base_path('routes/modules/announcements.php');
});
