<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\QuarterlyDilgMc201819Encoding;
use App\Models\QuarterlyDilgMc201819Upload;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuarterlyDilgMc201819Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:road_maintenance_status_reports,view')->only(['index', 'show', 'viewDocument']);
        $this->middleware('crud_permission:road_maintenance_status_reports,add')->only(['edit', 'saveEncoding', 'exportEncoding', 'upload']);
        $this->middleware('crud_permission:road_maintenance_status_reports,update')->only(['approveDocument']);
        $this->middleware('crud_permission:road_maintenance_status_reports,delete')->only(['deleteDocument']);
    }

    public function index(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $officeRows = $this->scopeOfficeRowsForUser($this->buildOfficeRows($this->getOffices()));
        $setupWarning = null;
        $perPage = (int) $request->query('per_page', 15);
        $filters = [
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
        ];
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
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
            $officeRows = array_values(array_filter($officeRows, function (array $row) use ($filters) {
                return (string) ($row['province'] ?? '') === $filters['province'];
            }));
        }

        if ($filters['city'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function (array $row) use ($filters) {
                return (string) ($row['city_municipality'] ?? '') === $filters['city'];
            }));
        }

        $officeNames = collect($officeRows)
            ->pluck('city_municipality')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $documentsByOffice = [];
        $missingTables = $this->missingRequiredTables();
        if (!empty($missingTables)) {
            $setupWarning = $this->buildMissingTablesMessage($missingTables);
        } elseif (!empty($officeNames)) {
            $documents = QuarterlyDilgMc201819Upload::query()
                ->whereIn('office', $officeNames)
                ->where('year', $reportingYear)
                ->orderBy('office')
                ->orderBy('quarter')
                ->orderByDesc('uploaded_at')
                ->orderByDesc('id')
                ->get();

            foreach ($documents as $document) {
                $documentsByOffice[$document->office][$document->quarter][] = $document;
            }
        }

        $officeRowsCollection = collect($officeRows)
            ->sort(function (array $leftRow, array $rightRow) {
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

        return view('reports.quarterly.dilg-mc-2018-19.index', compact(
            'documentsByOffice',
            'filterOptions',
            'filters',
            'officeRows',
            'perPage',
            'reportingYear',
            'setupWarning'
        ));
    }

    public function show(Request $request, string $office)
    {
        $this->abortUnlessOfficeAccessible($office);
        return $this->renderDetailView($request, $office);
    }

    public function edit(Request $request, string $office, string $quarter)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office, true)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $user = auth()->user();
        abort_unless(
            $user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'add')
            && !$user->isRegionalOfficeAssignment(),
            403
        );

        $reportingYear = $this->resolveReportingYear($request);
        $normalizedQuarter = $this->normalizeQuarterOrAbort($quarter);
        $province = $this->findProvinceByOffice($office);
        $encoding = QuarterlyDilgMc201819Encoding::query()
            ->where('office', $office)
            ->where('year', $reportingYear)
            ->where('quarter', $normalizedQuarter)
            ->first();

        $savedRows = collect($encoding?->rows ?? [])
            ->map(fn ($row) => $this->normalizeEncodingRow($row, $office))
            ->filter(fn (array $row) => $this->rowHasUserInput($row))
            ->values()
            ->all();

        $formRows = old('rows');
        if (!is_array($formRows)) {
            $formRows = $this->buildEncodingFormRows($savedRows, $office);
        } else {
            $formRows = $this->buildEncodingFormRows($formRows, $office);
        }

        return view('reports.quarterly.dilg-mc-2018-19.edit', [
            'encoding' => $encoding,
            'formRows' => $formRows,
            'office' => $office,
            'province' => $province,
            'quarter' => $normalizedQuarter,
            'quarterLabel' => $this->quarterLabels()[$normalizedQuarter],
            'reportingYear' => $reportingYear,
        ]);
    }

    public function saveEncoding(Request $request, string $office, string $quarter)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office, true)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $user = auth()->user();
        abort_unless(
            $user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'add')
            && !$user->isRegionalOfficeAssignment(),
            403
        );

        $normalizedQuarter = $this->normalizeQuarterOrAbort($quarter);
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'rows' => ['nullable', 'array', 'max:250'],
            'rows.*.project_title' => ['nullable', 'string', 'max:255'],
            'rows.*.timeline_exceeded' => ['nullable', 'in:Yes,No'],
            'rows.*.target_completion_date' => ['nullable', 'date'],
            'rows.*.catch_up_mandated' => ['nullable', 'in:Yes,No,NA'],
            'rows.*.revised_target_completion_date' => ['nullable', 'date'],
            'rows.*.project_status' => ['nullable', 'string', 'max:255'],
            'rows.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $province = $this->findProvinceByOffice($office);
        $rows = collect($validated['rows'] ?? [])
            ->map(fn ($row) => $this->sanitizeEncodingRow($row, $office))
            ->filter(fn (array $row) => $this->rowHasUserInput($row))
            ->values()
            ->all();

        QuarterlyDilgMc201819Encoding::query()->updateOrCreate(
            [
                'office' => $office,
                'year' => (int) $validated['year'],
                'quarter' => $normalizedQuarter,
            ],
            [
                'province' => $province,
                'rows' => $rows,
                'last_saved_by' => auth()->id(),
                'last_saved_at' => now(),
            ]
        );

        return redirect()
            ->route('reports.quarterly.dilg-mc-2018-19.edit', [
                'office' => $office,
                'quarter' => $normalizedQuarter,
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'Encoding form saved successfully.');
    }

    public function exportEncoding(Request $request, string $office, string $quarter)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office, true)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $user = auth()->user();
        abort_unless(
            $user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'add')
            && !$user->isRegionalOfficeAssignment(),
            403
        );

        $normalizedQuarter = $this->normalizeQuarterOrAbort($quarter);
        $reportingYear = $this->resolveReportingYear($request);
        $quarterLabel = $this->quarterLabels()[$normalizedQuarter];
        $rows = $this->loadEncodingRowsForExport($office, $reportingYear, $normalizedQuarter);
        $headers = $this->encodingExportHeaders();
        $format = strtolower(trim((string) $request->query('format', 'excel')));
        if (!in_array($format, ['excel', 'pdf', 'csv'], true)) {
            return back()->with('error', 'Invalid export format.');
        }

        $baseFilename = 'dilg_mc_2018_19_' . Str::slug($office, '_') . '_' . strtolower($normalizedQuarter) . '_' . $reportingYear;
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows): void {
                $output = fopen('php://output', 'w');
                fputcsv($output, $headers);
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
            }, $baseFilename . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'pdf') {
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('PDMU');
            $pdf->SetAuthor('PDMU');
            $pdf->SetTitle('DILG MC No. 2018-19');
            $pdf->SetMargins(6, 8, 6);
            $pdf->SetAutoPageBreak(true, 8);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 7);
            $pdf->writeHTML($this->buildEncodingHtmlTable($headers, $rows), true, false, true, false, '');

            return response($pdf->Output($baseFilename . '.pdf', 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $baseFilename . '.pdf"',
            ]);
        }

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= $this->buildEncodingHtmlTable($headers, $rows, true);
        $html .= '</body></html>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $baseFilename . '.xls"',
        ]);
    }

    public function upload(Request $request, string $office)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $user = auth()->user();
        if ($user && $user->isRegionalOfficeAssignment()) {
            abort(403);
        }

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'quarter' => ['required', 'in:Q1,Q2,Q3,Q4'],
            'reupload_document_id' => ['nullable', 'integer'],
        ]);

        $province = $this->findProvinceByOffice($office) ?? 'Unknown';
        $path = $request->file('document')->store(
            'quarterly/dilg-mc-2018-19/' . Str::slug($office, '_'),
            'public'
        );
        $uploadedAt = now();
        $isProvincialDilgUploader = $user && $user->isDilgUser() && !$user->isRegionalOfficeAssignment();
        $originalName = $request->file('document')->getClientOriginalName();

        $reuploadDocumentId = isset($validated['reupload_document_id'])
            ? (int) $validated['reupload_document_id']
            : 0;

        $existingDocumentQuery = QuarterlyDilgMc201819Upload::query()
            ->where('office', $office)
            ->where('year', (int) $validated['year'])
            ->where('quarter', $validated['quarter']);

        if ($reuploadDocumentId > 0) {
            $existingDocumentQuery->where('id', $reuploadDocumentId);
        } else {
            $existingDocumentQuery
                ->orderByDesc('uploaded_at')
                ->orderByDesc('id');
        }

        $existingDocument = $existingDocumentQuery->first();

        if ($existingDocument && $existingDocument->status === 'returned') {
            $oldPath = $existingDocument->file_path;
            $existingDocument->update([
                'province' => $province,
                'file_path' => $path,
                'original_name' => $originalName,
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
            ]);

            if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            QuarterlyDilgMc201819Upload::query()->create([
                'office' => $office,
                'province' => $province,
                'year' => (int) $validated['year'],
                'quarter' => $validated['quarter'],
                'file_path' => $path,
                'original_name' => $originalName,
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
            ]);
        }

        return redirect()
            ->route('reports.quarterly.dilg-mc-2018-19.show', [
                'office' => $office,
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'Quarterly report uploaded successfully.');
    }

    public function viewDocument(Request $request, string $office, int $docId)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $document = QuarterlyDilgMc201819Upload::query()
            ->where('office', $office)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($document->file_path);
        $mimeType = @mime_content_type($filePath) ?: 'application/pdf';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function approveDocument(Request $request, string $office, int $docId)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $user = auth()->user();
        if (!$user || !$user->isDilgUser()) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $document = QuarterlyDilgMc201819Upload::query()
            ->where('office', $office)
            ->where('id', $docId)
            ->firstOrFail();

        $status = $this->normalizeDocumentStatus($document);
        $isRegionalOffice = $user->isRegionalOfficeAssignment();
        $canProvincialValidate = !$isRegionalOffice && in_array($status, ['pending', 'returned'], true);
        $canRegionalValidate = $isRegionalOffice && $status === 'pending_ro';
        abort_unless($canProvincialValidate || $canRegionalValidate, 403);

        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return back()->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $now = now();
        $updates = [
            'approved_at' => $now,
        ];

        if ($action === 'approve') {
            if ($isRegionalOffice) {
                $updates['approved_at_dilg_ro'] = $now;
                $updates['approved_by_dilg_ro'] = $user->idno;
                $updates['status'] = 'approved';
            } else {
                $updates['approved_at_dilg_po'] = $now;
                $updates['approved_by_dilg_po'] = $user->idno;
                $updates['status'] = 'pending_ro';
            }
            $updates['approval_remarks'] = null;
            $updates['user_remarks'] = null;
        } else {
            if ($isRegionalOffice) {
                $updates['approved_by_dilg_ro'] = $user->idno;
                $updates['approved_at_dilg_ro'] = null;
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
            $this->logDocumentActivity($request, 'APPROVE', $document);
        } else {
            $this->logDocumentActivity($request, 'RETURN', $document, $remarks);
        }

        return redirect()
            ->route('reports.quarterly.dilg-mc-2018-19.show', ['office' => $office, 'year' => $this->resolveReportingYear($request)])
            ->with('success', $action === 'approve' ? 'Document validated.' : 'Document returned.');
    }

    public function deleteDocument(Request $request, string $office, int $docId)
    {
        if ($redirect = $this->redirectForMissingTables($request, $office)) {
            return $redirect;
        }

        $this->abortUnlessOfficeAccessible($office);

        $document = QuarterlyDilgMc201819Upload::query()
            ->where('office', $office)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        $this->logDocumentActivity($request, ActivityLog::ACTION_DELETE, $document);

        return redirect()
            ->route('reports.quarterly.dilg-mc-2018-19.show', ['office' => $office, 'year' => $this->resolveReportingYear($request)])
            ->with('success', 'Uploaded document deleted successfully.');
    }

    private function logDocumentActivity(Request $request, string $action, QuarterlyDilgMc201819Upload $document, ?string $remarks = null): void
    {
        $fileName = trim((string) ($document->original_name ?? ''));
        $fileLabel = $fileName !== '' ? ' ("' . $fileName . '")' : '';
        $quarterLabel = trim((string) ($document->quarter ?? ''));
        $subject = 'document for ' . $document->office . ($quarterLabel !== '' ? ' ' . $quarterLabel : '') . ' (Doc #' . $document->id . ')' . $fileLabel;

        $description = match ($action) {
            'APPROVE' => 'Approved ' . $subject . '.',
            'RETURN' => 'Returned ' . $subject . ($remarks ? ' Remarks: ' . $remarks . '.' : '.'),
            ActivityLog::ACTION_DELETE => 'Deleted ' . $subject . '.',
            default => 'Updated ' . $subject . '.',
        };

        app(ActivityLogService::class)->log(
            $request->user(),
            $action,
            $description,
            [
                'request' => $request,
                'properties' => [
                    'route_name' => $request->route()?->getName(),
                    'route_parameters' => $request->route()?->parametersWithoutNulls() ?? [],
                    'document_id' => $document->id,
                    'office' => $document->office,
                    'quarter' => $document->quarter,
                    'original_name' => $fileName,
                    'remarks' => $remarks,
                ],
            ],
        );
    }

    private function resolveReportingYear(Request $request): int
    {
        $year = (int) $request->query('year', $request->input('year', now()->year));
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return $year;
    }

    private function abortUnlessOfficeAccessible(string $office): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->isSuperAdmin() || $user->isRegionalUser() || $user->isRegionalOfficeAssignment()) {
            return;
        }

        if ($user->isLguScopedUser()) {
            abort_unless($user->matchesAssignedOffice($office), 403);
            return;
        }

        if ($user->isDilgUser()) {
            abort_unless($this->findProvinceByOffice($office) === $user->province, 403);
            return;
        }

        abort(403);
    }

    private function normalizeDocumentStatus(?QuarterlyDilgMc201819Upload $document): string
    {
        if (!$document || empty($document->file_path)) {
            return 'no_upload';
        }

        $status = trim(Str::lower((string) ($document->status ?? '')));

        return match ($status) {
            'approved' => 'approved',
            'returned' => 'returned',
            'pending_ro' => 'pending_ro',
            default => 'pending',
        };
    }

    private function renderDetailView(Request $request, string $office)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $province = $this->findProvinceByOffice($office);
        $setupWarning = null;
        $missingTables = $this->missingRequiredTables();
        $documents = collect();
        if (!empty($missingTables)) {
            $setupWarning = $this->buildMissingTablesMessage($missingTables);
        } else {
            $documents = QuarterlyDilgMc201819Upload::query()
                ->where('office', $office)
                ->where('year', $reportingYear)
                ->orderBy('quarter')
                ->orderByDesc('uploaded_at')
                ->orderByDesc('id')
                ->get();
        }

        $documentsByQuarter = $documents
            ->groupBy('quarter')
            ->map(fn ($group) => $group->values());
        $userIds = $documents->pluck('uploaded_by')
            ->merge($documents->pluck('approved_by_dilg_po'))
            ->merge($documents->pluck('approved_by_dilg_ro'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $usersById = empty($userIds)
            ? collect()
            : User::query()->whereIn('idno', $userIds)->get()->keyBy('idno');
        $activityLogs = $this->buildActivityLogs($office, $reportingYear);

        $user = auth()->user();
        $canUpload = (bool) ($user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'add')
            && !$user->isRegionalOfficeAssignment());
        $canDelete = (bool) ($user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'delete')
            && $user->isDilgUser());
        $canValidate = (bool) ($user
            && $user->hasCrudPermission('road_maintenance_status_reports', 'update')
            && $user->isDilgUser());

        return view('reports.quarterly.dilg-mc-2018-19.show', compact(
            'canDelete',
            'canUpload',
            'canValidate',
            'documentsByQuarter',
            'office',
            'province',
            'reportingYear',
            'activityLogs',
            'usersById',
            'setupWarning'
        ));
    }

    private function missingRequiredTables(bool $includeEncoding = false): array
    {
        $requiredTables = ['quarterly_dilg_mc_2018_19_uploads'];

        if ($includeEncoding) {
            $requiredTables[] = 'quarterly_dilg_mc_2018_19_encodings';
        }

        return array_values(array_filter($requiredTables, fn (string $table) => !Schema::hasTable($table)));
    }

    private function buildMissingTablesMessage(array $missingTables): string
    {
        return 'DILG MC No. 2018-19 setup is incomplete on this deployment. Missing database tables: '
            . implode(', ', $missingTables)
            . '. Run the latest Laravel migrations on the server.';
    }

    private function redirectForMissingTables(Request $request, string $office, bool $includeEncoding = false)
    {
        $missingTables = $this->missingRequiredTables($includeEncoding);
        if (empty($missingTables)) {
            return null;
        }

        return redirect()
            ->route('reports.quarterly.dilg-mc-2018-19.show', [
                'office' => $office,
                'year' => $this->resolveReportingYear($request),
            ])
            ->with('error', $this->buildMissingTablesMessage($missingTables));
    }

    private function buildActivityLogs(string $office, int $reportingYear): array
    {
        if (!Schema::hasTable('activity_logs')) {
            return [];
        }

        $officeKey = Str::lower(trim($office));
        $logs = ActivityLog::query()
            ->with('user:idno,fname,lname,role')
            ->where(function ($query) {
                $query
                    ->where('properties->route_name', 'like', 'reports.quarterly.dilg-mc-2018-19%')
                    ->orWhere('properties->path', 'like', '/reports/quarterly/dilg-mc-2018-19%');
            })
            ->orderByDesc('created_at')
            ->limit(250)
            ->get();

        return $logs
            ->filter(function (ActivityLog $log) use ($officeKey, $reportingYear) {
                $loggedOffice = Str::lower(trim((string) data_get($log->properties, 'route_parameters.office', '')));
                if ($loggedOffice !== $officeKey) {
                    return false;
                }

                $loggedYear = data_get($log->properties, 'query.year');
                if ($loggedYear !== null && $loggedYear !== '' && (int) $loggedYear !== $reportingYear) {
                    return false;
                }

                return true;
            })
            ->take(100)
            ->map(function (ActivityLog $log): array {
                $routeName = trim((string) data_get($log->properties, 'route_name', ''));
                $action = Str::upper(trim((string) $log->action));
                $category = 'other';

                if (str_contains($routeName, '.approve')) {
                    $category = 'approval';
                    $action = 'APPROVAL';
                } elseif (str_contains($routeName, '.upload')) {
                    $category = 'upload';
                    $action = 'UPLOAD';
                } elseif (str_contains($routeName, '.save-encoding')) {
                    $category = 'create';
                    $action = 'CREATION';
                }

                $quarter = strtoupper(trim((string) data_get($log->properties, 'route_parameters.quarter', '')));
                $docId = trim((string) data_get($log->properties, 'route_parameters.docId', ''));
                $uploadedFiles = collect(data_get($log->properties, 'uploaded_files', []))
                    ->map(fn ($file) => trim((string) data_get($file, 'name', '')))
                    ->filter()
                    ->values();

                $subjectParts = [];
                if ($quarter !== '') {
                    $subjectParts[] = $quarter;
                }
                if ($docId !== '') {
                    $subjectParts[] = 'Doc #' . $docId;
                }
                if ($uploadedFiles->isNotEmpty()) {
                    $subjectParts[] = $uploadedFiles->join(', ');
                }

                $userName = trim((string) ($log->user?->fullName() ?? $log->username ?? 'Unknown'));
                if ($userName === '') {
                    $userName = 'Unknown';
                }

                return [
                    'timestamp' => $log->created_at,
                    'category' => $category,
                    'action' => $action !== '' ? str_replace('_', ' ', $action) : 'READ',
                    'subject' => !empty($subjectParts) ? implode(' · ', $subjectParts) : 'Workspace',
                    'user_name' => $userName,
                    'details' => trim((string) $log->description) !== '' ? trim((string) $log->description) : '—',
                    'device' => trim((string) ($log->device ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeQuarterOrAbort(?string $quarter): string
    {
        $normalized = strtoupper(trim((string) $quarter));

        abort_unless(in_array($normalized, ['Q1', 'Q2', 'Q3', 'Q4'], true), 404);

        return $normalized;
    }

    private function quarterLabels(): array
    {
        return [
            'Q1' => 'Quarter 1',
            'Q2' => 'Quarter 2',
            'Q3' => 'Quarter 3',
            'Q4' => 'Quarter 4',
        ];
    }

    private function buildEncodingFormRows(array $rows, string $office, int $minimumRows = 5): array
    {
        $normalizedRows = collect($rows)
            ->map(fn ($row) => $this->normalizeEncodingRow($row, $office))
            ->values()
            ->all();

        while (count($normalizedRows) < $minimumRows) {
            $normalizedRows[] = $this->blankEncodingRow($office);
        }

        return $normalizedRows;
    }

    private function loadEncodingRowsForExport(string $office, int $reportingYear, string $quarter): array
    {
        $encoding = QuarterlyDilgMc201819Encoding::query()
            ->where('office', $office)
            ->where('year', $reportingYear)
            ->where('quarter', $quarter)
            ->first();

        $rows = collect($encoding?->rows ?? [])
            ->map(fn ($row) => $this->normalizeEncodingRow($row, $office))
            ->filter(fn (array $row) => $this->rowHasUserInput($row))
            ->values();

        if ($rows->isEmpty()) {
            $rows = collect([$this->blankEncodingRow($office)]);
        }

        return $rows->map(function (array $row): array {
            return [
                $row['lgu_name'] ?? '',
                $row['project_title'] ?? '',
                $row['timeline_exceeded'] ?? '',
                $row['target_completion_date'] ?? '',
                $row['catch_up_mandated'] ?? '',
                $row['revised_target_completion_date'] ?? '',
                $row['project_status'] ?? '',
                $row['remarks'] ?? '',
            ];
        })->all();
    }

    private function encodingExportHeaders(): array
    {
        return [
            'Name of LGU with Road/Public Works',
            'Project Title',
            'The project exceeded the timeline based on the POW (Yes/No)',
            'Target Date of Completion',
            'The LGU mandated the contractor/s to catch-up within 30 days with the agreed project schedule (if there are delays) Yes/No/NA',
            'Revised Target Date of Completion',
            'Status of Project',
            'Remarks',
        ];
    }

    private function buildEncodingHtmlTable(array $headers, array $rows, bool $forExcel = false): string
    {
        $tableStyle = $forExcel
            ? 'border-collapse:collapse; table-layout:fixed; width:100%;'
            : '';
        $html = '<table border="1" cellpadding="3" cellspacing="0" style="' . $tableStyle . '">';
        $html .= '<thead><tr style="background-color:#002C76;color:#FFFFFF;">';
        foreach ($headers as $header) {
            $html .= '<th style="font-weight:bold;text-align:center;vertical-align:middle;">' . $this->escapeXml((string) $header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $cell) {
                $alignment = in_array($index, [2, 3, 4, 5], true) ? 'center' : 'left';
                $html .= '<td style="text-align:' . $alignment . ';">' . $this->escapeXml((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function blankEncodingRow(string $office): array
    {
        return [
            'lgu_name' => $office,
            'project_title' => '',
            'timeline_exceeded' => '',
            'target_completion_date' => '',
            'catch_up_mandated' => '',
            'revised_target_completion_date' => '',
            'project_status' => '',
            'remarks' => '',
        ];
    }

    private function normalizeEncodingRow(mixed $row, string $office): array
    {
        $data = is_array($row) ? $row : [];

        return [
            'lgu_name' => $office,
            'project_title' => trim((string) ($data['project_title'] ?? '')),
            'timeline_exceeded' => trim((string) ($data['timeline_exceeded'] ?? '')),
            'target_completion_date' => trim((string) ($data['target_completion_date'] ?? '')),
            'catch_up_mandated' => trim((string) ($data['catch_up_mandated'] ?? '')),
            'revised_target_completion_date' => trim((string) ($data['revised_target_completion_date'] ?? '')),
            'project_status' => trim((string) ($data['project_status'] ?? '')),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
        ];
    }

    private function sanitizeEncodingRow(array $row, string $office): array
    {
        return [
            'lgu_name' => $office,
            'project_title' => InputSanitizer::sanitizePlainText($row['project_title'] ?? null, true),
            'timeline_exceeded' => $this->normalizeEncodingOption($row['timeline_exceeded'] ?? null, ['Yes', 'No']),
            'target_completion_date' => $this->normalizeEncodingDate($row['target_completion_date'] ?? null),
            'catch_up_mandated' => $this->normalizeEncodingOption($row['catch_up_mandated'] ?? null, ['Yes', 'No', 'NA']),
            'revised_target_completion_date' => $this->normalizeEncodingDate($row['revised_target_completion_date'] ?? null),
            'project_status' => InputSanitizer::sanitizePlainText($row['project_status'] ?? null, true),
            'remarks' => InputSanitizer::sanitizePlainText($row['remarks'] ?? null, true),
        ];
    }

    private function normalizeEncodingOption(?string $value, array $allowed): string
    {
        $clean = trim((string) $value);

        return in_array($clean, $allowed, true) ? $clean : '';
    }

    private function normalizeEncodingDate(?string $value): string
    {
        $clean = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $clean) === 1 ? $clean : '';
    }

    private function rowHasUserInput(array $row): bool
    {
        foreach ([
            'project_title',
            'timeline_exceeded',
            'target_completion_date',
            'catch_up_mandated',
            'revised_target_completion_date',
            'project_status',
            'remarks',
        ] as $field) {
            if (trim((string) ($row[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function scopeOfficeRowsForUser(array $officeRows): array
    {
        $user = auth()->user();

        if ($user && $user->isLguScopedUser() && $user->normalizedOffice() !== '') {
            return array_values(array_filter($officeRows, function (array $row) use ($user) {
                return $user->matchesAssignedOffice((string) ($row['city_municipality'] ?? ''));
            }));
        }

        if ($user && $user->isDilgUser() && !empty($user->province) && $user->province !== 'Regional Office') {
            return array_values(array_filter($officeRows, function (array $row) use ($user) {
                return (string) ($row['province'] ?? '') === (string) $user->province;
            }));
        }

        return $officeRows;
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
}
