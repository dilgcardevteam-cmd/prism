<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\LocallyFundedProject;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Controllers\RlipLimeProjectController;
use App\Http\Controllers\SglgifProjectController;
use App\Http\Controllers\SystemManagementController;

// Authentication Module
require base_path('routes/modules/auth.php');

// Projects & Data Ingestion Module
require base_path('routes/modules/projects.php');

// Communications & Messaging Module
require base_path('routes/modules/communications.php');

Route::get('/', function () {
    return view('landing');
})->name('landing');

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
        $notificationQuery = \Illuminate\Support\Facades\DB::table('tbnotifications')->where('id', $id);
        $authUser = \Illuminate\Support\Facades\Auth::user();

        if ($authUser instanceof \App\Models\User && $authUser->isRegionalUser()) {
            $notificationQuery->whereIn('user_id', \App\Support\NotificationCenter::regionalPoolUserIds($authUser));
        } else {
            $notificationQuery->where('user_id', \Illuminate\Support\Facades\Auth::id());
        }

        $notification = $notificationQuery->first();

        if (!$notification) {
            if (is_string($id) && str_starts_with($id, 'returned-fund-utilization-')) {
                $workflowId = (int) \Illuminate\Support\Str::after($id, 'returned-fund-utilization-');
                $workflow = \Illuminate\Support\Facades\DB::table('fund_utilization_approval_workflows')->where('id', $workflowId)->first();
                if ($workflow) {
                    \Illuminate\Support\Facades\DB::table('tbnotifications')
                        ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                        ->where(function ($q) use ($workflow) {
                            $q->where('url', 'like', '%' . $workflow->project_code . '%')
                              ->orWhere('message', 'like', '%' . $workflow->project_code . '%');
                        })
                        ->update(['read_at' => now(), 'updated_at' => now()]);

                    return redirect()->route('fund-utilization.show', [
                        'projectCode' => $workflow->project_code,
                        'quarter' => $workflow->quarter,
                        'document' => $workflow->document_type,
                    ]);
                }
            } elseif (is_string($id) && str_starts_with($id, 'returned-')) {
                $remainder = \Illuminate\Support\Str::after($id, 'returned-');
                $lastDashPos = strrpos($remainder, '-');
                if ($lastDashPos !== false) {
                    $table = substr($remainder, 0, $lastDashPos);
                    $recordId = (int) substr($remainder, $lastDashPos + 1);
                    if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                        $record = \Illuminate\Support\Facades\DB::table($table)->where('id', $recordId)->first();
                        if ($record) {
                            $poolService = app(\App\Services\RegionalApprovalPoolService::class);
                            $refClass = new \ReflectionClass($poolService);
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

                                    \Illuminate\Support\Facades\DB::table('tbnotifications')
                                        ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                                        ->where('url', 'like', '%' . $officeOrProject . '%')
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

        $sharedReadQuery = \Illuminate\Support\Facades\DB::table('tbnotifications')
            ->where('url', $notification->url)
            ->where('document_type', $notification->document_type);

        if ($notification->quarter === null) {
            $sharedReadQuery->whereNull('quarter');
        } else {
            $sharedReadQuery->where('quarter', $notification->quarter);
        }

        if ($authUser instanceof \App\Models\User && $authUser->isRegionalUser()) {
            $sharedReadQuery->whereIn('user_id', \App\Support\NotificationCenter::regionalPoolUserIds($authUser));
        } else {
            $sharedReadQuery->where('user_id', \Illuminate\Support\Facades\Auth::id());
        }

        $sharedReadQuery->update(['read_at' => now(), 'updated_at' => now()]);

        $notificationUrl = \App\Support\NotificationUrl::normalizeForRedirect($notification->url);
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

            if (in_array($actionSegment, ['approve', 'save-remarks', 'delete-document'], true) && $documentType === '' && !empty($notificationSegments[3] ?? null)) {
                $documentType = urldecode((string) $notificationSegments[3]);
            }

            if ($quarter !== '') {
                $redirectParameters['quarter'] = $quarter;
            } elseif (in_array($actionSegment, ['approve', 'save-remarks', 'delete-document'], true) && !empty($notificationSegments[4] ?? null)) {
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
        $userId = \Illuminate\Support\Facades\Auth::id();
        if ($userId) {
            \Illuminate\Support\Facades\Cache::forever('notifications.bell-counter-reset.' . $userId, now()->toIso8601String());
        }

        return response()->json([
            'success' => true,
        ]);
    })->name('notifications.reset-counter');

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

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
});
