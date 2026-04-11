<?php

namespace App\Http\Controllers;

use App\Models\SwaAnnexFDocument;
use App\Models\User;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SwaAnnexFReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:swa_annex_f_monthly_reports,view')->only(['index', 'edit', 'viewDocument']);
        $this->middleware('crud_permission:swa_annex_f_monthly_reports,add')->only(['upload']);
        $this->middleware('crud_permission:swa_annex_f_monthly_reports,update')->only(['approveDocument']);
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
        return 'swa_annex_f';
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
        $rows = [];

        foreach ($offices as $province => $municipalities) {
            foreach ($municipalities as $office) {
                $rows[] = [
                    'province' => $province,
                    'city_municipality' => $office,
                ];
            }
        }

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

    public function index(Request $request)
    {
        $reportingYear = $this->resolveReportingYear($request);
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'funding_year' => trim((string) $request->query('funding_year', '')),
            'level' => trim((string) $request->query('level', '')),
            'type' => trim((string) $request->query('type', '')),
            'status' => 'ongoing',
        ];
        $perPage = (int) $request->query('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50];
        $sortBy = trim((string) $request->query('sort_by', 'funding_year'));
        $sortDir = strtolower(trim((string) $request->query('sort_dir', 'desc')));

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (!Schema::hasTable('subay_project_profiles')) {
            return view('reports.monthly.swa-annex-f.index', [
                'filters' => $filters,
                'fundingYears' => collect(),
                'provinces' => collect(),
                'cityOptions' => collect(),
                'levelOptions' => collect(),
                'typeOptions' => collect(),
                'projects' => new LengthAwarePaginator([], 0, $perPage, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDir' => $sortDir,
                'latestUpdateAt' => null,
                'totalProjects' => 0,
                'reportingYear' => $reportingYear,
            ]);
        }

        $baseQuery = $this->buildScopedProjectQuery();

        $fundingYears = (clone $baseQuery)
            ->select('spp.funding_year')
            ->whereNotNull('spp.funding_year')
            ->whereRaw('TRIM(spp.funding_year) <> ""')
            ->distinct()
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->pluck('spp.funding_year');

        $provinces = (clone $baseQuery)
            ->select('spp.province')
            ->whereNotNull('spp.province')
            ->whereRaw('TRIM(spp.province) <> ""')
            ->distinct()
            ->orderBy('spp.province')
            ->pluck('spp.province');

        $levelOptions = (clone $baseQuery)
            ->select('spp.sglgif_level')
            ->whereNotNull('spp.sglgif_level')
            ->whereRaw('TRIM(spp.sglgif_level) <> ""')
            ->distinct()
            ->orderBy('spp.sglgif_level')
            ->pluck('spp.sglgif_level');

        $typeOptions = (clone $baseQuery)
            ->select('spp.type_of_project')
            ->whereNotNull('spp.type_of_project')
            ->whereRaw('TRIM(spp.type_of_project) <> ""')
            ->distinct()
            ->orderBy('spp.type_of_project')
            ->pluck('spp.type_of_project');

        $cityOptionsQuery = clone $baseQuery;
        if ($filters['province'] !== '') {
            $cityOptionsQuery->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [mb_strtolower($filters['province'])]);
        }

        $cityOptions = $cityOptionsQuery
            ->select('spp.city_municipality')
            ->whereNotNull('spp.city_municipality')
            ->whereRaw('TRIM(spp.city_municipality) <> ""')
            ->distinct()
            ->orderBy('spp.city_municipality')
            ->pluck('spp.city_municipality');

        $filteredQuery = clone $baseQuery;
        $this->applyProjectFiltersToQuery($filteredQuery, $filters);

        $latestUpdateAt = (clone $filteredQuery)->max('spp.updated_at');

        $subsidyExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.national_subsidy_original_allocation, '')), ',', ''), '')";
        $projectCostExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.total_project_cost, '')), ',', ''), '')";
        $financialExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.sglgif_financial, '')), ',', ''), '')";
        $physicalExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.total_accomplishment, '')), ',', ''), '')";
        $attachmentExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.sglgif_attachment, '')), ',', ''), '')";
        $overallExpr = "NULLIF(REPLACE(TRIM(COALESCE(spp.sglgif_overall, '')), ',', ''), '')";

        $query = clone $filteredQuery;
        $query->select([
            'spp.project_code',
            'spp.project_title',
            'spp.funding_year',
            'spp.region',
            'spp.province',
            'spp.city_municipality',
            'spp.beneficiaries',
            'spp.status',
            'spp.type_of_project',
            'spp.sub_type_of_project',
            'spp.sglgif_level',
            'spp.national_subsidy_original_allocation',
            'spp.total_project_cost',
            'spp.sglgif_financial',
            'spp.total_accomplishment',
            'spp.sglgif_attachment',
            'spp.sglgif_overall',
            'spp.updated_at',
        ]);

        switch ($sortBy) {
            case 'project_title':
                $query->orderByRaw("CASE WHEN spp.project_title IS NULL OR TRIM(spp.project_title) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.project_title', $sortDir);
                break;
            case 'province':
                $query->orderByRaw("CASE WHEN spp.province IS NULL OR TRIM(spp.province) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.province', $sortDir);
                break;
            case 'city':
                $query->orderByRaw("CASE WHEN spp.city_municipality IS NULL OR TRIM(spp.city_municipality) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.city_municipality', $sortDir);
                break;
            case 'funding_year':
                $query->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) {$sortDir}");
                break;
            case 'level':
                $query->orderByRaw("CASE WHEN spp.sglgif_level IS NULL OR TRIM(spp.sglgif_level) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.sglgif_level', $sortDir);
                break;
            case 'type':
                $query->orderByRaw("CASE WHEN spp.type_of_project IS NULL OR TRIM(spp.type_of_project) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.type_of_project', $sortDir);
                break;
            case 'category':
                $query->orderByRaw("CASE WHEN spp.sub_type_of_project IS NULL OR TRIM(spp.sub_type_of_project) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.sub_type_of_project', $sortDir);
                break;
            case 'subsidy':
                $query->orderByRaw("CASE WHEN {$subsidyExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$subsidyExpr} + 0 {$sortDir}");
                break;
            case 'project_cost':
                $query->orderByRaw("CASE WHEN {$projectCostExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$projectCostExpr} + 0 {$sortDir}");
                break;
            case 'financial':
                $query->orderByRaw("CASE WHEN {$financialExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$financialExpr} + 0 {$sortDir}");
                break;
            case 'physical':
                $query->orderByRaw("CASE WHEN {$physicalExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$physicalExpr} + 0 {$sortDir}");
                break;
            case 'attachment':
                $query->orderByRaw("CASE WHEN {$attachmentExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$attachmentExpr} + 0 {$sortDir}");
                break;
            case 'overall':
                $query->orderByRaw("CASE WHEN {$overallExpr} IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("{$overallExpr} + 0 {$sortDir}");
                break;
            case 'status':
                $query->orderByRaw("CASE WHEN spp.status IS NULL OR TRIM(spp.status) = '' THEN 1 ELSE 0 END")
                    ->orderBy('spp.status', $sortDir);
                break;
            case 'updated_at':
                $query->orderByRaw("CASE WHEN spp.updated_at IS NULL THEN 1 ELSE 0 END")
                    ->orderBy('spp.updated_at', $sortDir);
                break;
            default:
                $sortBy = 'funding_year';
                $sortDir = 'desc';
                $query->orderByRaw("CASE WHEN spp.funding_year IS NULL OR TRIM(spp.funding_year) = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) DESC");
                break;
        }

        $projects = $query
            ->orderBy('spp.province')
            ->orderBy('spp.city_municipality')
            ->orderBy('spp.project_title')
            ->paginate($perPage)
            ->withQueryString();

        $projects->setCollection(
            $projects->getCollection()->map(function ($row) {
                return $this->mapProjectRow($row);
            })
        );

        return view('reports.monthly.swa-annex-f.index', [
            'filters' => $filters,
            'fundingYears' => $fundingYears,
            'provinces' => $provinces,
            'cityOptions' => $cityOptions,
            'levelOptions' => $levelOptions,
            'typeOptions' => $typeOptions,
            'projects' => $projects,
            'perPage' => $perPage,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'latestUpdateAt' => $latestUpdateAt,
            'totalProjects' => $projects->total(),
            'reportingYear' => $reportingYear,
        ]);
    }

    private function buildScopedProjectQuery()
    {
        $query = DB::table('subay_project_profiles as spp')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) = ?', ['SGLGIF']);

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        $province = trim((string) $user->province);
        $office = trim((string) $user->office);
        $region = trim((string) $user->region);

        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $regionLower = $user->normalizedRegion();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $cityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
        $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();

        $applyOfficeScope = function ($query) use ($officeLower, $officeComparableLower, $cityComparableExpression) {
            if ($officeLower === '') {
                return;
            }

            $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

            $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $cityComparableExpression) {
                $subQuery
                    ->whereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) = ?', [$officeLower])
                    ->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
            });
        };

        if ($user->isLguScopedUser()) {
            if ($office !== '') {
                if ($province !== '') {
                    $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
                    $applyOfficeScope($query);
                } else {
                    $applyOfficeScope($query);
                }
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
            }

            return $query;
        }

        if (!$user->isDilgUser()) {
            return $query;
        }

        if ($isRegionalOfficeUser) {
            return $query;
        }

        if ($province !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
        } elseif ($region !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(spp.region, ""))) = ?', [$regionLower]);
        }

        return $query;
    }

    private function applyProjectFiltersToQuery($query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $keyword = '%' . mb_strtolower($filters['search']) . '%';
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->whereRaw('LOWER(COALESCE(spp.project_code, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(spp.project_title, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(spp.province, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(spp.city_municipality, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(spp.sub_type_of_project, "")) LIKE ?', [$keyword]);
            });
        }

        $exactFilters = [
            'province' => 'spp.province',
            'city' => 'spp.city_municipality',
            'funding_year' => 'spp.funding_year',
            'level' => 'spp.sglgif_level',
            'type' => 'spp.type_of_project',
            'status' => 'spp.status',
        ];

        foreach ($exactFilters as $filterKey => $column) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value === '') {
                continue;
            }

            $query->whereRaw('LOWER(TRIM(COALESCE(' . $column . ', ""))) = ?', [mb_strtolower($value)]);
        }
    }

    private function mapProjectRow(object $row): array
    {
        $subsidy = $this->extractNumeric($row->national_subsidy_original_allocation ?? null);
        $projectCost = $this->extractNumeric($row->total_project_cost ?? null);
        $financial = $this->extractNumeric($row->sglgif_financial ?? null);
        $physical = $this->extractNumeric($row->total_accomplishment ?? null);
        $attachment = $this->extractNumeric($row->sglgif_attachment ?? null);
        $overall = $this->extractNumeric($row->sglgif_overall ?? null);
        $submissionOffice = $this->resolveSubmissionOffice((string) ($row->city_municipality ?? ''));

        return [
            'project_code' => trim((string) ($row->project_code ?? '')),
            'project_title' => trim((string) ($row->project_title ?? '')),
            'funding_year' => trim((string) ($row->funding_year ?? '')),
            'region' => trim((string) ($row->region ?? '')),
            'province' => trim((string) ($row->province ?? '')),
            'city_municipality' => trim((string) ($row->city_municipality ?? '')),
            'beneficiaries' => trim((string) ($row->beneficiaries ?? '')),
            'status' => trim((string) ($row->status ?? '')),
            'status_lc' => mb_strtolower(trim((string) ($row->status ?? ''))),
            'type_of_project' => trim((string) ($row->type_of_project ?? '')),
            'type_lc' => mb_strtolower(trim((string) ($row->type_of_project ?? ''))),
            'sub_type_of_project' => trim((string) ($row->sub_type_of_project ?? '')),
            'sglgif_level' => trim((string) ($row->sglgif_level ?? '')),
            'level_lc' => mb_strtolower(trim((string) ($row->sglgif_level ?? ''))),
            'subsidy_value' => $subsidy ?? 0.0,
            'project_cost_value' => $projectCost ?? 0.0,
            'financial_pct' => $financial,
            'physical_pct' => $physical,
            'attachment_pct' => $attachment,
            'overall_pct' => $overall,
            'updated_at' => $row->updated_at,
            'submission_office' => $submissionOffice,
        ];
    }

    private function resolveSubmissionOffice(string $cityMunicipality): ?string
    {
        $needle = $this->normalizeOfficeComparable($cityMunicipality);
        if ($needle === '') {
            return null;
        }

        foreach ($this->getOffices() as $municipalities) {
            foreach ($municipalities as $office) {
                if ($this->normalizeOfficeComparable($office) === $needle) {
                    return $office;
                }
            }
        }

        return null;
    }

    private function normalizeOfficeComparable(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/,.*$/', '', $normalized) ?? $normalized;
        $normalized = str_replace('(capital)', '', $normalized);
        $normalized = str_replace('municipality of ', '', $normalized);
        $normalized = str_replace('city of ', '', $normalized);
        $normalized = str_replace(' municipality', '', $normalized);
        $normalized = str_replace(' city', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function extractNumeric(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9\\.-]/', '', trim((string) $value));
        if ($clean === null || $clean === '' || $clean === '-' || $clean === '.' || $clean === '-.') {
            return null;
        }

        return (float) $clean;
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

        $documents = SwaAnnexFDocument::query()
            ->where('office', $officeName)
            ->where('doc_type', $this->reportDocType())
            ->where('year', $reportingYear)
            ->get();

        $documentsByKey = $this->indexDocumentsByKey($documents);
        $userIds = $documents->pluck('uploaded_by')
            ->merge($documents->pluck('approved_by_dilg_po'))
            ->merge($documents->pluck('approved_by_dilg_ro'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $usersById = $userIds !== []
            ? User::whereIn('idno', $userIds)->get()->keyBy('idno')
            : collect();

        return view('reports.monthly.swa-annex-f.edit', compact(
            'officeName',
            'province',
            'documentsByKey',
            'usersById',
            'reportingYear',
            'months'
        ));
    }

    public function deleteDocument($office, $docId)
    {
        $officeName = (string) $office;
        $document = SwaAnnexFDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Uploaded document deleted successfully.');
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

        $existingDocument = SwaAnnexFDocument::query()
            ->where('office', $officeName)
            ->where('doc_type', $docType)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        $oldFilePath = $existingDocument?->file_path;

        $file = $request->file('document');
        $officeSlug = Str::slug($officeName, '_');
        $path = $file->store('swa-annex-f/' . $officeSlug, 'public');
        $uploadedAt = now();
        $isMountainProvinceDilgUploader = $user
            && strtoupper(trim((string) $user->agency)) === 'DILG'
            && strtolower(trim((string) $user->province)) === 'mountain province';

        SwaAnnexFDocument::updateOrCreate(
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

        return back()->with('success', 'SWA- Annex F uploaded successfully.');
    }

    public function viewDocument($office, $docId)
    {
        $officeName = (string) $office;
        $document = SwaAnnexFDocument::query()
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

        $document = SwaAnnexFDocument::query()
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

        $isRegionalOffice = trim((string) $user->province) === 'Regional Office';
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

        return back()->with('success', $action === 'approve' ? 'Document validated.' : 'Document returned.');
    }
}
