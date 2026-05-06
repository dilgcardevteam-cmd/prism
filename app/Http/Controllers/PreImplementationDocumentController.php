<?php

namespace App\Http\Controllers;

use App\Models\PreImplementationDocument;
use App\Models\PreImplementationDocumentFile;
use App\Services\InterventionNotificationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreImplementationDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:pre_implementation_documents,view')->only(['index', 'show']);
        $this->middleware('crud_permission:pre_implementation_documents,add')->only(['save']);
        $this->middleware('crud_permission:pre_implementation_documents,update')->only(['validateDocument']);
    }

    public function index(Request $request)
    {
        $allProjectsScope = $this->hasAllProjectsScope($request);
        $pageConfig = $this->pageConfig($request);
        $routeConfig = $this->routeConfig($request);
        $scopeQuery = $this->scopeQuery($request);
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'province' => trim((string) $request->input('province', '')),
            'city_municipality' => trim((string) $request->input('city_municipality', '')),
            'barangay' => trim((string) $request->input('barangay', '')),
            'program' => trim((string) $request->input('program', '')),
            'funding_year' => trim((string) $request->input('funding_year', '')),
            'project_type' => trim((string) $request->input('project_type', '')),
            'project_status' => trim((string) $request->input('project_status', '')),
        ];

        if ($filters['province'] === '') {
            $filters['city_municipality'] = '';
        }

        if ($filters['city_municipality'] === '') {
            $filters['barangay'] = '';
        }

        if (!Schema::hasTable('subay_project_profiles')) {
            $projects = new LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            $filterOptions = [
                'provinces' => collect(),
                'cities' => collect(),
                'barangays' => collect(),
                'programs' => collect(),
                'funding_years' => collect(),
                'project_types' => collect(),
                'project_statuses' => collect(),
            ];

            return view('reports.pre-implementation-documents.index', compact('projects', 'filters', 'filterOptions', 'perPage', 'pageConfig', 'routeConfig', 'scopeQuery'));
        }

        $baseQuery = $this->buildAccessibleSubayQuery(Auth::user(), $allProjectsScope);
        $projectTypeExpression = $this->projectTypeExpression('spp');

        $filterOptions = [
            'provinces' => (clone $baseQuery)
                ->select('spp.province')
                ->whereNotNull('spp.province')
                ->where('spp.province', '!=', '')
                ->distinct()
                ->orderBy('spp.province')
                ->pluck('spp.province'),
            'cities' => $filters['province'] !== ''
                ? (clone $baseQuery)
                    ->select('spp.city_municipality')
                    ->where('spp.province', $filters['province'])
                    ->whereNotNull('spp.city_municipality')
                    ->where('spp.city_municipality', '!=', '')
                    ->distinct()
                    ->orderBy('spp.city_municipality')
                    ->pluck('spp.city_municipality')
                : collect(),
            'barangays' => $filters['city_municipality'] !== ''
                ? (clone $baseQuery)
                    ->select('spp.barangay')
                    ->where('spp.city_municipality', $filters['city_municipality'])
                    ->whereNotNull('spp.barangay')
                    ->where('spp.barangay', '!=', '')
                    ->distinct()
                    ->orderBy('spp.barangay')
                    ->pluck('spp.barangay')
                : collect(),
            'programs' => (clone $baseQuery)
                ->select('spp.program')
                ->whereNotNull('spp.program')
                ->where('spp.program', '!=', '')
                ->distinct()
                ->orderBy('spp.program')
                ->pluck('spp.program'),
            'funding_years' => (clone $baseQuery)
                ->select('spp.funding_year')
                ->whereNotNull('spp.funding_year')
                ->where('spp.funding_year', '!=', '')
                ->distinct()
                ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) DESC')
                ->pluck('spp.funding_year'),
            'project_types' => (clone $baseQuery)
                ->select(DB::raw("{$projectTypeExpression} as project_type"))
                ->whereRaw("{$projectTypeExpression} <> ''")
                ->distinct()
                ->orderBy('project_type')
                ->pluck('project_type'),
            'project_statuses' => (clone $baseQuery)
                ->select('spp.status')
                ->whereNotNull('spp.status')
                ->where('spp.status', '!=', '')
                ->distinct()
                ->orderBy('spp.status')
                ->pluck('spp.status'),
        ];

        $query = clone $baseQuery;

        if ($filters['search'] !== '') {
            $keyword = strtolower($filters['search']);
            $query->where(function ($subQuery) use ($keyword) {
                $like = '%' . $keyword . '%';
                $subQuery
                    ->whereRaw('LOWER(spp.project_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(spp.project_title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(spp.province) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(spp.city_municipality) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(spp.barangay) LIKE ?', [$like]);
            });
        }

        if ($filters['province'] !== '') {
            $query->where('spp.province', $filters['province']);
        }

        if ($filters['city_municipality'] !== '') {
            $query->where('spp.city_municipality', $filters['city_municipality']);
        }

        if ($filters['barangay'] !== '') {
            $query->where('spp.barangay', $filters['barangay']);
        }

        if ($filters['program'] !== '') {
            $query->where('spp.program', $filters['program']);
        }

        if ($filters['funding_year'] !== '') {
            $query->whereRaw('CAST(NULLIF(TRIM(COALESCE(spp.funding_year, \'\')), \'\') AS UNSIGNED) = ?', [(int) $filters['funding_year']]);
        }

        if ($filters['project_type'] !== '') {
            $query->whereRaw("{$this->projectTypeExpression('spp')} = ?", [$filters['project_type']]);
        }

        if ($filters['project_status'] !== '') {
            $query->where('spp.status', $filters['project_status']);
        }

        $fundSourceExpression = $this->fundSourceExpression('spp');

        $projectsQuery = $query
            ->select([
                'spp.project_code',
                'spp.project_title',
                'spp.province',
                'spp.city_municipality',
                'spp.barangay',
                'spp.funding_year',
                'spp.status',
                'spp.updated_at',
                DB::raw("{$fundSourceExpression} as fund_source"),
            ])
            ->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END");

        if ($allProjectsScope) {
            $projectsQuery
                ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) DESC')
                ->orderBy('spp.project_code');
        } else {
            $projectsQuery
                ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) ASC')
                ->orderBy('spp.project_code');
        }

        $projects = $projectsQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('reports.pre-implementation-documents.index', compact('projects', 'filters', 'filterOptions', 'perPage', 'pageConfig', 'routeConfig', 'scopeQuery'));
    }

    public function show(Request $request, string $projectCode)
    {
        $pageConfig = $this->pageConfig($request);
        $routeConfig = $this->routeConfig($request);
        $scopeQuery = $this->scopeQuery($request);
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        $document = PreImplementationDocument::where('project_code', $project->project_code)->first();
        $documentFiles = PreImplementationDocumentFile::where('project_code', $project->project_code)->get();
        $documentFilesByType = $documentFiles
            ->groupBy('document_type')
            ->map(function ($group) {
                return $group->sortByDesc(function ($file) {
                    return optional($file->uploaded_at)->getTimestamp()
                        ?? optional($file->created_at)->getTimestamp()
                        ?? 0;
                })->values();
            });
        $latestDocumentFilesByType = $documentFilesByType->map(function ($group) {
            return $group->first();
        });
        $activityLogs = $this->buildActivityLogs($documentFiles, $project->project_code);

        $documentUserIds = $documentFiles
            ->flatMap(function ($row) {
                return [
                    $row->uploaded_by,
                    $row->approved_by,
                    $row->approved_by_dilg_po,
                    $row->approved_by_dilg_ro,
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
        $logUserIds = collect($activityLogs)->pluck('user_id')->filter()->unique()->values()->all();
        $userIds = array_values(array_unique(array_merge($documentUserIds, $logUserIds)));

        $usersById = empty($userIds)
            ? collect()
            : User::whereIn('idno', $userIds)->get()->keyBy('idno');

        return view('reports.pre-implementation-documents.show', [
            'project' => $project,
            'document' => $document,
            'documentFilesByType' => $documentFilesByType,
            'latestDocumentFilesByType' => $latestDocumentFilesByType,
            'usersById' => $usersById,
            'activityLogs' => $activityLogs,
            'documentFields' => $this->documentFieldMap(),
            'documentGroups' => $this->documentFieldGroups(),
            'multiUploadDocumentTypes' => $this->multiUploadDocumentTypes(),
            'allowedModeOfContract' => ['By Contract', 'By Administration'],
            'pageConfig' => $pageConfig,
            'routeConfig' => $routeConfig,
            'scopeQuery' => $scopeQuery,
        ]);
    }

    public function save(Request $request, string $projectCode)
    {
        $scopeQuery = $this->scopeQuery($request);
        $pageConfig = $this->pageConfig($request);
        $routeConfig = $this->routeConfig($request);
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        $validationRules = [
            'mode_of_contract' => ['nullable', 'in:By Contract,By Administration'],
        ];

        foreach ($this->singleUploadDocumentTypes() as $field) {
            $validationRules[$field] = ['nullable', 'file', 'mimes:pdf', 'max:15360'];
        }

        $validated = $request->validate($validationRules);

        $document = PreImplementationDocument::firstOrNew(['project_code' => $project->project_code]);
        $document->project_title = $project->project_title;
        $document->province = $project->province;
        $document->city_municipality = $project->city_municipality;
        $document->funding_year = $project->funding_year;
        $document->mode_of_contract = $validated['mode_of_contract'] ?? $document->mode_of_contract;
        $document->updated_by = Auth::user()->idno ?? null;

        $folder = 'pre-implementation/projects/' . Str::slug((string) $project->project_code, '_');
        $now = now();
        $currentUser = Auth::user();
        $userId = $currentUser->idno ?? null;
        $isProvincialDilgUploader = $currentUser && $currentUser->isDilgUser() && !$currentUser->isRegionalOfficeAssignment();
        $uploadedDocumentTypes = [];

        foreach ($this->singleUploadDocumentTypes() as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $fileRecord = PreImplementationDocumentFile::firstOrNew([
                'project_code' => $project->project_code,
                'document_type' => $field,
            ]);

            $existingPath = $fileRecord->file_path ?: ($document->{$field} ?? null);
            if (!empty($existingPath)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        $field => $this->documentFieldMap()[$field] . ' already has an uploaded file. Upload is locked for this document.',
                    ]);
            }

            $path = $request->file($field)->store($folder, 'public');

            $document->{$field} = $path;
            $fileRecord->file_path = $path;
            $fileRecord->uploaded_at = $now;
            $fileRecord->uploaded_by = $userId;
            $fileRecord->status = $isProvincialDilgUploader ? 'pending_ro' : 'pending';
            $fileRecord->approved_at = $isProvincialDilgUploader ? $now : null;
            $fileRecord->approved_by = $isProvincialDilgUploader ? $userId : null;
            $fileRecord->approved_at_dilg_po = $isProvincialDilgUploader ? $now : null;
            $fileRecord->approved_by_dilg_po = $isProvincialDilgUploader ? $userId : null;
            $fileRecord->approved_at_dilg_ro = null;
            $fileRecord->approved_by_dilg_ro = null;
            $fileRecord->approval_remarks = null;
            $fileRecord->user_remarks = null;
            $fileRecord->save();

            $this->logActivity(
                $project->project_code,
                'upload',
                'Uploaded',
                $fileRecord,
                null,
                $now
            );

            $uploadedDocumentTypes[] = $field;
        }

        $document->save();

        if (!empty($uploadedDocumentTypes)) {
            $this->notifyUploadInterventionRecipients($project, $uploadedDocumentTypes, $routeConfig, $scopeQuery);
        }

        return redirect()
            ->route($routeConfig['show'], array_merge(['projectCode' => $project->project_code], $scopeQuery))
            ->with('success', $pageConfig['save_success_message']);
    }

    public function uploadMultiDocument(Request $request, string $projectCode, string $documentType)
    {
        if (!$this->isMultiUploadDocumentType($documentType) || !array_key_exists($documentType, $this->documentFieldMap())) {
            abort(404);
        }

        $scopeQuery = $this->scopeQuery($request);
        $routeConfig = $this->routeConfig($request);
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        $validated = $request->validate([
            'document_file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $document = PreImplementationDocument::firstOrNew(['project_code' => $project->project_code]);
        $document->project_title = $project->project_title;
        $document->province = $project->province;
        $document->city_municipality = $project->city_municipality;
        $document->funding_year = $project->funding_year;
        $document->updated_by = Auth::user()->idno ?? null;

        $folder = 'pre-implementation/projects/' . Str::slug((string) $project->project_code, '_') . '/' . Str::slug($documentType, '_');
        $now = now();
        $currentUser = Auth::user();
        $userId = $currentUser->idno ?? null;
        $isProvincialDilgUploader = $currentUser && $currentUser->isDilgUser() && !$currentUser->isRegionalOfficeAssignment();
        $path = $validated['document_file']->store($folder, 'public');

        $fileRecord = new PreImplementationDocumentFile();
        $fileRecord->project_code = $project->project_code;
        $fileRecord->document_type = $documentType;
        $fileRecord->file_path = $path;
        $fileRecord->uploaded_at = $now;
        $fileRecord->uploaded_by = $userId;
        $fileRecord->status = $isProvincialDilgUploader ? 'pending_ro' : 'pending';
        $fileRecord->approved_at = $isProvincialDilgUploader ? $now : null;
        $fileRecord->approved_by = $isProvincialDilgUploader ? $userId : null;
        $fileRecord->approved_at_dilg_po = $isProvincialDilgUploader ? $now : null;
        $fileRecord->approved_by_dilg_po = $isProvincialDilgUploader ? $userId : null;
        $fileRecord->approved_at_dilg_ro = null;
        $fileRecord->approved_by_dilg_ro = null;
        $fileRecord->approval_remarks = null;
        $fileRecord->user_remarks = null;
        $fileRecord->save();

        $document->{$documentType} = $path;
        $document->save();

        $this->logActivity(
            $project->project_code,
            'upload',
            'Uploaded',
            $fileRecord,
            null,
            $now
        );

        $this->notifyUploadInterventionRecipients($project, [$documentType], $routeConfig, $scopeQuery);

        return redirect()
            ->route($routeConfig['show'], array_merge(['projectCode' => $project->project_code], $scopeQuery))
            ->with('success', $this->documentFieldMap()[$documentType] . ' uploaded successfully.');
    }

    private function notifyUploadInterventionRecipients(object $project, array $documentTypes, array $routeConfig, array $scopeQuery = []): void
    {
        try {
            $actor = Auth::user();
            if (!$actor || empty($documentTypes)) {
                return;
            }

            $targetProvince = trim((string) ($project->province ?? ''));
            $targetOffice = trim((string) ($project->city_municipality ?? ''));
            if ($targetProvince === '' && $targetOffice === '') {
                return;
            }

            $actorId = (int) ($actor->idno ?? Auth::id());
            $actorName = $actor->fullName() ?: 'A user';
            $projectLabel = trim((string) ($project->project_code ?? ''));
            $projectTitle = trim((string) ($project->project_title ?? ''));
            if ($projectTitle !== '') {
                $projectLabel .= ' (' . $projectTitle . ')';
            }

            $documentSummary = count($documentTypes) === 1
                ? $this->formatDocumentLabel((string) $documentTypes[0])
                : number_format(count($documentTypes)) . ' pre-implementation documents';

            $messageContext = $projectLabel !== '' ? $projectLabel : 'the project';
            if ($targetOffice !== '') {
                $messageContext .= ' - ' . $targetOffice;
            }
            if ($targetProvince !== '') {
                $messageContext .= ' - ' . $targetProvince;
            }

            $url = route($routeConfig['show'], array_merge(['projectCode' => $project->project_code], $scopeQuery), false);
            $notificationService = app(InterventionNotificationService::class);

            if ($actor->isLguScopedUser() && $targetProvince !== '') {
                $message = sprintf(
                    '%s uploaded %s for %s and it is awaiting DILG Provincial Office validation.',
                    $actorName,
                    $documentSummary,
                    $messageContext
                );

                $notificationService->notifyProvincialDilg(
                    $targetProvince,
                    $actorId,
                    $message,
                    $url,
                    'pre-implementation-upload'
                );

                return;
            }

            if ($actor->isDilgUser() && !$actor->isRegionalOfficeAssignment()) {
                $message = sprintf(
                    '%s uploaded %s for %s and it is awaiting DILG Regional Office validation.',
                    $actorName,
                    $documentSummary,
                    $messageContext
                );

                $notificationService->notifyRegionalDilg(
                    $actorId,
                    $message,
                    $url,
                    'pre-implementation-upload'
                );
            }
        } catch (\Throwable $error) {
            Log::warning('Failed to create upload notifications (Pre-Implementation).', [
                'project_code' => $project->project_code ?? null,
                'document_types' => $documentTypes,
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function viewDocument(Request $request, string $projectCode, string $documentType)
    {
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        if (!array_key_exists($documentType, $this->documentFieldMap())) {
            abort(404);
        }

        $document = PreImplementationDocument::where('project_code', $project->project_code)->first();
        $fileRecord = PreImplementationDocumentFile::where('project_code', $project->project_code)
            ->where('document_type', $documentType)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->first();

        $path = $fileRecord->file_path ?? ($document?->{$documentType} ?? null);
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($path);
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

    public function viewDocumentFile(Request $request, string $projectCode, int $fileId)
    {
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        $fileRecord = PreImplementationDocumentFile::where('project_code', $project->project_code)
            ->where('id', $fileId)
            ->firstOrFail();

        $path = $fileRecord->file_path;
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $filePath = Storage::disk('public')->path($path);
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

    public function validateDocument(Request $request, string $projectCode, string $documentType)
    {
        $scopeQuery = $this->scopeQuery($request);
        $routeConfig = $this->routeConfig($request);
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        if (!array_key_exists($documentType, $this->documentFieldMap())) {
            abort(404);
        }

        $user = Auth::user();
        $isDilg = strtoupper(trim((string) ($user->agency ?? ''))) === 'DILG';
        if (!$isDilg) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['nullable', 'string', 'max:1000', 'required_if:action,return'],
        ]);

        $fileRecord = PreImplementationDocumentFile::where('project_code', $project->project_code)
            ->where('document_type', $documentType)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->firstOrFail();

        return $this->handleDocumentValidation($request, $project, $fileRecord, $routeConfig, $scopeQuery);
    }

    public function validateDocumentFile(Request $request, string $projectCode, int $fileId)
    {
        $scopeQuery = $this->scopeQuery($request);
        $routeConfig = $this->routeConfig($request);
        $project = $this->resolveProjectForUser($projectCode, Auth::user(), $this->hasAllProjectsScope($request));
        if (!$project) {
            abort(404);
        }

        $fileRecord = PreImplementationDocumentFile::where('project_code', $project->project_code)
            ->where('id', $fileId)
            ->firstOrFail();

        return $this->handleDocumentValidation($request, $project, $fileRecord, $routeConfig, $scopeQuery);
    }

    private function handleDocumentValidation(Request $request, object $project, PreImplementationDocumentFile $fileRecord, array $routeConfig, array $scopeQuery)
    {
        $user = Auth::user();
        $isDilg = strtoupper(trim((string) ($user->agency ?? ''))) === 'DILG';
        if (!$isDilg) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['nullable', 'string', 'max:1000', 'required_if:action,return'],
        ]);

        if (empty($fileRecord->file_path)) {
            return back()->with('error', 'No file uploaded for this document yet.');
        }

        $documentType = (string) $fileRecord->document_type;
        $action = $validated['action'];
        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $isRegionalOffice = strcasecmp(trim((string) ($user->province ?? '')), 'Regional Office') === 0;
        $now = now();
        $userId = $user->idno ?? null;

        if ($action === 'approve') {
            if ($isRegionalOffice) {
                if (!$fileRecord->approved_at_dilg_po) {
                    return back()->with('error', 'Regional validation requires DILG Provincial validation first.');
                }

                $fileRecord->approved_at_dilg_ro = $now;
                $fileRecord->approved_by_dilg_ro = $userId;
                $fileRecord->status = 'approved';
            } else {
                $fileRecord->approved_at_dilg_po = $now;
                $fileRecord->approved_by_dilg_po = $userId;
                $fileRecord->approved_at_dilg_ro = null;
                $fileRecord->approved_by_dilg_ro = null;
                $fileRecord->status = 'pending_ro';
            }

            $fileRecord->approved_at = $now;
            $fileRecord->approved_by = $userId;
            $fileRecord->approval_remarks = null;
            $fileRecord->user_remarks = null;
            $fileRecord->save();

            $fileRecord->refresh();
            $this->logActivity(
                $project->project_code,
                $isRegionalOffice ? 'validate_ro' : 'validate_po',
                $isRegionalOffice ? 'Validated (DILG RO)' : 'Validated (DILG PO)',
                $fileRecord,
                null,
                $now
            );

            $this->notifyLguUsersAfterRegionalApproval(
                $project,
                $documentType,
                $fileRecord,
                $action,
                $isRegionalOffice,
                null
            );

            return back()->with('success', 'Document validated successfully.');
        }

        // return
        if ($isRegionalOffice) {
            $fileRecord->approved_at_dilg_ro = null;
            $fileRecord->approved_by_dilg_ro = $userId;
        } else {
            $fileRecord->approved_at_dilg_po = null;
            $fileRecord->approved_by_dilg_po = $userId;
            $fileRecord->approved_at_dilg_ro = null;
            $fileRecord->approved_by_dilg_ro = null;
        }

        $fileRecord->status = 'returned';
        $fileRecord->approved_at = $now;
        $fileRecord->approved_by = $userId;
        $fileRecord->approval_remarks = $remarks;
        $fileRecord->user_remarks = $remarks;
        $fileRecord->save();

        $fileRecord->refresh();
        $this->logActivity(
            $project->project_code,
            'return',
            'Returned',
            $fileRecord,
            $remarks !== '' ? $remarks : null,
            $now
        );

        $this->notifyLguUsersAfterRegionalApproval(
            $project,
            $documentType,
            $fileRecord,
            $action,
            $isRegionalOffice,
            $remarks !== '' ? $remarks : null
        );

        return back()->with('success', 'Document returned with remarks.');
    }

    private function formatDocumentLabel(string $documentType): string
    {
        $label = $this->documentFieldMap()[$documentType] ?? null;
        if ($label) {
            return $label;
        }

        return strtoupper(str_replace('_', ' ', $documentType));
    }

    private function notifyLguUsersAfterRegionalApproval(
        object $project,
        string $documentType,
        PreImplementationDocumentFile $fileRecord,
        string $action,
        bool $isRegionalOffice,
        ?string $remarks = null
    ): void
    {
        try {
            if (!Schema::hasTable('tbnotifications')) {
                return;
            }

            $actor = Auth::user();
            if (!$actor || strtoupper(trim((string) ($actor->agency ?? ''))) !== 'DILG') {
                return;
            }

            $targetProvince = trim((string) ($project->province ?? ''));
            $targetOffice = trim((string) ($project->city_municipality ?? ''));

            if ($targetProvince === '' && $targetOffice === '') {
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
                $fileRecord->uploaded_by,
                $fileRecord->approved_by_dilg_po,
                $fileRecord->approved_by_dilg_ro,
                $fileRecord->approved_by,
            ])->filter()->map(function ($value) {
                return (int) $value;
            });

            $recipientIds = $recipients->pluck('idno')->merge($relatedUserIds);

            $actorName = trim((string) ($actor->fname ?? '') . ' ' . (string) ($actor->lname ?? ''));
            if ($actorName === '') {
                $actorName = 'DILG Regional Office';
            }

            $projectCode = trim((string) ($project->project_code ?? ''));
            $projectTitle = trim((string) ($project->project_title ?? ''));
            $projectLabel = $projectCode;
            if ($projectTitle !== '') {
                $projectLabel .= ' (' . $projectTitle . ')';
            }

            $url = $projectCode !== ''
                ? route($routeConfig['show'], array_merge(['projectCode' => $projectCode], $scopeQuery), false)
                : route($routeConfig['index'], $scopeQuery, false);
            $actorId = (int) Auth::id();
            $notificationService = app(InterventionNotificationService::class);

            if ($action === 'approve' && !$isRegionalOffice) {
                $message = sprintf(
                    '%s validated (DILG PO) %s for %s%s%s and it is awaiting DILG Regional Office validation.',
                    $actorName,
                    $this->formatDocumentLabel($documentType),
                    $projectLabel !== '' ? $projectLabel : 'a project',
                    $targetOffice !== '' ? ' - ' . $targetOffice : '',
                    $targetProvince !== '' ? ' - ' . $targetProvince : ''
                );

                $notificationService->notifyRegionalDilg(
                    $actorId,
                    $message,
                    $url,
                    substr('pre-implementation-' . $documentType, 0, 100)
                );

                return;
            }

            $actionLabel = $action === 'approve'
                ? ($isRegionalOffice ? 'approved' : 'validated (DILG PO)')
                : 'returned';

            $message = sprintf(
                '%s %s %s for %s%s%s.',
                $actorName,
                $actionLabel,
                $this->formatDocumentLabel($documentType),
                $projectLabel !== '' ? $projectLabel : 'a project',
                $targetOffice !== '' ? ' - ' . $targetOffice : '',
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
                substr('pre-implementation-' . $documentType, 0, 100)
            );
        } catch (\Throwable $error) {
            Log::warning('Failed to create approval notifications (Pre-Implementation).', [
                'project_code' => $project->project_code ?? null,
                'document_type' => $documentType,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function buildCurrentActivityLogs($documentFiles): array
    {
        $logs = [];

        foreach ($documentFiles as $fileRecord) {
            $documentLabel = $this->formatDocumentLabel((string) $fileRecord->document_type);

            if ($fileRecord->uploaded_at) {
                $logs[] = [
                    'timestamp' => $fileRecord->uploaded_at,
                    'action' => 'Uploaded',
                    'document' => $documentLabel,
                    'user_id' => $fileRecord->uploaded_by,
                    'remarks' => null,
                ];
            }

            if ($fileRecord->approved_at_dilg_po) {
                $logs[] = [
                    'timestamp' => $fileRecord->approved_at_dilg_po,
                    'action' => 'Validated (DILG PO)',
                    'document' => $documentLabel,
                    'user_id' => $fileRecord->approved_by_dilg_po,
                    'remarks' => null,
                ];
            }

            if ($fileRecord->approved_at_dilg_ro) {
                $logs[] = [
                    'timestamp' => $fileRecord->approved_at_dilg_ro,
                    'action' => 'Validated (DILG RO)',
                    'document' => $documentLabel,
                    'user_id' => $fileRecord->approved_by_dilg_ro,
                    'remarks' => null,
                ];
            }

            if ($fileRecord->status === 'returned') {
                $logs[] = [
                    'timestamp' => $fileRecord->approved_at ?? $fileRecord->updated_at ?? $fileRecord->uploaded_at,
                    'action' => 'Returned',
                    'document' => $documentLabel,
                    'user_id' => $fileRecord->approved_by_dilg_ro ?: ($fileRecord->approved_by_dilg_po ?: $fileRecord->approved_by),
                    'remarks' => $fileRecord->approval_remarks,
                ];
            }
        }

        return $logs;
    }

    private function parsePersistedActivityLog(string $line, string $projectCode): ?array
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

        if (($context['module'] ?? null) !== 'pre_implementation_documents') {
            return null;
        }

        if (trim((string) ($context['project_code'] ?? '')) !== trim($projectCode)) {
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
            'document' => $context['document_label'] ?? 'Document',
            'user_id' => $context['user_id'] ?? null,
            'remarks' => $context['remarks'] ?? null,
        ];
    }

    private function getPersistedActivityLogs(string $projectCode): array
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
                if ($logEntry === '' || strpos($logEntry, '"module":"pre_implementation_documents"') === false) {
                    continue;
                }

                $parsed = $this->parsePersistedActivityLog($logEntry, $projectCode);
                if ($parsed) {
                    $entries[] = $parsed;
                }
            }
        }

        return $entries;
    }

    private function buildActivityLogs($documentFiles, string $projectCode): array
    {
        $persistedLogs = $this->getPersistedActivityLogs($projectCode);
        $currentLogs = $this->buildCurrentActivityLogs($documentFiles);

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
        string $projectCode,
        string $action,
        string $actionLabel,
        PreImplementationDocumentFile $documentFile,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        $timestamp = $timestamp ?: now();

        Log::channel('upload_timestamps')->info('Document action', [
            'module' => 'pre_implementation_documents',
            'project_code' => $projectCode,
            'document_type' => $documentFile->document_type,
            'document_label' => $this->formatDocumentLabel((string) $documentFile->document_type),
            'action' => $action,
            'action_label' => $actionLabel,
            'action_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'user_id' => Auth::id(),
            'remarks' => $remarks,
        ]);
    }

    private function buildAccessibleSubayQuery($user, bool $includeAllProjects = false)
    {
        $province = trim((string) ($user->province ?? ''));
        $office = trim((string) ($user->office ?? ''));
        $region = trim((string) ($user->region ?? ''));
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $regionLower = $user->normalizedRegion();
        $fundSourceExpression = $this->fundSourceExpression('spp');
        $lfpSources = $this->subaybayanLfpFundSources();
        $lfpSourcePlaceholders = implode(', ', array_fill(0, count($lfpSources), '?'));
        $cityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

        $query = DB::table('subay_project_profiles as spp');

        if (!$includeAllProjects) {
            $query
                ->whereRaw('CAST(NULLIF(TRIM(COALESCE(spp.funding_year, \'\')), \'\') AS UNSIGNED) >= 2024')
                ->whereRaw("{$fundSourceExpression} IN ({$lfpSourcePlaceholders})", $lfpSources);
        }

        if ($user->isLguScopedUser()) {
            if ($office !== '') {
                $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;
                if ($province !== '') {
                    $query
                        ->whereRaw('LOWER(spp.province) = ?', [$provinceLower])
                        ->where(function ($subQuery) use ($officeLower, $officeNeedle, $cityComparableExpression) {
                            $subQuery->whereRaw('LOWER(spp.city_municipality) = ?', [$officeLower]);

                            if ($officeNeedle !== '') {
                                $subQuery->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
                            }
                        });
                } else {
                    $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $cityComparableExpression) {
                        $subQuery->whereRaw('LOWER(spp.city_municipality) = ?', [$officeLower]);

                        if ($officeNeedle !== '') {
                            $subQuery->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
                        }
                    });
                }
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(spp.province) = ?', [$provinceLower]);
            }
        } elseif ($user->isDilgUser()) {
            if ($provinceLower === 'regional office') {
                // Regional Office can access all matched projects.
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(spp.province) = ?', [$provinceLower]);
            } elseif ($region !== '') {
                $query->whereRaw('LOWER(spp.region) = ?', [$regionLower]);
            }
        }

        return $query;
    }

    private function resolveProjectForUser(string $projectCode, $user, bool $includeAllProjects = false): ?object
    {
        $projectCode = trim($projectCode);
        if ($projectCode === '') {
            return null;
        }

        return $this->buildAccessibleSubayQuery($user, $includeAllProjects)
            ->where('spp.project_code', $projectCode)
            ->select([
                'spp.project_code',
                'spp.project_title',
                'spp.province',
                'spp.city_municipality',
                'spp.barangay',
                'spp.funding_year',
                'spp.status',
                DB::raw($this->fundSourceExpression('spp') . ' as fund_source'),
            ])
            ->first();
    }

    private function fundSourceExpression(string $alias = 'spp'): string
    {
        return "
            CASE
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SBDP%' THEN 'SBDP'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'FA-%' THEN 'FALGU'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'CMGP%' THEN 'CMGP'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'GEF%' THEN 'GEF'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SAFPB%' THEN 'SAFPB'
                WHEN UPPER(TRIM({$alias}.project_code)) LIKE 'SGLGIF%' THEN 'SGLGIF'
                WHEN TRIM(COALESCE({$alias}.program, '')) <> '' THEN UPPER(TRIM(COALESCE({$alias}.program, '')))
                ELSE 'UNSPECIFIED'
            END
        ";
    }

    private function projectTypeExpression(string $alias = 'spp'): string
    {
        return "TRIM(COALESCE(NULLIF(TRIM({$alias}.type_of_project), ''), NULLIF(TRIM({$alias}.type), ''), ''))";
    }

    private function subaybayanLfpFundSources(): array
    {
        return ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB'];
    }

    private function hasAllProjectsScope(?Request $request = null): bool
    {
        $request = $request ?: request();

        if ($request->routeIs('initial-project-documents.*')) {
            return true;
        }

        return strtolower(trim((string) $request->query('scope', ''))) === 'all';
    }

    private function scopeQuery(?Request $request = null): array
    {
        $request = $request ?: request();

        if ($request->routeIs('initial-project-documents.*')) {
            return [];
        }

        return $this->hasAllProjectsScope($request) ? ['scope' => 'all'] : [];
    }

    private function pageConfig(?Request $request = null): array
    {
        if ($this->hasAllProjectsScope($request)) {
            return [
                'title' => 'Initial Project Documents',
                'index_heading' => 'Initial Project Documents',
                'index_description' => 'View all accessible projects and open each project profile to manage initial project document records.',
                'show_description' => 'Upload and validate initial project documents for this project.',
                'empty_state' => 'No accessible projects found.',
                'save_success_message' => 'Initial project documents saved successfully.',
            ];
        }

        return [
            'title' => 'Pre-Implementation Documents',
            'index_heading' => 'Pre-Implementation Documents',
            'index_description' => 'View accessible SubayBayan LFP projects from 2024 onward and open each project profile to manage pre-implementation records.',
            'show_description' => 'Upload and validate pre-implementation documents for this project.',
            'empty_state' => 'No SubayBayan LFP projects found from 2024 onward.',
            'save_success_message' => 'Pre-implementation documents saved successfully.',
        ];
    }

    private function routeConfig(?Request $request = null): array
    {
        if ($this->hasAllProjectsScope($request)) {
            return [
                'index' => 'initial-project-documents.index',
                'show' => 'initial-project-documents.show',
                'document' => 'initial-project-documents.document',
                'document_file' => 'initial-project-documents.document-file',
                'save' => 'initial-project-documents.save',
                'validate' => 'initial-project-documents.validate',
                'validate_file' => 'initial-project-documents.validate-file',
                'upload_multi' => 'initial-project-documents.upload-multi',
            ];
        }

        return [
            'index' => 'pre-implementation-documents.index',
            'show' => 'pre-implementation-documents.show',
            'document' => 'pre-implementation-documents.document',
            'document_file' => 'pre-implementation-documents.document-file',
            'save' => 'pre-implementation-documents.save',
            'validate' => 'pre-implementation-documents.validate',
            'validate_file' => 'pre-implementation-documents.validate-file',
            'upload_multi' => 'pre-implementation-documents.upload-multi',
        ];
    }

    private function multiUploadDocumentTypes(): array
    {
        return [
            'variation_orders_path',
            'suspensions_path',
            'work_resumptions_path',
            'time_extensions_path',
        ];
    }

    private function singleUploadDocumentTypes(): array
    {
        return array_values(array_diff(array_keys($this->documentFieldMap()), $this->multiUploadDocumentTypes()));
    }

    private function isMultiUploadDocumentType(string $documentType): bool
    {
        return in_array($documentType, $this->multiUploadDocumentTypes(), true);
    }

    private function documentFieldMap(): array
    {
        return [
            'nadai_path' => 'NADAI',
            'confirmation_receipt_fund_path' => 'Confirmation on the Receipt of Fund',
            'proof_transfer_trust_fund_path' => 'Proof on the Transfer of Fund to LGU Trust Fund',
            'signed_lgu_letter_path' => 'Signed LGU Letter (if any)',
            'approved_ldip_path' => 'Approved LDIP',
            'approved_aip_path' => 'Approved AIP',
            'approved_dtp_path' => 'Approved DTP',
            'ecc_or_cnc_path' => 'ECC or CNC',
            'water_permit_or_application_path' => 'Water Permit or Application',
            'fpic_or_ncip_certification_path' => 'FPIC / NCIP Certification',
            'land_ownership_path' => 'Land Ownership',
            'right_of_way_path' => 'Right of Way',
            'moa_rural_electrification_path' => 'MOA (For Rural Electrification Projects)',
            'itb_posting_philgeps_path' => 'ITB Posting on PhilGEPS',
            'noa_path' => 'NOA Issuances',
            'contract_path' => 'Contract',
            'ntp_path' => 'Notice to Proceed',
            'program_of_works_path' => 'Program of Works (POW)',
            'design_and_engineering_documents_path' => 'Design and Engineering Documents (DEDs)',
            'variation_orders_path' => 'Variation Orders',
            'suspensions_path' => 'Suspensions',
            'work_resumptions_path' => 'Work Resumptions',
            'time_extensions_path' => 'Time Extensions',
            'cancellation_termination_path' => 'Cancellation / Termination',
        ];
    }

    private function documentFieldGroups(): array
    {
        return [
            'Initial Project Documents' => [
                'nadai_path',
                'confirmation_receipt_fund_path',
                'proof_transfer_trust_fund_path',
                'signed_lgu_letter_path',
            ],
            'Permits and Certifications' => [
                'approved_ldip_path',
                'approved_aip_path',
                'approved_dtp_path',
                'ecc_or_cnc_path',
                'water_permit_or_application_path',
                'fpic_or_ncip_certification_path',
                'land_ownership_path',
                'right_of_way_path',
                'moa_rural_electrification_path',
            ],
            'Contract Implementation Documents' => [
                'itb_posting_philgeps_path',
                'noa_path',
                'contract_path',
                'ntp_path',
            ],
            'Implementation Documents' => [
                'program_of_works_path',
                'design_and_engineering_documents_path',
                'variation_orders_path',
                'suspensions_path',
                'work_resumptions_path',
                'time_extensions_path',
                'cancellation_termination_path',
            ],
        ];
    }
}
