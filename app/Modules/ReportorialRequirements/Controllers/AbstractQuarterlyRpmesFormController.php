<?php

namespace App\Modules\ReportorialRequirements\Controllers;

use App\Http\Controllers\Controller;

use App\Services\SecureTimestampService;
use App\Support\InputSanitizer;
use App\Support\LguReportorialDeadlineResolver;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

abstract class AbstractQuarterlyRpmesFormController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    abstract protected function formConfig(): array;

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $perPage = (int) $request->input('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $filters = $this->resolveProjectFilters($request);

        if (!Schema::hasTable('subay_project_profiles')) {
            $projects = new LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('reports.quarterly.rpmes.shared.index', [
                'projects' => $projects,
                'perPage' => $perPage,
                'filters' => $filters,
                'filterOptions' => $this->emptyProjectFilterOptions(),
                'formMeta' => $this->viewMeta(),
                'submissionAnalysis' => $this->emptySubmissionAnalysis(),
            ]);
        }

        $baseQuery = $this->buildAccessibleSubayQuery($user);
        $filterOptions = $this->buildProjectFilterOptions(clone $baseQuery, $filters);
        $submissionAnalysis = !empty($this->formConfig()['show_submission_analysis'])
            ? $this->buildSubmissionAnalysis(clone $baseQuery, $filters)
            : [];

        $projects = $this->buildProjectListQuery($baseQuery, $filters)
            ->orderBy('validation_priority')
            ->orderByRaw('CASE WHEN latest_uploaded_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('latest_uploaded_at')
            ->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END")
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->orderBy('spp.project_code')
            ->paginate($perPage)
            ->withQueryString();

        return view('reports.quarterly.rpmes.shared.index', [
            'projects' => $projects,
            'perPage' => $perPage,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'formMeta' => $this->viewMeta(),
            'submissionAnalysis' => $submissionAnalysis,
        ]);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $filters = $this->resolveProjectFilters($request);
        $filename = 'rpmes-form-2-report-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Project Code',
            'Project Title',
            'Fund Source',
            'Funding Year',
            'City / Municipality',
            'Province',
            'Project Status',
            'Approval Status',
            'Date Submitted',
            'Validation Level',
        ];

        return response()->streamDownload(function () use ($user, $filters, $headers): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the report export stream.');
            }

            fputcsv($output, $headers);

            if (Schema::hasTable('subay_project_profiles')) {
                $query = $this->buildProjectListQuery(
                    $this->buildAccessibleSubayQuery($user),
                    $filters
                )
                    ->orderBy('spp.project_code');

                foreach ($query->cursor() as $project) {
                    $isReturned = strtolower(trim((string) $project->latest_submission_status)) === 'returned';
                    $isApproved = !empty($project->latest_approved_at_dilg_ro);
                    $isPendingRo = !empty($project->latest_approved_at_dilg_po) && !$isReturned;

                    $approvalStatus = 'Awaiting Upload';
                    $validationLevel = '—';
                    if (!empty($project->latest_report_file_path)) {
                        $approvalStatus = 'For DILG Provincial Office Validation';
                        $validationLevel = 'DILG Provincial Office';
                    }

                    if ($isPendingRo) {
                        $approvalStatus = 'For DILG Regional Office Validation';
                        $validationLevel = 'DILG Regional Office';
                    }
                    if ($isApproved) {
                        $approvalStatus = 'Approved';
                        $validationLevel = 'Completed';
                    }
                    if ($isReturned) {
                        $approvalStatus = 'Returned';
                        $validationLevel = !empty($project->latest_approved_at_dilg_po)
                            ? 'Returned at DILG Regional Office'
                            : 'Returned at DILG Provincial Office';
                    }

                    $dateSubmitted = $project->latest_uploaded_at
                        ? \Illuminate\Support\Carbon::parse($project->latest_uploaded_at)
                            ->setTimezone(config('app.timezone'))
                            ->format('M d, Y h:i A')
                        : '—';

                    fputcsv($output, array_map($this->safeCsvCell(...), [
                        $project->project_code,
                        $project->project_title ?: '-',
                        $project->fund_source ?: 'Unspecified',
                        $project->funding_year ?: '-',
                        $project->city_municipality ?: '-',
                        $project->province ?: '-',
                        $project->status ?: 'Unknown',
                        $approvalStatus,
                        $dateSubmitted,
                        $validationLevel,
                    ]));
                }
            }

            $analysis = $this->buildSubmissionAnalysis(
                $this->buildAccessibleSubayQuery($user),
                $filters
            );
            $overall = $analysis['overall'];

            fputcsv($output, array_fill(0, count($headers), ''));
            fputcsv($output, ['Analysis Summary']);
            fputcsv($output, ['Metric', 'Count / Rate']);
            fputcsv($output, ['Unique projects', $overall['total_projects']]);
            fputcsv($output, ['Quarterly reports submitted (all quarters)', $overall['submitted']]);
            fputcsv($output, ['Approved reports', $overall['approved']]);
            fputcsv($output, ['Reports awaiting validation', $overall['pending_validation']]);
            fputcsv($output, ['Returned reports', $overall['returned']]);
            fputcsv($output, ['Submission rate', number_format((float) $overall['submission_rate'], 1) . '%']);
            fputcsv($output, array_fill(0, count($headers), ''));
            fputcsv($output, ['Quarterly Breakdown']);
            fputcsv($output, ['Quarter', 'Submitted', 'Approved', 'Awaiting Validation', 'Returned', 'Approval Rate']);

            foreach (['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'] as $quarterCode => $quarterLabel) {
                $quarter = $analysis['quarters'][$quarterCode];
                fputcsv($output, [
                    $quarterLabel,
                    $quarter['submitted'],
                    $quarter['approved'],
                    $quarter['pending_validation'],
                    $quarter['returned'],
                    number_format((float) $quarter['approval_rate'], 1) . '%',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildProjectListQuery($baseQuery, array $filters)
    {
        $projectsQuery = (clone $baseQuery)
            ->whereNotNull('spp.project_code')
            ->whereRaw("TRIM(COALESCE(spp.project_code, '')) <> ''")
            ->select([
                'spp.id',
                'spp.project_code',
                'spp.project_title',
                'spp.city_municipality',
                'spp.province',
                'spp.funding_year',
                'spp.status',
                DB::raw($this->fundSourceExpression('spp') . ' as fund_source'),
            ]);

        $this->applyProjectFilters($projectsQuery, $filters);
        if (!empty($this->formConfig()['show_submission_analysis'])) {
            $canonicalProjectIds = (clone $baseQuery)
                ->whereNotNull('spp.project_code')
                ->whereRaw("TRIM(COALESCE(spp.project_code, '')) <> ''");
            $this->applyProjectFilters($canonicalProjectIds, $filters);
            $canonicalProjectIds
                ->selectRaw('MAX(spp.id)')
                ->groupByRaw('LOWER(TRIM(spp.project_code))');
            $projectsQuery->whereIn('spp.id', $canonicalProjectIds);
        }

        $this->applyValidationListMetadata($projectsQuery);

        return $projectsQuery;
    }

    private function safeCsvCell($value): string
    {
        $value = trim((string) ($value ?? ''));

        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1
            ? "'" . $value
            : $value;
    }

    private function buildSubmissionAnalysis($baseQuery, array $filters): array
    {
        $projectScope = (clone $baseQuery)
            ->whereNotNull('spp.project_code')
            ->whereRaw("TRIM(COALESCE(spp.project_code, '')) <> ''");
        $this->applyProjectFilters($projectScope, $filters);

        $projectScope
            ->select([
                DB::raw('LOWER(TRIM(spp.project_code)) as project_code'),
                DB::raw('MIN(spp.city_municipality) as city_municipality'),
            ])
            ->groupByRaw('LOWER(TRIM(spp.project_code))');

        $totalProjects = (int) DB::query()
            ->fromSub(clone $projectScope, 'analysis_projects')
            ->count();

        $quarterStats = [];
        foreach (array_keys($this->quarters()) as $quarter) {
            $quarterStats[$quarter] = [
                'total' => $totalProjects,
                'submitted' => 0,
                'approved' => 0,
                'pending_validation' => 0,
                'returned' => 0,
                'no_submission' => $totalProjects,
                'approval_rate' => 0,
            ];
        }

        $overall = [
            'total_projects' => $totalProjects,
            'total_periods' => $totalProjects * count($quarterStats),
            'submitted' => 0,
            'approved' => 0,
            'pending_validation' => 0,
            'returned' => 0,
            'no_submission' => $totalProjects * count($quarterStats),
            'submission_rate' => 0,
            'approval_rate' => 0,
        ];

        if ($totalProjects === 0 || !Schema::hasTable($this->uploadTable())) {
            return ['overall' => $overall, 'quarters' => $quarterStats];
        }

        $uploads = DB::table($this->uploadTable() . ' as upload')
            ->joinSub(clone $projectScope, 'analysis_projects', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(upload.project_code))'), '=', 'analysis_projects.project_code');
            })
            ->whereIn('upload.quarter', array_keys($quarterStats))
            ->select([
                'upload.quarter',
                'upload.file_path',
                'upload.status',
                'upload.approved_at_dilg_po',
                'upload.approved_at_dilg_ro',
                'upload.approved_at_c_mlgoo',
                'analysis_projects.city_municipality',
            ])
            ->get();

        $workflowService = app(\App\Services\LguReportorialWorkflowService::class);
        $modelClass = $this->uploadModelClass();
        foreach ($uploads as $upload) {
            if (!isset($quarterStats[$upload->quarter])) {
                continue;
            }

            $uploadModel = new $modelClass((array) $upload);
            $stage = $workflowService->currentStage($uploadModel, $upload->city_municipality);
            if ($stage === 'pending_upload') {
                continue;
            }

            $quarterStats[$upload->quarter]['submitted']++;
            $quarterStats[$upload->quarter]['no_submission']--;
            $overall['submitted']++;
            $overall['no_submission']--;

            if ($stage === \App\Services\LguReportorialWorkflowService::STATUS_APPROVED) {
                $quarterStats[$upload->quarter]['approved']++;
                $overall['approved']++;
            } elseif ($stage === \App\Services\LguReportorialWorkflowService::STATUS_RETURNED) {
                $quarterStats[$upload->quarter]['returned']++;
                $overall['returned']++;
            } else {
                $quarterStats[$upload->quarter]['pending_validation']++;
                $overall['pending_validation']++;
            }
        }

        foreach ($quarterStats as &$quarterStat) {
            $quarterStat['approval_rate'] = $quarterStat['total'] > 0
                ? round(($quarterStat['approved'] / $quarterStat['total']) * 100, 1)
                : 0;
        }
        unset($quarterStat);

        $overall['submission_rate'] = $overall['total_periods'] > 0
            ? round(($overall['submitted'] / $overall['total_periods']) * 100, 1)
            : 0;
        $overall['approval_rate'] = $overall['total_periods'] > 0
            ? round(($overall['approved'] / $overall['total_periods']) * 100, 1)
            : 0;

        return ['overall' => $overall, 'quarters' => $quarterStats];
    }

    private function emptySubmissionAnalysis(): array
    {
        $quarterStats = [];
        foreach (array_keys($this->quarters()) as $quarter) {
            $quarterStats[$quarter] = [
                'total' => 0,
                'submitted' => 0,
                'approved' => 0,
                'pending_validation' => 0,
                'returned' => 0,
                'no_submission' => 0,
                'approval_rate' => 0,
            ];
        }

        return [
            'overall' => [
                'total_projects' => 0,
                'total_periods' => 0,
                'submitted' => 0,
                'approved' => 0,
                'pending_validation' => 0,
                'returned' => 0,
                'no_submission' => 0,
                'submission_rate' => 0,
                'approval_rate' => 0,
            ],
            'quarters' => $quarterStats,
        ];
    }

    public function show(Request $request, string $projectCode)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        if (!Schema::hasTable('subay_project_profiles')) {
            abort(404);
        }

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        $quarters = $this->quarters();
        $defaultPeriod = array_key_first($quarters) ?? 'Q1';
        $selectedQuarter = $this->normalizeQuarter($request->query('quarter', $defaultPeriod), $defaultPeriod);
        $uploadsByQuarter = array_fill_keys(array_keys($quarters), null);
        $isProvincialDilgViewer = $this->isProvincialDilgUser($user);
        $isRegionalDilgViewer = (bool) ($user && $user->isRegionalOfficeAssignment());
        $deadlineReportingYear = $this->deadlineReportingYear();
        $configuredQuarterDeadlines = app(LguReportorialDeadlineResolver::class)->resolveMany(
            $this->deadlineAspect(),
            $deadlineReportingYear,
            array_keys($quarters)
        );

        if (Schema::hasTable($this->uploadTable())) {
            $modelClass = $this->uploadModelClass();
            $uploads = $modelClass::with([
                    'uploader:idno,fname,lname',
                    'approver:idno,fname,lname',
                    'cMlgooApprover:idno,fname,lname',
                    'dilgPoApprover:idno,fname,lname',
                    'dilgRoApprover:idno,fname,lname',
                ])
                ->where('project_code', $project->project_code)
                ->whereIn('quarter', array_keys($quarters))
                ->get()
                ->keyBy('quarter');

            foreach ($uploadsByQuarter as $quarterCode => $value) {
                $uploadsByQuarter[$quarterCode] = $uploads->get($quarterCode);
            }
        }

        return view('reports.quarterly.rpmes.shared.show', [
            'project' => $project,
            'quarters' => $quarters,
            'selectedQuarter' => $selectedQuarter,
            'uploadsByQuarter' => $uploadsByQuarter,
            'isProvincialDilgViewer' => $isProvincialDilgViewer,
            'isRegionalDilgViewer' => $isRegionalDilgViewer,
            'deadlineReportingYear' => $deadlineReportingYear,
            'configuredQuarterDeadlines' => $configuredQuarterDeadlines,
            'formMeta' => $this->viewMeta(),
        ]);
    }

    public function upload(Request $request, string $projectCode)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable($this->uploadTable()), 500, $this->reportShortName() . ' uploads table is not available.');

        abort_unless($user && $user->canSubmitForLgu($project->province, $project->city_municipality), 403);

        $validated = $request->validate([
            'quarter' => ['required', Rule::in(array_keys($this->quarters()))],
            'report_file' => ['required', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $quarter = $validated['quarter'];
        $modelClass = $this->uploadModelClass();
        $existingUpload = $modelClass::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if ($existingUpload && $existingUpload->file_path && $existingUpload->status !== 'returned') {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'A ' . $this->reportShortName() . ' report already exists for this submission period. Use the current submission flow before replacing it.']);
        }

        $oldFilePath = $existingUpload?->file_path;
        $file = $request->file('report_file');
        $path = $file->store($this->storageDirectory() . '/' . $project->project_code . '/' . $quarter, 'public');
        $secureTimestamp = SecureTimestampService::getUploadTimestamp();
        $actorId = $user->idno ?? auth()->id();

        $workflowService = app(\App\Services\LguReportorialWorkflowService::class);
        $initialStatus = $workflowService->initialStatusForUpload($user, $project->city_municipality, $project->province);

        $upload = $existingUpload ?? new $modelClass();
        $upload->timestamps = false;

        if (!$upload->exists) {
            $upload->project_code = $project->project_code;
            $upload->quarter = $quarter;
            $upload->created_at = $secureTimestamp;
        }

        $upload->file_path = $path;
        $upload->original_name = $file->getClientOriginalName();
        $upload->uploaded_by = $actorId;
        $upload->uploaded_at = $secureTimestamp;
        $upload->status = $initialStatus;
        $upload->approved_by = null;
        $upload->approved_at = null;
        $upload->approved_at_c_mlgoo = null;
        $upload->approved_by_c_mlgoo = null;
        $upload->approved_at_dilg_po = null;
        $upload->approved_at_dilg_ro = null;
        $upload->approved_by_dilg_po = null;
        $upload->approved_by_dilg_ro = null;
        $upload->approval_remarks = null;
        $upload->user_remarks = null;
        $upload->updated_at = $secureTimestamp;
        $upload->save();

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        SecureTimestampService::logUploadTimestamp($this->timestampLogKey(), $project->project_code, $quarter, $secureTimestamp);

        $statusBadge = $workflowService->resolveStatusBadge($upload, $project->city_municipality);
        $message = $this->reportShortName() . ' report uploaded successfully. Current status: ' . $statusBadge['label'] . '.';

        return redirect()
            ->route($this->showRouteName(), [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $message);
    }

    public function approveDocument(Request $request, string $projectCode, string $quarter)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable($this->uploadTable()), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $validated = $request->validate([
            'action' => ['required', 'in:approve,return'],
            'remarks' => ['required_if:action,return', 'nullable', 'string', 'max:1000'],
        ]);

        $modelClass = $this->uploadModelClass();
        $upload = $modelClass::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload || !$upload->file_path) {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'No uploaded ' . $this->reportShortName() . ' report was found for the selected submission period.']);
        }

        $action = $validated['action'];
        $remarks = InputSanitizer::sanitizeNullablePlainText($validated['remarks'] ?? null, true);

        if ($action === 'return' && $remarks === null) {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['remarks' => 'Return remarks must contain plain text.']);
        }

        $workflowService = app(\App\Services\LguReportorialWorkflowService::class);
        $check = $workflowService->canUserValidate($user, $upload, $project->city_municipality, $project->province);
        if (!$check['allowed']) {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => $check['reason'] ?? 'Unauthorized validation action.']);
        }

        $result = $workflowService->processValidation($user, $upload, $action, $remarks, $project->city_municipality, $project->province);

        return redirect()
            ->route($this->showRouteName(), [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $result['message']);
    }

    public function viewDocument(string $projectCode, string $quarter)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable($this->uploadTable()), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $modelClass = $this->uploadModelClass();
        $upload = $modelClass::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload || !$upload->file_path || !Storage::disk('public')->exists($upload->file_path)) {
            abort(404, 'Document not found.');
        }

        $filePath = Storage::disk('public')->path($upload->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $inlineExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if (!in_array($extension, $inlineExtensions, true)) {
            return response()->download($filePath, $upload->original_name ?: basename($filePath), $headers);
        }

        return response()->file($filePath, $headers);
    }

    public function deleteDocument(string $projectCode, string $quarter)
    {
        $user = Auth::user();
        abort_unless($this->userCanAccessReport($user), 403);

        $project = $this->resolveProjectForUser($projectCode, $user);
        abort_if(!$project, 404);

        abort_unless(Schema::hasTable($this->uploadTable()), 404);

        $quarter = $this->normalizeQuarter($quarter);
        $modelClass = $this->uploadModelClass();
        $upload = $modelClass::query()
            ->where('project_code', $project->project_code)
            ->where('quarter', $quarter)
            ->first();

        if (!$upload) {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'No uploaded ' . $this->reportShortName() . ' report was found for the selected submission period.']);
        }

        if (in_array((string) $upload->status, ['pending_ro', 'approved'], true)) {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['report_file' => 'This ' . $this->reportShortName() . ' report can no longer be deleted after DILG validation has started.']);
        }

        if ($upload->file_path && Storage::disk('public')->exists($upload->file_path)) {
            Storage::disk('public')->delete($upload->file_path);
        }

        $upload->delete();

        return redirect()
            ->route($this->showRouteName(), [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $this->reportShortName() . ' report deleted successfully.');
    }

    protected function userCanAccessReport($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasCrudPermission($this->permissionAspect(), 'view');
    }

    protected function userCanApproveReport($user): bool
    {
        if (!$this->userCanAccessReport($user) || !$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isMlgooUser() || $user->isProvincialUser() || $user->isRegionalUser() || $user->isDilgUser();
    }

    protected function isProvincialDilgUser($user): bool
    {
        if (!$user) {
            return false;
        }

        $agency = strtoupper(trim((string) $user->agency));
        if ($agency !== 'DILG') {
            return false;
        }

        $provinceLower = strtolower(trim((string) $user->province));
        return $provinceLower !== '' && $provinceLower !== 'regional office';
    }

    protected function buildAccessibleSubayQuery($user)
    {
        $province = trim((string) ($user->province ?? ''));
        $office = trim((string) ($user->office ?? ''));
        $region = trim((string) ($user->region ?? ''));
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $regionLower = $user->normalizedRegion();
        $cityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

        $query = DB::table('subay_project_profiles as spp');

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
                // Regional Office can access all SubayBayan projects.
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(spp.province) = ?', [$provinceLower]);
            } elseif ($region !== '') {
                $query->whereRaw('LOWER(spp.region) = ?', [$regionLower]);
            }
        }

        $minimumFundingYear = (int) ($this->formConfig()['minimum_funding_year'] ?? 0);
        if ($minimumFundingYear > 0) {
            $query->whereRaw(
                "CAST(NULLIF(TRIM(COALESCE(spp.funding_year, '')), '') AS UNSIGNED) >= ?",
                [$minimumFundingYear]
            );
        }

        $allowedFundSources = $this->allowedFundSources();
        if (!empty($allowedFundSources)) {
            $normalizedFundSources = collect($allowedFundSources)
                ->map(static fn ($value) => strtoupper(trim((string) $value)))
                ->filter(static fn (string $value) => $value !== '')
                ->values()
                ->all();

            if (!empty($normalizedFundSources)) {
                $placeholders = implode(', ', array_fill(0, count($normalizedFundSources), '?'));
                $query->whereRaw(
                    'UPPER(TRIM(' . $this->fundSourceExpression('spp') . ")) IN ({$placeholders})",
                    $normalizedFundSources
                );
            }
        }

        $excludedFundSources = collect($this->formConfig()['excluded_fund_sources'] ?? [])
            ->map(static fn ($value) => strtoupper(trim((string) $value)))
            ->filter(static fn (string $value) => $value !== '')
            ->values()
            ->all();
        if ($excludedFundSources !== []) {
            $placeholders = implode(', ', array_fill(0, count($excludedFundSources), '?'));
            $query->whereRaw(
                'UPPER(TRIM(' . $this->fundSourceExpression('spp') . ")) NOT IN ({$placeholders})",
                $excludedFundSources
            );
        }

        return $query;
    }

    protected function resolveProjectForUser(string $projectCode, $user): ?object
    {
        $projectCode = trim($projectCode);
        if ($projectCode === '') {
            return null;
        }

        return $this->buildAccessibleSubayQuery($user)
            ->where('spp.project_code', $projectCode)
            ->select([
                'spp.project_code',
                'spp.project_title',
                'spp.city_municipality',
                'spp.province',
                'spp.barangay',
                'spp.region',
                'spp.funding_year',
                'spp.status',
                'spp.program',
                'spp.type',
                'spp.type_of_project',
                'spp.sub_type_of_project',
                'spp.exact_location',
                'spp.project_description',
                DB::raw($this->fundSourceExpression('spp') . ' as fund_source'),
            ])
            ->first();
    }

    protected function fundSourceExpression(string $alias = 'spp'): string
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
                WHEN UPPER(TRIM(COALESCE({$alias}.program, ''))) LIKE '%FALGU%' THEN 'FALGU'
                WHEN UPPER(TRIM(COALESCE({$alias}.program, ''))) IN ('GROWTH EQUITY FUND', 'GEF') THEN 'GEF'
                WHEN UPPER(TRIM(COALESCE({$alias}.program, ''))) IN ('SUPPORT TO THE BARANGAY DEVELOPMENT PROGRAM', 'SBDP') THEN 'SBDP'
                WHEN TRIM(COALESCE({$alias}.program, '')) <> '' THEN UPPER(TRIM(COALESCE({$alias}.program, '')))
                ELSE 'UNSPECIFIED'
            END
        ";
    }

    protected function projectTypeExpression(string $alias = 'spp'): string
    {
        return "TRIM(COALESCE(NULLIF(TRIM({$alias}.type_of_project), ''), NULLIF(TRIM({$alias}.type), ''), ''))";
    }

    protected function resolveProjectFilters(Request $request): array
    {
        $user = Auth::user();
        $provinceFilter = $this->normalizeProjectFilterValues($request->input('province', []));
        if ($user && ($user->isProvincialUser() || ($user->isDilgUser() && !$user->isRegionalUser() && !$user->isSuperAdmin() && !empty($user->province) && $user->province !== 'Regional Office'))) {
            $provinceFilter = [$user->province];
        }

        $filters = [
            'province' => $provinceFilter,
            'city_municipality' => $this->normalizeProjectFilterValues($request->input('city_municipality', [])),
            'barangay' => $this->normalizeProjectFilterValues($request->input('barangay', [])),
            'program' => $this->normalizeProjectFilterValues($request->input('program', [])),
            'funding_year' => $this->normalizeProjectFilterValues($request->input('funding_year', [])),
            'project_type' => $this->normalizeProjectFilterValues($request->input('project_type', [])),
            'project_status' => $this->normalizeProjectFilterValues($request->input('project_status', [])),
        ];

        if (empty($filters['province'])) {
            $filters['city_municipality'] = [];
        }

        if (empty($filters['city_municipality'])) {
            $filters['barangay'] = [];
        }

        return $filters;
    }

    protected function normalizeProjectFilterValues($rawValues): array
    {
        $values = is_array($rawValues) ? $rawValues : [$rawValues];

        return collect($values)
            ->map(static fn ($value) => trim((string) $value))
            ->filter(static fn (string $value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function emptyProjectFilterOptions(): array
    {
        return [
            'provinces' => collect(),
            'cities' => collect(),
            'barangays' => collect(),
            'province_city_map' => [],
            'city_barangay_map' => [],
            'programs' => collect(),
            'funding_years' => collect(),
            'project_types' => collect(),
            'project_statuses' => collect(),
        ];
    }

    protected function buildProjectFilterOptions($baseQuery, array $filters): array
    {
        $projectTypeExpression = $this->projectTypeExpression('spp');
        $provinces = (clone $baseQuery)
            ->select('spp.province')
            ->whereRaw("TRIM(COALESCE(spp.province, '')) <> ''")
            ->distinct()
            ->orderBy('spp.province')
            ->pluck('spp.province')
            ->map([ProjectLocationFilterHelper::class, 'normalizeLabel'])
            ->filter()
            ->values();

        $provinceCityMap = ProjectLocationFilterHelper::buildProvinceCityMap(
            clone $baseQuery,
            $provinces->all(),
            'spp.province',
            'spp.city_municipality'
        );
        $selectedCities = ProjectLocationFilterHelper::selectedMappedValues(
            $filters['province'] ?? [],
            $provinceCityMap
        );
        $cityBarangayMap = ProjectLocationFilterHelper::buildCityBarangayMap(
            clone $baseQuery,
            'spp.city_municipality',
            'spp.barangay'
        );
        $selectedBarangays = ProjectLocationFilterHelper::selectedMappedValues(
            $filters['city_municipality'] ?? [],
            $cityBarangayMap
        );

        return [
            'provinces' => $provinces,
            'cities' => $selectedCities,
            'barangays' => $selectedBarangays,
            'province_city_map' => $provinceCityMap,
            'city_barangay_map' => $cityBarangayMap,
            'programs' => (clone $baseQuery)
                ->select('spp.program')
                ->whereRaw("TRIM(COALESCE(spp.program, '')) <> ''")
                ->distinct()
                ->orderBy('spp.program')
                ->pluck('spp.program'),
            'funding_years' => (clone $baseQuery)
                ->select('spp.funding_year')
                ->whereRaw("TRIM(COALESCE(spp.funding_year, '')) <> ''")
                ->distinct()
                ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) DESC")
                ->pluck('spp.funding_year'),
            'project_types' => (clone $baseQuery)
                ->select(DB::raw("{$projectTypeExpression} as project_type"))
                ->whereRaw("{$projectTypeExpression} <> ''")
                ->distinct()
                ->orderBy('project_type')
                ->pluck('project_type'),
            'project_statuses' => (clone $baseQuery)
                ->select('spp.status')
                ->whereRaw("TRIM(COALESCE(spp.status, '')) <> ''")
                ->distinct()
                ->orderBy('spp.status')
                ->pluck('spp.status'),
        ];
    }

    protected function applyProjectFilters($query, array $filters): void
    {
        if (!empty($filters['province'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.province, ''))"), $filters['province']);
        }

        if (!empty($filters['city_municipality'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.city_municipality, ''))"), $filters['city_municipality']);
        }

        if (!empty($filters['barangay'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.barangay, ''))"), $filters['barangay']);
        }

        if (!empty($filters['program'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.program, ''))"), $filters['program']);
        }

        if (!empty($filters['funding_year'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.funding_year, ''))"), $filters['funding_year']);
        }

        if (!empty($filters['project_type'])) {
            $query->whereIn(DB::raw($this->projectTypeExpression('spp')), $filters['project_type']);
        }

        if (!empty($filters['project_status'])) {
            $query->whereIn(DB::raw("TRIM(COALESCE(spp.status, ''))"), $filters['project_status']);
        }
    }

    protected function applyValidationListMetadata($query): void
    {
        if (!Schema::hasTable($this->uploadTable())) {
            $query->addSelect([
                DB::raw('NULL as latest_report_file_path'),
                DB::raw('NULL as latest_submission_status'),
                DB::raw('NULL as latest_uploaded_at'),
                DB::raw('NULL as latest_approved_at_dilg_po'),
                DB::raw('NULL as latest_approved_at_dilg_ro'),
                DB::raw('3 as validation_priority'),
            ]);

            return;
        }

        $table = $this->uploadTable();
        $latestOrderExpression = "COALESCE(uploaded_at, updated_at, created_at)";
        $filePathSubquery = "(SELECT file_path FROM {$table} WHERE project_code = spp.project_code ORDER BY {$latestOrderExpression} DESC, id DESC LIMIT 1)";
        $statusSubquery = "(SELECT status FROM {$table} WHERE project_code = spp.project_code ORDER BY {$latestOrderExpression} DESC, id DESC LIMIT 1)";
        $uploadedAtSubquery = "(SELECT uploaded_at FROM {$table} WHERE project_code = spp.project_code ORDER BY {$latestOrderExpression} DESC, id DESC LIMIT 1)";
        $approvedAtPoSubquery = "(SELECT approved_at_dilg_po FROM {$table} WHERE project_code = spp.project_code ORDER BY {$latestOrderExpression} DESC, id DESC LIMIT 1)";
        $approvedAtRoSubquery = "(SELECT approved_at_dilg_ro FROM {$table} WHERE project_code = spp.project_code ORDER BY {$latestOrderExpression} DESC, id DESC LIMIT 1)";

        $query->addSelect([
            DB::raw("{$filePathSubquery} as latest_report_file_path"),
            DB::raw("{$statusSubquery} as latest_submission_status"),
            DB::raw("{$uploadedAtSubquery} as latest_uploaded_at"),
            DB::raw("{$approvedAtPoSubquery} as latest_approved_at_dilg_po"),
            DB::raw("{$approvedAtRoSubquery} as latest_approved_at_dilg_ro"),
            DB::raw("
                CASE
                    WHEN COALESCE({$filePathSubquery}, '') = '' THEN 3
                    WHEN LOWER(COALESCE({$statusSubquery}, '')) <> 'returned'
                        AND ({$approvedAtPoSubquery} IS NULL OR ({$approvedAtPoSubquery} IS NOT NULL AND {$approvedAtRoSubquery} IS NULL)) THEN 0
                    WHEN LOWER(COALESCE({$statusSubquery}, '')) = 'returned' THEN 1
                    WHEN {$approvedAtRoSubquery} IS NOT NULL THEN 2
                    ELSE 3
                END as validation_priority
            "),
        ]);
    }

    protected function deadlineReportingYear(): int
    {
        return (int) now()->year;
    }

    protected function quarters(): array
    {
        return [
            'Q1' => '1st Quarter',
            'Q2' => '2nd Quarter',
            'Q3' => '3rd Quarter',
            'Q4' => '4th Quarter',
        ];
    }

    protected function normalizeQuarter(?string $quarter, ?string $default = null): string
    {
        $periods = $this->quarters();
        $defaultPeriod = is_string($default) && array_key_exists($default, $periods)
            ? $default
            : (array_key_first($periods) ?? 'Q1');

        $quarter = trim((string) $quarter);
        if ($quarter === '') {
            return $defaultPeriod;
        }

        foreach (array_keys($periods) as $periodKey) {
            if (strcasecmp($periodKey, $quarter) === 0) {
                return $periodKey;
            }
        }

        return $defaultPeriod;
    }

    protected function viewMeta(): array
    {
        return array_merge([
            'submission_section_label' => 'Quarterly Submission',
            'deadline_card_label' => 'Quarter Deadline',
            'validation_scope_label' => 'quarterly validation',
        ], $this->formConfig());
    }

    protected function permissionAspect(): string
    {
        return (string) $this->formConfig()['permission_aspect'];
    }

    protected function deadlineAspect(): string
    {
        return (string) ($this->formConfig()['deadline_aspect'] ?? $this->permissionAspect());
    }

    protected function uploadTable(): string
    {
        return (string) $this->formConfig()['upload_table'];
    }

    protected function uploadModelClass(): string
    {
        return (string) $this->formConfig()['model_class'];
    }

    protected function storageDirectory(): string
    {
        return (string) $this->formConfig()['storage_directory'];
    }

    protected function timestampLogKey(): string
    {
        return (string) $this->formConfig()['timestamp_log_key'];
    }

    protected function reportShortName(): string
    {
        return (string) $this->formConfig()['report_short_name'];
    }

    protected function allowedFundSources(): array
    {
        return (array) ($this->formConfig()['allowed_fund_sources'] ?? []);
    }

    protected function showRouteName(): string
    {
        return (string) $this->formConfig()['show_route'];
    }
}
