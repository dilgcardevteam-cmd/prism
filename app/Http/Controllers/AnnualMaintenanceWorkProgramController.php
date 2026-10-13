<?php

namespace App\Http\Controllers;

use App\Models\AnnualMaintenanceWorkProgramDocument;
use App\Models\User;
use App\Support\InputSanitizer;
use App\Support\LguReportorialDeadlineResolver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnualMaintenanceWorkProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:annual_maintenance_work_program,view')->only(['index', 'edit', 'viewDocument']);
        $this->middleware('crud_permission:annual_maintenance_work_program,add')->only(['upload']);
        $this->middleware('crud_permission:annual_maintenance_work_program,update')->only(['approveDocument']);
        $this->middleware('superadmin')->only(['deleteDocument']);
    }

    private function reportConfig(): array
    {
        return [
            'pageTitle' => 'Annual Maintenance Work Program (AMWP)',
            'headingTitle' => 'Annual Maintenance Work Program (AMWP)',
            'headingDescription' => 'Each city/municipality and PLGU has its own profile page for annual AMWP document uploads.',
            'browserTitle' => 'Annual Maintenance Work Program (AMWP) - Update',
            'indexRoute' => 'reports.annual.amwp',
            'editRoute' => 'reports.annual.amwp.edit',
            'uploadRoute' => 'reports.annual.amwp.upload',
            'documentRoute' => 'reports.annual.amwp.document',
            'deleteRoute' => 'reports.annual.amwp.delete-document',
            'approveRoute' => 'reports.annual.amwp.approve',
            'documentTitleShort' => 'Annual Maintenance Work Program (AMWP)',
            'uploadSectionTitle' => 'Annual Maintenance Work Program (AMWP) Upload',
            'uploadFieldLabel' => 'AMWP Upload',
        ];
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

    private function getSortedOfficesByProvince(): array
    {
        $officesByProvince = $this->getOffices();
        ksort($officesByProvince, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($officesByProvince as $province => $offices) {
            usort($offices, function (string $a, string $b): int {
                $aIsPlgu = str_starts_with($a, 'PLGU ');
                $bIsPlgu = str_starts_with($b, 'PLGU ');

                if ($aIsPlgu && !$bIsPlgu) {
                    return -1;
                }

                if (!$aIsPlgu && $bIsPlgu) {
                    return 1;
                }

                return strcasecmp($a, $b);
            });

            $officesByProvince[$province] = $offices;
        }

        return $officesByProvince;
    }

    private function resolveReportingYear(Request $request): int
    {
        $year = (int) $request->query('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return $year;
    }

    private function applyReportingYearFilter($query, int $reportingYear): void
    {
        $query->where(function ($yearQuery) use ($reportingYear) {
            $yearQuery->where('document_year', $reportingYear)
                ->orWhere(function ($legacyQuery) use ($reportingYear) {
                    $legacyQuery->whereNull('document_year')
                        ->whereYear('uploaded_at', $reportingYear);
                });
        });
    }

    private function resolveConfiguredDeadline(int $reportingYear): ?array
    {
        return app(LguReportorialDeadlineResolver::class)->resolve(
            'annual_maintenance_work_program',
            $reportingYear,
            'Annual'
        );
    }

    private function buildOfficeRows(array $officesByProvince): array
    {
        $officeRows = [];
        foreach ($officesByProvince as $province => $offices) {
            foreach ($offices as $office) {
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
        foreach ($this->getOffices() as $province => $offices) {
            if (in_array($officeName, $offices, true)) {
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

        return $user->canAccessLguScope($province, $officeName);
    }

    private function formatDocumentLabel(AnnualMaintenanceWorkProgramDocument $document): string
    {
        $label = trim((string) ($document->document_name ?: 'Annual Maintenance Work Program (AMWP) Document'));
        if (!empty($document->document_year)) {
            $label .= ' (CY ' . $document->document_year . ')';
        }

        return $label;
    }

    private function buildActivityLogs($documents): array
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
        AnnualMaintenanceWorkProgramDocument $document,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        $timestamp = $timestamp ?: now();

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'annual_maintenance_work_program',
            'office' => $officeName,
            'document_label' => $this->formatDocumentLabel($document),
            'action' => $action,
            'action_label' => $actionLabel,
            'action_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
            'remarks' => $remarks,
        ]);
    }

    public function index(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $configuredDeadline = $this->resolveConfiguredDeadline($reportingYear);
        $officeRows = $this->buildOfficeRows($this->getSortedOfficesByProvince());
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
        } elseif ($user && ($user->isProvincialUser() || ($user->isDilgUser() && !$user->isRegionalUser() && !$user->isSuperAdmin() && !empty($user->province) && $user->province !== 'Regional Office'))) {
            $userProvince = $user->province;
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($userProvince) {
                return strcasecmp($row['province'], $userProvince) === 0;
            }));
        }

        $scopedOfficeRows = collect($officeRows);
        $filterOptions = [
            'provinces' => $scopedOfficeRows->pluck('province')->filter()->unique()->sort()->values()->all(),
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

        $totalProvinces = count(array_unique(array_map(fn ($row) => $row['province'], $officeRows)));
        $totalOffices = count($officeRows);

        $officeRowsCollection = collect($officeRows);
        $officeNames = $officeRowsCollection->pluck('city_municipality')->unique()->values()->all();

        $uploadCountsByOffice = collect();
        $latestDocumentsByOffice = collect();
        if (!empty($officeNames)) {
            $uploadCountsByOfficeQuery = AnnualMaintenanceWorkProgramDocument::query()
                ->whereIn('office', $officeNames)
                ->selectRaw('office, COUNT(*) as total');
            $this->applyReportingYearFilter($uploadCountsByOfficeQuery, $reportingYear);

            $uploadCountsByOffice = $uploadCountsByOfficeQuery
                ->groupBy('office')
                ->pluck('total', 'office');

            $latestDocumentsByOfficeQuery = AnnualMaintenanceWorkProgramDocument::query()
                ->whereIn('office', $officeNames)
                ->orderByDesc('uploaded_at')
                ->orderByDesc('id');
            $this->applyReportingYearFilter($latestDocumentsByOfficeQuery, $reportingYear);

            $latestDocumentsByOffice = $latestDocumentsByOfficeQuery
                ->get()
                ->unique('office')
                ->keyBy('office');
        }

        $resolveValidationPriority = function ($document): int {
            if (!$document || !$document->file_path) {
                return 4;
            }

            if (
                $document->status !== 'returned'
                && $document->approved_at_dilg_po
                && !$document->approved_at_dilg_ro
            ) {
                return 0;
            }

            if (
                $document->status !== 'returned'
                && !$document->approved_at_dilg_po
            ) {
                return 1;
            }

            if ($document->status === 'returned') {
                return 2;
            }

            if ($document->approved_at_dilg_ro) {
                return 3;
            }

            return 4;
        };

        $officeRowsCollection = $officeRowsCollection
            ->sort(function (array $leftRow, array $rightRow) use ($latestDocumentsByOffice, $resolveValidationPriority) {
                $leftDocument = $latestDocumentsByOffice->get($leftRow['city_municipality']);
                $rightDocument = $latestDocumentsByOffice->get($rightRow['city_municipality']);

                $priorityComparison = $resolveValidationPriority($leftDocument) <=> $resolveValidationPriority($rightDocument);
                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                $leftUploadedAt = $leftDocument?->uploaded_at ? Carbon::parse($leftDocument->uploaded_at)->getTimestamp() : 0;
                $rightUploadedAt = $rightDocument?->uploaded_at ? Carbon::parse($rightDocument->uploaded_at)->getTimestamp() : 0;
                if ($leftUploadedAt !== $rightUploadedAt) {
                    return $rightUploadedAt <=> $leftUploadedAt;
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

        $reportConfig = $this->reportConfig();

        return view('reports.rbis-annual-certification.index', compact(
            'officeRows',
            'uploadCountsByOffice',
            'latestDocumentsByOffice',
            'totalProvinces',
            'totalOffices',
            'reportingYear',
            'configuredDeadline',
            'perPage',
            'filters',
            'filterOptions',
            'reportConfig'
        ));
    }

    public function deleteDocument($id, $docId)
    {
        $officeName = (string) $id;
        $document = AnnualMaintenanceWorkProgramDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Uploaded document deleted successfully.');
    }

    public function edit(Request $request, $id)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $documentsQuery = AnnualMaintenanceWorkProgramDocument::query()
            ->where('office', $officeName);
        $this->applyReportingYearFilter($documentsQuery, $reportingYear);
        $documents = $documentsQuery
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->limit(1)
            ->get();
        $activityLogs = $this->buildActivityLogs($documents);

        $uploaderIds = $documents->pluck('uploaded_by')->filter()->unique()->values()->all();
        $approverIds = $documents->pluck('approved_by_dilg_po')
            ->merge($documents->pluck('approved_by_dilg_ro'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $logUserIds = collect($activityLogs)->pluck('user_id')->filter()->unique()->values()->all();
        $userIds = array_values(array_unique(array_merge($uploaderIds, $approverIds, $logUserIds)));
        $usersById = $userIds ? User::whereIn('idno', $userIds)->get()->keyBy('idno') : collect();

        $configuredDeadline = $this->resolveConfiguredDeadline($reportingYear);
        if (is_array($configuredDeadline) && !empty($configuredDeadline['updated_by'])) {
            $deadlineUpdater = User::query()
                ->select(['idno', 'fname', 'lname'])
                ->where('idno', (int) $configuredDeadline['updated_by'])
                ->first();

            if ($deadlineUpdater) {
                $deadlineUpdatedByName = trim(implode(' ', array_filter([
                    trim((string) $deadlineUpdater->fname),
                    trim((string) $deadlineUpdater->lname),
                ])));
                $configuredDeadline['updated_by_name'] = $deadlineUpdatedByName !== '' ? $deadlineUpdatedByName : 'Unknown';
            }
        }

        $reportConfig = $this->reportConfig();

        return view('reports.rbis-annual-certification.edit', compact(
            'officeName',
            'province',
            'documents',
            'usersById',
            'activityLogs',
            'reportingYear',
            'configuredDeadline',
            'reportConfig'
        ));
    }

    public function upload(Request $request, $id)
    {
        $officeName = $id;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $user = auth()->user();
        abort_unless($user && $user->canSubmitForLgu($province, $officeName), 403);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        $workflowService = app(\App\Services\LguReportorialWorkflowService::class);
        $reportingYear = (int) $request->input('year');
        $officeSlug = Str::slug($officeName, '_');
        $existingDocumentQuery = AnnualMaintenanceWorkProgramDocument::query()
            ->where('office', $officeName);
        $this->applyReportingYearFilter($existingDocumentQuery, $reportingYear);
        $existingDocument = $existingDocumentQuery
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->first();

        $isReturned = ($existingDocument && $existingDocument->status === \App\Services\LguReportorialWorkflowService::STATUS_RETURNED);
        if ($isReturned) {
            $checkResubmit = $workflowService->canUserResubmit($user, $existingDocument);
            if (!$checkResubmit['allowed']) {
                abort(403, $checkResubmit['reason']);
            }
        } elseif ($existingDocument && !empty($existingDocument->file_path)) {
            return redirect()
                ->back()
                ->with('error', 'Document already submitted. Upload is disabled until the current file is returned.');
        }

        $oldFilePath = $existingDocument?->file_path;
        $file = $request->file('document');
        $path = $file->store('annual-maintenance-work-program/' . $officeSlug, 'public');
        $uploadedAt = now();

        $uploadFields = $isReturned
            ? $workflowService->resubmissionFields($existingDocument, $user, $officeName, $province)
            : $workflowService->initialFieldsForUpload($user, $officeName, $province);

        $documentPayload = array_merge([
            'province' => $province,
            'document_name' => 'Annual Maintenance Work Program (AMWP) Document (CY ' . $reportingYear . ')',
            'document_year' => $reportingYear,
            'remarks' => null,
            'file_path' => $path,
            'uploaded_by' => $user->idno ?? auth()->id(),
        ], $uploadFields);

        if ($existingDocument) {
            $existingDocument->update($documentPayload);
            $document = $existingDocument->refresh();
        } else {
            $document = AnnualMaintenanceWorkProgramDocument::create(array_merge([
                'office' => $officeName,
            ], $documentPayload));
        }

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        $workflowService->recordSubmissionAuditLog($user, $document, $isReturned, $officeName, $province);
        $this->logActivity($officeName, $isReturned ? 'resubmit' : 'upload', $isReturned ? 'Resubmitted' : 'Uploaded', $document, null, $uploadedAt);
        $this->notifyWorkflowUsersOnUpload($document, $officeName);

        return back()->with('success', 'Annual Maintenance Work Program document uploaded successfully.');
    }

    public function viewDocument($id, $docId)
    {
        $officeName = $id;
        $document = AnnualMaintenanceWorkProgramDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
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

    public function approveDocument(Request $request, $id, $docId)
    {
        $officeName = $id;
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $province = $this->findProvinceByOffice($officeName);
        abort_unless($province && $this->canAccessOffice($officeName, $province), 403);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $document = AnnualMaintenanceWorkProgramDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $workflowService = app(\App\Services\LguReportorialWorkflowService::class);
        $check = $workflowService->canUserValidate($user, $document, $officeName, $province);
        if (!$check['allowed']) {
            abort(403, $check['reason'] ?? 'Unauthorized validation action.');
        }

        $now = now();
        $result = $workflowService->processValidation($user, $document, $action, $remarks, $officeName, $province);
        $document->refresh();

        $logAction = match ($check['current_stage']) {
            \App\Services\LguReportorialWorkflowService::STATUS_PENDING_MLGOO => ($action === 'approve' ? 'validate_mlgoo' : 'return'),
            \App\Services\LguReportorialWorkflowService::STATUS_PENDING_PO => ($action === 'approve' ? 'validate_po' : 'return'),
            default => ($action === 'approve' ? 'validate_ro' : 'return'),
        };

        $logDesc = match ($check['current_stage']) {
            \App\Services\LguReportorialWorkflowService::STATUS_PENDING_MLGOO => ($action === 'approve' ? 'Validated (C/MLGOO)' : 'Returned (C/MLGOO)'),
            \App\Services\LguReportorialWorkflowService::STATUS_PENDING_PO => ($action === 'approve' ? 'Validated (DILG PO)' : 'Returned (DILG PO)'),
            default => ($action === 'approve' ? 'Validated (DILG RO)' : 'Returned (DILG RO)'),
        };

        $this->logActivity($officeName, $logAction, $logDesc, $document, $action === 'return' ? $remarks : null, $now);
        $this->notifyLguUsersAfterRegionalApproval($document, $officeName, $action, $user->isRegionalUser(), $remarks);

        return back()->with('success', $result['message']);
    }
}
