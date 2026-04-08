<?php

namespace App\Http\Controllers;

use App\Mail\AutomatedDatabaseBackupMail;
use App\Mail\BulkNotificationMail;
use App\Models\BackupAutomationSetting;
use App\Models\DatabaseBackupRun;
use App\Models\LguReportorialDeadline;
use App\Models\RolePermissionSetting;
use App\Models\User;
use App\Models\UserRole;
use App\Services\DatabaseBackupService;
use App\Support\InputSanitizer;
use App\Support\NotificationUrl;
use App\Support\RolePermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DatabaseUtilityController extends Controller
{
    private const LOCATION_IMPORT_HISTORY_TABLE = 'location_configuration_import_histories';
    private const LOCATION_IMPORT_STORAGE_DIRECTORY = 'location-configuration-imports';

    private const LOCATION_DATASETS = [
        'regions' => [
            'key' => 'regions',
            'label' => 'Region',
            'table' => 'location_regions',
            'load_tables' => ['location_regions', 'regions'],
            'icon' => 'fas fa-globe-asia',
            'description' => 'Upload the regional reference list as a CSV snapshot.',
            'columns' => ['region_code', 'region_name'],
            'required' => ['region_name'],
            'sql_aliases' => ['location_regions', 'regions', 'region'],
            'aliases' => [
                'region' => 'region_name',
                'name' => 'region_name',
                'region_name' => 'region_name',
                'description' => 'region_name',
                'code' => 'region_code',
                'region_code' => 'region_code',
                'reg_code' => 'region_code',
            ],
        ],
        'provinces' => [
            'key' => 'provinces',
            'label' => 'Provinces',
            'table' => 'location_provinces',
            'load_tables' => ['location_provinces'],
            'icon' => 'fas fa-map',
            'description' => 'Upload the province master list as a CSV file using the region reference ID, code, or name plus province code and name.',
            'columns' => ['region_id', 'province_code', 'province_name'],
            'required' => ['province_name'],
            'integer_columns' => ['region_id'],
            'source_columns' => ['region_lookup_code', 'region_lookup_name'],
            'sql_aliases' => ['location_provinces', 'provinces', 'province'],
            'aliases' => [
                'region_id' => 'region_id',
                'reg_id' => 'region_id',
                'regionid' => 'region_id',
                'region_code' => 'region_lookup_code',
                'reg_code' => 'region_lookup_code',
                'region_name' => 'region_lookup_name',
                'region' => 'region_lookup_name',
                'province' => 'province_name',
                'province_name' => 'province_name',
                'prov_name' => 'province_name',
                'name' => 'province_name',
                'province_code' => 'province_code',
                'prov_code' => 'province_code',
                'code' => 'province_code',
            ],
        ],
        'city-municipalities' => [
            'key' => 'city-municipalities',
            'label' => 'City / Municipality',
            'table' => 'location_city_municipalities',
            'load_tables' => ['location_city_municipalities'],
            'icon' => 'fas fa-city',
            'description' => 'Upload the city or municipality list as a CSV file using the province reference ID plus city or municipality code and name.',
            'columns' => ['province_id', 'citymun_code', 'citymun_name'],
            'required' => ['citymun_name'],
            'integer_columns' => ['province_id'],
            'sql_aliases' => ['location_city_municipalities', 'city_municipalities', 'cities', 'municipalities', 'city_municipality'],
            'aliases' => [
                'province_id' => 'province_id',
                'prov_id' => 'province_id',
                'provinceid' => 'province_id',
                'citymun_code' => 'citymun_code',
                'city_municipality_code' => 'citymun_code',
                'municipality_code' => 'citymun_code',
                'city_code' => 'citymun_code',
                'code' => 'citymun_code',
                'citymun_name' => 'citymun_name',
                'city' => 'citymun_name',
                'city_name' => 'citymun_name',
                'municipality' => 'citymun_name',
                'municipality_name' => 'citymun_name',
                'city_municipality' => 'citymun_name',
                'city_municipality_name' => 'citymun_name',
                'name' => 'citymun_name',
            ],
        ],
    ];

    public function __construct(
        private readonly DatabaseBackupService $backupService,
    ) {
        $this->middleware('auth');
        $this->middleware('superadmin');
    }

    public function index(): View
    {
        $connection = $this->backupService->databaseConnection();
        $automationSetting = BackupAutomationSetting::query()->first();

        return view('admin.utilities.backup-and-restore', [
            'databaseName' => $connection['database'],
            'databaseHost' => $connection['host'],
            'automationSetting' => $automationSetting,
            'recentBackupRuns' => DatabaseBackupRun::query()
                ->orderByDesc('started_at')
                ->limit(10)
                ->get(),
            'dayOptions' => [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            ],
            'nextScheduledRun' => $this->nextScheduledRun($automationSetting),
        ]);
    }

    public function systemSetup(): View
    {
        $user = Auth::user();

        $systemSetupItems = collect([
            [
                'icon' => 'fas fa-user-shield',
                'title' => 'Role Configuration',
                'description' => 'Configure CRUD access by hierarchy role and apply it to every assigned user from one place.',
                'route' => route('utilities.role-configuration.index'),
                'visible' => $user?->isSuperAdmin() ?? false,
            ],
            [
                'icon' => 'fas fa-map-marker-alt',
                'title' => 'Location Configuration',
                'description' => 'Review and manage the location-related configuration used across the application.',
                'route' => route('utilities.location-configuration.index'),
                'visible' => $user?->hasCrudPermission('utilities_location_configuration', 'view') ?? false,
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'title' => 'Deadlines Configuration',
                'description' => 'Review and maintain deadline settings used across project monitoring and reportorial workflows.',
                'route' => route('utilities.deadlines-configuration.index'),
                'visible' => $user?->hasCrudPermission('utilities_deadlines_configuration', 'view') ?? false,
            ],
            [
                'icon' => 'fas fa-bell',
                'title' => 'Bulk Notification',
                'description' => 'Send announcement emails to role-based audiences and review the notification workspace.',
                'route' => route('utilities.notifications.index'),
                'visible' => $user?->hasCrudPermission('utilities_bulk_notifications', 'view') ?? false,
            ],
            [
                'icon' => 'fas fa-database',
                'title' => 'Database and Backups',
                'description' => 'Download SQL backups, restore data, and maintain automated backup routines.',
                'route' => route('utilities.backup-and-restore.index'),
                'visible' => $user?->hasCrudPermission('utilities_backup_restore', 'view') ?? false,
            ],
        ])
            ->filter(fn (array $item): bool => $item['visible'])
            ->map(function (array $item): array {
                unset($item['visible']);

                return $item;
            })
            ->values()
            ->all();

        return view('admin.utilities.system-setup', [
            'systemSetupItems' => $systemSetupItems,
        ]);
    }

    public function notifications(): View
    {
        $activeUsers = User::query()
            ->select(['idno', 'fname', 'lname', 'username', 'emailaddress', 'role', 'province', 'office'])
            ->whereRaw('LOWER(TRIM(COALESCE(status, ""))) = ?', ['active'])
            ->orderByRaw("
                CASE LOWER(TRIM(COALESCE(role, '')))
                    WHEN '" . User::ROLE_SUPERADMIN . "' THEN 1
                    WHEN '" . User::ROLE_REGIONAL . "' THEN 2
                    WHEN '" . User::ROLE_PROVINCIAL . "' THEN 3
                    WHEN '" . User::ROLE_MLGOO . "' THEN 4
                    WHEN '" . User::ROLE_LGU . "' THEN 5
                    ELSE 6
                END
            ")
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();

        $roleGroups = collect(User::roleOptions())
            ->map(function (string $label, string $role) use ($activeUsers): array {
                return [
                    'role' => $role,
                    'label' => $label,
                    'count' => $activeUsers->filter(function (User $user) use ($role): bool {
                        return $user->normalizedRole() === $role;
                    })->count(),
                ];
            })
            ->values();

        return view('admin.utilities.notifications', [
            'activeUsers' => $activeUsers,
            'roleGroups' => $roleGroups,
            'roleOptions' => User::roleOptions(),
            'totalActiveUsers' => $activeUsers->count(),
        ]);
    }

    public function deadlinesConfiguration(): View
    {
        return view('admin.utilities.deadlines-configuration', [
            'deadlineCards' => [
                [
                    'icon' => 'fas fa-landmark',
                    'icon_background' => '#ede9fe',
                    'icon_color' => '#6d28d9',
                    'title' => 'LGU Reportorial Requirements',
                    'description' => 'Container for LGU reportorial requirement items and future commands.',
                    'route' => route('utilities.deadlines-configuration.lgu-reportorial'),
                ],
                [
                    'icon' => 'fas fa-file-signature',
                    'icon_background' => '#dcfce7',
                    'icon_color' => '#166534',
                    'title' => 'DILG Reportorial Requirements',
                    'description' => 'Container for DILG reportorial requirement items and future commands.',
                    'route' => route('utilities.deadlines-configuration.dilg-reportorial'),
                ],
            ],
        ]);
    }

    public function lguReportorialRequirements(): View
    {
        [$savedDeadlines, $latestDeadlinesByAspect] = $this->lguReportorialDeadlineData();

        return view('admin.utilities.lgu-reportorial-requirements', [
            'timelineCards' => $this->buildLguReportorialTimelineCards($latestDeadlinesByAspect),
            'savedDeadlines' => $savedDeadlines,
            'deadlineSaveUrl' => route('utilities.deadlines-configuration.lgu-reportorial.store'),
        ]);
    }

    public function storeLguReportorialDeadline(Request $request): JsonResponse
    {
        if (!Schema::hasTable('lgu_reportorial_deadlines')) {
            return response()->json([
                'message' => 'LGU reportorial deadlines table is not available yet. Run the migration first.',
            ], 409);
        }

        $itemsByAspect = $this->lguReportorialAspectDefinitions();

        $validated = $request->validate([
            'aspect' => ['required', 'string', Rule::in(array_keys($itemsByAspect))],
            'reporting_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'reporting_period' => ['required', 'string', 'max:20'],
            'deadline_date' => ['required', 'date'],
            'deadline_time' => ['required', 'date_format:H:i'],
        ]);

        $aspect = strtolower(trim((string) $validated['aspect']));
        $definition = $itemsByAspect[$aspect] ?? null;
        if (!is_array($definition)) {
            return response()->json([
                'message' => 'Invalid LGU reportorial requirement selected.',
            ], 422);
        }

        $timeline = strtolower(trim((string) ($definition['timeline'] ?? '')));
        $allowedPeriods = $this->lguReportorialAllowedPeriods($timeline);
        $reportingPeriod = trim((string) $validated['reporting_period']);

        if (!in_array($reportingPeriod, $allowedPeriods, true)) {
            return response()->json([
                'message' => 'Invalid reporting period selected for this requirement.',
                'errors' => [
                    'reporting_period' => ['The selected reporting period is not allowed for this requirement.'],
                ],
            ], 422);
        }

        $record = LguReportorialDeadline::query()->updateOrCreate(
            [
                'aspect' => $aspect,
                'reporting_year' => (int) $validated['reporting_year'],
                'reporting_period' => $reportingPeriod,
            ],
            [
                'timeline' => $timeline,
                'deadline_date' => $validated['deadline_date'],
                'deadline_time' => $validated['deadline_time'] . ':00',
                'updated_by' => Auth::id(),
            ]
        );

        $deadlineDate = $record->deadline_date instanceof Carbon
            ? $record->deadline_date->format('Y-m-d')
            : (string) $record->deadline_date;
        $deadlineTime = $this->normalizeLguReportorialDeadlineTime($record->deadline_time);
        $updatedByUser = Auth::user();

        return response()->json([
            'message' => 'Deadline saved successfully.',
            'record' => [
                'id' => (int) $record->id,
                'aspect' => $aspect,
                'timeline' => $timeline,
                'reporting_year' => (int) $record->reporting_year,
                'reporting_period' => (string) $record->reporting_period,
                'deadline_date' => $deadlineDate,
                'deadline_time' => $deadlineTime,
                'deadline_display' => $this->formatLguReportorialDeadlineDisplay($deadlineDate, $deadlineTime),
                'updated_at' => $record->updated_at?->toIso8601String(),
                'updated_at_display' => $this->formatLguReportorialUpdatedAt($record->updated_at),
                'updated_by' => $record->updated_by ? (int) $record->updated_by : null,
                'updated_by_name' => $this->formatLguReportorialUserName($updatedByUser instanceof User ? $updatedByUser : null),
            ],
            'status_text' => $this->lguReportorialDeadlineStatusText(
                $deadlineDate,
                $deadlineTime,
                (string) $record->reporting_period,
                (int) $record->reporting_year
            ),
        ]);
    }

    public function dilgReportorialRequirements(): View
    {
        return view('admin.utilities.dilg-reportorial-requirements');
    }

    private function buildLguReportorialTimelineCards(array $latestDeadlinesByAspect = []): array
    {
        $timelineCards = $this->lguReportorialTimelineDefinitions();
        $itemsByTimeline = collect($this->lguReportorialItems())
            ->groupBy('timeline');

        return collect($timelineCards)
            ->map(function (array $card, string $timeline) use ($itemsByTimeline, $latestDeadlinesByAspect): array {
                return [
                    ...$card,
                    'items' => $itemsByTimeline->get($timeline, collect())
                        ->map(function (array $item) use ($latestDeadlinesByAspect): array {
                            $latestDeadline = $latestDeadlinesByAspect[$item['aspect']] ?? null;

                            return [
                                ...$item,
                                'saved_status_text' => is_array($latestDeadline)
                                    ? (string) ($latestDeadline['status_text'] ?? '')
                                    : '',
                                'saved_period' => is_array($latestDeadline)
                                    ? (string) ($latestDeadline['reporting_period'] ?? '')
                                    : '',
                                'saved_year' => is_array($latestDeadline)
                                    ? (int) ($latestDeadline['reporting_year'] ?? 0)
                                    : null,
                                'saved_date' => is_array($latestDeadline)
                                    ? (string) ($latestDeadline['deadline_date'] ?? '')
                                    : '',
                                'saved_time' => is_array($latestDeadline)
                                    ? (string) ($latestDeadline['deadline_time'] ?? '')
                                    : '',
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function lguReportorialTimelineDefinitions(): array
    {
        return [
            'annual' => [
                'badge' => 'Annual',
                'icon' => 'fas fa-calendar-check',
                'title' => 'Annual Requirements',
                'description' => 'Use this section for reportorial requirements submitted once per year.',
            ],
            'quarterly' => [
                'badge' => 'Quarterly',
                'icon' => 'fas fa-chart-line',
                'title' => 'Quarterly Requirements',
                'description' => 'Use this section for reportorial requirements submitted every quarter.',
            ],
            'monthly' => [
                'badge' => 'Monthly',
                'icon' => 'fas fa-calendar-day',
                'title' => 'Monthly Requirements',
                'description' => 'Use this section for recurring reportorial requirements submitted each month.',
            ],
        ];
    }

    private function lguReportorialItems(): array
    {
        $module = collect(RolePermissionRegistry::modules())
            ->first(fn (array $entry) => strtolower(trim((string) ($entry['module'] ?? ''))) === 'lgu reportorial requirements');

        $timelineCards = $this->lguReportorialTimelineDefinitions();

        $routeMap = [
            'rbis_annual_certification' => [
                'route' => route('rbis-annual-certification.index'),
                'icon' => 'fas fa-bridge',
            ],
            'fund_utilization_reports' => [
                'route' => route('fund-utilization.index'),
                'icon' => 'fas fa-coins',
            ],
            'local_project_monitoring_committee' => [
                'route' => route('local-project-monitoring-committee.index'),
                'icon' => 'fas fa-users-cog',
            ],
            'road_maintenance_status_reports' => [
                'route' => route('road-maintenance-status.index'),
                'icon' => 'fas fa-road',
            ],
            'pd_no_pbbm_monthly_reports' => [
                'route' => route('reports.monthly.pd-no-pbbm-2025-1572-1573'),
                'icon' => 'fas fa-file-alt',
            ],
            'swa_annex_f_monthly_reports' => [
                'route' => route('reports.monthly.swa-annex-f'),
                'icon' => 'fas fa-award',
            ],
        ];

        return collect($module['items'] ?? [])
            ->map(function (array $item) use ($routeMap): ?array {
                $label = trim((string) ($item['label'] ?? ''));
                if ($label === '') {
                    return null;
                }

                [$timelineLabel, $itemLabel] = array_pad(explode(' / ', $label, 2), 2, '');
                $timelineKey = strtolower(trim($timelineLabel));
                $aspect = strtolower(trim((string) ($item['aspect'] ?? '')));
                $routeMeta = $routeMap[$aspect] ?? null;

                return [
                    'timeline' => $timelineKey,
                    'aspect' => $aspect,
                    'label' => trim($itemLabel) !== '' ? trim($itemLabel) : $label,
                    'description' => trim((string) ($item['description'] ?? '')),
                    'route' => $routeMeta['route'] ?? null,
                    'icon' => $routeMeta['icon'] ?? 'fas fa-file-circle-check',
                ];
            })
            ->filter(fn (?array $item) => $item !== null && array_key_exists($item['timeline'], $timelineCards))
            ->values()
            ->all();
    }

    private function lguReportorialAspectDefinitions(): array
    {
        return collect($this->lguReportorialItems())
            ->keyBy(fn (array $item) => (string) ($item['aspect'] ?? ''))
            ->all();
    }

    private function lguReportorialAllowedPeriods(string $timeline): array
    {
        return match (strtolower(trim($timeline))) {
            'quarterly' => ['Q1', 'Q2', 'Q3', 'Q4'],
            'annual' => ['Annual'],
            'monthly' => $this->lguReportorialMonthlyPeriods(),
            default => [],
        };
    }

    private function lguReportorialMonthlyPeriods(): array
    {
        return [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    }

    private function lguReportorialDeadlineData(): array
    {
        if (!Schema::hasTable('lgu_reportorial_deadlines')) {
            return [[], []];
        }

        $savedDeadlines = [];
        $latestDeadlinesByAspect = [];

        $records = LguReportorialDeadline::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $updaterIds = $records
            ->pluck('updated_by')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();
        $updatersById = $updaterIds->isEmpty()
            ? collect()
            : User::query()
                ->select(['idno', 'fname', 'lname'])
                ->whereIn('idno', $updaterIds->all())
                ->get()
                ->keyBy('idno');

        foreach ($records as $record) {
            $aspect = strtolower(trim((string) $record->aspect));
            $period = trim((string) $record->reporting_period);
            $year = (int) $record->reporting_year;
            $deadlineDate = $record->deadline_date instanceof Carbon
                ? $record->deadline_date->format('Y-m-d')
                : trim((string) $record->deadline_date);
            $deadlineTime = $this->normalizeLguReportorialDeadlineTime($record->deadline_time);

            if ($aspect === '' || $period === '' || $year < 2020 || $deadlineDate === '') {
                continue;
            }

            $entry = [
                'id' => (int) $record->id,
                'aspect' => $aspect,
                'timeline' => (string) $record->timeline,
                'reporting_year' => $year,
                'reporting_period' => $period,
                'deadline_date' => $deadlineDate,
                'deadline_time' => $deadlineTime,
                'deadline_display' => $this->formatLguReportorialDeadlineDisplay($deadlineDate, $deadlineTime),
                'updated_at' => $record->updated_at?->toIso8601String(),
                'updated_at_display' => $this->formatLguReportorialUpdatedAt($record->updated_at),
                'updated_by' => $record->updated_by ? (int) $record->updated_by : null,
                'updated_by_name' => $this->formatLguReportorialUserName(
                    $record->updated_by ? $updatersById->get((int) $record->updated_by) : null
                ),
            ];

            $savedDeadlines[$aspect][(string) $year][$period] = $entry;

            if (!array_key_exists($aspect, $latestDeadlinesByAspect)) {
                $latestDeadlinesByAspect[$aspect] = [
                    ...$entry,
                    'status_text' => $this->lguReportorialDeadlineStatusText($deadlineDate, $deadlineTime, $period, $year),
                ];
            }
        }

        return [$savedDeadlines, $latestDeadlinesByAspect];
    }

    private function lguReportorialDeadlineStatusText(string $deadlineDate, string $deadlineTime, string $period, int $year): string
    {
        return 'Saved deadline: '
            . $this->formatLguReportorialDeadlineDisplay($deadlineDate, $deadlineTime)
            . ' (' . $period . ', CY ' . $year . ')';
    }

    private function formatLguReportorialUpdatedAt(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('M j, Y h:i A');
        }

        return '';
    }

    private function formatLguReportorialUserName(?User $user): string
    {
        if (!$user) {
            return '';
        }

        $fullName = trim(implode(' ', array_filter([
            trim((string) $user->fname),
            trim((string) $user->lname),
        ])));

        return $fullName !== '' ? $fullName : trim((string) $user->username);
    }

    private function normalizeLguReportorialDeadlineTime(mixed $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->format('H:i');
            } catch (\Throwable) {
                continue;
            }
        }

        return '';
    }

    private function formatLguReportorialDeadlineDisplay(string $deadlineDate, string $deadlineTime = ''): string
    {
        try {
            $formattedDate = Carbon::parse($deadlineDate)->format('M j, Y');
        } catch (\Throwable) {
            $formattedDate = $deadlineDate;
        }

        if ($deadlineTime === '') {
            return $formattedDate;
        }

        try {
            $formattedTime = Carbon::createFromFormat('H:i', $deadlineTime)->format('h:i A');
        } catch (\Throwable) {
            return $formattedDate;
        }

        return $formattedDate . ' ' . $formattedTime;
    }

    public function sendBulkNotification(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('tbnotifications')) {
            return redirect()
                ->route('utilities.notifications.index')
                ->with('error', 'System notifications table is not available yet. Run the migration first.');
        }

        $validated = $request->validate([
            'target_scope' => ['required', 'in:selected_users,selected_role,all_users'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:tbusers,idno'],
            'role' => ['nullable', 'in:' . implode(',', array_keys(User::roleOptions()))],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
            'redirect_path' => ['nullable', 'string', 'max:255'],
        ]);

        $targetScope = (string) $validated['target_scope'];
        $selectedUserIds = array_values(array_unique(array_map('intval', $validated['user_ids'] ?? [])));
        $selectedRole = isset($validated['role']) ? strtolower(trim((string) $validated['role'])) : null;
        $title = InputSanitizer::sanitizePlainText((string) $validated['title']);
        $message = InputSanitizer::sanitizePlainText((string) $validated['message'], true);
        $redirectPath = InputSanitizer::sanitizeInternalRedirect($validated['redirect_path'] ?? null);

        if ($title === '') {
            return back()
                ->withErrors(['title' => 'Enter a notification title.'])
                ->withInput();
        }

        if ($message === '') {
            return back()
                ->withErrors(['message' => 'Enter the message to broadcast.'])
                ->withInput();
        }

        if ($targetScope === 'selected_users' && $selectedUserIds === []) {
            return back()
                ->withErrors(['user_ids' => 'Select at least one active recipient.'])
                ->withInput();
        }

        if ($targetScope === 'selected_role' && (!$selectedRole || !array_key_exists($selectedRole, User::roleOptions()))) {
            return back()
                ->withErrors(['role' => 'Choose the user level that should receive the broadcast.'])
                ->withInput();
        }

        if ($request->filled('redirect_path') && $redirectPath === null) {
            return back()
                ->withErrors(['redirect_path' => 'Use an internal path that starts with /. Example: /dashboard'])
                ->withInput();
        }

        $recipients = $this->resolveBulkNotificationRecipients(
            targetScope: $targetScope,
            selectedUserIds: $selectedUserIds,
            selectedRole: $selectedRole,
        );

        if ($recipients->isEmpty()) {
            return back()
                ->with('error', 'No active users matched the selected audience.')
                ->withInput();
        }

        $sender = auth()->user();
        $senderId = $sender?->idno ? (int) $sender->idno : null;
        $senderName = trim(implode(' ', array_filter([
            $sender?->fname,
            $sender?->lname,
        ])));
        if ($senderName === '') {
            $senderName = trim((string) ($sender?->username ?? 'PDMU PDMUOMS'));
        }

        $actionUrl = $redirectPath ? url($redirectPath) : route('dashboard', [], false);
        $notificationUrl = NotificationUrl::normalizeForStorage($actionUrl);
        $systemMessage = $this->formatBulkNotificationSystemMessage($title, $message);
        $now = now();

        foreach (array_chunk($recipients->map(function (User $recipient) use ($systemMessage, $notificationUrl, $now, $senderId, $senderName): array {
            return [
                'user_id' => (int) $recipient->idno,
                'sender_user_id' => $senderId,
                'sender_name' => $senderName,
                'message' => $systemMessage,
                'url' => $notificationUrl,
                'document_type' => 'bulk-notification',
                'quarter' => null,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all(), 500) as $notificationChunk) {
            DB::table('tbnotifications')->insert($notificationChunk);
        }

        $emailedCount = 0;
        $emailFailedCount = 0;
        $emailSkippedCount = 0;

        foreach ($recipients as $recipient) {
            $emailAddress = strtolower(trim((string) $recipient->emailaddress));

            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $emailSkippedCount++;
                continue;
            }

            try {
                Mail::to($emailAddress)->send(new BulkNotificationMail(
                    recipient: $recipient,
                    titleText: $title,
                    messageText: $message,
                    actionUrl: $actionUrl,
                    senderName: $senderName,
                ));
                $emailedCount++;
            } catch (\Throwable $exception) {
                $emailFailedCount++;

                Log::warning('Bulk notification email delivery failed.', [
                    'recipient_id' => $recipient->idno,
                    'email' => $emailAddress,
                    'target_scope' => $targetScope,
                    'selected_role' => $selectedRole,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $summaryParts = [
            'System notifications were sent to ' . number_format($recipients->count()) . ' active recipient(s).',
        ];

        if ($emailedCount > 0) {
            $summaryParts[] = 'Email was delivered to ' . number_format($emailedCount) . ' recipient(s).';
        }

        if ($emailSkippedCount > 0) {
            $summaryParts[] = number_format($emailSkippedCount) . ' recipient(s) were skipped because no valid email address was available.';
        }

        if ($emailFailedCount > 0) {
            $summaryParts[] = 'Email delivery failed for ' . number_format($emailFailedCount) . ' recipient(s). Check the mail configuration or logs.';
        }

        $summaryParts[] = 'Audience: ' . $this->bulkNotificationAudienceLabel($targetScope, $selectedRole) . '.';

        return redirect()
            ->route('utilities.notifications.index')
            ->with('success', implode(' ', $summaryParts));
    }

    public function roleConfiguration(): View
    {
        return view('admin.utilities.role-configuration', [
            'crudActionOptions' => RolePermissionRegistry::actionOptions(),
            'accessGrantModules' => RolePermissionRegistry::modules(),
            'roleDescriptions' => RolePermissionRegistry::roleDescriptions(),
            'roleConfigurations' => $this->buildRoleConfigurations(),
            'roleManagementCards' => $this->buildRoleManagementCards(),
        ]);
    }

    public function storeRoleDefinition(Request $request): RedirectResponse|JsonResponse
    {
        $payload = $this->validatedRoleDefinitionPayload($request);
        $roleKey = UserRole::generateUniqueRoleKey($payload['label']);

        $roleDefinition = UserRole::query()->create([
            'role_key' => $roleKey,
            'label' => $payload['label'],
            'base_role' => '',
            'description' => $payload['description'],
        ]);

        UserRole::flushRoleCache();
        RolePermissionSetting::flushPermissionsCache();

        $redirectUrl = route('utilities.role-configuration.index', ['role' => $roleDefinition->role_key]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role created successfully.',
                'role' => $roleDefinition->role_key,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Role created successfully.');
    }

    public function updateRoleDefinition(Request $request, string $role): RedirectResponse|JsonResponse
    {
        $normalizedRole = strtolower(trim($role));
        $isBuiltInRole = array_key_exists($normalizedRole, User::builtInRoleOptions());

        if ($normalizedRole === User::ROLE_SUPERADMIN) {
            return $this->roleDefinitionRejectedResponse($request, 'Superadmin cannot be edited here.');
        }

        if (!$isBuiltInRole && !UserRole::query()->where('role_key', $normalizedRole)->exists()) {
            abort(404);
        }

        $roleDefinition = $isBuiltInRole
            ? UserRole::query()->firstOrNew(['role_key' => $normalizedRole])
            : $this->findCustomRoleDefinition($role);
        $payload = $this->validatedRoleDefinitionPayload($request, $roleDefinition);

        $roleDefinition->fill([
            'role_key' => $normalizedRole,
            'label' => $payload['label'],
            'base_role' => '',
            'description' => $payload['description'],
            'is_active' => true,
        ])->save();

        UserRole::flushRoleCache();
        RolePermissionSetting::flushPermissionsCache();

        $redirectUrl = route('utilities.role-configuration.index', ['role' => $roleDefinition->role_key]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role updated successfully.',
                'role' => $roleDefinition->role_key,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Role updated successfully.');
    }

    public function destroyRoleDefinition(Request $request, string $role): RedirectResponse|JsonResponse
    {
        $normalizedRole = strtolower(trim($role));
        $isBuiltInRole = array_key_exists($normalizedRole, User::builtInRoleOptions());

        if ($normalizedRole === User::ROLE_SUPERADMIN) {
            return $this->roleDefinitionRejectedResponse($request, 'Superadmin cannot be deleted here.');
        }

        $roleDefinition = $isBuiltInRole
            ? UserRole::query()->firstOrNew(['role_key' => $normalizedRole])
            : $this->findCustomRoleDefinition($role);
        $assignedUsersCount = User::query()
            ->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [$normalizedRole])
            ->count();

        if ($assignedUsersCount > 0) {
            $message = 'Reassign the ' . number_format($assignedUsersCount) . ' user(s) assigned to this role before deleting it.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', $message);
        }

        RolePermissionSetting::query()
            ->where('role', $normalizedRole)
            ->delete();

        if ($isBuiltInRole) {
            $roleDefinition->fill([
                'role_key' => $normalizedRole,
                'label' => User::allRoleOptions()[$normalizedRole] ?? (User::builtInRoleOptions()[$normalizedRole] ?? $normalizedRole),
                'base_role' => '',
                'description' => RolePermissionRegistry::roleDescriptions()[$normalizedRole] ?? UserRole::defaultDescriptionFor(),
                'is_active' => false,
            ])->save();
        } else {
            $roleDefinition->delete();
        }

        UserRole::flushRoleCache();
        RolePermissionSetting::flushPermissionsCache();

        $nextRole = collect(RolePermissionRegistry::configurableRoles())->first() ?? User::ROLE_REGIONAL;
        $redirectUrl = route('utilities.role-configuration.index', ['role' => $nextRole]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role deleted successfully.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Role deleted successfully.');
    }

    public function updateRoleConfiguration(Request $request, string $role): RedirectResponse|JsonResponse
    {
        $normalizedRole = strtolower(trim($role));

        if (!in_array($normalizedRole, RolePermissionRegistry::configurableRoles(), true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'That role cannot be configured from Role Configuration.',
                ], 422);
            }

            return redirect()
                ->route('utilities.role-configuration.index')
                ->with('error', 'That role cannot be configured from Role Configuration.');
        }

        $validated = $request->validate([
            'crud_permissions' => ['nullable', 'array'],
            'crud_permissions.*' => ['string'],
        ]);

        $permissions = RolePermissionRegistry::normalizePermissions($validated['crud_permissions'] ?? []);

        RolePermissionSetting::query()->updateOrCreate(
            ['role' => $normalizedRole],
            ['permissions' => $permissions],
        );

        RolePermissionSetting::flushPermissionsCache();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role configuration updated successfully.',
                'role' => $normalizedRole,
                'uses_recommended_defaults' => false,
                'permissions' => RolePermissionRegistry::permissionsForRole($normalizedRole, $permissions),
            ]);
        }

        return redirect()
            ->route('utilities.role-configuration.index', ['role' => $normalizedRole])
            ->with('success', 'Role configuration updated successfully.');
    }

    public function resetRoleConfiguration(Request $request, string $role): RedirectResponse|JsonResponse
    {
        $normalizedRole = strtolower(trim($role));

        if (!in_array($normalizedRole, RolePermissionRegistry::configurableRoles(), true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'That role cannot be configured from Role Configuration.',
                ], 422);
            }

            return redirect()
                ->route('utilities.role-configuration.index')
                ->with('error', 'That role cannot be configured from Role Configuration.');
        }

        RolePermissionSetting::query()
            ->where('role', $normalizedRole)
            ->delete();

        RolePermissionSetting::flushPermissionsCache();

        $defaultPermissions = RolePermissionRegistry::permissionsForRole($normalizedRole);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role configuration reset to the default baseline.',
                'role' => $normalizedRole,
                'uses_recommended_defaults' => true,
                'permissions' => $defaultPermissions,
            ]);
        }

        return redirect()
            ->route('utilities.role-configuration.index', ['role' => $normalizedRole])
            ->with('success', 'Role configuration reset to the default baseline.');
    }

    private function findCustomRoleDefinition(string $role): UserRole
    {
        $normalizedRole = strtolower(trim($role));
        $roleDefinition = UserRole::query()
            ->where('role_key', $normalizedRole)
            ->first();

        if ($roleDefinition instanceof UserRole) {
            return $roleDefinition;
        }

        abort(404);
    }

    private function validatedRoleDefinitionPayload(Request $request, ?UserRole $existingRole = null): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
        ]);

        $label = InputSanitizer::sanitizePlainText((string) $validated['label']);
        $normalizedLabel = Str::lower($label);

        if ($label === '') {
            throw ValidationException::withMessages([
                'label' => 'Enter a role name.',
            ]);
        }

        $allRoleOptions = collect(User::allRoleOptions());
        if ($existingRole instanceof UserRole && $existingRole->role_key !== '') {
            $allRoleOptions->forget(strtolower(trim((string) $existingRole->role_key)));
        }

        $roleNameExists = $allRoleOptions
            ->contains(fn (string $existingLabel): bool => Str::lower(trim($existingLabel)) === $normalizedLabel);

        if ($roleNameExists) {
            throw ValidationException::withMessages([
                'label' => 'That role name already exists.',
            ]);
        }

        return [
            'label' => $label,
            'description' => UserRole::defaultDescriptionFor(),
        ];
    }

    private function roleDefinitionRejectedResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->route('utilities.role-configuration.index')
            ->with('error', $message);
    }

    public function locationConfiguration(): View
    {
        $importHistoryTableMissing = !Schema::hasTable(self::LOCATION_IMPORT_HISTORY_TABLE);

        $locationDatasets = collect(self::LOCATION_DATASETS)
            ->map(function (array $dataset, string $key) use ($importHistoryTableMissing): array {
                $tableExists = Schema::hasTable($dataset['table']);
                $importHistoryRows = $importHistoryTableMissing
                    ? collect()
                    : DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)
                        ->where('dataset_key', $key)
                        ->orderByDesc('imported_at')
                        ->orderByDesc('id')
                        ->limit(8)
                        ->get();

                return array_merge($dataset, [
                    'route' => route('utilities.location-configuration.import', ['dataset' => $key]),
                    'history_table_missing' => $importHistoryTableMissing,
                    'import_history_rows' => $importHistoryRows,
                    'table_exists' => $tableExists,
                    'row_count' => $tableExists ? DB::table($dataset['table'])->count() : 0,
                    'last_updated_at' => $tableExists ? DB::table($dataset['table'])->max('updated_at') : null,
                ]);
            })
            ->values();

        return view('admin.utilities.location-configuration', [
            'locationDatasets' => $locationDatasets,
        ]);
    }

    public function importLocationDataset(Request $request, string $dataset): RedirectResponse
    {
        $config = $this->locationDatasetConfig($dataset);

        if (!Schema::hasTable($config['table'])) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', $config['label'] . ' table is not available yet. Run the migration first.');
        }

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $file = $request->file('file');
        if (!$file instanceof UploadedFile) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'No file was uploaded.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== 'csv') {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->withErrors([
                    'file' => 'Upload a .csv file for ' . $config['label'] . '.',
                ]);
        }

        $originalFileName = (string) $file->getClientOriginalName();
        $storageFileName = $this->generateLocationImportStorageFileName($originalFileName, $config['key']);
        $storedPath = $file->storeAs(
            self::LOCATION_IMPORT_STORAGE_DIRECTORY . '/' . $config['key'],
            $storageFileName,
            'local'
        );

        if (!$storedPath) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Unable to store the uploaded file.');
        }

        if (!Schema::hasTable(self::LOCATION_IMPORT_HISTORY_TABLE)) {
            Storage::disk('local')->delete($storedPath);

            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $now = now();
        DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)->insert([
            'dataset_key' => $dataset,
            'original_file_name' => $originalFileName !== '' ? $originalFileName : basename($storedPath),
            'stored_file_path' => $storedPath,
            'file_size_bytes' => $file->getSize(),
            'imported_at' => $now,
            'last_loaded_at' => null,
            'created_by' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()
            ->route('utilities.location-configuration.index')
            ->with('success', 'CSV file added to import history. Click Load to replace the current ' . Str::lower($config['label']) . ' dataset.');
    }

    public function loadLocationDatasetImport(string $dataset, $importId): RedirectResponse
    {
        $config = $this->locationDatasetConfig($dataset);

        if (!Schema::hasTable($config['table'])) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', $config['label'] . ' table is not available yet. Run the migration first.');
        }

        if (!Schema::hasTable(self::LOCATION_IMPORT_HISTORY_TABLE)) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findLocationImportRecord($dataset, (int) $importId);
        if (!$record) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'The selected imported file is no longer available.');
        }

        try {
            $inserted = $this->importLocationCsvFromPath(
                Storage::disk('local')->path($storedPath),
                $config
            );
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', $exception->getMessage());
        }

        $now = now();
        DB::transaction(function () use ($dataset, $importId, $now): void {
            DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)
                ->where('dataset_key', $dataset)
                ->whereNotNull('last_loaded_at')
                ->update([
                    'last_loaded_at' => null,
                    'updated_at' => $now,
                ]);

            DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)
                ->where('dataset_key', $dataset)
                ->where('id', (int) $importId)
                ->update([
                    'last_loaded_at' => $now,
                    'updated_at' => $now,
                ]);
        });

        $displayName = trim((string) ($record->original_file_name ?? ''));
        if ($displayName === '') {
            $displayName = basename($storedPath);
        }

        return redirect()
            ->route('utilities.location-configuration.index')
            ->with('success', "Loaded {$inserted} rows from {$displayName}.");
    }

    public function downloadLocationDatasetImport(string $dataset, $importId)
    {
        $config = $this->locationDatasetConfig($dataset);

        if (!Schema::hasTable(self::LOCATION_IMPORT_HISTORY_TABLE)) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findLocationImportRecord($dataset, (int) $importId);
        if (!$record) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'The selected imported file is no longer available.');
        }

        $downloadName = trim((string) ($record->original_file_name ?? ''));
        if ($downloadName === '') {
            $downloadName = $config['key'] . '.csv';
        }

        return response()->download(
            Storage::disk('local')->path($storedPath),
            basename($downloadName),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function deleteLocationDatasetImport(string $dataset, $importId): RedirectResponse
    {
        if (!Schema::hasTable(self::LOCATION_IMPORT_HISTORY_TABLE)) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findLocationImportRecord($dataset, (int) $importId);
        if (!$record) {
            return redirect()
                ->route('utilities.location-configuration.index')
                ->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath !== '' && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)
            ->where('dataset_key', $dataset)
            ->where('id', (int) $importId)
            ->delete();

        return redirect()
            ->route('utilities.location-configuration.index')
            ->with('success', 'Imported file record deleted successfully.');
    }

    public function downloadBackup()
    {
        $backup = $this->backupService->createBackup([
            'type' => 'manual',
            'directory' => 'app/backups/manual',
            'prefix' => $this->backupService->databaseConnection()['database'] . '-backup',
        ]);

        return response()->download($backup['absolute_path'], $backup['filename'], [
            'Content-Type' => $backup['mime_type'],
        ])->deleteFileAfterSend(true);
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'],
        ]);

        $uploadedFile = $request->file('backup_file');
        if (strtolower((string) $uploadedFile->getClientOriginalExtension()) !== 'sql') {
            return back()->withErrors([
                'backup_file' => 'The backup file must use the .sql extension.',
            ]);
        }

        try {
            $this->backupService->restoreFromSqlFile($uploadedFile->getRealPath());
        } catch (\Throwable $exception) {
            return back()->with('error', 'Database restore failed. ' . $exception->getMessage());
        }

        return redirect()
            ->route('utilities.backup-and-restore.index')
            ->with('success', 'Database restore completed successfully.');
    }

    public function saveSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'frequency' => ['required', 'in:daily,weekly'],
            'weekly_day' => ['nullable', 'integer', 'between:0,6'],
            'run_time' => ['required', 'date_format:H:i'],
            'recipient_emails' => ['required', 'string'],
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'encrypt_backup' => ['nullable', 'boolean'],
            'encryption_password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        $isEnabled = $request->boolean('is_enabled');
        $encryptBackup = $request->boolean('encrypt_backup');
        $recipientEmails = $this->parseEmailList($validated['recipient_emails']);

        if ($recipientEmails === []) {
            return back()->withErrors([
                'recipient_emails' => 'Enter at least one valid email address.',
            ])->withInput();
        }

        if (($validated['frequency'] ?? null) === 'weekly' && ! $request->filled('weekly_day')) {
            return back()->withErrors([
                'weekly_day' => 'Choose the weekday for weekly backups.',
            ])->withInput();
        }

        if ($encryptBackup && ! $request->filled('encryption_password')) {
            return back()->withErrors([
                'encryption_password' => 'Set an encryption password when password protection is enabled.',
            ])->withInput();
        }

        $setting = BackupAutomationSetting::query()->firstOrNew();
        $setting->fill([
            'is_enabled' => $isEnabled,
            'frequency' => $validated['frequency'],
            'weekly_day' => $validated['frequency'] === 'weekly' ? (int) $validated['weekly_day'] : null,
            'run_time' => $validated['run_time'] . ':00',
            'recipient_emails' => $recipientEmails,
            'retention_days' => $request->filled('retention_days') ? (int) $validated['retention_days'] : null,
            'encrypt_backup' => $encryptBackup,
        ]);

        if ($encryptBackup && $request->filled('encryption_password')) {
            $setting->encryption_password = $validated['encryption_password'];
        }

        if (! $encryptBackup) {
            $setting->encryption_password = null;
        }

        $setting->save();

        return redirect()
            ->route('utilities.backup-and-restore.index')
            ->with('success', 'Backup scheduler settings saved successfully.');
    }

    public function sendTestBackupNow(): RedirectResponse
    {
        $setting = BackupAutomationSetting::query()->first();

        if (! $setting) {
            return redirect()
                ->route('utilities.backup-and-restore.index', ['tab' => 'scheduler'])
                ->with('error', 'Save scheduler settings before sending a test backup.');
        }

        $recipients = array_values(array_filter($setting->recipient_emails ?? []));
        if ($recipients === []) {
            return redirect()
                ->route('utilities.backup-and-restore.index', ['tab' => 'scheduler'])
                ->with('error', 'Add at least one recipient email before sending a test backup.');
        }

        try {
            $connection = $this->backupService->databaseConnection();
            $backup = $this->backupService->createBackup([
                'type' => 'test',
                'directory' => 'app/backups/automated',
                'prefix' => $connection['database'] . '-test-backup',
                'encrypt' => $setting->encrypt_backup,
                'encryption_password' => $setting->encrypt_backup ? $setting->encryption_password : null,
                'setting_id' => $setting->id,
                'mailed_to' => $recipients,
            ]);

            Mail::to($recipients)->send(new AutomatedDatabaseBackupMail(
                databaseName: $connection['database'],
                filePath: $backup['absolute_path'],
                fileName: $backup['filename'],
                wasEncrypted: $backup['was_encrypted'],
                retentionDays: $setting->retention_days,
            ));

            $deletedCount = $this->backupService->pruneOldBackups($setting->retention_days);

            DatabaseBackupRun::query()
                ->where('stored_path', $backup['stored_path'])
                ->latest('id')
                ->first()?->update([
                    'retention_deleted_count' => $deletedCount,
                ]);

            return redirect()
                ->route('utilities.backup-and-restore.index', ['tab' => 'scheduler'])
                ->with('success', 'Test backup email sent successfully.');
        } catch (\Throwable $exception) {
            return redirect()
                ->route('utilities.backup-and-restore.index', ['tab' => 'scheduler'])
                ->with('error', 'Test backup failed. ' . $exception->getMessage());
        }
    }

    private function locationDatasetConfig(string $dataset): array
    {
        abort_unless(array_key_exists($dataset, self::LOCATION_DATASETS), 404);

        return self::LOCATION_DATASETS[$dataset];
    }

    private function buildRoleConfigurations(): array
    {
        $rolePermissionSettings = RolePermissionSetting::query()
            ->whereIn('role', RolePermissionRegistry::configurableRoles())
            ->get()
            ->keyBy(function (RolePermissionSetting $setting) {
                return strtolower(trim((string) $setting->role));
            });

        return collect(RolePermissionRegistry::configurableRoles())
            ->map(function (string $role) use ($rolePermissionSettings): array {
                $setting = $rolePermissionSettings->get($role);
                $configuredPermissions = $setting?->permissions;

                return [
                    'role' => $role,
                    'label' => User::roleOptions()[$role] ?? $role,
                    'description' => RolePermissionRegistry::roleDescriptions()[$role] ?? null,
                    'permissions' => RolePermissionRegistry::permissionsForRole($role, $configuredPermissions),
                    'uses_recommended_defaults' => $setting === null,
                ];
            })
            ->values()
            ->all();
    }

    private function buildRoleManagementCards(): array
    {
        $roleCounts = User::query()
            ->selectRaw('LOWER(TRIM(COALESCE(role, ""))) as role_key')
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'active' THEN 1 ELSE 0 END) as active_users")
            ->selectRaw("SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'inactive' THEN 1 ELSE 0 END) as inactive_users")
            ->groupBy('role_key')
            ->get()
            ->keyBy('role_key');

        $roleDescriptions = RolePermissionRegistry::roleDescriptions();

        return collect(User::roleOptions())
            ->map(function (string $label, string $role) use ($roleCounts, $roleDescriptions): array {
                $countRow = $roleCounts->get($role);
                $isConfigurableRole = in_array($role, RolePermissionRegistry::configurableRoles(), true);
                $customRoleDefinition = UserRole::findDefinition($role);
                $isCustomRole = $customRoleDefinition !== null;

                return [
                    'role' => $role,
                    'label' => $label,
                    'description' => $roleDescriptions[$role] ?? 'Manage users assigned to this role classification.',
                    'kind' => $isCustomRole ? 'custom' : 'builtin',
                    'total_users' => (int) ($countRow->total_users ?? 0),
                    'active_users' => (int) ($countRow->active_users ?? 0),
                    'inactive_users' => (int) ($countRow->inactive_users ?? 0),
                    'users_route' => route('users.index', ['role' => $role]),
                    'create_route' => route('users.create', ['role' => $role]),
                    'role_configuration_route' => $isConfigurableRole
                        ? route('utilities.role-configuration.index', ['role' => $role])
                        : null,
                    'role_definition_update_route' => $role !== User::ROLE_SUPERADMIN
                        ? route('utilities.role-configuration.role-definitions.update', ['role' => $role])
                        : null,
                    'role_definition_delete_route' => $role !== User::ROLE_SUPERADMIN
                        ? route('utilities.role-configuration.role-definitions.destroy', ['role' => $role])
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function findLocationImportRecord(string $dataset, int $importId): ?object
    {
        return DB::table(self::LOCATION_IMPORT_HISTORY_TABLE)
            ->where('dataset_key', $dataset)
            ->where('id', $importId)
            ->first();
    }

    private function importLocationCsvFromPath(string $path, array $config): int
    {
        if (!is_readable($path)) {
            throw new \RuntimeException('Unable to read the selected CSV file.');
        }

        $destinationTables = $this->resolveLocationDestinationTables($config);
        if ($destinationTables === []) {
            throw new \RuntimeException($config['label'] . ' table is not available yet. Run the migration first.');
        }

        $lookupContext = $this->buildLocationLookupContext($config);

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the selected CSV file.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \RuntimeException('The selected CSV file is empty.');
            }

            $headerMap = $this->buildLocationHeaderMap($headers, $config);
            if ($headerMap === []) {
                throw new \RuntimeException('No recognized columns were found in the uploaded CSV file.');
            }

            foreach ($config['required'] as $requiredColumn) {
                if (!in_array($requiredColumn, $headerMap, true)) {
                    throw new \RuntimeException('The uploaded CSV file must include a column for ' . str_replace('_', ' ', $requiredColumn) . '.');
                }
            }

            return DB::transaction(function () use ($config, $handle, $headerMap, $destinationTables, $lookupContext) {
                foreach ($destinationTables as $table) {
                    DB::table($table)->delete();
                }

                $now = now();
                $rows = [];
                $inserted = 0;
                $rowNumber = 1;

                while (($data = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    if ($this->locationRowIsEmpty($data)) {
                        continue;
                    }

                    $row = [];
                    foreach ($headerMap as $index => $column) {
                        $value = $this->normalizeLocationValue($data[$index] ?? null);
                        if ($column === 'sort_order') {
                            $row[$column] = is_numeric($value) ? (int) $value : null;
                            continue;
                        }

                        if (in_array($column, $config['integer_columns'] ?? [], true)) {
                            $row[$column] = is_numeric($value) ? (int) $value : null;
                            continue;
                        }

                        $row[$column] = $value !== '' ? $value : null;
                    }

                    try {
                        $row = $this->prepareLocationImportRow($row, $config, $destinationTables, $lookupContext);
                    } catch (\RuntimeException $exception) {
                        throw new \RuntimeException('Row ' . $rowNumber . ': ' . $exception->getMessage());
                    }

                    $hasRequiredValues = true;
                    foreach ($config['required'] as $requiredColumn) {
                        $requiredValue = $row[$requiredColumn] ?? null;
                        if ($requiredValue === null || trim((string) $requiredValue) === '') {
                            $hasRequiredValues = false;
                            break;
                        }
                    }

                    if (!$hasRequiredValues) {
                        continue;
                    }

                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    $rows[] = $row;

                    if (count($rows) >= 500) {
                        foreach ($destinationTables as $table) {
                            DB::table($table)->insert($rows);
                        }
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if ($rows !== []) {
                    foreach ($destinationTables as $table) {
                        DB::table($table)->insert($rows);
                    }
                    $inserted += count($rows);
                }

                if ($inserted === 0) {
                    throw new \RuntimeException('No valid rows were found in the selected import file.');
                }

                return $inserted;
            });
        } finally {
            fclose($handle);
        }
    }

    private function generateLocationImportStorageFileName(string $originalFileName, string $fallbackBaseName): string
    {
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $baseNameSlug = Str::slug($baseName);
        if ($baseNameSlug === '') {
            $baseNameSlug = Str::slug($fallbackBaseName);
        }
        if ($baseNameSlug === '') {
            $baseNameSlug = 'location-import';
        }

        $timestamp = now()->format('Ymd_His');
        $randomSuffix = Str::lower(Str::random(8));
        $fileName = $timestamp . '_' . $baseNameSlug . '_' . $randomSuffix;

        return $fileName . ($extension !== '' ? '.' . $extension : '.csv');
    }

    private function buildLocationHeaderMap(array $headers, array $config): array
    {
        $allowedColumns = array_fill_keys(array_merge(
            $config['columns'],
            $config['source_columns'] ?? []
        ), true);
        $aliases = $config['aliases'] ?? [];
        $mappedColumns = [];
        $headerMap = [];

        foreach ($headers as $index => $header) {
            $normalizedHeader = $this->normalizeLocationHeader($header);
            if ($normalizedHeader === '') {
                continue;
            }

            $column = $aliases[$normalizedHeader] ?? $normalizedHeader;
            if (!isset($allowedColumns[$column]) || isset($mappedColumns[$column])) {
                continue;
            }

            $mappedColumns[$column] = true;
            $headerMap[$index] = $column;
        }

        return $headerMap;
    }

    private function normalizeLocationHeader(mixed $value): string
    {
        $value = is_string($value) ? $value : '';
        $value = ltrim($value, "\xEF\xBB\xBF");
        $value = str_replace(['&', '/'], [' and ', ' '], $value);
        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower(trim($value))) ?? '';

        return trim($value, '_');
    }

    private function normalizeLocationValue(mixed $value): string
    {
        return trim(is_scalar($value) ? (string) $value : '');
    }

    private function buildLocationLookupContext(array $config): array
    {
        if (($config['key'] ?? null) !== 'provinces' || !Schema::hasTable('regions')) {
            return [];
        }

        $byId = [];
        $byCode = [];
        $byName = [];

        foreach (DB::table('regions')->get(['id', 'region_code', 'region_name']) as $region) {
            $regionId = (int) ($region->id ?? 0);
            if ($regionId < 1) {
                continue;
            }

            $byId[$regionId] = true;

            $codeKey = $this->normalizeLocationLookupKey($region->region_code ?? null);
            if ($codeKey !== '') {
                $byCode[$codeKey] = $regionId;
            }

            $nameKey = $this->normalizeLocationLookupKey($region->region_name ?? null);
            if ($nameKey !== '') {
                $byName[$nameKey] = $regionId;
            }
        }

        return [
            'regions' => [
                'by_id' => $byId,
                'by_code' => $byCode,
                'by_name' => $byName,
            ],
        ];
    }

    private function prepareLocationImportRow(
        array $row,
        array $config,
        array $destinationTables,
        array $lookupContext
    ): array {
        if (($config['key'] ?? null) === 'provinces') {
            $row = $this->prepareProvinceImportRow($row, $destinationTables, $lookupContext);
        }

        return $this->filterLocationInsertableColumns($row, $config);
    }

    private function prepareProvinceImportRow(
        array $row,
        array $destinationTables,
        array $lookupContext
    ): array {
        $regions = $lookupContext['regions'] ?? [
            'by_id' => [],
            'by_code' => [],
            'by_name' => [],
        ];

        $resolvedRegionId = null;
        $regionCodeKey = $this->normalizeLocationLookupKey($row['region_lookup_code'] ?? null);
        if ($regionCodeKey !== '' && isset($regions['by_code'][$regionCodeKey])) {
            $resolvedRegionId = $regions['by_code'][$regionCodeKey];
        }

        $regionNameKey = $this->normalizeLocationLookupKey($row['region_lookup_name'] ?? null);
        if ($resolvedRegionId === null && $regionNameKey !== '' && isset($regions['by_name'][$regionNameKey])) {
            $resolvedRegionId = $regions['by_name'][$regionNameKey];
        }

        $candidateRegionId = $row['region_id'] ?? null;
        if ($resolvedRegionId === null && is_numeric($candidateRegionId)) {
            $candidateRegionId = (int) $candidateRegionId;
            if ($candidateRegionId > 0 && isset($regions['by_id'][$candidateRegionId])) {
                $resolvedRegionId = $candidateRegionId;
            }
        }

        if ($resolvedRegionId !== null) {
            $row['region_id'] = $resolvedRegionId;
        }

        $requiresLegacyProvinceInsert = in_array('provinces', $destinationTables, true);
        if ($requiresLegacyProvinceInsert && (!isset($row['region_id']) || !is_numeric($row['region_id']) || (int) $row['region_id'] < 1)) {
            $provinceName = $this->normalizeLocationValue($row['province_name'] ?? null);
            throw new \RuntimeException(
                'Unable to resolve region_id for province "' . ($provinceName !== '' ? $provinceName : 'unknown') . '". ' .
                'Include a valid region_id or add region_code/region_name that matches the regions table.'
            );
        }

        $provinceCode = $this->normalizeLocationValue($row['province_code'] ?? null);
        if ($requiresLegacyProvinceInsert && $provinceCode === '') {
            throw new \RuntimeException('province_code is required to insert rows into the provinces table.');
        }

        unset($row['region_lookup_code'], $row['region_lookup_name']);

        return $row;
    }

    private function filterLocationInsertableColumns(array $row, array $config): array
    {
        $filtered = [];

        foreach ($config['columns'] as $column) {
            if (array_key_exists($column, $row)) {
                $filtered[$column] = $row[$column];
            }
        }

        return $filtered;
    }

    private function normalizeLocationLookupKey(mixed $value): string
    {
        return Str::lower($this->normalizeLocationValue($value));
    }

    private function resolveLocationDestinationTables(array $config): array
    {
        $tables = $config['load_tables'] ?? [$config['table']];
        $tables = array_values(array_unique(array_filter($tables, static function (mixed $table): bool {
            return is_string($table) && trim($table) !== '';
        })));

        return array_values(array_filter($tables, static function (string $table): bool {
            return Schema::hasTable($table);
        }));
    }

    private function locationRowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->normalizeLocationValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseEmailList(string $emails): array
    {
        $items = preg_split('/[\s,;]+/', $emails) ?: [];
        $items = array_unique(array_filter(array_map('trim', $items)));

        return array_values(array_filter($items, fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));
    }

    private function resolveBulkNotificationRecipients(
        string $targetScope,
        array $selectedUserIds = [],
        ?string $selectedRole = null
    ) {
        $query = User::query()
            ->select(['idno', 'fname', 'lname', 'username', 'emailaddress', 'role'])
            ->whereRaw('LOWER(TRIM(COALESCE(status, ""))) = ?', ['active']);

        if ($targetScope === 'selected_users') {
            $query->whereIn('idno', $selectedUserIds);
        } elseif ($targetScope === 'selected_role' && $selectedRole !== null) {
            $query->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [$selectedRole]);
        }

        return $query
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();
    }

    private function formatBulkNotificationSystemMessage(string $title, string $message): string
    {
        $singleLineMessage = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return Str::limit(trim($title . ': ' . $singleLineMessage), 500, '...');
    }

    private function bulkNotificationAudienceLabel(string $targetScope, ?string $selectedRole = null): string
    {
        if ($targetScope === 'selected_users') {
            return 'selected user(s)';
        }

        if ($targetScope === 'selected_role') {
            return User::roleOptions()[$selectedRole] ?? 'selected level';
        }

        return 'all active users';
    }

    private function nextScheduledRun(?BackupAutomationSetting $setting): ?string
    {
        if (! $setting || ! $setting->is_enabled) {
            return null;
        }

        $candidate = now()->copy()->setTimeFromTimeString($setting->run_time);

        if ($setting->frequency === 'daily') {
            if ($candidate->lte(now())) {
                $candidate->addDay();
            }

            return $candidate->format('F j, Y g:i A');
        }

        $weeklyDay = (int) $setting->weekly_day;
        while ($candidate->dayOfWeek !== $weeklyDay || $candidate->lte(now())) {
            $candidate->addDay();
        }

        return $candidate->format('F j, Y g:i A');
    }
}
