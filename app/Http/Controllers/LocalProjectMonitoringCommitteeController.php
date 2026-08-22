<?php

namespace App\Http\Controllers;

use App\Services\InterventionNotificationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Support\LguReportorialDeadlineResolver;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\LpmcDocument;
use App\Models\LpmcDocumentFile;
use App\Support\InputSanitizer;
use App\Models\User;

class LocalProjectMonitoringCommitteeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:local_project_monitoring_committee,view')->only(['index', 'show', 'edit', 'viewDocument', 'export']);
        $this->middleware('crud_permission:local_project_monitoring_committee,add')->only(['create', 'store', 'upload']);
        $this->middleware('crud_permission:local_project_monitoring_committee,update')->only(['update', 'approveDocument']);
        $this->middleware('crud_permission:local_project_monitoring_committee,delete')->only(['destroy']);
        $this->middleware('superadmin')->only(['deleteDocument']);
    }

    private function getOffices(): array
    {
        $configuredHierarchy = ProjectLocationFilterHelper::buildConfiguredLocationHierarchy();
        if (empty($configuredHierarchy)) {
            return $this->getFallbackOffices();
        }

        $fallbackOffices = $this->getFallbackOffices();
        $configuredOffices = [];

        foreach ($configuredHierarchy as $province => $cityMap) {
            $provinceLabel = ProjectLocationFilterHelper::normalizeLabel($province);
            if ($provinceLabel === '') {
                continue;
            }

            $legacyProvinceOffices = $fallbackOffices[$provinceLabel] ?? [];
            $officeLabels = [];

            if (!$this->isIndependentCityProvince($provinceLabel)) {
                $officeLabels[] = 'PLGU ' . $provinceLabel;
            }

            foreach (array_keys(is_array($cityMap) ? $cityMap : []) as $cityLabel) {
                $normalizedCityLabel = ProjectLocationFilterHelper::normalizeLabel($cityLabel);
                if ($normalizedCityLabel === '') {
                    continue;
                }

                $officeLabels[] = $this->resolveConfiguredOfficeLabel($normalizedCityLabel, $legacyProvinceOffices);
            }

            $configuredOffices[$provinceLabel] = collect($officeLabels)
                ->map(fn ($label) => ProjectLocationFilterHelper::normalizeLabel($label))
                ->filter()
                ->unique(function ($label) {
                    return mb_strtolower((string) $label);
                })
                ->values()
                ->all();
        }

        return !empty($configuredOffices) ? $configuredOffices : $fallbackOffices;
    }

    private function getFallbackOffices(): array
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

    private function resolveConfiguredOfficeLabel(string $officeName, array $fallbackOffices): string
    {
        foreach ($fallbackOffices as $fallbackOffice) {
            if (strcasecmp($fallbackOffice, $officeName) === 0) {
                return $fallbackOffice;
            }
        }

        $comparableOffice = ProjectLocationFilterHelper::normalizeComparableLocationLabel($officeName);
        foreach ($fallbackOffices as $fallbackOffice) {
            if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($fallbackOffice) === $comparableOffice) {
                return $fallbackOffice;
            }
        }

        return $officeName;
    }

    private function isIndependentCityProvince(string $provinceName): bool
    {
        return ProjectLocationFilterHelper::normalizeComparableLocationLabel($provinceName) === 'baguio';
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

    private function getDocTypeLabel(string $docType): string
    {
        return [
            'eo' => 'Executive Order',
            'awfp' => 'Annual Work and Financial Plan',
            'mep' => 'Monitoring and Evaluation Plan',
            'meetings' => 'Meetings Conducted',
            'monitoring' => 'Monitoring Conducted',
            'training' => 'Training Conducted',
        ][$docType] ?? strtoupper($docType);
    }

    private function formatDocumentLabel(LpmcDocument $document): string
    {
        $label = $this->getDocTypeLabel($document->doc_type);
        $suffixParts = [];
        if (!empty($document->year)) {
            $suffixParts[] = 'CY ' . $document->year;
        }
        if (!empty($document->quarter)) {
            $suffixParts[] = $document->quarter;
        }
        if (empty($suffixParts)) {
            return $label;
        }
        return $label . ' (' . implode(' ', $suffixParts) . ')';
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
                    'doc_type' => $doc->doc_type,
                    'year' => $doc->year,
                    'quarter' => $doc->quarter,
                    'user_id' => $doc->uploaded_by,
                    'remarks' => null,
                ];
            }

            if ($doc->approved_at_dilg_po) {
                $logs[] = [
                    'timestamp' => $doc->approved_at_dilg_po,
                    'action' => 'Validated (DILG PO)',
                    'document' => $docLabel,
                    'doc_type' => $doc->doc_type,
                    'year' => $doc->year,
                    'quarter' => $doc->quarter,
                    'user_id' => $doc->approved_by_dilg_po,
                    'remarks' => null,
                ];
            }

            if ($doc->approved_at_dilg_ro) {
                $logs[] = [
                    'timestamp' => $doc->approved_at_dilg_ro,
                    'action' => 'Validated (DILG RO)',
                    'document' => $docLabel,
                    'doc_type' => $doc->doc_type,
                    'year' => $doc->year,
                    'quarter' => $doc->quarter,
                    'user_id' => $doc->approved_by_dilg_ro,
                    'remarks' => null,
                ];
            }

            if ($doc->status === 'returned') {
                $logs[] = [
                    'timestamp' => $doc->approved_at ?? $doc->updated_at ?? $doc->uploaded_at,
                    'action' => 'Returned',
                    'document' => $docLabel,
                    'doc_type' => $doc->doc_type,
                    'year' => $doc->year,
                    'quarter' => $doc->quarter,
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

        if (($context['module'] ?? null) !== 'lpmc') {
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

        $docType = $context['doc_type'] ?? '';
        $year = $context['year'] ?? '';
        $quarter = $context['quarter'] ?? '';
        $docLabel = $context['document_label'] ?? 'Document';

        if (!$docType) {
            $lblLower = strtolower($docLabel);
            if (str_contains($lblLower, 'executive order')) $docType = 'eo';
            elseif (str_contains($lblLower, 'work and financial plan') || str_contains($lblLower, 'awfp')) $docType = 'awfp';
            elseif (str_contains($lblLower, 'monitoring and evaluation plan') || str_contains($lblLower, 'mep')) $docType = 'mep';
            elseif (str_contains($lblLower, 'meetings')) $docType = 'meetings';
            elseif (str_contains($lblLower, 'monitoring conducted')) $docType = 'monitoring';
            elseif (str_contains($lblLower, 'training')) $docType = 'training';
        }

        if (!$year) {
            if (str_contains($docLabel, '2025')) $year = '2025';
            elseif (str_contains($docLabel, '2026')) $year = '2026';
        }

        if (!$quarter) {
            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
                if (str_contains($docLabel, $q)) {
                    $quarter = $q;
                    break;
                }
            }
        }

        return [
            'timestamp' => $timestamp,
            'action' => $context['action_label'] ?? 'Updated',
            'document' => $docLabel,
            'doc_type' => (string) $docType,
            'year' => (string) $year,
            'quarter' => (string) $quarter,
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
                if ($logEntry === '' || strpos($logEntry, '"module":"lpmc"') === false) {
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
        LpmcDocument $document,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        $timestamp = $timestamp ?: now();

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'lpmc',
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
        LpmcDocument $document,
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
                ? route('local-project-monitoring-committee.edit', ['lpmc' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route('local-project-monitoring-committee.index');
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
                    'lpmc-' . (string) ($document->doc_type ?? 'document'),
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
                'lpmc-' . (string) ($document->doc_type ?? 'document'),
                $document->quarter ?? null
            );
        } catch (\Throwable $error) {
            Log::warning('Failed to create approval notifications (LPMC).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function provincialUploadRecipientIds(?string $province)
    {
        $normalizedProvince = Str::lower(trim((string) $province));
        if ($normalizedProvince === '') {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('role', User::ROLE_PROVINCIAL)
                    ->orWhere(function ($fallback) {
                        $fallback->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
                            ->whereRaw('LOWER(TRIM(COALESCE(role, ""))) NOT IN (?, ?, ?)', [
                                strtolower(User::ROLE_REGIONAL),
                                'lgu',
                                'mlgoo',
                            ])
                            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) <> ?', ['regional office'])
                            ->whereRaw('TRIM(COALESCE(province, "")) <> ""');
                    });
            })
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$normalizedProvince])
            ->pluck('idno');
    }

    private function notifyWorkflowUsersOnUpload(LpmcDocument $document): void
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
                ? route('local-project-monitoring-committee.edit', ['lpmc' => $targetOffice, 'year' => $document->year ?: now()->year])
                : route('local-project-monitoring-committee.index');
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

                $recipientIds = $this->provincialUploadRecipientIds($targetProvince);
                if ($recipientIds->isNotEmpty()) {
                    $notificationService->notifyRecipientIds(
                        $recipientIds,
                        $actorId,
                        $message,
                        $url,
                        'lpmc-' . (string) ($document->doc_type ?? 'document'),
                        $document->quarter ?? null
                    );
                } else {
                    $notificationService->notifyProvincialDilg(
                        $targetProvince,
                        $actorId,
                        $message,
                        $url,
                        'lpmc-' . (string) ($document->doc_type ?? 'document'),
                        $document->quarter ?? null
                    );
                }

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
                    'lpmc-' . (string) ($document->doc_type ?? 'document'),
                    $document->quarter ?? null
                );
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create upload notifications (LPMC).', [
                'document_id' => $document->id ?? null,
                'office' => $document->office ?? null,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function normalizeOfficeDocumentStatus(?LpmcDocument $document): string
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

    private function officeMatchesStatusFilter(array $officeDocuments, string $filter): bool
    {
        $normalizedFilter = trim(Str::lower($filter));
        if ($normalizedFilter === '' || $normalizedFilter === 'all') {
            return true;
        }

        $documents = collect($officeDocuments)
            ->filter(function ($document) {
                return $document instanceof LpmcDocument;
            })
            ->values();

        if ($normalizedFilter === 'no_upload') {
            return $documents->every(function (LpmcDocument $document) {
                return $this->normalizeOfficeDocumentStatus($document) === 'no_upload';
            });
        }

        return $documents->contains(function (LpmcDocument $document) use ($normalizedFilter) {
            return $this->normalizeOfficeDocumentStatus($document) === $normalizedFilter;
        });
    }

    private function summarizeOfficeValidation(array $officeDocuments): array
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
                return $document instanceof LpmcDocument && trim((string) ($document->file_path ?? '')) !== '';
            })
            ->sort(function (LpmcDocument $left, LpmcDocument $right) {
                $leftPriority = $this->resolveOfficeValidationPriority($left);
                $rightPriority = $this->resolveOfficeValidationPriority($right);
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

        $status = $this->normalizeOfficeDocumentStatus($selectedDocument);
        $uploadedAtTimestamp = $selectedDocument->uploaded_at ? Carbon::parse($selectedDocument->uploaded_at)->getTimestamp() : 0;
        $summary['priority'] = $this->resolveOfficeValidationPriority($selectedDocument);
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

    private function resolveOfficeValidationPriority(?LpmcDocument $document): int
    {
        $status = $this->normalizeOfficeDocumentStatus($document);

        return match ($status) {
            'pending_po', 'pending_ro' => 0,
            'returned' => 1,
            'approved' => 2,
            default => 3,
        };
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $officeRows = $this->buildOfficeRows($this->getOffices());
        $perPage = (int) $request->query('per_page', 15);
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'status' => trim((string) $request->query('status', '')),
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
        } elseif (
            $user
            && $user->isDilgUser()
            && !empty($user->province)
            && !$user->isRegionalUser()
            && !$user->isRegionalOfficeAssignment()
        ) {
            $selectedProvince = $request->query('province');
            $userProvince = !empty($selectedProvince) ? $selectedProvince : $user->province;
            if ($userProvince !== 'Regional Office') {
                $officeRows = array_values(array_filter($officeRows, function ($row) use ($userProvince) {
                    return $row['province'] === $userProvince;
                }));
            }
        }

        $filterOptions = [
            'provinces' => collect($officeRows)
                ->pluck('province')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'provinceMunicipalities' => collect($officeRows)
                ->groupBy('province')
                ->map(function ($rows) {
                    return collect($rows)
                        ->pluck('city_municipality')
                        ->map(function ($city) {
                            return trim((string) $city);
                        })
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                })
                ->toArray(),
            'statuses' => [
                'no_upload' => 'No Upload',
                'pending_po' => 'For PO Approval',
                'pending_ro' => 'For RO Approval',
                'approved' => 'Approved',
                'returned' => 'Returned',
            ],
        ];

        $documentsByOffice = [];
        $allOfficeNames = collect($officeRows)
            ->pluck('city_municipality')
            ->unique()
            ->values()
            ->all();

        if (!empty($allOfficeNames)) {
            $documents = LpmcDocument::whereIn('office', $allOfficeNames)->get();
            foreach ($documents as $doc) {
                $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->quarter ?? '');
                $documentsByOffice[$doc->office][$key] = $doc;
            }
        }

        if ($filters['search'] !== '') {
            $keyword = Str::lower($filters['search']);
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($keyword) {
                $province = Str::lower(trim((string) ($row['province'] ?? '')));
                $office = Str::lower(trim((string) ($row['city_municipality'] ?? '')));

                return str_contains($province, $keyword) || str_contains($office, $keyword);
            }));
        }

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

        if ($filters['status'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($documentsByOffice, $filters) {
                $officeName = (string) ($row['city_municipality'] ?? '');

                return $this->officeMatchesStatusFilter($documentsByOffice[$officeName] ?? [], $filters['status']);
            }));
        }

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $officeRowsCollection = collect($officeRows);
        $officeValidationSummaryByOffice = $officeRowsCollection
            ->mapWithKeys(function (array $row) use ($documentsByOffice) {
                $officeName = (string) ($row['city_municipality'] ?? '');
                return [$officeName => $this->summarizeOfficeValidation($documentsByOffice[$officeName] ?? [])];
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

        return view('reports.local-project-monitoring-committee.index', compact('officeRows', 'documentsByOffice', 'officeValidationSummaryByOffice', 'perPage', 'filters', 'filterOptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('reports.local-project-monitoring-committee.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Implementation for storing a new record
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        $documents = LpmcDocument::where('office', $officeName)->get();
        $documentsByKey = $this->indexDocumentsByKey($documents);
        return view('reports.local-project-monitoring-committee.show', compact('officeName', 'province', 'documents', 'documentsByKey'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        $documents = LpmcDocument::where('office', $officeName)->with('files')->get();
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
        $deadlineReportingYear = (int) now()->year;
        $configuredQuarterDeadlines = app(LguReportorialDeadlineResolver::class)->resolveMany(
            'local_project_monitoring_committee',
            $deadlineReportingYear,
            ['Q1', 'Q2', 'Q3', 'Q4']
        );

        return view('reports.local-project-monitoring-committee.edit', compact(
            'officeName',
            'province',
            'documentsByKey',
            'usersById',
            'activityLogs',
            'deadlineReportingYear',
            'configuredQuarterDeadlines'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Implementation for updating the record
    }

    public function deleteDocument($id, $docId)
    {
        $officeName = (string) $id;
        $document = LpmcDocument::query()
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
            'document' => ['required'],
            'document.*' => ['file', 'mimes:pdf', 'max:25600'],
            'doc_type' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer'],
            'quarter' => ['nullable', 'in:Q1,Q2,Q3,Q4'],
        ]);

        $province = $this->findProvinceByOffice($officeName) ?? 'Unknown';
        $docType = $request->input('doc_type');
        $year = $request->input('year');
        $quarter = $request->input('quarter');
        $isQuarterlyLpmcDocument = in_array($docType, ['meetings', 'monitoring', 'training'], true);

        if ($isQuarterlyLpmcDocument && $quarter) {
            $configuredQuarterDeadline = app(LguReportorialDeadlineResolver::class)->resolve(
                'local_project_monitoring_committee',
                (int) now()->year,
                $quarter
            );

            if (is_array($configuredQuarterDeadline) && !empty($configuredQuarterDeadline['is_closed'])) {
                $deadlineDisplay = trim((string) ($configuredQuarterDeadline['display'] ?? ''));

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Uploads are closed for ' . $quarter . ' based on the superadmin deadline'
                        . ($deadlineDisplay !== '' ? ' (' . $deadlineDisplay . ').' : '.')
                    );
            }
        }

        $existingDocument = LpmcDocument::where('office', $officeName)
            ->where('doc_type', $docType)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();

        // Block new uploads if document was already submitted and not yet returned,
        // UNLESS the slot has no files at all (legacy: file_path is null AND no files table rows).
        if ($existingDocument && $existingDocument->status !== 'returned') {
            $hasFiles = !empty($existingDocument->file_path) ||
                LpmcDocumentFile::where('lpmc_document_id', $existingDocument->id)->exists();
            if ($hasFiles) {
                return redirect()
                    ->back()
                    ->with('error', 'Document already submitted. Upload is disabled until the current file is returned.');
            }
        }

        $uploadedAt = now();
        $user = auth()->user();
        $isProvincialDilgUploader = $user && $user->isProvincialDilgAssignment();
        $officeSlug = Str::slug($officeName, '_');

        $document = LpmcDocument::updateOrCreate(
            [
                'office' => $officeName,
                'doc_type' => $docType,
                'year' => $year,
                'quarter' => $quarter,
            ],
            [
                'province' => $province,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => $uploadedAt,
                'status' => $isProvincialDilgUploader ? 'pending_ro' : 'pending',
                'approved_at' => $isProvincialDilgUploader ? $uploadedAt : null,
                'approved_at_dilg_po' => $isProvincialDilgUploader ? $uploadedAt : null,
                'approved_at_dilg_ro' => null,
                'approved_by_dilg_po' => $isProvincialDilgUploader ? ($user->idno ?? auth()->id()) : null,
                'approved_by_dilg_ro' => null,
                'approval_remarks' => null,
                'user_remarks' => null,
            ]
        );

        $uploadedFiles = $request->file('document');
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $firstFile = true;
        foreach ($uploadedFiles as $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('lpmc/' . $officeSlug, $originalName, 'public');

            LpmcDocumentFile::create([
                'lpmc_document_id' => $document->id,
                'file_path' => $path,
                'original_filename' => $originalName,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => $uploadedAt,
            ]);

            // Keep legacy file_path in sync with the first uploaded file
            if ($firstFile) {
                $document->update([
                    'file_path' => $path,
                    'original_filename' => $originalName,
                ]);
                $firstFile = false;
            }
        }

        $this->logActivity($officeName, 'upload', 'Uploaded', $document, null, $uploadedAt);
        if ($isProvincialDilgUploader) {
            $this->logActivity($officeName, 'validate_po', 'Validated (DILG PO)', $document, null, $uploadedAt);
        }

        $this->notifyWorkflowUsersOnUpload($document);

        return redirect()
            ->back()
            ->with('success', 'Document uploaded successfully.');
    }

    public function viewDocumentFile($id, $fileId)
    {
        $officeName = $id;
        $file = LpmcDocumentFile::where('id', $fileId)
            ->whereHas('document', fn ($q) => $q->where('office', $officeName))
            ->firstOrFail();

        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($file->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if (!in_array($extension, $inlineExtensions, true)) {
            return response()->download($filePath, $file->original_filename ?: basename($filePath), $headers);
        }

        return response()->file($filePath, $headers);
    }

    public function deleteDocumentFile($id, $fileId)
    {
        $officeName = (string) $id;
        $file = LpmcDocumentFile::where('id', $fileId)
            ->whereHas('document', fn ($q) => $q->where('office', $officeName))
            ->firstOrFail();

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $document = $file->document;
        $file->delete();

        // If no more files remain, clear the legacy file_path on the parent document
        $remainingFiles = LpmcDocumentFile::where('lpmc_document_id', $document->id)->latest()->first();
        if ($remainingFiles) {
            $document->update([
                'file_path' => $remainingFiles->file_path,
                'original_filename' => $remainingFiles->original_filename,
            ]);
        } else {
            $document->update(['file_path' => null, 'original_filename' => null]);
        }

        return back()->with('success', 'File deleted successfully.');
    }

    public function viewDocument($id, $docId)
    {
        $officeName = $id;
        $document = LpmcDocument::where('office', $officeName)->where('id', $docId)->firstOrFail();
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

        $document = LpmcDocument::where('office', $officeName)->where('id', $docId)->firstOrFail();
        $now = now();
        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $isRegionalOffice = (bool) ($user && $user->isRegionalOfficeAssignment());
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

    public function export(Request $request)
    {
        $officeRows = $this->buildOfficeRows($this->getOffices());
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $user = auth()->user();
        if ($user && $user->isLguScopedUser() && $user->normalizedOffice() !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                return $user->matchesAssignedOffice((string) ($row['city_municipality'] ?? ''));
            }));
        } elseif (
            $user
            && $user->isDilgUser()
            && !empty($user->province)
            && !$user->isRegionalUser()
            && !$user->isRegionalOfficeAssignment()
        ) {
            $selectedProvince = $request->query('province');
            $userProvince = !empty($selectedProvince) ? $selectedProvince : $user->province;
            if ($userProvince !== 'Regional Office') {
                $officeRows = array_values(array_filter($officeRows, function ($row) use ($userProvince) {
                    return $row['province'] === $userProvince;
                }));
            }
        }

        $documentsByOffice = [];
        $allOfficeNames = collect($officeRows)
            ->pluck('city_municipality')
            ->unique()
            ->values()
            ->all();

        if (!empty($allOfficeNames)) {
            $documents = LpmcDocument::whereIn('office', $allOfficeNames)->get();
            foreach ($documents as $doc) {
                $key = $doc->doc_type . '|' . ($doc->year ?? '') . '|' . ($doc->quarter ?? '');
                $documentsByOffice[$doc->office][$key] = $doc;
            }
        }

        if ($filters['search'] !== '') {
            $keyword = Str::lower($filters['search']);
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($keyword) {
                $province = Str::lower(trim((string) ($row['province'] ?? '')));
                $office = Str::lower(trim((string) ($row['city_municipality'] ?? '')));

                return str_contains($province, $keyword) || str_contains($office, $keyword);
            }));
        }

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

        if ($filters['status'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($documentsByOffice, $filters) {
                $officeName = (string) ($row['city_municipality'] ?? '');

                return $this->officeMatchesStatusFilter($documentsByOffice[$officeName] ?? [], $filters['status']);
            }));
        }

        $officeRowsCollection = collect($officeRows);
        $officeValidationSummaryByOffice = $officeRowsCollection
            ->mapWithKeys(function (array $row) use ($documentsByOffice) {
                $officeName = (string) ($row['city_municipality'] ?? '');
                return [$officeName => $this->summarizeOfficeValidation($documentsByOffice[$officeName] ?? [])];
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

        $headers = [
            'Province',
            'City/Municipality',
            'Executive Order for CY 2025 (MOV)',
            'Annual Work and Financial Plan (AWFP) for CY 2025',
            'Monitoring and Evaluation Plan for CY 2025',
            'Meetings Conducted Q1',
            'Meetings Conducted Q2',
            'Meetings Conducted Q3',
            'Meetings Conducted Q4',
            'Monitoring Conducted Q1',
            'Monitoring Conducted Q2',
            'Monitoring Conducted Q3',
            'Monitoring Conducted Q4',
            'Training Conducted Q1',
            'Training Conducted Q2',
            'Training Conducted Q3',
            'Training Conducted Q4',
            'Executive Order for 2026',
            'CY 2026 Annual Work and Financial Plan',
            'CY 2026 Monitoring and Evaluation Plan',
            'Approval Status',
            'Date Submitted',
            'Validation Level'
        ];

        $getDocStatusText = function ($doc) {
            if (!$doc) {
                return '-';
            }
            if ($doc->status === 'approved') {
                return 'Approved';
            }
            if ($doc->status === 'returned') {
                return 'Returned';
            }
            if ($doc->status === 'pending_ro') {
                return 'For RO';
            }
            if ($doc->status === 'pending' || !empty($doc->file_path)) {
                return 'For PO';
            }
            return '-';
        };

        $rows = [];
        foreach ($officeRowsCollection as $row) {
            $officeName = $row['city_municipality'];
            $officeDocs = $documentsByOffice[$officeName] ?? [];
            $validationSummary = $officeValidationSummaryByOffice[$officeName] ?? [
                'approval_status_label' => 'Awaiting Upload',
                'date_submitted_label' => '—',
                'validation_level_label' => '—',
            ];

            $rows[] = [
                $row['province'],
                $officeName,
                $getDocStatusText($officeDocs['eo|2025|'] ?? null),
                $getDocStatusText($officeDocs['awfp|2025|'] ?? null),
                $getDocStatusText($officeDocs['mep|2025|'] ?? null),
                $getDocStatusText($officeDocs['meetings||Q1'] ?? null),
                $getDocStatusText($officeDocs['meetings||Q2'] ?? null),
                $getDocStatusText($officeDocs['meetings||Q3'] ?? null),
                $getDocStatusText($officeDocs['meetings||Q4'] ?? null),
                $getDocStatusText($officeDocs['monitoring||Q1'] ?? null),
                $getDocStatusText($officeDocs['monitoring||Q2'] ?? null),
                $getDocStatusText($officeDocs['monitoring||Q3'] ?? null),
                $getDocStatusText($officeDocs['monitoring||Q4'] ?? null),
                $getDocStatusText($officeDocs['training||Q1'] ?? null),
                $getDocStatusText($officeDocs['training||Q2'] ?? null),
                $getDocStatusText($officeDocs['training||Q3'] ?? null),
                $getDocStatusText($officeDocs['training||Q4'] ?? null),
                $getDocStatusText($officeDocs['eo|2026|'] ?? null),
                $getDocStatusText($officeDocs['awfp|2026|'] ?? null),
                $getDocStatusText($officeDocs['mep|2026|'] ?? null),
                $validationSummary['approval_status_label'] ?? 'Awaiting Upload',
                $validationSummary['date_submitted_label'] ?? '—',
                $validationSummary['validation_level_label'] ?? '—',
            ];
        }

        $format = strtolower($request->query('format', 'excel'));
        $filename = 'local_project_monitoring_committee_' . date('Ymd_His');

        if ($format === 'pdf') {
            return $this->exportPdf($filename . '.pdf', $headers, $rows);
        }

        return $this->exportExcel($filename . '.xls', $headers, $rows);
    }

    private function exportExcel(string $filename, array $headers, array $rows)
    {
        $title = 'Local Project Monitoring Committee (LPMC) Reports';
        $table = $this->buildHtmlTable($headers, $rows, false, true);
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

    private function exportPdf(string $filename, array $headers, array $rows)
    {
        $title = 'Local Project Monitoring Committee (LPMC) Reports';
        $pdf = new \TCPDF('L', 'mm', 'A3', true, 'UTF-8', false);
        $pdf->SetCreator('PDMU');
        $pdf->SetAuthor('PDMU');
        $pdf->SetTitle('LPMC Reports');
        $pdf->SetMargins(6, 8, 6);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 7);

        $html = '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3><br>' . $this->buildHtmlTable($headers, $rows, true);
        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildHtmlTable(array $headers, array $rows, bool $forPdf, bool $allowHtml = false): string
    {
        $table = '<table border="1" cellpadding="3" cellspacing="0" style="font-size: 7px; width: 100%; border-collapse: collapse;">';
        $table .= '<thead><tr style="background-color:#f3f4f6;">';
        foreach ($headers as $header) {
            $table .= '<th style="font-weight:bold; border: 1px solid #d1d5db; text-align: center;">' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $table .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $table .= '<tr>';
            foreach ($row as $cell) {
                if ($allowHtml) {
                    $table .= '<td style="border: 1px solid #e5e7eb;">' . $cell . '</td>';
                } else {
                    $table .= '<td style="border: 1px solid #e5e7eb;">' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
                }
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';
        return $table;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Implementation for deleting the record
    }
}
