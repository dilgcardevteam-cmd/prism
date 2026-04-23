<?php

namespace App\Http\Controllers;

use App\Models\RoadMaintenanceStatusDocument;
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

class RoadMaintenanceStatusReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:road_maintenance_status_reports,view')->only(['index', 'show', 'edit', 'viewDocument']);
        $this->middleware('crud_permission:road_maintenance_status_reports,add')->only(['create', 'store', 'upload']);
        $this->middleware('crud_permission:road_maintenance_status_reports,update')->only(['update', 'approveDocument']);
        $this->middleware('crud_permission:road_maintenance_status_reports,delete')->only(['destroy']);
        $this->middleware('superadmin')->only(['deleteDocument']);
    }

    private function getOffices(): array
    {
        return [
            'Abra' => [
                'PLGU Abra', 'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores',
                'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong',
                'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan',
                'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa',
            ],
            'Apayao' => [
                'PLGU Apayao', 'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela',
            ],
            'Benguet' => [
                'PLGU Benguet', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan',
                'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay',
            ],
            'City of Baguio' => [
                'City of Baguio',
            ],
            'Ifugao' => [
                'PLGU Ifugao', 'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan',
                'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc',
            ],
            'Kalinga' => [
                'PLGU Kalinga', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tanudan',
            ],
            'Mountain Province' => [
                'PLGU Mountain Province', 'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis',
                'Sabangan', 'Sadanga', 'Sagada', 'Tadian',
            ],
        ];
    }

    private function quarterlyDocType(): string
    {
        return 'road_maintenance_status';
    }

    private function resolveReportingYear(Request $request): int
    {
        $year = (int) $request->query('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return $year;
    }

    private function buildOfficeRows(array $offices): array
    {
        $officeRows = [];
        foreach ($offices as $province => $municipalities) {
            foreach ($municipalities as $office) {
                $officeRows[] = [
                    'province' => $province,
                    'city_municipality' => $office,
                ];
            }
        }

        return $officeRows;
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

    private function indexDocumentsByKey($documents): array
    {
        $indexed = [];
        foreach ($documents as $doc) {
            $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->quarter ?? '');
            $indexed[$key] = $doc;
        }

        return $indexed;
    }

    private function formatDocumentLabel(RoadMaintenanceStatusDocument $document): string
    {
        $suffixParts = [];
        if (!empty($document->year)) {
            $suffixParts[] = 'CY ' . $document->year;
        }
        if (!empty($document->quarter)) {
            $suffixParts[] = $document->quarter;
        }
        $suffix = empty($suffixParts) ? '' : ' (' . implode(' ', $suffixParts) . ')';

        return 'Road Maintenance Status Report' . $suffix;
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

        if (($context['module'] ?? null) !== 'road_maintenance') {
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
            'document' => $context['document_label'] ?? 'Road Maintenance Status Report',
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
                if ($logEntry === '' || strpos($logEntry, '"module":"road_maintenance"') === false) {
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
            // Persisted logs are append-only history; keep all of them.
            // Add current-state fallback entries only when they are not yet in persisted history.
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
        RoadMaintenanceStatusDocument $document,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        $timestamp = $timestamp ?: now();

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'road_maintenance',
            'office' => $officeName,
            'doc_type' => $document->doc_type,
            'year' => $document->year,
            'quarter' => $document->quarter,
            'document_label' => $this->formatDocumentLabel($document),
            'action' => $action,
            'action_label' => $actionLabel,
            'action_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
            'remarks' => $remarks,
        ]);
    }

    private function notifyLguUsersAfterRegionalApproval(
        RoadMaintenanceStatusDocument $document,
        string $action,
        bool $isRegionalOffice,
        ?string $remarks = null
    ): void
    {
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
            if ($provinceRecipients->isEmpty()) {
                return;
            }

            $recipients = $provinceRecipients;
            if (!empty($candidateOfficeNames)) {
                $filteredRecipients = $provinceRecipients->filter(function ($lguUser) use ($candidateOfficeNames) {
                    $office = strtolower(trim((string) ($lguUser->office ?? '')));
                    $officeWithoutPrefix = trim((string) preg_replace('/^(municipality|city)\s+of\s+/i', '', $office));
                    return in_array($office, $candidateOfficeNames, true)
                        || in_array($officeWithoutPrefix, $candidateOfficeNames, true);
                })->values();

                // Fallback to province-level recipients when office normalization does not match.
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

            $actorName = trim((string) ($actor->fname ?? '') . ' ' . (string) ($actor->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'DILG Regional Office';
            }

            $url = $targetOffice !== ''
                ? route('road-maintenance-status.edit', ['roadMaintenance' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route('road-maintenance-status.index');
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
                    'road-maintenance-status',
                    $document->quarter ?? null
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
                'road-maintenance-status',
                $document->quarter ?? null
            );
        } catch (\Throwable $error) {
            Log::warning('Failed to create approval notifications (Road Maintenance).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function notifyWorkflowUsersOnUpload(RoadMaintenanceStatusDocument $document): void
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

            $actorName = $actor->fullName() ?: 'A user';
            $url = $targetOffice !== ''
                ? route('road-maintenance-status.edit', ['roadMaintenance' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route('road-maintenance-status.index');
            $actorId = (int) ($actor->idno ?? auth()->id());
            $notificationService = app(InterventionNotificationService::class);

            if ($actor->isLguScopedUser() && $targetProvince !== '') {
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
                    'road-maintenance-status',
                    $document->quarter ?? null
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
                    'road-maintenance-status',
                    $document->quarter ?? null
                );
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create upload notifications (Road Maintenance).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function normalizeRoadMaintenanceDocumentStatus(?RoadMaintenanceStatusDocument $document): string
    {
        if (!$document || empty($document->file_path)) {
            return 'no_upload';
        }

        $status = trim(Str::lower((string) ($document->status ?? '')));

        if ($status === 'approved') {
            return 'approved';
        }

        if ($status === 'returned') {
            return 'returned';
        }

        if ($status === 'pending_ro') {
            return 'pending_ro';
        }

        return 'pending_po';
    }

    private function resolveRoadMaintenanceValidationPriority(?RoadMaintenanceStatusDocument $document): int
    {
        $status = $this->normalizeRoadMaintenanceDocumentStatus($document);

        return match ($status) {
            'pending_po', 'pending_ro' => 0,
            'returned' => 1,
            'approved' => 2,
            default => 3,
        };
    }

    private function summarizeRoadMaintenanceValidation(array $officeDocuments): array
    {
        $summary = [
            'priority' => 3,
            'approval_status_label' => 'Awaiting Upload',
            'approval_status_text_color' => '#4b5563',
            'approval_status_background_color' => '#f3f4f6',
            'approval_status_border_color' => '#d1d5db',
            'date_submitted_label' => '—',
            'uploaded_at_timestamp' => 0,
            'validation_level_label' => '—',
            'validation_level_text_color' => '#4b5563',
            'validation_level_background_color' => '#f3f4f6',
            'validation_level_border_color' => '#d1d5db',
        ];

        $selectedDocument = collect($officeDocuments)
            ->filter(function ($document) {
                return $document instanceof RoadMaintenanceStatusDocument && trim((string) ($document->file_path ?? '')) !== '';
            })
            ->sort(function (RoadMaintenanceStatusDocument $left, RoadMaintenanceStatusDocument $right) {
                $leftPriority = $this->resolveRoadMaintenanceValidationPriority($left);
                $rightPriority = $this->resolveRoadMaintenanceValidationPriority($right);
                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                $leftUploadedAt = $left->uploaded_at ? Carbon::parse($left->uploaded_at)->getTimestamp() : 0;
                $rightUploadedAt = $right->uploaded_at ? Carbon::parse($right->uploaded_at)->getTimestamp() : 0;

                return $rightUploadedAt <=> $leftUploadedAt;
            })
            ->first();

        if (!$selectedDocument) {
            return $summary;
        }

        $status = $this->normalizeRoadMaintenanceDocumentStatus($selectedDocument);
        $uploadedAtTimestamp = $selectedDocument->uploaded_at ? Carbon::parse($selectedDocument->uploaded_at)->getTimestamp() : 0;
        $summary['priority'] = $this->resolveRoadMaintenanceValidationPriority($selectedDocument);
        $summary['uploaded_at_timestamp'] = $uploadedAtTimestamp;
        $summary['date_submitted_label'] = $selectedDocument->uploaded_at
            ? Carbon::parse($selectedDocument->uploaded_at)->setTimezone(config('app.timezone'))->format('M d, Y h:i A')
            : '—';

        if ($status === 'returned') {
            $summary['approval_status_label'] = 'Returned';
            $summary['approval_status_text_color'] = '#b91c1c';
            $summary['approval_status_background_color'] = '#fef2f2';
            $summary['approval_status_border_color'] = '#fca5a5';
            $summary['validation_level_label'] = $selectedDocument->approved_at_dilg_po
                ? 'Returned at DILG Regional Office'
                : 'Returned at DILG Provincial Office';
            $summary['validation_level_text_color'] = '#b91c1c';
            $summary['validation_level_background_color'] = '#fef2f2';
            $summary['validation_level_border_color'] = '#fca5a5';

            return $summary;
        }

        if ($status === 'pending_ro') {
            $summary['approval_status_label'] = 'For DILG Regional Office Validation';
            $summary['approval_status_text_color'] = '#1d4ed8';
            $summary['approval_status_background_color'] = '#dbeafe';
            $summary['approval_status_border_color'] = '#60a5fa';
            $summary['validation_level_label'] = 'DILG Regional Office';
            $summary['validation_level_text_color'] = '#1d4ed8';
            $summary['validation_level_background_color'] = '#dbeafe';
            $summary['validation_level_border_color'] = '#60a5fa';

            return $summary;
        }

        if ($status === 'pending_po') {
            $summary['approval_status_label'] = 'For DILG Provincial Office Validation';
            $summary['approval_status_text_color'] = '#1d4ed8';
            $summary['approval_status_background_color'] = '#eff6ff';
            $summary['approval_status_border_color'] = '#93c5fd';
            $summary['validation_level_label'] = 'DILG Provincial Office';
            $summary['validation_level_text_color'] = '#1d4ed8';
            $summary['validation_level_background_color'] = '#eff6ff';
            $summary['validation_level_border_color'] = '#93c5fd';

            return $summary;
        }

        if ($status === 'approved') {
            $summary['approval_status_label'] = 'Approved';
            $summary['approval_status_text_color'] = '#047857';
            $summary['approval_status_background_color'] = '#ecfdf5';
            $summary['approval_status_border_color'] = '#6ee7b7';
            $summary['validation_level_label'] = 'Completed';
            $summary['validation_level_text_color'] = '#047857';
            $summary['validation_level_background_color'] = '#ecfdf5';
            $summary['validation_level_border_color'] = '#6ee7b7';
        }

        return $summary;
    }

    public function index(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
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

        $officeRowsCollection = collect($officeRows);
        $officeNames = $officeRowsCollection
            ->pluck('city_municipality')
            ->unique()
            ->values()
            ->all();

        $documentsByOffice = [];
        if (!empty($officeNames)) {
            $documents = RoadMaintenanceStatusDocument::whereIn('office', $officeNames)
                ->where('doc_type', $this->quarterlyDocType())
                ->where('year', $reportingYear)
                ->get();

            foreach ($documents as $doc) {
                $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->quarter ?? '');
                $documentsByOffice[$doc->office][$key] = $doc;
            }
        }

        $officeValidationSummaryByOffice = $officeRowsCollection
            ->mapWithKeys(function (array $row) use ($documentsByOffice) {
                $officeName = (string) ($row['city_municipality'] ?? '');
                return [$officeName => $this->summarizeRoadMaintenanceValidation($documentsByOffice[$officeName] ?? [])];
            });

        $officeRowsCollection = $officeRowsCollection
            ->sort(function (array $leftRow, array $rightRow) use ($officeValidationSummaryByOffice) {
                $leftSummary = $officeValidationSummaryByOffice->get($leftRow['city_municipality'], ['priority' => 3, 'uploaded_at_timestamp' => 0]);
                $rightSummary = $officeValidationSummaryByOffice->get($rightRow['city_municipality'], ['priority' => 3, 'uploaded_at_timestamp' => 0]);

                $priorityComparison = ((int) ($leftSummary['priority'] ?? 3)) <=> ((int) ($rightSummary['priority'] ?? 3));
                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                $uploadedAtComparison = ((int) ($rightSummary['uploaded_at_timestamp'] ?? 0)) <=> ((int) ($leftSummary['uploaded_at_timestamp'] ?? 0));
                if ($uploadedAtComparison !== 0) {
                    return $uploadedAtComparison;
                }

                $provinceComparison = strcasecmp((string) ($leftRow['province'] ?? ''), (string) ($rightRow['province'] ?? ''));
                if ($provinceComparison !== 0) {
                    return $provinceComparison;
                }

                return strcasecmp((string) ($leftRow['city_municipality'] ?? ''), (string) ($rightRow['city_municipality'] ?? ''));
            })
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
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

        return view('reports.road-maintenance-status.index', compact(
            'officeRows',
            'documentsByOffice',
            'officeValidationSummaryByOffice',
            'reportingYear',
            'perPage',
            'filters',
            'filterOptions'
        ));
    }

    public function create()
    {
        return redirect()->route('road-maintenance-status.index');
    }

    public function store(Request $request)
    {
        return redirect()->route('road-maintenance-status.index');
    }

    public function show(Request $request, $id)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        $documents = RoadMaintenanceStatusDocument::where('office', $officeName)
            ->where('doc_type', $this->quarterlyDocType())
            ->where('year', $reportingYear)
            ->orderBy('quarter')
            ->get();
        $documentsByKey = $this->indexDocumentsByKey($documents);

        return view('reports.road-maintenance-status.show', compact('officeName', 'province', 'documents', 'documentsByKey', 'reportingYear'));
    }

    public function edit(Request $request, $id)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        $documents = RoadMaintenanceStatusDocument::where('office', $officeName)
            ->where('doc_type', $this->quarterlyDocType())
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
        $configuredQuarterDeadlines = app(LguReportorialDeadlineResolver::class)->resolveMany(
            'road_maintenance_status_reports',
            $reportingYear,
            ['Q1', 'Q2', 'Q3', 'Q4']
        );

        return view('reports.road-maintenance-status.edit', compact(
            'officeName',
            'province',
            'documentsByKey',
            'usersById',
            'activityLogs',
            'reportingYear',
            'configuredQuarterDeadlines'
        ));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('road-maintenance-status.edit', ['roadMaintenance' => $id]);
    }

    public function deleteDocument($id, $docId)
    {
        $officeName = (string) $id;
        $document = RoadMaintenanceStatusDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Uploaded document deleted successfully.');
    }

    public function upload(Request $request, $id)
    {
        $officeName = $id;
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
        ]);

        $province = $this->findProvinceByOffice($officeName) ?? 'Unknown';
        $year = (int) $request->input('year');
        $quarter = $request->input('quarter');
        $docType = $this->quarterlyDocType();
        $existingDocument = RoadMaintenanceStatusDocument::where('office', $officeName)
            ->where('doc_type', $docType)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();
        $oldFilePath = $existingDocument?->file_path;

        $file = $request->file('document');
        $officeSlug = Str::slug($officeName, '_');
        $path = $file->store('road-maintenance-status/' . $officeSlug, 'public');
        $uploadedAt = now();
        $user = auth()->user();
        $isMountainProvinceDilgUploader = $user
            && strtoupper(trim((string) $user->agency)) === 'DILG'
            && strtolower(trim((string) $user->province)) === 'mountain province';

        $document = RoadMaintenanceStatusDocument::updateOrCreate(
            [
                'office' => $officeName,
                'doc_type' => $docType,
                'year' => $year,
                'quarter' => $quarter,
            ],
            [
                'province' => $province,
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => $uploadedAt,
                'status' => $isMountainProvinceDilgUploader ? 'pending_ro' : 'pending',
                'approved_at' => $isMountainProvinceDilgUploader ? $uploadedAt : null,
                'approved_at_dilg_po' => $isMountainProvinceDilgUploader ? $uploadedAt : null,
                'approved_at_dilg_ro' => null,
                'approved_by_dilg_po' => $isMountainProvinceDilgUploader ? ($user->idno ?? auth()->id()) : null,
                'approved_by_dilg_ro' => null,
                'approval_remarks' => null,
                'user_remarks' => null,
            ]
        );

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        $this->logActivity($officeName, 'upload', 'Uploaded', $document, null, $uploadedAt);
        if ($isMountainProvinceDilgUploader) {
            $this->logActivity($officeName, 'validate_po', 'Validated (DILG PO)', $document, null, $uploadedAt);
        }

        $this->notifyWorkflowUsersOnUpload($document);

        return redirect()
            ->back()
            ->with('success', 'Quarterly road maintenance status report uploaded successfully.');
    }

    public function viewDocument($id, $docId)
    {
        $officeName = $id;
        $document = RoadMaintenanceStatusDocument::where('office', $officeName)
            ->where('id', $docId)
            ->where('doc_type', $this->quarterlyDocType())
            ->firstOrFail();

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

    public function approveDocument(Request $request, $id, $docId)
    {
        $officeName = $id;
        $user = auth()->user();
        if (!$user || $user->agency !== 'DILG') {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $document = RoadMaintenanceStatusDocument::where('office', $officeName)
            ->where('id', $docId)
            ->where('doc_type', $this->quarterlyDocType())
            ->firstOrFail();

        $now = now();
        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $isRegionalOffice = $user->province === 'Regional Office';
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

        return back()->with('success', $action === 'approve' ? 'Document validated.' : 'Document returned.');
    }

    public function destroy($id)
    {
        return redirect()->route('road-maintenance-status.index');
    }
}
