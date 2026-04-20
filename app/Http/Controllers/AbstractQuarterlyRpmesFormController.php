<?php

namespace App\Http\Controllers;

use App\Services\SecureTimestampService;
use App\Support\InputSanitizer;
use App\Support\LguReportorialDeadlineResolver;
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
            ]);
        }

        $baseQuery = $this->buildAccessibleSubayQuery($user);
        $filterOptions = $this->buildProjectFilterOptions(clone $baseQuery);

        $projectsQuery = (clone $baseQuery)
            ->whereNotNull('spp.project_code')
            ->whereRaw("TRIM(COALESCE(spp.project_code, '')) <> ''")
            ->select([
                'spp.project_code',
                'spp.project_title',
                'spp.city_municipality',
                'spp.province',
                'spp.funding_year',
                'spp.status',
                DB::raw($this->fundSourceExpression('spp') . ' as fund_source'),
            ]);

        $this->applyProjectFilters($projectsQuery, $filters);

        $projects = $projectsQuery
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
        ]);
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

        if ($user && $user->isRegionalOfficeAssignment()) {
            $defaultPeriod = array_key_first($this->quarters()) ?? 'Q1';
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $this->normalizeQuarter($request->input('quarter', $defaultPeriod), $defaultPeriod),
                ])
                ->withErrors(['report_file' => 'DILG Regional Office cannot upload ' . $this->reportShortName() . ' reports.']);
        }

        $validated = $request->validate([
            'quarter' => ['required', Rule::in(array_keys($this->quarters()))],
            'report_file' => ['required', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $quarter = $validated['quarter'];
        $autoElevateToRegional = $this->isProvincialDilgUser($user);
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
        $upload->status = $autoElevateToRegional ? 'pending_ro' : 'pending';
        $upload->approved_by = $autoElevateToRegional ? $actorId : null;
        $upload->approved_at = $autoElevateToRegional ? $secureTimestamp : null;
        $upload->approved_at_dilg_po = $autoElevateToRegional ? $secureTimestamp : null;
        $upload->approved_at_dilg_ro = null;
        $upload->approved_by_dilg_po = $autoElevateToRegional ? $actorId : null;
        $upload->approved_by_dilg_ro = null;
        $upload->approval_remarks = null;
        $upload->user_remarks = null;
        $upload->updated_at = $secureTimestamp;
        $upload->save();

        if ($oldFilePath && $oldFilePath !== $path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        SecureTimestampService::logUploadTimestamp($this->timestampLogKey(), $project->project_code, $quarter, $secureTimestamp);

        $message = $autoElevateToRegional
            ? $this->reportShortName() . ' uploaded and validated by DILG Provincial Office. It is now pending DILG Regional Office validation.'
            : $this->reportShortName() . ' report uploaded successfully.';

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
        abort_unless($this->userCanApproveReport($user), 403);

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

        $isProvincialOffice = $this->isProvincialDilgUser($user);
        $isRegionalOffice = (bool) ($user && $user->isRegionalOfficeAssignment());

        if (!$isProvincialOffice && !$isRegionalOffice) {
            abort(403);
        }

        if ($isProvincialOffice && $upload->status !== 'pending') {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'This submission period is not awaiting DILG Provincial Office validation.']);
        }

        if ($isRegionalOffice && $upload->status !== 'pending_ro') {
            return redirect()
                ->route($this->showRouteName(), [
                    'projectCode' => $project->project_code,
                    'quarter' => $quarter,
                ])
                ->withErrors(['approval' => 'This submission period is not awaiting DILG Regional Office validation.']);
        }

        $now = SecureTimestampService::getUploadTimestamp();
        $actorId = $user->idno ?? auth()->id();

        $upload->timestamps = false;
        $upload->approved_at = $now;
        $upload->approved_by = $actorId;
        $upload->updated_at = $now;

        if ($action === 'approve') {
            if ($isProvincialOffice) {
                $upload->approved_at_dilg_po = $now;
                $upload->approved_by_dilg_po = $actorId;
                $upload->approved_at_dilg_ro = null;
                $upload->approved_by_dilg_ro = null;
                $upload->status = 'pending_ro';
                $upload->approval_remarks = null;
                $upload->user_remarks = null;
                $message = $this->reportShortName() . ' validated by DILG Provincial Office and elevated for DILG Regional Office validation.';
            } else {
                $upload->approved_at_dilg_ro = $now;
                $upload->approved_by_dilg_ro = $actorId;
                $upload->status = 'approved';
                $upload->approval_remarks = null;
                $upload->user_remarks = null;
                $message = $this->reportShortName() . ' approved by DILG Regional Office.';
            }
        } else {
            if ($isRegionalOffice) {
                $upload->approved_at_dilg_ro = null;
                $upload->approved_by_dilg_ro = $actorId;
            } else {
                $upload->approved_by_dilg_po = $actorId;
            }

            $upload->status = 'returned';
            $upload->approval_remarks = $remarks;
            $upload->user_remarks = $remarks;
            $message = $this->reportShortName() . ' returned with remarks.';
        }

        $upload->save();

        return redirect()
            ->route($this->showRouteName(), [
                'projectCode' => $project->project_code,
                'quarter' => $quarter,
            ])
            ->with('success', $message);
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

        return $user->hasCrudPermission('fund_utilization_reports', 'view')
            || $user->hasCrudPermission('local_project_monitoring_committee', 'view')
            || $user->hasCrudPermission('road_maintenance_status_reports', 'view')
            || $user->hasCrudPermission($this->permissionAspect(), 'view');
    }

    protected function userCanApproveReport($user): bool
    {
        if (!$this->userCanAccessReport($user) || !$user || !$user->isDilgUser()) {
            return false;
        }

        return $this->isProvincialDilgUser($user) || $user->isRegionalOfficeAssignment();
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
        $filters = [
            'province' => $this->normalizeProjectFilterValues($request->input('province', [])),
            'city_municipality' => $this->normalizeProjectFilterValues($request->input('city_municipality', [])),
            'barangay' => $this->normalizeProjectFilterValues($request->input('barangay', [])),
            'program' => $this->normalizeProjectFilterValues($request->input('program', [])),
            'funding_year' => $this->normalizeProjectFilterValues($request->input('funding_year', [])),
            'project_type' => $this->normalizeProjectFilterValues($request->input('project_type', [])),
            'project_status' => $this->normalizeProjectFilterValues($request->input('project_status', [])),
        ];

        if (empty($filters['province'])) {
            $filters['city_municipality'] = [];
            $filters['barangay'] = [];
        } elseif (empty($filters['city_municipality'])) {
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
            'locationHierarchy' => [],
            'programs' => collect(),
            'funding_years' => collect(),
            'project_types' => collect(),
            'project_statuses' => collect(),
        ];
    }

    protected function buildProjectFilterOptions($baseQuery): array
    {
        $projectTypeExpression = $this->projectTypeExpression('spp');

        return [
            'provinces' => (clone $baseQuery)
                ->select('spp.province')
                ->whereRaw("TRIM(COALESCE(spp.province, '')) <> ''")
                ->distinct()
                ->orderBy('spp.province')
                ->pluck('spp.province'),
            'cities' => (clone $baseQuery)
                ->select('spp.city_municipality')
                ->whereRaw("TRIM(COALESCE(spp.city_municipality, '')) <> ''")
                ->distinct()
                ->orderBy('spp.city_municipality')
                ->pluck('spp.city_municipality'),
            'barangays' => (clone $baseQuery)
                ->select('spp.barangay')
                ->whereRaw("TRIM(COALESCE(spp.barangay, '')) <> ''")
                ->distinct()
                ->orderBy('spp.barangay')
                ->pluck('spp.barangay'),
            'locationHierarchy' => $this->buildProjectLocationHierarchy(clone $baseQuery),
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

    protected function buildProjectLocationHierarchy($baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->select('spp.province', 'spp.city_municipality', 'spp.barangay')
            ->whereRaw("TRIM(COALESCE(spp.province, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(spp.city_municipality, '')) <> ''")
            ->get();

        $hierarchy = [];

        foreach ($rows as $row) {
            $province = $this->normalizeProjectLocationValue($row->province ?? null);
            $cityMunicipality = $this->normalizeProjectLocationValue($row->city_municipality ?? null);

            if ($province === '' || $cityMunicipality === '') {
                continue;
            }

            $hierarchy[$province] ??= [];
            $hierarchy[$province][$cityMunicipality] ??= [];

            $barangayValues = preg_split('/\r\n|\r|\n/u', (string) ($row->barangay ?? '')) ?: [];
            foreach ($barangayValues as $barangayValue) {
                $barangay = $this->normalizeProjectLocationValue($barangayValue);
                if ($barangay === '') {
                    continue;
                }

                $hierarchy[$province][$cityMunicipality][$barangay] = $barangay;
            }
        }

        uksort($hierarchy, 'strnatcasecmp');

        foreach ($hierarchy as $province => $cityGroups) {
            uksort($cityGroups, 'strnatcasecmp');

            foreach ($cityGroups as $cityMunicipality => $barangays) {
                $barangayList = array_values($barangays);
                usort($barangayList, 'strnatcasecmp');
                $cityGroups[$cityMunicipality] = $barangayList;
            }

            $hierarchy[$province] = $cityGroups;
        }

        return $hierarchy;
    }

    protected function normalizeProjectLocationValue($value): string
    {
        $normalizedValue = trim((string) $value);
        $normalizedValue = preg_replace('/\s+/u', ' ', $normalizedValue) ?? $normalizedValue;

        return trim($normalizedValue);
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

    protected function showRouteName(): string
    {
        return (string) $this->formConfig()['show_route'];
    }
}
