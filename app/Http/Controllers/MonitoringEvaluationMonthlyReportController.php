<?php

namespace App\Http\Controllers;

use App\Models\MonitoringEvaluationMonthlyDocument;
use App\Services\InterventionNotificationService;
use App\Support\LguReportorialDeadlineResolver;
use App\Support\InputSanitizer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MonitoringEvaluationMonthlyReportController extends Controller
{
    protected $currentDocType = 'monitoring_evaluation_monthly';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:dilg_deliverables_monitoring_evaluation,view')->only([
            'index', 'indexLfp', 'indexRlipLime',
            'edit', 'editLfp', 'editRlipLime',
            'viewDocument', 'viewDocumentLfp', 'viewDocumentRlipLime'
        ]);
        $this->middleware('crud_permission:dilg_deliverables_monitoring_evaluation,add')->only([
            'upload', 'uploadLfp', 'uploadRlipLime'
        ]);
        $this->middleware('crud_permission:dilg_deliverables_monitoring_evaluation,update')->only([
            'approveDocument', 'approveDocumentLfp', 'approveDocumentRlipLime',
            'requestDeletion', 'requestDeletionLfp', 'requestDeletionRlipLime',
            'decideDeletion', 'decideDeletionLfp', 'decideDeletionRlipLime'
        ]);
        $this->middleware('superadmin')->only([
            'deleteDocument', 'deleteDocumentLfp', 'deleteDocumentRlipLime'
        ]);
    }

    private function getOffices(): array
    {
        return [
            'Abra' => [
                'Abra',
            ],
            'Apayao' => [
                'Apayao',
            ],
            'Benguet' => [
                'Benguet',
            ],
            'City of Baguio' => [
                'City of Baguio',
            ],
            'Ifugao' => [
                'Ifugao',
            ],
            'Kalinga' => [
                'Kalinga',
            ],
            'Mountain Province' => [
                'Mountain Province',
            ],
        ];
    }

    private function monthOptions(): array
    {
        return [
            'JAN' => 'January',
            'FEB' => 'February',
            'MAR' => 'March',
            'APR' => 'April',
            'MAY' => 'May',
            'JUN' => 'June',
            'JUL' => 'July',
            'AUG' => 'August',
            'SEP' => 'September',
            'OCT' => 'October',
            'NOV' => 'November',
            'DEC' => 'December',
        ];
    }

    private function reportDocType(): string
    {
        return $this->currentDocType;
    }

    private function resolveReportingYear(Request $request): int
    {
        $year = (int) $request->query('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return $year;
    }

    private function resolveMonthlyDeadline(int $reportingYear, string $monthCode): ?array
    {
        $monthLabel = $this->monthOptions()[$monthCode] ?? null;
        if ($monthLabel === null) {
            return null;
        }

        return app(LguReportorialDeadlineResolver::class)->resolve(
            'dilg_deliverables_monitoring_evaluation',
            $reportingYear,
            $monthLabel
        );
    }

    private function resolveMonthlyDeadlines(int $reportingYear): array
    {
        $resolved = [];

        foreach (array_keys($this->monthOptions()) as $monthCode) {
            $resolved[$monthCode] = $this->resolveMonthlyDeadline($reportingYear, $monthCode);
        }

        return $resolved;
    }

    private function buildOfficeRows(array $offices): array
    {
        $rows = [];
        foreach ($offices as $province => $municipalities) {
            foreach ($municipalities as $office) {
                $rows[] = [
                    'province' => $province,
                    'city_municipality' => $office,
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return strcasecmp($a['province'], $b['province']);
        });

        return $rows;
    }

    private function findProvinceByOffice(string $officeName): ?string
    {
        foreach ($this->getOffices() as $province => $municipalities) {
            if (in_array($officeName, $municipalities, true)) {
                return $province;
            }
        }

        return null;
    }

    private function canAccessOffice(string $officeName, string $province): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isRegionalUser() || $user->isRegionalOfficeAssignment()) {
            return true;
        }

        $userProvince = trim((string) $user->province);

        if ($user->isLguScopedUser()) {
            return $user->matchesAssignedOffice($officeName);
        }

        if ($user->isDilgUser()) {
            if ($userProvince === '' || $userProvince === 'Regional Office') {
                return true;
            }

            return $userProvince === $province;
        }

        return true;
    }

    private function indexDocumentsByKey($documents): array
    {
        $indexed = [];
        foreach ($documents as $doc) {
            $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->month ?? '');
            $indexed[$key] = $doc;
        }

        return $indexed;
    }

    private function formatDocumentLabel(MonitoringEvaluationMonthlyDocument $document): string
    {
        $suffixParts = [];
        if (!empty($document->year)) {
            $suffixParts[] = 'CY ' . $document->year;
        }
        if (!empty($document->month)) {
            $monthLabel = $this->monthOptions()[$document->month] ?? $document->month;
            $suffixParts[] = $monthLabel;
        }

        $suffix = empty($suffixParts) ? '' : ' (' . implode(' ', $suffixParts) . ')';

        return 'Monthly Monitoring & Evaluation Report' . $suffix;
    }

    private function buildCurrentActivityLogs($documents): array
    {
        $logs = [];

        foreach ($documents as $doc) {
            $docLabel = $this->formatDocumentLabel($doc);

            if ($doc->uploaded_at) {
                $logs[] = [
                    'timestamp' => $doc->uploaded_at,
                    'action' => 'Uploaded',
                    'document' => $docLabel,
                    'user_id' => $doc->uploaded_by,
                    'remarks' => null,
                ];
            }

            if ($doc->approved_at_dilg_po) {
                $logs[] = [
                    'timestamp' => $doc->approved_at_dilg_po,
                    'action' => 'Validated (DILG PO)',
                    'document' => $docLabel,
                    'user_id' => $doc->approved_by_dilg_po,
                    'remarks' => null,
                ];
            }

            if ($doc->approved_at_dilg_ro) {
                $logs[] = [
                    'timestamp' => $doc->approved_at_dilg_ro,
                    'action' => 'Validated (DILG RO)',
                    'document' => $docLabel,
                    'user_id' => $doc->approved_by_dilg_ro,
                    'remarks' => null,
                ];
            }

            if ($doc->status === 'returned') {
                $logs[] = [
                    'timestamp' => $doc->approved_at ?? $doc->updated_at ?? $doc->uploaded_at,
                    'action' => 'Returned',
                    'document' => $docLabel,
                    'user_id' => $doc->approved_by_dilg_ro ?: $doc->approved_by_dilg_po,
                    'remarks' => $doc->approval_remarks,
                ];
            }
        }

        return $logs;
    }

    private function parsePersistedActivityLog(string $line, string $officeName): ?array
    {
        $pattern = '/^\[([^\]]+)\]\s+[^\:]+\.\w+:\s+([^{]+)\s*(\{.*)/';
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $loggedAt = trim($matches[1]);
        $contextJson = $matches[3];
        $context = json_decode($contextJson, true);

        if (!is_array($context)) {
            return null;
        }

        if (($context['module'] ?? null) !== 'monitoring_evaluation_monthly') {
            return null;
        }

        if (trim((string) ($context['office'] ?? '')) !== trim($officeName)) {
            return null;
        }

        $timestampRaw = $context['action_timestamp'] ?? $loggedAt;
        try {
            $timestamp = Carbon::parse($timestampRaw)->setTimezone(config('app.timezone'));
        } catch (\Throwable $e) {
            $timestamp = Carbon::parse($loggedAt)->setTimezone(config('app.timezone'));
        }

        return [
            'timestamp' => $timestamp,
            'action' => $context['action_label'] ?? 'Updated',
            'document' => $context['document_label'] ?? 'Monthly Monitoring & Evaluation Report',
            'user_id' => $context['user_id'] ?? null,
            'remarks' => $context['remarks'] ?? null,
        ];
    }

    private function getPersistedActivityLogs(string $officeName): array
    {
        $logFiles = glob(storage_path('logs/upload_timestamps-*.log')) ?: [];
        $singleLogFile = storage_path('logs/upload_timestamps.log');
        if (is_file($singleLogFile)) {
            $logFiles[] = $singleLogFile;
        }
        rsort($logFiles);

        $entries = [];
        foreach ($logFiles as $logFile) {
            $content = @file_get_contents($logFile);
            if (!$content) {
                continue;
            }

            $logEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\])/', $content, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($logEntries as $logEntry) {
                $logEntry = trim($logEntry);
                if ($logEntry === '' || strpos($logEntry, '"module":"monitoring_evaluation_monthly"') === false) {
                    continue;
                }

                $parsed = $this->parsePersistedActivityLog($logEntry, $officeName);
                if ($parsed) {
                    $entries[] = $parsed;
                }
            }
        }

        return $entries;
    }

    private function buildActivityLogs($documents, string $officeName): array
    {
        $persistedLogs = $this->getPersistedActivityLogs($officeName);
        $currentLogs = $this->buildCurrentActivityLogs($documents);

        if (empty($persistedLogs)) {
            $logs = $currentLogs;
        } else {
            $logs = $persistedLogs;
            foreach ($currentLogs as $currentLog) {
                $existsInPersisted = false;
                foreach ($persistedLogs as $persistedLog) {
                    $currentTs = ($currentLog['timestamp'] instanceof \DateTimeInterface) ? $currentLog['timestamp']->getTimestamp() : null;
                    $persistedTs = ($persistedLog['timestamp'] instanceof \DateTimeInterface) ? $persistedLog['timestamp']->getTimestamp() : null;

                    if (
                        $currentTs === $persistedTs
                        && ($currentLog['action'] ?? '') === ($persistedLog['action'] ?? '')
                        && ($currentLog['document'] ?? '') === ($persistedLog['document'] ?? '')
                        && (string) ($currentLog['user_id'] ?? '') === (string) ($persistedLog['user_id'] ?? '')
                        && (string) ($currentLog['remarks'] ?? '') === (string) ($persistedLog['remarks'] ?? '')
                    ) {
                        $existsInPersisted = true;
                        break;
                    }
                }

                if (!$existsInPersisted) {
                    $logs[] = $currentLog;
                }
            }
        }

        usort($logs, function ($a, $b) {
            $aTime = $a['timestamp'] ? $a['timestamp']->getTimestamp() : 0;
            $bTime = $b['timestamp'] ? $b['timestamp']->getTimestamp() : 0;

            return $bTime <=> $aTime;
        });

        return $logs;
    }

    private function logActivity(
        string $officeName,
        string $action,
        string $actionLabel,
        MonitoringEvaluationMonthlyDocument $document,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        $timestamp = $timestamp ?: now();

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'monitoring_evaluation_monthly',
            'office' => $officeName,
            'doc_type' => $document->doc_type,
            'year' => $document->year,
            'month' => $document->month,
            'document_label' => $this->formatDocumentLabel($document),
            'action' => $action,
            'action_label' => $actionLabel,
            'action_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
            'remarks' => $remarks,
        ]);
    }

    private function notifyWorkflowUsersOnUpload(MonitoringEvaluationMonthlyDocument $document): void
    {
        try {
            $actor = auth()->user();
            if (!$actor) {
                return;
            }

            $targetOffice = trim((string) ($document->office ?? ''));
            $targetProvince = trim((string) ($document->province ?? ''));
            if ($targetProvince === '' && $targetOffice !== '') {
                $targetProvince = trim((string) ($this->findProvinceByOffice($targetOffice) ?? ''));
            }

            if ($targetOffice === '' && $targetProvince === '') {
                return;
            }

            $actorName = trim((string) ($actor->fname ?? '') . ' ' . (string) ($actor->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'A user';
            }

            $url = $targetOffice !== ''
                ? route($this->getCategoryInfo()['editRoute'], ['office' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route($this->getCategoryInfo()['indexRoute']);
            $actorId = (int) auth()->id();
            $notificationService = app(InterventionNotificationService::class);

            if ($actor->isLguScopedUser()) {
                $message = sprintf(
                    '%s uploaded %s for %s%s and it is awaiting DILG Provincial Office validation.',
                    $actorName,
                    $this->formatDocumentLabel($document),
                    $targetOffice !== '' ? $targetOffice : 'the LGU',
                    $targetProvince !== '' ? ' - ' . $targetProvince : ''
                );

                $notificationService->notifyProvincialDilg(
                    $targetProvince,
                    $actorId,
                    $message,
                    $url,
                    'monitoring-evaluation-monthly',
                    $document->month ?? null
                );

                return;
            }

            if ($actor->isDilgUser() && !$actor->isRegionalOfficeAssignment()) {
                $message = sprintf(
                    '%s uploaded %s for %s%s and it is awaiting DILG Regional Office validation.',
                    $actorName,
                    $this->formatDocumentLabel($document),
                    $targetOffice !== '' ? $targetOffice : 'the LGU',
                    $targetProvince !== '' ? ' - ' . $targetProvince : ''
                );

                $notificationService->notifyRegionalDilg(
                    $actorId,
                    $message,
                    $url,
                    'monitoring-evaluation-monthly',
                    $document->month ?? null
                );
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create workflow upload notifications (Monitoring & Evaluation Monthly).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function notifyLguUsersAfterRegionalApproval(
        MonitoringEvaluationMonthlyDocument $document,
        string $action,
        bool $isRegionalOffice,
        ?string $remarks = null
    ): void {
        try {
            if (!Schema::hasTable('tbnotifications')) {
                return;
            }

            $actor = auth()->user();
            if (!$actor || strtoupper(trim((string) ($actor->agency ?? ''))) !== 'DILG') {
                return;
            }

            $targetOffice = trim((string) ($document->office ?? ''));
            $targetProvince = trim((string) ($document->province ?? ''));
            if ($targetProvince === '' && $targetOffice !== '') {
                $targetProvince = trim((string) ($this->findProvinceByOffice($targetOffice) ?? ''));
            }

            if ($targetOffice === '' && $targetProvince === '') {
                return;
            }

            $candidateOfficeNames = collect([$targetOffice])
                ->map(function ($value) {
                    return strtolower(trim((string) $value));
                })
                ->filter(function ($value) {
                    return $value !== '';
                })
                ->flatMap(function ($value) {
                    $withoutPrefix = trim((string) preg_replace('/^(municipality|city)\s+of\s+/i', '', $value));
                    return array_values(array_unique(array_filter([$value, $withoutPrefix])));
                })
                ->values()
                ->all();

            $recipientQuery = User::query()
                ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['LGU'])
                ->where('status', 'active');

            if ($targetProvince !== '') {
                $recipientQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($targetProvince)]);
            }

            $provinceRecipients = $recipientQuery->get(['idno', 'office']);
            $recipients = $provinceRecipients;
            if ($provinceRecipients->isNotEmpty() && !empty($candidateOfficeNames)) {
                $filteredRecipients = $provinceRecipients->filter(function ($lguUser) use ($candidateOfficeNames) {
                    $office = strtolower(trim((string) ($lguUser->office ?? '')));
                    $officeWithoutPrefix = trim((string) preg_replace('/^(municipality|city)\s+of\s+/i', '', $office));
                    return in_array($office, $candidateOfficeNames, true)
                        || in_array($officeWithoutPrefix, $candidateOfficeNames, true);
                })->values();

                if ($filteredRecipients->isNotEmpty()) {
                    $recipients = $filteredRecipients;
                }
            }

            $relatedUserIds = collect([
                $document->uploaded_by,
                $document->approved_by_dilg_po,
                $document->approved_by_dilg_ro,
            ])->filter()->map(function ($value) {
                return (int) $value;
            });

            $recipientIds = $recipients->pluck('idno')->merge($relatedUserIds);
            if ($isRegionalOffice && $targetProvince !== '') {
                $provincialDilgIds = User::query()
                    ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
                    ->where('status', 'active')
                    ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($targetProvince)])
                    ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) <> ?', ['regional office'])
                    ->pluck('idno');
                $recipientIds = $recipientIds->merge($provincialDilgIds);
            }

            $actorName = trim((string) ($actor->fname ?? '') . ' ' . (string) ($actor->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'DILG Regional Office';
            }

            $url = $targetOffice !== ''
                ? route($this->getCategoryInfo()['editRoute'], ['office' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route($this->getCategoryInfo()['indexRoute']);
            $actorId = (int) auth()->id();
            $notificationService = app(InterventionNotificationService::class);

            if ($action === 'approve' && !$isRegionalOffice) {
                $message = sprintf(
                    '%s validated (DILG PO) %s for %s%s and it is awaiting DILG Regional Office validation.',
                    $actorName,
                    $this->formatDocumentLabel($document),
                    $targetOffice !== '' ? $targetOffice : 'the LGU',
                    $targetProvince !== '' ? ' - ' . $targetProvince : ''
                );

                $notificationService->notifyRegionalDilg(
                    $actorId,
                    $message,
                    $url,
                    'monitoring-evaluation-monthly',
                    $document->month ?? null
                );

                return;
            }

            $actionLabel = $action === 'approve'
                ? ($isRegionalOffice ? 'approved' : 'validated (DILG PO)')
                : 'returned';

            $message = sprintf(
                '%s %s %s for %s%s.',
                $actorName,
                $actionLabel,
                $this->formatDocumentLabel($document),
                $targetOffice !== '' ? $targetOffice : 'the LGU',
                $targetProvince !== '' ? ' - ' . $targetProvince : ''
            );

            if ($action === 'return' && $remarks) {
                $message .= ' Remarks: ' . $remarks;
            }

            $notificationService->notifyScopedLgu(
                $targetProvince,
                $targetOffice,
                $recipientIds,
                $actorId,
                $message,
                $url,
                'monitoring-evaluation-monthly',
                $document->month ?? null
            );
        } catch (\Throwable $error) {
            Log::warning('Failed to create approval notifications (Monitoring & Evaluation Monthly).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $months = $this->monthOptions();
        $officeRows = $this->buildOfficeRows($this->getOffices());
        $perPage = (int) $request->query('per_page', 15);
        $filters = [
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
        ];
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $user = auth()->user();
        if ($user && $user->isLguScopedUser() && $user->normalizedOffice() !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                return $user->matchesAssignedOffice((string) ($row['city_municipality'] ?? ''));
            }));
        } elseif ($user && $user->isDilgUser() && !empty($user->province)) {
            if ($user->province !== 'Regional Office') {
                $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                    return $row['province'] === $user->province;
                }));
            }
        }

        $scopedOfficeRows = collect($officeRows);
        $filterOptions = [
            'provinces' => $scopedOfficeRows
                ->pluck('province')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'provinceMunicipalities' => $scopedOfficeRows
                ->groupBy('province')
                ->map(fn ($rows) => $rows->pluck('city_municipality')->filter()->values()->all())
                ->toArray(),
        ];

        if ($filters['province'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($filters) {
                return (string) ($row['province'] ?? '') === $filters['province'];
            }));
        }

        if ($filters['city'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($filters) {
                return (string) ($row['city_municipality'] ?? '') === $filters['city'];
            }));
        }

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $officeRowsCollection = collect($officeRows);
        $officeRows = (new LengthAwarePaginator(
            $officeRowsCollection->forPage($page, $perPage)->values(),
            $officeRowsCollection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ))->withQueryString();

        $officeNames = $officeRows->getCollection()
            ->pluck('city_municipality')
            ->unique()
            ->values()
            ->all();

        $documentsByOffice = [];
        if (!empty($officeNames)) {
            $documents = MonitoringEvaluationMonthlyDocument::query()
                ->whereIn('office', $officeNames)
                ->where('doc_type', $this->reportDocType())
                ->where('year', $reportingYear)
                ->get();

            foreach ($documents as $doc) {
                $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->month ?? '');
                $documentsByOffice[$doc->office][$key] = $doc;
            }
        }

        return view('reports.dilg-deliverables.monitoring-evaluation.index', array_merge(
            compact(
                'officeRows',
                'documentsByOffice',
                'reportingYear',
                'months',
                'perPage',
                'filters',
                'filterOptions'
            ),
            $this->getCategoryInfo()
        ));
    }

    public function edit(Request $request, $office)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $months = $this->monthOptions();
        $officeName = (string) $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $documents = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('doc_type', $this->reportDocType())
            ->where('year', $reportingYear)
            ->get();
        $documentsByKey = $this->indexDocumentsByKey($documents);
        $activityLogs = $this->buildActivityLogs($documents, $officeName);

        $uploaderIds = $documents->pluck('uploaded_by')->filter()->unique()->values()->all();
        $approverIds = $documents->pluck('approved_by_dilg_po')
            ->merge($documents->pluck('approved_by_dilg_ro'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $logUserIds = collect($activityLogs)->pluck('user_id')->filter()->unique()->values()->all();
        $userIds = array_values(array_unique(array_merge($uploaderIds, $approverIds, $logUserIds)));
        $usersById = $userIds
            ? User::whereIn('idno', $userIds)->get()->keyBy('idno')
            : collect();
        $configuredMonthlyDeadlines = $this->resolveMonthlyDeadlines($reportingYear);

        return view('reports.dilg-deliverables.monitoring-evaluation.edit', array_merge(
            compact(
                'officeName',
                'province',
                'documentsByKey',
                'usersById',
                'activityLogs',
                'reportingYear',
                'months',
                'configuredMonthlyDeadlines'
            ),
            $this->getCategoryInfo()
        ));
    }

    public function deleteDocument($office, $docId)
    {
        $officeName = (string) $office;
        $document = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $document->year])->with('success', 'Uploaded document deleted successfully.');
    }

    public function upload(Request $request, $office)
    {
        $officeName = (string) $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $user = auth()->user();
        if ($user && strtoupper(trim((string) $user->agency)) === 'DILG' && trim((string) $user->province) === 'Regional Office') {
            return back()->withErrors([
                'document' => 'Regional Office cannot upload files.',
            ]);
        }

        $validMonths = implode(',', array_keys($this->monthOptions()));
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'in:' . $validMonths],
        ]);

        $year = (int) $request->input('year');
        $month = (string) $request->input('month');
        $docType = $this->reportDocType();

        $existingDocument = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('doc_type', $docType)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $oldFilePath = $existingDocument?->file_path;

        $file = $request->file('document');
        $officeSlug = Str::slug($officeName, '_');
        $path = $file->store('monitoring-evaluation-monthly/' . $officeSlug, 'public');
        $uploadedAt = now();
        $isProvincialDilgUploader = true;

        $document = MonitoringEvaluationMonthlyDocument::updateOrCreate(
            [
                'office' => $officeName,
                'doc_type' => $docType,
                'year' => $year,
                'month' => $month,
            ],
            [
                'province' => $province,
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => $uploadedAt,
                'status' => 'pending_ro',
                'approved_at' => $uploadedAt,
                'approved_at_dilg_po' => $uploadedAt,
                'approved_at_dilg_ro' => null,
                'approved_by_dilg_po' => $user->idno ?? auth()->id(),
                'approved_by_dilg_ro' => null,
                'approval_remarks' => null,
                'user_remarks' => null,
            ]
        );

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        $this->logActivity($officeName, 'upload', 'Uploaded', $document, null, $uploadedAt);
        $this->notifyWorkflowUsersOnUpload($document);

        return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $year])->with('success', 'Monthly report uploaded successfully.');
    }

    public function viewDocument($office, $docId)
    {
        $officeName = (string) $office;
        $document = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->where('doc_type', $this->reportDocType())
            ->firstOrFail();

        if (!$this->canAccessOffice($officeName, (string) $document->province)) {
            abort(403);
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($document->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if (!in_array($extension, $inlineExtensions, true)) {
            return response()->download($filePath, basename($filePath), $headers);
        }

        return response()->file($filePath, $headers);
    }

    public function approveDocument(Request $request, $office, $docId)
    {
        $officeName = (string) $office;
        $user = auth()->user();
        if (!$user || strtoupper(trim((string) $user->agency)) !== 'DILG') {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $document = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->where('doc_type', $this->reportDocType())
            ->firstOrFail();

        if (!$this->canAccessOffice($officeName, (string) $document->province)) {
            abort(403);
        }

        $now = now();
        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $isRegionalOffice = (bool) ($user && ($user->isRegionalUser() || $user->isSuperAdmin() || $user->isRegionalOfficeAssignment()));
        $isProvincialOffice = !$isRegionalOffice;

        $updates = [
            'approved_at' => $now,
        ];

        if ($action === 'approve') {
            if ($isProvincialOffice) {
                $updates['approved_at_dilg_po'] = $now;
                $updates['approved_by_dilg_po'] = $user->idno;
                $updates['status'] = 'pending_ro';
                $updates['approval_remarks'] = null;
            } else {
                $updates['approved_at_dilg_ro'] = $now;
                $updates['approved_by_dilg_ro'] = $user->idno;
                $updates['status'] = 'approved';
                $updates['approval_remarks'] = null;
            }
        } else {
            if ($isRegionalOffice) {
                $updates['approved_at_dilg_ro'] = null;
                $updates['approved_by_dilg_ro'] = $user->idno;
            } else {
                $updates['approved_by_dilg_po'] = $user->idno;
            }
            $updates['status'] = 'returned';
            $updates['approval_remarks'] = $remarks;
            $updates['user_remarks'] = $remarks;
        }

        $document->update($updates);
        $document->refresh();

        if ($action === 'approve') {
            if ($isProvincialOffice) {
                $this->logActivity($officeName, 'validate_po', 'Validated (DILG PO)', $document, null, $now);
            } else {
                $this->logActivity($officeName, 'validate_ro', 'Validated (DILG RO)', $document, null, $now);
            }
        } else {
            $this->logActivity($officeName, 'return', 'Returned', $document, $remarks, $now);
        }

        $this->notifyLguUsersAfterRegionalApproval($document, $action, $isRegionalOffice, $remarks);

        return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $document->year])->with('success', $action === 'approve' ? 'Document validated.' : 'Document returned.');
    }

    public function requestDeletion(Request $request, $office, $docId)
    {
        $officeName = (string) $office;
        $document = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isUploader = (string) $user->idno === (string) $document->uploaded_by || $user->isSuperAdmin();
        if (!$isUploader) {
            abort(403, 'Only the uploader can request deletion.');
        }

        if ($document->status !== 'approved') {
            return back()->withErrors(['document' => 'Only approved documents can have deletion requested.']);
        }

        $request->validate([
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $remarks = $request->input('remarks');

        $document->update([
            'status' => 'pending_deletion',
            'approval_remarks' => $remarks,
        ]);

        $now = now();
        $this->logActivity($officeName, 'delete_request', 'Deletion Requested', $document, $remarks, $now);

        try {
            $notificationService = app(InterventionNotificationService::class);
            $actorName = trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'A user';
            $message = sprintf(
                '%s requested deletion of %s for %s.',
                $actorName,
                $this->formatDocumentLabel($document),
                $officeName
            );
            $url = route($this->getCategoryInfo()['editRoute'], [
                'office' => $officeName,
                'year' => $document->year ?: now()->year
            ]);

            $notificationService->notifyRegionalDilg(
                $user->idno ?: auth()->id(),
                $message,
                $url,
                'monitoring-evaluation-monthly',
                $document->month ?? null
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send deletion request notifications: ' . $e->getMessage());
        }

        return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $document->year])->with('success', 'Deletion request submitted successfully.');
    }

    public function decideDeletion(Request $request, $office, $docId)
    {
        $officeName = (string) $office;
        $document = MonitoringEvaluationMonthlyDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isRegionalOfficeUser = $user->isRegionalUser() || $user->isSuperAdmin() || $user->isRegionalOfficeAssignment();
        if (!$isRegionalOfficeUser) {
            abort(403, 'Only regional validators can decide on deletion requests.');
        }

        if ($document->status !== 'pending_deletion') {
            return back()->withErrors(['document' => 'This document is not pending deletion.']);
        }

        $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = $request->input('decision');
        $remarks = $request->input('remarks');
        $now = now();

        $targetProvince = trim((string) ($document->province ?? ''));
        if ($targetProvince === '' && $officeName !== '') {
            $targetProvince = trim((string) ($this->findProvinceByOffice($officeName) ?? ''));
        }

        $relatedUserIds = collect([
            $document->uploaded_by,
            $document->approved_by_dilg_po,
            $document->approved_by_dilg_ro,
        ])->filter()->map(fn($val) => (int) $val)->values()->all();

        $actorId = (int) auth()->id();
        $actorName = trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'DILG Regional Office';

        if ($decision === 'approve') {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $this->logActivity($officeName, 'delete', 'Deletion Approved', $document, $remarks, $now);

            try {
                $notificationService = app(InterventionNotificationService::class);
                $message = sprintf(
                    'Deletion request for %s has been APPROVED by DILG Regional Office.',
                    $this->formatDocumentLabel($document)
                );
                if ($remarks) {
                    $message .= ' Remarks: ' . $remarks;
                }
                $url = route($this->getCategoryInfo()['editRoute'], [
                    'office' => $officeName,
                    'year' => $document->year ?: now()->year
                ]);

                $notificationService->notifyScopedLgu(
                    $targetProvince,
                    $officeName,
                    $relatedUserIds,
                    $actorId,
                    $message,
                    $url,
                    'monitoring-evaluation-monthly',
                    $document->month ?? null
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to send deletion approval notification: ' . $e->getMessage());
            }

            $document->delete();

            return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $document->year])->with('success', 'Document deletion request approved and file deleted.');
        } else {
            $document->update([
                'status' => 'approved',
                'approval_remarks' => $remarks,
            ]);

            $this->logActivity($officeName, 'delete_reject', 'Deletion Rejected', $document, $remarks, $now);

            try {
                $notificationService = app(InterventionNotificationService::class);
                $message = sprintf(
                    'Deletion request for %s has been REJECTED by DILG Regional Office.',
                    $this->formatDocumentLabel($document)
                );
                if ($remarks) {
                    $message .= ' Remarks: ' . $remarks;
                }
                $url = route($this->getCategoryInfo()['editRoute'], [
                    'office' => $officeName,
                    'year' => $document->year ?: now()->year
                ]);

                $notificationService->notifyScopedLgu(
                    $targetProvince,
                    $officeName,
                    $relatedUserIds,
                    $actorId,
                    $message,
                    $url,
                    'monitoring-evaluation-monthly',
                    $document->month ?? null
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to send deletion rejection notification: ' . $e->getMessage());
            }

            return redirect()->route($this->getCategoryInfo()['editRoute'], ['office' => $officeName, 'year' => $document->year])->with('success', 'Document deletion request rejected.');
        }
    }

    public function export(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $months = $this->monthOptions();
        $officeRows = $this->buildOfficeRows($this->getOffices());

        $filters = [
            'province' => trim((string) $request->query('province', '')),
        ];

        $user = auth()->user();
        if ($user && $user->isLguScopedUser() && $user->normalizedOffice() !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                return $user->matchesAssignedOffice((string) ($row['city_municipality'] ?? ''));
            }));
        } elseif ($user && $user->isDilgUser() && !empty($user->province)) {
            if ($user->province !== 'Regional Office') {
                $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                    return $row['province'] === $user->province;
                }));
            }
        }

        if ($filters['province'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($filters) {
                return (string) ($row['province'] ?? '') === $filters['province'];
            }));
        }

        $officeRowsCollection = collect($officeRows);
        $officeNames = $officeRowsCollection
            ->pluck('city_municipality')
            ->unique()
            ->values()
            ->all();

        $documentsByOffice = [];
        if (!empty($officeNames)) {
            $documents = MonitoringEvaluationMonthlyDocument::query()
                ->whereIn('office', $officeNames)
                ->where('doc_type', $this->reportDocType())
                ->where('year', $reportingYear)
                ->get();

            foreach ($documents as $doc) {
                $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->month ?? '');
                $documentsByOffice[$doc->office][$key] = $doc;
            }
        }

        $headers = [
            'Province / Office',
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

        $getDocStatusText = function ($doc) {
            if ($doc && $doc->status === 'approved') {
                return '✓';
            }
            return '✗';
        };

        $rows = [];
        foreach ($officeRowsCollection as $row) {
            $officeName = $row['city_municipality'];
            $officeDocs = $documentsByOffice[$officeName] ?? [];

            $rowData = [
                $officeName,
            ];
            foreach (array_keys($months) as $monthCode) {
                $rowData[] = $getDocStatusText($officeDocs[$this->reportDocType() . '|' . $reportingYear . '|' . $monthCode] ?? null);
            }
            $rows[] = $rowData;
        }

        $filename = 'monitoring_evaluation_submissions_report_' . $reportingYear . '_' . date('Ymd_His');
        return $this->exportExcel($filename . '.xls', $headers, $rows, $reportingYear);
    }

    private function exportExcel(string $filename, array $headers, array $rows, int $year)
    {
        $title = 'Monthly Monitoring and Evaluation Reports Submissions - CY ' . $year;
        $table = $this->buildHtmlTable($headers, $rows);
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0">';
        $html .= '<tr><td colspan="' . count($headers) . '"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2></td></tr>';
        $html .= '<tr><td colspan="' . count($headers) . '">&nbsp;</td></tr>';
        $html .= '</table>';
        $html .= $table;
        $html .= '</body></html>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function buildHtmlTable(array $headers, array $rows): string
    {
        $table = '<table border="1" cellpadding="3" cellspacing="0" style="font-size: 10px; width: 100%; border-collapse: collapse;">';
        $table .= '<thead><tr style="background-color:#f3f4f6;">';
        foreach ($headers as $header) {
            $table .= '<th style="font-weight:bold; border: 1px solid #d1d5db; text-align: center;">' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $table .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $table .= '<tr>';
            foreach ($row as $cell) {
                $table .= '<td style="border: 1px solid #e5e7eb;">' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';
        return $table;
    }

    private function getCategoryInfo(): array
    {
        $docType = $this->reportDocType();
        $isLfp = ($docType === 'monitoring_evaluation_monthly_lfp');
        $prefix = $isLfp ? 'lfp' : 'rlip-lime';
        $categoryName = $isLfp ? 'LFP' : 'RLIP/LIME';

        return [
            'category' => $prefix,
            'pageTitle' => $categoryName . ' Monitoring and Evaluation Monthly Reports',
            'reportDocType' => $docType,
            'baseRouteUrl' => url('/reports/dilg-deliverables/monitoring-and-evaluation-reports/' . $prefix),
            'indexRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix,
            'editRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.edit',
            'uploadRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.upload',
            'exportRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.export',
            'approveRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.approve',
            'viewDocumentRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.view-document',
            'deleteRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.delete',
            'requestDeletionRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.request-deletion',
            'decideDeletionRoute' => 'reports.dilg-deliverables.monitoring-evaluation.' . $prefix . '.decide-deletion',
        ];
    }

    // LFP Wrappers
    public function indexLfp(Request $request)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->index($request);
    }
    public function exportLfp(Request $request)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->export($request);
    }
    public function editLfp(Request $request, $office)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->edit($request, $office);
    }
    public function uploadLfp(Request $request, $office)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->upload($request, $office);
    }
    public function approveDocumentLfp(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->approveDocument($request, $office, $docId);
    }
    public function viewDocumentLfp($office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->viewDocument($office, $docId);
    }
    public function deleteDocumentLfp($office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->deleteDocument($office, $docId);
    }
    public function requestDeletionLfp(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->requestDeletion($request, $office, $docId);
    }
    public function decideDeletionLfp(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_lfp';
        return $this->decideDeletion($request, $office, $docId);
    }

    // RLIP/LIME Wrappers
    public function indexRlipLime(Request $request)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->index($request);
    }
    public function exportRlipLime(Request $request)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->export($request);
    }
    public function editRlipLime(Request $request, $office)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->edit($request, $office);
    }
    public function uploadRlipLime(Request $request, $office)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->upload($request, $office);
    }
    public function approveDocumentRlipLime(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->approveDocument($request, $office, $docId);
    }
    public function viewDocumentRlipLime($office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->viewDocument($office, $docId);
    }
    public function deleteDocumentRlipLime($office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->deleteDocument($office, $docId);
    }
    public function requestDeletionRlipLime(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->requestDeletion($request, $office, $docId);
    }
    public function decideDeletionRlipLime(Request $request, $office, $docId)
    {
        $this->currentDocType = 'monitoring_evaluation_monthly_rlip_lime';
        return $this->decideDeletion($request, $office, $docId);
    }
}

