<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocallyFundedProject;
use App\Support\ProjectLocationFilterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        return $this->renderProjectDashboard($request->query('tab', 'locally-funded'));
    }

    public function modalBody(Request $request, string $modalId)
    {
        return $this->renderProjectDashboard($request->query('tab', 'locally-funded'), $modalId);
    }

    public function renderProjectDashboard(string $activeProjectTab = 'locally-funded', ?string $modalId = null)
    {
        try {
            if ($activeProjectTab === 'rssa') {
                return app(\App\Http\Controllers\RssaDashboardController::class)->index(request());
            }

            $subayUploadDateLabel = 'No SubayBAYAN upload yet';
            $latestSubayUploadCacheToken = 'none';
            if (Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'created_at')) {
                $latestSubayUploadAt = DB::table('subay_project_profiles')->max('created_at');
                if ($latestSubayUploadAt) {
                    $latestSubayUploadCacheToken = (string) $latestSubayUploadAt;
                    try {
                        $subayUploadDateLabel = \Illuminate\Support\Carbon::parse($latestSubayUploadAt)->format('F d, Y h:i A');
                    } catch (\Throwable $error) {
                        $subayUploadDateLabel = (string) $latestSubayUploadAt;
                    }
                }
            }

            $user = Auth::user();
            $province = trim((string) $user->province);
            $office = trim((string) $user->office);
            $region = trim((string) $user->region);
            $provinceLower = $user->normalizedProvince();
            $officeLower = $user->normalizedOffice();
            $regionLower = $user->normalizedRegion();
            $officeComparableLower = $user->normalizedOfficeComparable();
            $isLguScopedUser = $user->isLguScopedUser();
            $isDilgUser = $user->isDilgUser();
            $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();

            $normalizeFilterValue = function ($value): string {
                $normalizedValue = trim((string) $value);
                $normalizedValue = preg_replace('/\s+/u', ' ', $normalizedValue) ?? $normalizedValue;

                return trim($normalizedValue);
            };

            $parseRequestedFilterValues = function (string $inputName) use ($normalizeFilterValue): array {
                $requestedValues = request()->input($inputName, []);
                if (!is_array($requestedValues)) {
                    $requestedValues = $requestedValues === null ? [] : [$requestedValues];
                }

                return collect($requestedValues)
                    ->map($normalizeFilterValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();
            };

            $filters = [
                'search' => $normalizeFilterValue(request()->input('search', '')),
                'province' => $parseRequestedFilterValues('province'),
                'city_municipality' => $parseRequestedFilterValues('city_municipality'),
                'barangay' => $parseRequestedFilterValues('barangay'),
                'programs' => $parseRequestedFilterValues('program'),
                'fund_source' => $parseRequestedFilterValues('fund_source'),
                'funding_year' => $parseRequestedFilterValues('funding_year'),
                'project_type' => $parseRequestedFilterValues('project_type'),
                'project_status' => $parseRequestedFilterValues('project_status'),
            ];

            if (empty($filters['province'])) {
                $filters['city_municipality'] = [];
            }

            if (empty($filters['city_municipality'])) {
                $filters['barangay'] = [];
            }

            $dashboardCacheKey = 'dashboard_locally_funded_v2:' . md5(serialize([
                'user_id' => Auth::id(),
                'tab' => $activeProjectTab,
                'filters' => $filters,
                'subay_upload' => $latestSubayUploadCacheToken,
            ]));

            $cachedDashboardViewData = Cache::get($dashboardCacheKey);
            if (is_array($cachedDashboardViewData)) {
                if ($modalId !== null) {
                    return view('dashboard.partials.modal-body', array_merge($cachedDashboardViewData, ['modalId' => $modalId]));
                }

                return view('dashboard.index', $cachedDashboardViewData);
            }

            $filterOptions = [
                'provinces' => collect(),
                'cities' => collect(),
                'province_city_map' => [],
                'barangays' => collect(),
                'city_barangay_map' => [],
                'programs' => collect(),
                'fund_sources' => collect(),
                'funding_years' => collect(),
                'project_types' => collect(),
                'project_statuses' => collect(),
            ];

            $hasSubayProjectCodeKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'project_code_key');
            $hasSubayProvinceKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'province_key');
            $hasSubayStatusKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'status_key');
            $hasSubayFundingYearKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'funding_year_key');
            $hasSubayFundingYearNumColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'funding_year_num');
            $hasSubayFundSourceKeyColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'fund_source_key');
            $hasSubayDateParsedColumn = Schema::hasTable('subay_project_profiles') && Schema::hasColumn('subay_project_profiles', 'date_parsed');

            $subayProjectCodeFilterExpr = $hasSubayProjectCodeKeyColumn
                ? 'spp.project_code_key'
                : 'spp.project_code';
            $subayProvinceFilterExpr = $hasSubayProvinceKeyColumn
                ? 'spp.province_key'
                : 'spp.province';
            $subayStatusFilterExpr = $hasSubayStatusKeyColumn
                ? 'spp.status_key'
                : 'spp.status';
            $subayFundingYearFilterExpr = $hasSubayFundingYearKeyColumn
                ? 'spp.funding_year_key'
                : 'spp.funding_year';
            $subayProjectCodeGroupExpr = $hasSubayProjectCodeKeyColumn
                ? 'spp.project_code_key'
                : 'UPPER(TRIM(spp.project_code))';
            $subayProvinceGroupExpr = $hasSubayProvinceKeyColumn
                ? 'spp.province_key'
                : 'TRIM(COALESCE(spp.province, ""))';
            $subayStatusGroupExpr = $hasSubayStatusKeyColumn
                ? 'spp.status_key'
                : 'UPPER(TRIM(COALESCE(spp.status, "")))';
            $subayFundingYearGroupExpr = $hasSubayFundingYearKeyColumn
                ? 'spp.funding_year_key'
                : 'TRIM(COALESCE(spp.funding_year, ""))';
            $subayFundingYearOrderExpr = $hasSubayFundingYearNumColumn
                ? 'spp.funding_year_num'
                : 'CAST(spp.funding_year AS UNSIGNED)';

            $normalizeComparableLocationValue = function ($value): string {
                return \App\Support\ProjectLocationFilterHelper::normalizeComparableLocationLabel($value);
            };

            $subayCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(spp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
            $applyOfficeScopeToSubay = function ($query) use ($officeLower, $officeComparableLower, $subayCityComparableExpression) {
                if ($officeLower === '') {
                    return;
                }

                $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

                $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $subayCityComparableExpression) {
                    $subQuery->whereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) = ?', [$officeLower])
                        ->orWhereRaw("{$subayCityComparableExpression} = ?", [$officeNeedle]);
                });
            };

            $applyRoleScopeToSubay = function ($query) use (
                $province,
                $office,
                $region,
                $provinceLower,
                $regionLower,
                $isLguScopedUser,
                $isDilgUser,
                $isRegionalOfficeUser,
                $applyOfficeScopeToSubay,
                $subayProvinceFilterExpr
            ) {
                if ($isLguScopedUser) {
                    if ($office !== '') {
                        if ($province !== '') {
                            $query->whereRaw('LOWER(TRIM(COALESCE(' . $subayProvinceFilterExpr . ', ""))) = ?', [$provinceLower]);
                            $applyOfficeScopeToSubay($query);
                        } else {
                            $applyOfficeScopeToSubay($query);
                        }
                    } elseif ($province !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(' . $subayProvinceFilterExpr . ', ""))) = ?', [$provinceLower]);
                    }
                } elseif ($isDilgUser) {
                    if ($isRegionalOfficeUser) {
                        // Regional Office users can see all projects.
                    } elseif ($province !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(' . $subayProvinceFilterExpr . ', ""))) = ?', [$provinceLower]);
                    } elseif ($region !== '') {
                        $query->whereRaw('LOWER(TRIM(COALESCE(spp.region, ""))) = ?', [$regionLower]);
                    }
                }
            };

            $normalizedSqlComparable = function (string $column): string {
                return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' '), '  ', ' '), '  ', ' '), '  ', ' ')))";
            };

            $normalizedDelimitedSqlComparable = function (string $column): string {
                return "LOWER(CONCAT('|', TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), CHAR(13), '|'), CHAR(10), '|'), CHAR(9), ' '), ' |', '|'), '| ', '|'), '||', '|'), '||', '|')), '|'))";
            };

            $normalizedComparableLocationSql = function (string $column): string {
                return "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), CHAR(13), ' '), CHAR(10), ' '), CHAR(9), ' ')))";
            };

            $applyExactMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedSqlComparable, $normalizeFilterValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeFilterValue)
                    ->map(function ($value) {
                        return strtolower($value);
                    })
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
                $query->whereRaw($normalizedSqlComparable($column) . " IN ({$placeholders})", $normalizedValues);
            };

            $applyDelimitedMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedDelimitedSqlComparable, $normalizeFilterValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeFilterValue)
                    ->map(function ($value) {
                        return strtolower($value);
                    })
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $normalizedExpression = $normalizedDelimitedSqlComparable($column);
                $query->where(function ($subQuery) use ($normalizedValues, $normalizedExpression) {
                    foreach ($normalizedValues as $normalizedValue) {
                        $subQuery->orWhereRaw($normalizedExpression . ' LIKE ?', ['%|' . $normalizedValue . '|%']);
                    }
                });
            };

            $applyComparableLocationMultiFilterToSubay = function ($query, string $column, array $values) use ($normalizedComparableLocationSql, $normalizeComparableLocationValue) {
                $normalizedValues = collect($values)
                    ->map($normalizeComparableLocationValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->unique()
                    ->values()
                    ->all();

                if (empty($normalizedValues)) {
                    return;
                }

                $placeholders = implode(', ', array_fill(0, count($normalizedValues), '?'));
                $query->whereRaw($normalizedComparableLocationSql($column) . " IN ({$placeholders})", $normalizedValues);
            };

            $fundSourceFromProjectCodeExpr = $hasSubayFundSourceKeyColumn
                ? 'spp.fund_source_key'
                : "
                    CASE
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'SBDP%' THEN 'SBDP'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'FA-%' THEN 'FALGU'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'FALGU%' THEN 'FALGU'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'CMGP%' THEN 'CMGP'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'GEF%' THEN 'GEF'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'SAFPB%' THEN 'SAFPB'
                        WHEN UPPER(TRIM(spp.project_code)) LIKE 'SGLGIF%' THEN 'SGLGIF'
                        WHEN TRIM(COALESCE(spp.program, '')) <> '' THEN UPPER(TRIM(COALESCE(spp.program, '')))
                        ELSE 'UNSPECIFIED'
                    END
                ";

            $excludeSglgifFromSubay = function ($query) use ($fundSourceFromProjectCodeExpr, $subayProjectCodeFilterExpr) {
                $query->whereRaw($subayProjectCodeFilterExpr . " IS NOT NULL")
                    ->whereRaw($subayProjectCodeFilterExpr . " <> ''")
                    ->whereRaw("{$fundSourceFromProjectCodeExpr} <> ?", ['SGLGIF']);
            };

            $excludeSglgifFromFallback = function ($query) {
                $query->whereRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
                    ->whereRaw('UPPER(TRIM(COALESCE(fund_source, ""))) <> ?', ['SGLGIF']);
            };

            $applyDashboardFiltersToSubay = function ($query) use ($filters, $applyExactMultiFilterToSubay, $applyDelimitedMultiFilterToSubay, $applyComparableLocationMultiFilterToSubay, $fundSourceFromProjectCodeExpr, $subayProvinceFilterExpr, $subayFundingYearFilterExpr, $subayStatusFilterExpr, $subayProjectCodeFilterExpr) {
                $applyExactMultiFilterToSubay($query, $subayProvinceFilterExpr, $filters['province']);
                $applyComparableLocationMultiFilterToSubay($query, 'spp.city_municipality', $filters['city_municipality']);
                $applyDelimitedMultiFilterToSubay($query, 'spp.barangay', $filters['barangay']);
                $applyExactMultiFilterToSubay($query, 'spp.program', $filters['programs']);
                $applyExactMultiFilterToSubay($query, $fundSourceFromProjectCodeExpr, $filters['fund_source']);
                $applyExactMultiFilterToSubay($query, $subayFundingYearFilterExpr, $filters['funding_year']);
                $applyExactMultiFilterToSubay($query, 'spp.type_of_project', $filters['project_type']);
                $applyExactMultiFilterToSubay($query, $subayStatusFilterExpr, $filters['project_status']);

                if ($filters['search'] !== '') {
                    $searchNeedle = '%' . strtolower($filters['search']) . '%';
                    $query->where(function ($searchQuery) use ($searchNeedle, $subayProjectCodeFilterExpr, $subayProvinceFilterExpr, $fundSourceFromProjectCodeExpr) {
                        $searchQuery
                            ->orWhereRaw('LOWER(TRIM(COALESCE(' . $subayProjectCodeFilterExpr . ', ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.project_title, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(' . $subayProvinceFilterExpr . ', ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(spp.barangay, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(' . $fundSourceFromProjectCodeExpr . ', ""))) LIKE ?', [$searchNeedle]);
                    });
                }
            };

            $totalProjects = 0;
            $fundSourceOptions = ['SBDP', 'FALGU', 'CMGP', 'GEF', 'SAFPB'];
            $filterOptions['fund_sources'] = collect($fundSourceOptions);
            $fundSourceCountsMap = [];
            $fundSourceProjectsMap = [];
            $totalObligationAmount = 0.0;
            $totalDisbursementAmount = 0.0;
            $totalBalanceAmount = 0.0;
            $totalLgsfAllocationAmount = 0.0;
            $projectsWithBalance = collect();
            $financialStatusProjects = collect();
            $utilizationPercentage = 0.0;
            $expectedCompletionMonthLabel = now()->format('F Y');
            $currentYear = now()->year;
            $currentMonth = now()->month;
            $currentMonthStart = now()->copy()->startOfMonth()->toDateString();
            $currentMonthEnd = now()->copy()->endOfMonth()->toDateString();
            $projectsExpectedCompletionThisMonth = collect();
            $projectAtRiskOrder = ['Ahead', 'No Risk', 'On Schedule', 'High Risk', 'Moderate Risk', 'Low Risk'];
            $projectAtRiskAgingOrder = ['High Risk', 'Low Risk', 'No Risk'];
            $projectUpdateStatusOrder = ['High Risk', 'Low Risk', 'No Risk'];
            $projectAtRiskCounts = array_fill_keys($projectAtRiskOrder, 0);
            $projectAtRiskAgingCounts = array_fill_keys($projectAtRiskAgingOrder, 0);
            $projectUpdateStatusCounts = array_fill_keys($projectUpdateStatusOrder, 0);
            $projectAtRiskSlippageProjects = array_fill_keys($projectAtRiskOrder, collect());
            $projectUpdateRiskProjects = [
                'High Risk' => collect(),
                'Low Risk' => collect(),
                'No Risk' => collect(),
            ];
            $projectAtRiskAgingProjects = [
                'High Risk' => collect(),
                'Low Risk' => collect(),
                'No Risk' => collect(),
            ];

            $statusLabels = [
                'COMPLETED' => 'Completed',
                'ONGOING' => 'On-going',
                'BID EVALUATION/OPENING' => 'Bid Evaluation/Opening',
                'NOA ISSUANCE' => 'NOA Issuance',
                'DED PREPARATION' => 'DED Preparation',
                'NOT YET STARTED' => 'Not Yet Started',
                'ITB/AD POSTED' => 'ITB/AD Posted',
                'TERMINATED' => 'Terminated',
                'CANCELLED' => 'Cancelled',
            ];
            $statusAliases = [
                'ON-GOING' => 'ONGOING',
                'NOT STARTED' => 'NOT YET STARTED',
            ];

            $normalizeStatus = function ($status) use ($statusLabels, $statusAliases) {
                $raw = trim((string) $status);
                if ($raw === '') {
                    return null;
                }

                $upper = strtoupper($raw);
                if (array_key_exists($upper, $statusAliases)) {
                    $upper = $statusAliases[$upper];
                }
                if (array_key_exists($upper, $statusLabels)) {
                    return $upper;
                }

                return null;
            };

            $labelForStatus = function ($normalized) use ($statusLabels) {
                if ($normalized && array_key_exists($normalized, $statusLabels)) {
                    return $statusLabels[$normalized];
                }
                return null;
            };

            $statusDisplayOrder = array_values($statusLabels);
            $statusDisplayOrderPositions = array_flip($statusDisplayOrder);
            $statusActualCounts = array_fill_keys($statusDisplayOrder, 0);
            $statusSubaybayanCounts = array_fill_keys($statusDisplayOrder, 0);
            $statusSubaybayanProjectsMap = [];
            $statusSubaybayanLocationReport = [];
            $provinceFundingYearProgramStatusReport = [];
            $provinceFundingYearProgramStatusSourceRows = [];
            foreach ($statusDisplayOrder as $statusLabel) {
                $statusSubaybayanProjectsMap[$statusLabel] = [];
            }

            $carProvinceDisplayOrder = [
                'Abra',
                'Apayao',
                'Benguet',
                'Ifugao',
                'Kalinga',
                'Mountain Province',
            ];
            $carProvinceProjectCounts = array_fill_keys($carProvinceDisplayOrder, 0);
            $carProvinceProjectMaxCount = 0;

            $normalizeCarProvince = function ($province) {
                $raw = trim((string) $province);
                if ($raw === '') {
                    return null;
                }

                $compact = preg_replace('/[^A-Z]/', '', strtoupper($raw)) ?? '';
                if ($compact === '') {
                    return null;
                }

                $provinceAliases = [
                    'ABRA' => 'Abra',
                    'APAYAO' => 'Apayao',
                    'BENGUET' => 'Benguet',
                    'IFUGAO' => 'Ifugao',
                    'KALINGA' => 'Kalinga',
                    'MOUNTAINPROVINCE' => 'Mountain Province',
                    'MOUNTAINPROV' => 'Mountain Province',
                    'MTPROVINCE' => 'Mountain Province',
                ];

                if (array_key_exists($compact, $provinceAliases)) {
                    return $provinceAliases[$compact];
                }

                return null;
            };

            $normalizeRiskLevel = function ($riskLevel) {
                $raw = strtoupper(trim((string) $riskLevel));
                if ($raw === '') {
                    return null;
                }

                $compact = preg_replace('/[^A-Z]/', '', $raw) ?? '';
                if ($compact === '') {
                    return null;
                }

                if (str_contains($compact, 'AHEAD')) {
                    return 'Ahead';
                }
                if (str_contains($compact, 'ONSCHEDULE')) {
                    return 'On Schedule';
                }
                if (str_contains($compact, 'NORISK')) {
                    return 'No Risk';
                }
                if (str_contains($compact, 'HIGHRISK')) {
                    return 'High Risk';
                }
                if (str_contains($compact, 'MODERATERISK')) {
                    return 'Moderate Risk';
                }
                if (str_contains($compact, 'LOWRISK')) {
                    return 'Low Risk';
                }

                return null;
            };

            $computeProjectAtRiskCounts = function ($projectCodesQuery, string $riskColumn, array &$targetCounts) use ($normalizeRiskLevel) {
                if (!Schema::hasTable('project_at_risks')) {
                    return;
                }

                if (!in_array($riskColumn, ['risk_level'], true)) {
                    return;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw("TRIM(COALESCE(par.{$riskColumn}, \"\")) as risk_level_value")
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select('risk_base.project_code', 'risk_base.id', 'risk_base.risk_level_value');

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalRiskRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select('risk_base.risk_level_value')
                    ->get();

                foreach ($finalRiskRows as $row) {
                    $riskLabel = $normalizeRiskLevel($row->risk_level_value ?? null);
                    if ($riskLabel !== null && array_key_exists($riskLabel, $targetCounts)) {
                        $targetCounts[$riskLabel] += 1;
                    }
                }
            };

            $computeProjectAtRiskAgingCounts = function ($projectCodesQuery, array &$targetCounts) {
                if (!Schema::hasTable('project_at_risks')) {
                    return;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select('risk_base.project_code', 'risk_base.id', 'risk_base.aging_value');

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalAgingRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select('risk_base.aging_value')
                    ->get();

                foreach ($finalAgingRows as $row) {
                    $rawAging = trim((string) ($row->aging_value ?? ''));
                    if ($rawAging === '') {
                        continue;
                    }

                    if (is_numeric($rawAging)) {
                        $agingValue = (float) $rawAging;
                    } else {
                        $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                        if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                            continue;
                        }
                        $agingValue = (float) $cleanedAging;
                    }

                    if ($agingValue >= 60) {
                        $riskLabel = 'High Risk';
                    } elseif ($agingValue > 30 && $agingValue < 60) {
                        $riskLabel = 'Low Risk';
                    } else {
                        $riskLabel = 'No Risk';
                    }

                    if (array_key_exists($riskLabel, $targetCounts)) {
                        $targetCounts[$riskLabel] += 1;
                    }
                }
            };

            $fetchProjectAtRiskAgingProjects = function ($projectCodesQuery) {
                $projectsByRisk = [
                    'High Risk' => collect(),
                    'Low Risk' => collect(),
                    'No Risk' => collect(),
                ];

                if (!Schema::hasTable('project_at_risks')) {
                    return $projectsByRisk;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(par.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(par.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.aging_value',
                        'risk_base.extraction_date',
                        'risk_base.id'
                    );

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalAgingRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.aging_value',
                        'risk_base.extraction_date'
                    )
                    ->get();

                $rowsByRisk = [
                    'High Risk' => [],
                    'Low Risk' => [],
                    'No Risk' => [],
                ];

                foreach ($finalAgingRows as $row) {
                    $rawAging = trim((string) ($row->aging_value ?? ''));
                    if ($rawAging === '') {
                        continue;
                    }

                    if (is_numeric($rawAging)) {
                        $agingValue = (float) $rawAging;
                    } else {
                        $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                        if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                            continue;
                        }
                        $agingValue = (float) $cleanedAging;
                    }

                    if ($agingValue >= 60) {
                        $riskLabel = 'High Risk';
                    } elseif ($agingValue > 30 && $agingValue < 60) {
                        $riskLabel = 'Low Risk';
                    } else {
                        $riskLabel = 'No Risk';
                    }

                    $rowsByRisk[$riskLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'latest_update_date' => $row->extraction_date ?? null,
                        'aging_days' => (fmod($agingValue, 1.0) === 0.0) ? (int) $agingValue : round($agingValue, 2),
                    ];
                }

                foreach (array_keys($rowsByRisk) as $riskLabel) {
                    $projectsByRisk[$riskLabel] = collect($rowsByRisk[$riskLabel])
                        ->sortByDesc('aging_days')
                        ->values();
                }

                return $projectsByRisk;
            };

            $fetchProjectAtRiskSlippageProjects = function ($projectCodesQuery) use ($normalizeRiskLevel, $projectAtRiskOrder) {
                $projectsByRisk = array_fill_keys($projectAtRiskOrder, collect());

                if (!Schema::hasTable('project_at_risks')) {
                    return $projectsByRisk;
                }

                $riskBaseQuery = DB::table('project_at_risks as par')
                    ->joinSub($projectCodesQuery, 'filtered_codes', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(par.project_code))'), '=', 'filtered_codes.project_code');
                    })
                    ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
                    ->selectRaw('TRIM(COALESCE(par.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(par.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(par.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(par.risk_level, "")) as risk_level_value')
                    ->selectRaw('TRIM(COALESCE(par.slippage, "")) as slippage_value')
                    ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
                    ->addSelect('par.id')
                    ->whereNotNull('par.project_code')
                    ->whereRaw('TRIM(par.project_code) <> ""');

                $latestExtractionByProject = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->selectRaw('risk_base.project_code')
                    ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
                    ->groupBy('risk_base.project_code');

                $latestRowsByExtraction = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                            ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.risk_level_value',
                        'risk_base.slippage_value',
                        'risk_base.extraction_date',
                        'risk_base.id'
                    );

                $latestIdByProject = DB::query()
                    ->fromSub($latestRowsByExtraction, 'risk_rows')
                    ->selectRaw('risk_rows.project_code')
                    ->selectRaw('MAX(risk_rows.id) as latest_id')
                    ->groupBy('risk_rows.project_code');

                $finalRiskRows = DB::query()
                    ->fromSub($riskBaseQuery, 'risk_base')
                    ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                        $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                            ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
                    })
                    ->select(
                        'risk_base.project_code',
                        'risk_base.project_title',
                        'risk_base.province',
                        'risk_base.city_municipality',
                        'risk_base.risk_level_value',
                        'risk_base.slippage_value',
                        'risk_base.extraction_date'
                    )
                    ->get();

                $rowsByRisk = array_fill_keys($projectAtRiskOrder, []);

                foreach ($finalRiskRows as $row) {
                    $riskLabel = $normalizeRiskLevel($row->risk_level_value ?? null);
                    if ($riskLabel === null || !array_key_exists($riskLabel, $rowsByRisk)) {
                        continue;
                    }

                    $rawSlippage = trim((string) ($row->slippage_value ?? ''));
                    $normalizedSlippage = null;
                    if ($rawSlippage !== '') {
                        if (is_numeric($rawSlippage)) {
                            $normalizedSlippage = (float) $rawSlippage;
                        } else {
                            $cleanedSlippage = preg_replace('/[^0-9\.\-]/', '', $rawSlippage);
                            if ($cleanedSlippage !== null && $cleanedSlippage !== '' && is_numeric($cleanedSlippage)) {
                                $normalizedSlippage = (float) $cleanedSlippage;
                            }
                        }
                    }

                    $rowsByRisk[$riskLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'latest_update_date' => $row->extraction_date ?? null,
                        'slippage_value' => $normalizedSlippage,
                        'slippage_display' => $rawSlippage,
                        'risk_level' => $riskLabel,
                    ];
                }

                foreach (array_keys($rowsByRisk) as $riskLabel) {
                    $projectsByRisk[$riskLabel] = collect($rowsByRisk[$riskLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftSlippage = $leftRow->slippage_value;
                            $rightSlippage = $rightRow->slippage_value;

                            if ($leftSlippage !== null && $rightSlippage !== null && $leftSlippage !== $rightSlippage) {
                                return $leftSlippage <=> $rightSlippage;
                            }
                            if ($leftSlippage === null && $rightSlippage !== null) {
                                return 1;
                            }
                            if ($leftSlippage !== null && $rightSlippage === null) {
                                return -1;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode !== $rightCode) {
                                return $leftCode < $rightCode ? -1 : 1;
                            }

                            $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                            $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                            if ($leftTitle === $rightTitle) {
                                return 0;
                            }

                            return $leftTitle < $rightTitle ? -1 : 1;
                        })
                        ->values();
                }

                return $projectsByRisk;
            };

            $projectUpdateStatusParsedDateExpression = $hasSubayDateParsedColumn
                ? 'spp.date_parsed'
                : "
                    COALESCE(
                        IF(
                            TRIM(COALESCE(spp.date, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                            DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.date, '')) AS DECIMAL(12,4))) DAY),
                            NULL
                        ),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%Y-%m-%d'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%Y-%m-%d %H:%i:%s'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%m/%d/%Y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%m/%d/%Y %H:%i'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%m/%d/%Y %H:%i:%s'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%m/%d/%Y %h:%i:%s %p'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%m/%d/%y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%d/%m/%Y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%d-%m-%Y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%d-%b-%Y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%b %e, %Y'),
                        STR_TO_DATE(NULLIF(TRIM(COALESCE(spp.date, '')), ''), '%M %e, %Y')
                    )
                ";

            $expectedCompletionParsedDateExpression = "
                COALESCE(
                    IF(
                        TRIM(COALESCE(spp.intended_completion_date_2, '')) REGEXP '^[0-9]+(\\.[0-9]+)?$',
                        DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.intended_completion_date_2, '')) AS DECIMAL(12,4))) DAY),
                        NULL
                    ),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%Y-%m-%d'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%Y-%m-%d %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %H:%i'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %H:%i:%s'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%Y %h:%i:%s %p'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%m/%d/%y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d/%m/%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d-%m-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%d-%b-%Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%b %e, %Y'),
                    STR_TO_DATE(TRIM(COALESCE(spp.intended_completion_date_2, '')), '%M %e, %Y')
                )
            ";

            $computeProjectUpdateStatusCountsFromSubay = function ($subayQuery, array &$targetCounts) use ($projectUpdateStatusParsedDateExpression, $subayStatusGroupExpr, $subayProjectCodeGroupExpr) {

                $projectUpdateRowsQuery = (clone $subayQuery)
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("LOWER(TRIM(COALESCE({$subayStatusGroupExpr}, ''))) as status_raw")
                    ->selectRaw("{$projectUpdateStatusParsedDateExpression} as latest_update_date");

                $counts = DB::query()
                    ->fromSub($projectUpdateRowsQuery, 'project_updates')
                    ->where('project_updates.status_raw', '!=', 'completed')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60 THEN 1 ELSE 0 END) as high_risk_total')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60 THEN 1 ELSE 0 END) as low_risk_total')
                    ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30 THEN 1 ELSE 0 END) as no_risk_total')
                    ->first();

                $targetCounts['High Risk'] = (int) ($counts->high_risk_total ?? 0);
                $targetCounts['Low Risk'] = (int) ($counts->low_risk_total ?? 0);
                $targetCounts['No Risk'] = (int) ($counts->no_risk_total ?? 0);
            };

            $fetchProjectUpdateProjectsFromSubay = function ($subayQuery, string $riskLabel) use ($projectUpdateStatusParsedDateExpression, $subayStatusGroupExpr, $subayProjectCodeGroupExpr) {
                $projectUpdateRowsQuery = (clone $subayQuery)
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("LOWER(TRIM(COALESCE({$subayStatusGroupExpr}, ''))) as status_raw")
                    ->selectRaw("{$projectUpdateStatusParsedDateExpression} as latest_update_date");

                $statusRowsQuery = DB::query()
                    ->fromSub($projectUpdateRowsQuery, 'project_updates')
                    ->select(
                        'project_updates.project_code',
                        'project_updates.project_title',
                        'project_updates.province',
                        'project_updates.city_municipality',
                        'project_updates.latest_update_date'
                    )
                    ->selectRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) as aging_days')
                    ->where('project_updates.status_raw', '!=', 'completed')
                    ->whereNotNull('project_updates.latest_update_date');

                if ($riskLabel === 'High Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60');
                } elseif ($riskLabel === 'Low Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60');
                } elseif ($riskLabel === 'No Risk') {
                    $statusRowsQuery->whereRaw('DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30');
                } else {
                    return collect();
                }

                return $statusRowsQuery
                    ->orderByDesc('aging_days')
                    ->orderBy('project_updates.project_code')
                    ->orderBy('project_updates.project_title')
                    ->get();
            };

            if (Schema::hasTable('subay_project_profiles')) {
                $subayBaseQuery = DB::table('subay_project_profiles as spp')
                    ->whereRaw($subayProjectCodeFilterExpr . ' IS NOT NULL')
                    ->whereRaw($subayProjectCodeFilterExpr . " <> ''");

                $excludeSglgifFromSubay($subayBaseQuery);

                $configuredProvinceLabels = collect(\App\Support\ProjectLocationFilterHelper::buildConfiguredProvinceLabels())
                    ->map($normalizeFilterValue)
                    ->filter(function ($value) {
                        return $value !== '';
                    })
                    ->values();

                if ($configuredProvinceLabels->isNotEmpty()) {
                    $filterOptions['provinces'] = $configuredProvinceLabels;

                    $provinceCityMap = \App\Support\ProjectLocationFilterHelper::buildConfiguredProvinceCityMapFromHierarchy(
                        $configuredProvinceLabels->all()
                    );

                    $filterOptions['province_city_map'] = $provinceCityMap;
                    $filterOptions['cities'] = collect($filters['province'])
                        ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                            return $provinceCityMap[$provinceLabel] ?? [];
                        })
                        ->unique(function ($cityLabel) {
                            return strtolower(trim((string) $cityLabel));
                        })
                        ->values();

                    $cityBarangayMap = \App\Support\ProjectLocationFilterHelper::buildConfiguredCityBarangayMapFromHierarchy(
                        $configuredProvinceLabels->all()
                    );

                    if (!empty($filters['province'])) {
                        $allowedCityKeys = collect($filters['province'])
                            ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                                return $provinceCityMap[$provinceLabel] ?? [];
                            })
                            ->map(function ($cityLabel) {
                                return strtolower(trim((string) $cityLabel));
                            })
                            ->unique()
                            ->values()
                            ->all();

                        if (!empty($allowedCityKeys)) {
                            $cityBarangayMap = array_filter(
                                $cityBarangayMap,
                                function ($cityLabel) use ($allowedCityKeys) {
                                    return in_array(strtolower(trim((string) $cityLabel)), $allowedCityKeys, true);
                                },
                                ARRAY_FILTER_USE_KEY
                            );
                        }
                    }

                    $filterOptions['city_barangay_map'] = $cityBarangayMap;
                    $filterOptions['barangays'] = collect($filters['city_municipality'])
                        ->flatMap(function ($cityLabel) use ($cityBarangayMap) {
                            return $cityBarangayMap[$cityLabel] ?? [];
                        })
                        ->unique(function ($barangayLabel) {
                            return strtolower(trim((string) $barangayLabel));
                        })
                        ->values();
                } else {
                    $filterOptions['provinces'] = (clone $subayBaseQuery)
                        ->select('spp.province')
                        ->whereNotNull('spp.province')
                        ->whereRaw('TRIM(spp.province) <> ""')
                        ->distinct()
                        ->orderBy('spp.province')
                        ->pluck('spp.province');

                    $provinceCityMap = [];
                    $provinceOptionLabels = collect($filterOptions['provinces'] ?? [])
                        ->map($normalizeFilterValue)
                        ->filter(function ($value) {
                            return $value !== '';
                        })
                        ->values();

                    if (
                        Schema::hasTable('location_provinces')
                        && Schema::hasTable('location_city_municipalities')
                    ) {
                        $configuredProvinceCityRows = DB::table('location_city_municipalities as lcm')
                            ->join('location_provinces as lp', 'lp.id', '=', 'lcm.province_id')
                            ->selectRaw('TRIM(COALESCE(lp.province_name, "")) as province')
                            ->selectRaw('TRIM(COALESCE(lcm.citymun_name, "")) as city_municipality')
                            ->whereNotNull('lp.province_name')
                            ->whereRaw('TRIM(lp.province_name) <> ""')
                            ->whereNotNull('lcm.citymun_name')
                            ->whereRaw('TRIM(lcm.citymun_name) <> ""')
                            ->orderBy('lp.province_name')
                            ->orderBy('lcm.citymun_name')
                            ->get();

                        $configuredProvinceCityIndex = [];
                        foreach ($configuredProvinceCityRows as $row) {
                            $provinceLabel = $normalizeFilterValue($row->province ?? '');
                            $cityLabel = $normalizeFilterValue($row->city_municipality ?? '');

                            if ($provinceLabel === '' || $cityLabel === '') {
                                continue;
                            }

                            $configuredProvinceKey = strtolower($provinceLabel);
                            $configuredProvinceCityIndex[$configuredProvinceKey] ??= [];
                            if (!in_array($cityLabel, $configuredProvinceCityIndex[$configuredProvinceKey], true)) {
                                $configuredProvinceCityIndex[$configuredProvinceKey][] = $cityLabel;
                            }
                        }

                        foreach ($provinceOptionLabels as $provinceOptionLabel) {
                            $provinceCityMap[$provinceOptionLabel] = $configuredProvinceCityIndex[strtolower($provinceOptionLabel)] ?? [];
                        }
                    }

                    if (empty(array_filter($provinceCityMap))) {
                        $provinceCityRows = (clone $subayBaseQuery)
                            ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                            ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                            ->whereNotNull('spp.province')
                            ->whereRaw('TRIM(spp.province) <> ""')
                            ->whereNotNull('spp.city_municipality')
                            ->whereRaw('TRIM(spp.city_municipality) <> ""')
                            ->distinct()
                            ->orderBy('spp.province')
                            ->orderBy('spp.city_municipality')
                            ->get();

                        foreach ($provinceCityRows as $row) {
                            $provinceLabel = trim((string) ($row->province ?? ''));
                            $cityLabel = trim((string) ($row->city_municipality ?? ''));

                            if ($provinceLabel === '' || $cityLabel === '') {
                                continue;
                            }

                            $provinceCityMap[$provinceLabel] ??= [];
                            if (!in_array($cityLabel, $provinceCityMap[$provinceLabel], true)) {
                                $provinceCityMap[$provinceLabel][] = $cityLabel;
                            }
                        }
                    }

                    $filterOptions['province_city_map'] = $provinceCityMap;
                    $filterOptions['cities'] = collect($filters['province'])
                        ->flatMap(function ($provinceLabel) use ($provinceCityMap) {
                            return $provinceCityMap[$provinceLabel] ?? [];
                        })
                        ->unique(function ($cityLabel) {
                            return strtolower(trim((string) $cityLabel));
                        })
                        ->values();

                    $cityBarangayRows = (clone $subayBaseQuery)
                        ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                        ->selectRaw('TRIM(COALESCE(spp.barangay, "")) as barangay')
                        ->whereNotNull('spp.city_municipality')
                        ->whereRaw('TRIM(spp.city_municipality) <> ""')
                        ->whereNotNull('spp.barangay')
                        ->whereRaw('TRIM(spp.barangay) <> ""')
                        ->orderBy('spp.city_municipality')
                        ->get();

                    $cityBarangayMap = [];
                    $cityBarangaySeenMap = [];
                    foreach ($cityBarangayRows as $row) {
                        $cityLabel = trim((string) ($row->city_municipality ?? ''));
                        if ($cityLabel === '') {
                            continue;
                        }

                        $barangayItems = preg_split('/\r\n|\r|\n/u', (string) ($row->barangay ?? '')) ?: [];
                        foreach ($barangayItems as $barangayValue) {
                            $barangayLabel = $normalizeFilterValue($barangayValue);
                            if ($barangayLabel === '') {
                                continue;
                            }

                            $cityBarangayMap[$cityLabel] ??= [];
                            $cityBarangaySeenMap[$cityLabel] ??= [];
                            $dedupeKey = strtolower($barangayLabel);
                            if (!array_key_exists($dedupeKey, $cityBarangaySeenMap[$cityLabel])) {
                                $cityBarangayMap[$cityLabel][] = $barangayLabel;
                                $cityBarangaySeenMap[$cityLabel][$dedupeKey] = true;
                            }
                        }
                    }

                    $filterOptions['city_barangay_map'] = $cityBarangayMap;
                    $filterOptions['barangays'] = collect($filters['city_municipality'])
                        ->flatMap(function ($cityLabel) use ($cityBarangayMap) {
                            return $cityBarangayMap[$cityLabel] ?? [];
                        })
                        ->unique(function ($barangayLabel) {
                            return strtolower(trim((string) $barangayLabel));
                        })
                        ->values();
                }

                $programOptionsQuery = clone $subayBaseQuery;
                $applyExactMultiFilterToSubay($programOptionsQuery, $subayProvinceFilterExpr, $filters['province']);
                $applyComparableLocationMultiFilterToSubay($programOptionsQuery, 'spp.city_municipality', $filters['city_municipality']);
                $applyExactMultiFilterToSubay($programOptionsQuery, 'spp.barangay', $filters['barangay']);
                $filterOptions['programs'] = $programOptionsQuery
                    ->select('spp.program')
                    ->whereNotNull('spp.program')
                    ->whereRaw('TRIM(spp.program) <> ""')
                    ->distinct()
                    ->orderBy('spp.program')
                    ->pluck('spp.program');

                $filterOptions['fund_sources'] = (clone $subayBaseQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->whereRaw("{$fundSourceFromProjectCodeExpr} <> 'SGLGIF'")
                    ->distinct()
                    ->orderBy('fund_source')
                    ->pluck('fund_source');

                $filterOptions['funding_years'] = (clone $subayBaseQuery)
                    ->select('spp.funding_year')
                    ->whereNotNull('spp.funding_year')
                    ->whereRaw('TRIM(spp.funding_year) <> ""')
                    ->distinct()
                    ->orderByRaw("{$subayFundingYearOrderExpr} DESC")
                    ->pluck('spp.funding_year');

                $filterOptions['project_types'] = (clone $subayBaseQuery)
                    ->select('spp.type_of_project')
                    ->whereNotNull('spp.type_of_project')
                    ->whereRaw('TRIM(spp.type_of_project) <> ""')
                    ->distinct()
                    ->orderBy('spp.type_of_project')
                    ->pluck('spp.type_of_project');

                $filterOptions['project_statuses'] = (clone $subayBaseQuery)
                    ->select('spp.status')
                    ->whereNotNull('spp.status')
                    ->whereRaw('TRIM(spp.status) <> ""')
                    ->distinct()
                    ->orderBy('spp.status')
                    ->pluck('spp.status');

                $subayDashboardQuery = clone $subayBaseQuery;
                $applyDashboardFiltersToSubay($subayDashboardQuery);

                $totalProjects = (int) (clone $subayDashboardQuery)->count();

                $subayProvinceProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw('MAX(TRIM(COALESCE(spp.province, ""))) as province')
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw($subayProvinceGroupExpr))
                    ->get();

                foreach ($subayProvinceProjectRows as $row) {
                    $normalizedProvince = $normalizeCarProvince($row->province ?? null);
                    if ($normalizedProvince === null || !array_key_exists($normalizedProvince, $carProvinceProjectCounts)) {
                        continue;
                    }

                    $carProvinceProjectCounts[$normalizedProvince] += (int) ($row->total ?? 0);
                }

                $balanceByProjectQuery = (clone $subayDashboardQuery)
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('MAX(TRIM(COALESCE(spp.project_title, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(spp.status, ""))) as status')
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.national_subsidy_original_allocation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as original_allocation")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.lgu_counterpart_original_allocation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as lgu_counterpart")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.obligation, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as obligation")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.disbursement, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as disbursement")
                    ->selectRaw("MAX(CAST(NULLIF(REPLACE(REPLACE(TRIM(COALESCE(spp.national_subsidy_reverted_amount, '')), ',', ''), ' ', ''), '') AS DECIMAL(20,2))) as reverted_allocation")
                    ->groupBy(DB::raw($subayProjectCodeGroupExpr));

                $balanceFormulaExpression = 'COALESCE(balance_projects.original_allocation, 0) - (COALESCE(balance_projects.disbursement, 0) + COALESCE(balance_projects.reverted_allocation, 0))';
                $revertedAllocationExpression = 'COALESCE(balance_projects.reverted_allocation, 0)';

                $financialStatusProjectsBaseQuery = DB::query()
                    ->fromSub($balanceByProjectQuery, 'balance_projects')
                    ->select(
                        'balance_projects.project_code',
                        'balance_projects.project_title',
                        'balance_projects.status',
                        'balance_projects.original_allocation',
                        'balance_projects.lgu_counterpart',
                        'balance_projects.obligation',
                        'balance_projects.disbursement'
                    )
                    ->selectRaw("{$revertedAllocationExpression} as reverted_allocation")
                    ->selectRaw("{$balanceFormulaExpression} as balance");

                $financialStatusProjects = (clone $financialStatusProjectsBaseQuery)
                    ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(balance_projects.status, ''))) = 'completed' THEN 1 ELSE 0 END")
                    ->orderBy('balance_projects.project_code')
                    ->get();

                $projectsWithBalance = (clone $financialStatusProjectsBaseQuery)
                    ->whereRaw("{$balanceFormulaExpression} > 0")
                    ->orderByRaw("{$balanceFormulaExpression} DESC")
                    ->orderBy('balance_projects.project_code')
                    ->get();

                $financialTotals = DB::query()
                    ->fromSub($balanceByProjectQuery, 'balance_projects')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.original_allocation, 0)), 0) as total_lgsf_allocation')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.obligation, 0)), 0) as total_obligation')
                    ->selectRaw('COALESCE(SUM(COALESCE(balance_projects.disbursement, 0)), 0) as total_disbursement')
                    ->selectRaw("COALESCE(SUM({$balanceFormulaExpression}), 0) as total_balance")
                    ->first();

                $totalLgsfAllocationAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0);
                $totalObligationAmount = (float) ($financialTotals->total_obligation ?? 0);
                $totalDisbursementAmount = (float) ($financialTotals->total_disbursement ?? 0);
                $totalBalanceAmount = (float) ($financialTotals->total_balance ?? 0);

                $utilizationPercentage = $totalObligationAmount > 0
                    ? (($totalDisbursementAmount / $totalObligationAmount) * 100)
                    : 0.0;

                $projectsExpectedCompletionBaseQuery = (clone $subayDashboardQuery)
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("{$expectedCompletionParsedDateExpression} as expected_completion_date");

                $projectsExpectedCompletionThisMonth = DB::query()
                    ->fromSub($projectsExpectedCompletionBaseQuery, 'due_projects')
                    ->whereNotNull('due_projects.expected_completion_date')
                    ->whereYear('due_projects.expected_completion_date', $currentYear)
                    ->whereMonth('due_projects.expected_completion_date', $currentMonth)
                    ->orderBy('due_projects.expected_completion_date')
                    ->orderBy('due_projects.project_code')
                    ->orderBy('due_projects.project_title')
                    ->get();

                $fundSourceCountsMap = (clone $subayDashboardQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw($fundSourceFromProjectCodeExpr))
                    ->get()
                    ->reduce(function ($carry, $row) {
                        $label = strtoupper(trim((string) $row->fund_source));
                        $label = $label !== '' && $label !== 'UNSPECIFIED' ? $label : 'Unspecified';
                        $carry[$label] = (int) $row->total;
                        return $carry;
                    }, []);

                $fundSourceProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw("{$fundSourceFromProjectCodeExpr} as fund_source")
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(spp.status, "")) as status')
                    ->orderByRaw("{$fundSourceFromProjectCodeExpr}")
                    ->orderByRaw($subayProjectCodeGroupExpr)
                    ->orderByRaw('TRIM(COALESCE(spp.project_title, ""))')
                    ->get();

                foreach ($fundSourceProjectRows as $row) {
                    $label = strtoupper(trim((string) ($row->fund_source ?? '')));
                    $label = $label !== '' && $label !== 'UNSPECIFIED' ? $label : 'Unspecified';

                    if (!array_key_exists($label, $fundSourceProjectsMap)) {
                        $fundSourceProjectsMap[$label] = [];
                    }

                    $fundSourceProjectsMap[$label][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'status' => $row->status ?? null,
                    ];
                }

                foreach (array_keys($fundSourceProjectsMap) as $sourceLabel) {
                    $fundSourceProjectsMap[$sourceLabel] = collect($fundSourceProjectsMap[$sourceLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftIsCompleted = strtolower(trim((string) ($leftRow->status ?? ''))) === 'completed' ? 1 : 0;
                            $rightIsCompleted = strtolower(trim((string) ($rightRow->status ?? ''))) === 'completed' ? 1 : 0;

                            if ($leftIsCompleted !== $rightIsCompleted) {
                                return $leftIsCompleted <=> $rightIsCompleted;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode !== $rightCode) {
                                return $leftCode < $rightCode ? -1 : 1;
                            }

                            $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                            $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                            if ($leftTitle === $rightTitle) {
                                return 0;
                            }

                            return $leftTitle < $rightTitle ? -1 : 1;
                        })
                        ->values();
                }

                $subayStatusRows = (clone $subayDashboardQuery)
                    ->selectRaw("{$subayStatusGroupExpr} as status_raw")
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(DB::raw($subayStatusGroupExpr))
                    ->get();

                foreach ($subayStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel !== null) {
                        $statusSubaybayanCounts[$statusLabel] += (int) $row->total;
                    }
                }

                $subayStatusLocationRows = (clone $subayDashboardQuery)
                    ->selectRaw('MAX(TRIM(COALESCE(spp.province, ""))) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw("{$subayStatusGroupExpr} as status_raw")
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(
                        DB::raw($subayProvinceGroupExpr),
                        DB::raw('TRIM(COALESCE(spp.city_municipality, ""))'),
                        DB::raw($subayStatusGroupExpr)
                    )
                    ->get();

                $statusByProvince = [];
                foreach ($subayStatusLocationRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceLabel = trim((string) ($row->province ?? ''));
                    $provinceLabel = $provinceLabel !== '' ? $provinceLabel : 'Unspecified Province';
                    $cityLabel = trim((string) ($row->city_municipality ?? ''));
                    $cityLabel = $cityLabel !== '' ? $cityLabel : 'Unspecified City/Municipality';
                    $countValue = (int) ($row->total ?? 0);

                    if (!array_key_exists($provinceLabel, $statusByProvince)) {
                        $statusByProvince[$provinceLabel] = [
                            'province_totals' => array_fill_keys($statusDisplayOrder, 0),
                            'cities' => [],
                        ];
                    }

                    if (!array_key_exists($cityLabel, $statusByProvince[$provinceLabel]['cities'])) {
                        $statusByProvince[$provinceLabel]['cities'][$cityLabel] = array_fill_keys($statusDisplayOrder, 0);
                    }

                    $statusByProvince[$provinceLabel]['province_totals'][$statusLabel] += $countValue;
                    $statusByProvince[$provinceLabel]['cities'][$cityLabel][$statusLabel] += $countValue;
                }

                if (!empty($statusByProvince)) {
                    $provinceLabels = array_keys($statusByProvince);
                    natcasesort($provinceLabels);

                    foreach ($provinceLabels as $provinceLabel) {
                        $provinceData = $statusByProvince[$provinceLabel];

                        $statusSubaybayanLocationReport[] = [
                            'row_type' => 'province',
                            'province' => $provinceLabel,
                            'city_municipality' => '',
                            'counts' => $provinceData['province_totals'],
                        ];

                        $cityLabels = array_keys($provinceData['cities']);
                        natcasesort($cityLabels);

                        foreach ($cityLabels as $cityLabel) {
                            $statusSubaybayanLocationReport[] = [
                                'row_type' => 'city',
                                'province' => $provinceLabel,
                                'city_municipality' => $cityLabel,
                                'counts' => $provinceData['cities'][$cityLabel],
                            ];
                        }
                    }
                }

                $subayProvinceFundingYearProgramStatusRows = (clone $subayDashboardQuery)
                    ->selectRaw('MAX(TRIM(COALESCE(spp.province, ""))) as province')
                    ->selectRaw('MAX(TRIM(COALESCE(spp.funding_year, ""))) as funding_year')
                    ->selectRaw('TRIM(COALESCE(spp.program, "")) as program')
                    ->selectRaw("{$subayStatusGroupExpr} as status_raw")
                    ->selectRaw('COUNT(*) as total')
                    ->groupBy(
                        DB::raw($subayProvinceGroupExpr),
                        DB::raw($subayFundingYearGroupExpr),
                        DB::raw('TRIM(COALESCE(spp.program, ""))'),
                        DB::raw($subayStatusGroupExpr)
                    )
                    ->get();

                $provinceFundingYearProgramStatusRows = [];
                foreach ($subayProvinceFundingYearProgramStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceLabel = trim((string) ($row->province ?? ''));
                    $provinceLabel = $provinceLabel !== '' ? $provinceLabel : 'Unspecified Province';
                    $fundingYearLabel = trim((string) ($row->funding_year ?? ''));
                    $fundingYearLabel = $fundingYearLabel !== '' ? $fundingYearLabel : 'Unspecified Funding Year';
                    $programLabel = trim((string) ($row->program ?? ''));
                    $programLabel = $programLabel !== '' ? $programLabel : 'Unspecified Program';
                    $groupKey = implode('||', [$provinceLabel, $fundingYearLabel, $programLabel, $statusLabel]);

                    if (!array_key_exists($groupKey, $provinceFundingYearProgramStatusRows)) {
                        $provinceFundingYearProgramStatusRows[$groupKey] = [
                            'province' => $provinceLabel,
                            'funding_year' => $fundingYearLabel,
                            'program' => $programLabel,
                            'project_status' => $statusLabel,
                            'total' => 0,
                        ];
                    }

                    $provinceFundingYearProgramStatusRows[$groupKey]['total'] += (int) ($row->total ?? 0);
                }

                $provinceFundingYearProgramStatusReport = array_values($provinceFundingYearProgramStatusRows);
                usort($provinceFundingYearProgramStatusReport, function (array $leftRow, array $rightRow) use ($statusDisplayOrderPositions) {
                    $provinceCompare = strnatcasecmp((string) ($leftRow['province'] ?? ''), (string) ($rightRow['province'] ?? ''));
                    if ($provinceCompare !== 0) {
                        return $provinceCompare;
                    }

                    $leftFundingYear = trim((string) ($leftRow['funding_year'] ?? ''));
                    $rightFundingYear = trim((string) ($rightRow['funding_year'] ?? ''));
                    $leftFundingYearIsNumeric = is_numeric($leftFundingYear);
                    $rightFundingYearIsNumeric = is_numeric($rightFundingYear);
                    if ($leftFundingYearIsNumeric && $rightFundingYearIsNumeric) {
                        $fundingYearCompare = (int) $rightFundingYear <=> (int) $leftFundingYear;
                    } else {
                        $fundingYearCompare = strnatcasecmp($leftFundingYear, $rightFundingYear);
                    }
                    if ($fundingYearCompare !== 0) {
                        return $fundingYearCompare;
                    }

                    $programCompare = strnatcasecmp((string) ($leftRow['program'] ?? ''), (string) ($rightRow['program'] ?? ''));
                    if ($programCompare !== 0) {
                        return $programCompare;
                    }

                    $leftStatus = (string) ($leftRow['project_status'] ?? '');
                    $rightStatus = (string) ($rightRow['project_status'] ?? '');
                    $leftStatusPosition = $statusDisplayOrderPositions[$leftStatus] ?? PHP_INT_MAX;
                    $rightStatusPosition = $statusDisplayOrderPositions[$rightStatus] ?? PHP_INT_MAX;

                    if ($leftStatusPosition !== $rightStatusPosition) {
                        return $leftStatusPosition <=> $rightStatusPosition;
                    }

                    return strnatcasecmp($leftStatus, $rightStatus);
                });

                $subayStatusProjectRows = (clone $subayDashboardQuery)
                    ->selectRaw("{$subayStatusGroupExpr} as status_raw")
                    ->selectRaw("{$subayProjectCodeGroupExpr} as project_code")
                    ->selectRaw('TRIM(COALESCE(spp.project_title, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(spp.province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(spp.city_municipality, "")) as city_municipality')
                    ->selectRaw('TRIM(COALESCE(spp.funding_year, "")) as funding_year')
                    ->selectRaw('TRIM(COALESCE(spp.program, "")) as program')
                    ->orderByRaw($subayStatusGroupExpr)
                    ->orderByRaw($subayProjectCodeGroupExpr)
                    ->orderByRaw('TRIM(COALESCE(spp.project_title, ""))')
                    ->get();

                foreach ($subayStatusProjectRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    if (!array_key_exists($statusLabel, $statusSubaybayanProjectsMap)) {
                        $statusSubaybayanProjectsMap[$statusLabel] = [];
                    }

                    $statusSubaybayanProjectsMap[$statusLabel][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'funding_year' => $row->funding_year ?? null,
                        'program' => $row->program ?? null,
                        'status' => $statusLabel,
                    ];
                }

                $filteredProjectCodesQuery = (clone $subayDashboardQuery)
                    ->selectRaw("DISTINCT {$subayProjectCodeGroupExpr} as project_code");
                $computeProjectAtRiskCounts(clone $filteredProjectCodesQuery, 'risk_level', $projectAtRiskCounts);
                $computeProjectAtRiskAgingCounts(clone $filteredProjectCodesQuery, $projectAtRiskAgingCounts);
                $projectAtRiskSlippageProjects = $fetchProjectAtRiskSlippageProjects(clone $filteredProjectCodesQuery);
                $projectAtRiskAgingProjects = $fetchProjectAtRiskAgingProjects(clone $filteredProjectCodesQuery);
                $computeProjectUpdateStatusCountsFromSubay(clone $subayDashboardQuery, $projectUpdateStatusCounts);
                foreach ($projectUpdateStatusOrder as $riskLabel) {
                    $projectUpdateRiskProjects[$riskLabel] = $fetchProjectUpdateProjectsFromSubay(clone $subayDashboardQuery, $riskLabel);
                }
            } else {
                $fallbackQuery = LocallyFundedProject::query();
                $excludeSglgifFromFallback($fallbackQuery);
                $fallbackCityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $fallbackOfficeComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(office, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";
                $applyOfficeScopeToFallback = function ($query) use (
                    $officeLower,
                    $officeComparableLower,
                    $fallbackCityComparableExpression,
                    $fallbackOfficeComparableExpression
                ) {
                    if ($officeLower === '') {
                        return;
                    }

                    $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

                    $query->where(function ($subQuery) use (
                        $officeLower,
                        $officeNeedle,
                        $fallbackCityComparableExpression,
                        $fallbackOfficeComparableExpression
                    ) {
                        $subQuery->whereRaw('LOWER(TRIM(COALESCE(office, ""))) = ?', [$officeLower])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(city_municipality, ""))) = ?', [$officeLower])
                            ->orWhereRaw("{$fallbackOfficeComparableExpression} = ?", [$officeNeedle])
                            ->orWhereRaw("{$fallbackCityComparableExpression} = ?", [$officeNeedle]);
                    });
                };

                if ($isLguScopedUser) {
                    if ($office !== '') {
                        if ($province !== '') {
                            $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                            $applyOfficeScopeToFallback($fallbackQuery);
                        } else {
                            $applyOfficeScopeToFallback($fallbackQuery);
                        }
                    } elseif ($province !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                    }
                } elseif ($isDilgUser) {
                    if ($isRegionalOfficeUser) {
                        // Regional Office users can see all projects
                    } elseif ($province !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
                    } elseif ($region !== '') {
                        $fallbackQuery->whereRaw('LOWER(TRIM(COALESCE(region, ""))) = ?', [$regionLower]);
                    }
                }

                $applyExactMultiFilterToSubay($fallbackQuery, 'province', $filters['province']);
                $applyComparableLocationMultiFilterToSubay($fallbackQuery, 'city_municipality', $filters['city_municipality']);
                $applyDelimitedMultiFilterToSubay($fallbackQuery, 'barangay', $filters['barangay']);
                if (!empty($filters['programs'])) {
                    $normalizedPrograms = collect($filters['programs'])
                        ->map(function ($value) {
                            return strtolower(trim((string) $value));
                        })
                        ->filter(function ($value) {
                            return $value !== '';
                        })
                        ->unique()
                        ->values()
                        ->all();

                    if (!empty($normalizedPrograms)) {
                        $placeholders = implode(', ', array_fill(0, count($normalizedPrograms), '?'));
                        $fallbackQuery->whereRaw("LOWER(TRIM(COALESCE(fund_source, \"\"))) IN ({$placeholders})", $normalizedPrograms);
                    }
                }
                $applyExactMultiFilterToSubay($fallbackQuery, 'fund_source', $filters['fund_source']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'funding_year', $filters['funding_year']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'project_type', $filters['project_type']);
                $applyExactMultiFilterToSubay($fallbackQuery, 'status', $filters['project_status']);

                if ($filters['search'] !== '') {
                    $searchNeedle = '%' . strtolower($filters['search']) . '%';
                    $fallbackQuery->where(function ($searchQuery) use ($searchNeedle) {
                        $searchQuery
                            ->orWhereRaw('LOWER(TRIM(COALESCE(subaybayan_project_code, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(project_name, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(province, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(city_municipality, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(barangay, ""))) LIKE ?', [$searchNeedle])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(fund_source, ""))) LIKE ?', [$searchNeedle]);
                    });
                }

                $totalProjects = (int) (clone $fallbackQuery)->count();

                $fallbackProvinceProjectRows = (clone $fallbackQuery)
                    ->selectRaw('TRIM(COALESCE(province, "")) as province')
                    ->selectRaw('COUNT(DISTINCT NULLIF(UPPER(TRIM(COALESCE(subaybayan_project_code, ""))), "")) as code_total')
                    ->selectRaw('COUNT(*) as row_total')
                    ->groupBy(DB::raw('TRIM(COALESCE(province, ""))'))
                    ->get();

                foreach ($fallbackProvinceProjectRows as $row) {
                    $normalizedProvince = $normalizeCarProvince($row->province ?? null);
                    if ($normalizedProvince === null || !array_key_exists($normalizedProvince, $carProvinceProjectCounts)) {
                        continue;
                    }

                    $countValue = (int) ($row->code_total ?? 0);
                    if ($countValue < 1) {
                        $countValue = (int) ($row->row_total ?? 0);
                    }

                    $carProvinceProjectCounts[$normalizedProvince] += $countValue;
                }

                $financialTotals = (clone $fallbackQuery)
                    ->selectRaw('COALESCE(SUM(COALESCE(obligation, 0)), 0) as total_obligation')
                    ->selectRaw('COALESCE(SUM(COALESCE(disbursed_amount, 0)), 0) as total_disbursement')
                    ->selectRaw('COALESCE(SUM(COALESCE(lgsf_allocation, 0)), 0) as total_lgsf_allocation')
                    ->selectRaw('COALESCE(SUM(COALESCE(reverted_amount, 0)), 0) as total_reverted_amount')
                    ->first();

                $totalObligationAmount = (float) ($financialTotals->total_obligation ?? 0);
                $totalDisbursementAmount = (float) ($financialTotals->total_disbursement ?? 0);
                $totalLgsfAllocationAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0);
                $totalBalanceAmount = (float) ($financialTotals->total_lgsf_allocation ?? 0)
                    - (
                        (float) ($financialTotals->total_disbursement ?? 0)
                        + (float) ($financialTotals->total_reverted_amount ?? 0)
                    );
                $utilizationPercentage = $totalObligationAmount > 0
                    ? (($totalDisbursementAmount / $totalObligationAmount) * 100)
                    : 0.0;

                $projectsExpectedCompletionThisMonth = (clone $fallbackQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('MAX(TRIM(COALESCE(project_name, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(province, ""))) as province')
                    ->selectRaw('MAX(TRIM(COALESCE(city_municipality, ""))) as city_municipality')
                    ->selectRaw('MAX(target_date_completion) as expected_completion_date')
                    ->groupBy(DB::raw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))'))
                    ->havingRaw('MAX(target_date_completion) BETWEEN ? AND ?', [$currentMonthStart, $currentMonthEnd])
                    ->orderByRaw('MAX(target_date_completion) ASC')
                    ->orderByRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    ->get();

                $financialStatusProjects = (clone $fallbackQuery)
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('TRIM(COALESCE(project_name, "")) as project_title')
                    ->selectRaw('TRIM(COALESCE(status, "")) as status')
                    ->selectRaw('COALESCE(lgsf_allocation, 0) as original_allocation')
                    ->selectRaw('COALESCE(lgu_counterpart, 0) as lgu_counterpart')
                    ->selectRaw('COALESCE(obligation, 0) as obligation')
                    ->selectRaw('COALESCE(disbursed_amount, 0) as disbursement')
                    ->selectRaw('COALESCE(reverted_amount, 0) as reverted_allocation')
                    ->selectRaw('COALESCE(lgsf_allocation, 0) - (COALESCE(disbursed_amount, 0) + COALESCE(reverted_amount, 0)) as balance')
                    ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'completed' THEN 1 ELSE 0 END")
                    ->orderByRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    ->get();

                $fundSourceCountsMap = (clone $fallbackQuery)
                    ->select('fund_source', DB::raw('COUNT(*) as total'))
                    ->groupBy('fund_source')
                    ->get()
                    ->reduce(function ($carry, $row) {
                        $label = strtoupper(trim((string) $row->fund_source));
                        $label = $label !== '' ? $label : 'Unspecified';
                        $carry[$label] = ($carry[$label] ?? 0) + (int) $row->total;
                        return $carry;
                    }, collect())
                    ->toArray();

                $fundSourceProjectRows = (clone $fallbackQuery)
                    ->selectRaw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified") as fund_source')
                    ->selectRaw('UPPER(TRIM(COALESCE(subaybayan_project_code, ""))) as project_code')
                    ->selectRaw('MAX(TRIM(COALESCE(project_name, ""))) as project_title')
                    ->selectRaw('MAX(TRIM(COALESCE(province, ""))) as province')
                    ->selectRaw('MAX(TRIM(COALESCE(city_municipality, ""))) as city_municipality')
                    ->groupBy(
                        DB::raw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified")'),
                        DB::raw('UPPER(TRIM(COALESCE(subaybayan_project_code, "")))')
                    )
                    ->orderBy('fund_source')
                    ->orderBy('project_code')
                    ->get();

                foreach ($fundSourceProjectRows as $row) {
                    $label = strtoupper(trim((string) ($row->fund_source ?? '')));
                    $label = $label !== '' ? $label : 'Unspecified';

                    if (!array_key_exists($label, $fundSourceProjectsMap)) {
                        $fundSourceProjectsMap[$label] = [];
                    }

                    $fundSourceProjectsMap[$label][] = (object) [
                        'project_code' => $row->project_code ?? null,
                        'project_title' => $row->project_title ?? null,
                        'province' => $row->province ?? null,
                        'city_municipality' => $row->city_municipality ?? null,
                        'status' => null,
                    ];
                }

                foreach (array_keys($fundSourceProjectsMap) as $sourceLabel) {
                    $fundSourceProjectsMap[$sourceLabel] = collect($fundSourceProjectsMap[$sourceLabel])
                        ->sort(function ($leftRow, $rightRow) {
                            $leftIsCompleted = strtolower(trim((string) ($leftRow->status ?? ''))) === 'completed' ? 1 : 0;
                            $rightIsCompleted = strtolower(trim((string) ($rightRow->status ?? ''))) === 'completed' ? 1 : 0;

                            if ($leftIsCompleted !== $rightIsCompleted) {
                                return $leftIsCompleted <=> $rightIsCompleted;
                            }

                            $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                            $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                            if ($leftCode === $rightCode) {
                                return 0;
                            }

                            return $leftCode < $rightCode ? -1 : 1;
                        })
                        ->values();
                }

                $fallbackProjectCodesQuery = (clone $fallbackQuery)
                    ->whereNotNull('subaybayan_project_code')
                    ->whereRaw('TRIM(subaybayan_project_code) <> ""')
                    ->selectRaw('DISTINCT UPPER(TRIM(subaybayan_project_code)) as project_code');
                $computeProjectAtRiskCounts(clone $fallbackProjectCodesQuery, 'risk_level', $projectAtRiskCounts);
                $computeProjectAtRiskAgingCounts(clone $fallbackProjectCodesQuery, $projectAtRiskAgingCounts);
                $projectAtRiskSlippageProjects = $fetchProjectAtRiskSlippageProjects(clone $fallbackProjectCodesQuery);
                $projectAtRiskAgingProjects = $fetchProjectAtRiskAgingProjects(clone $fallbackProjectCodesQuery);

                $fallbackProvinceFundingYearProgramStatusRows = (clone $fallbackQuery)
                    ->selectRaw('TRIM(COALESCE(province, "")) as province')
                    ->selectRaw('TRIM(COALESCE(funding_year, "")) as funding_year')
                    ->selectRaw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified Program") as program')
                    ->selectRaw('TRIM(COALESCE(status, "")) as status_raw')
                    ->selectRaw('COUNT(*) as total')
                    ->groupByRaw('TRIM(COALESCE(province, ""))')
                    ->groupByRaw('TRIM(COALESCE(funding_year, ""))')
                    ->groupByRaw('COALESCE(NULLIF(TRIM(fund_source), ""), "Unspecified Program")')
                    ->groupByRaw('TRIM(COALESCE(status, ""))')
                    ->get();

                $fallbackProvinceFundingYearProgramStatusGrouped = [];
                foreach ($fallbackProvinceFundingYearProgramStatusRows as $row) {
                    $statusLabel = $labelForStatus($normalizeStatus($row->status_raw));
                    if ($statusLabel === null) {
                        continue;
                    }

                    $provinceLabel = trim((string) ($row->province ?? '')) ?: 'Unspecified Province';
                    $fundingYearLabel = trim((string) ($row->funding_year ?? '')) ?: 'Unspecified Funding Year';
                    $programLabel = trim((string) ($row->program ?? '')) ?: 'Unspecified Program';
                    $groupKey = "{$provinceLabel}|{$fundingYearLabel}|{$programLabel}|{$statusLabel}";

                    if (!array_key_exists($groupKey, $fallbackProvinceFundingYearProgramStatusGrouped)) {
                        $fallbackProvinceFundingYearProgramStatusGrouped[$groupKey] = [
                            'province' => $provinceLabel,
                            'funding_year' => $fundingYearLabel,
                            'program' => $programLabel,
                            'project_status' => $statusLabel,
                            'total' => 0,
                        ];
                    }

                    $fallbackProvinceFundingYearProgramStatusGrouped[$groupKey]['total'] += (int) ($row->total ?? 0);
                }

                $provinceFundingYearProgramStatusReport = array_values($fallbackProvinceFundingYearProgramStatusGrouped);
                usort($provinceFundingYearProgramStatusReport, function (array $leftRow, array $rightRow) use ($statusDisplayOrderPositions) {
                    $provinceCompare = strnatcasecmp((string) ($leftRow['province'] ?? ''), (string) ($rightRow['province'] ?? ''));
                    if ($provinceCompare !== 0) {
                        return $provinceCompare;
                    }

                    $leftFundingYear = trim((string) ($leftRow['funding_year'] ?? ''));
                    $rightFundingYear = trim((string) ($rightRow['funding_year'] ?? ''));
                    $leftFundingYearIsNumeric = is_numeric($leftFundingYear);
                    $rightFundingYearIsNumeric = is_numeric($rightFundingYear);
                    if ($leftFundingYearIsNumeric && $rightFundingYearIsNumeric) {
                        $fundingYearCompare = (int) $rightFundingYear <=> (int) $leftFundingYear;
                    } else {
                        $fundingYearCompare = strnatcasecmp($leftFundingYear, $rightFundingYear);
                    }
                    if ($fundingYearCompare !== 0) {
                        return $fundingYearCompare;
                    }

                    $programCompare = strnatcasecmp((string) ($leftRow['program'] ?? ''), (string) ($rightRow['program'] ?? ''));
                    if ($programCompare !== 0) {
                        return $programCompare;
                    }

                    $leftStatus = (string) ($leftRow['project_status'] ?? '');
                    $rightStatus = (string) ($rightRow['project_status'] ?? '');
                    $leftStatusPosition = $statusDisplayOrderPositions[$leftStatus] ?? PHP_INT_MAX;
                    $rightStatusPosition = $statusDisplayOrderPositions[$rightStatus] ?? PHP_INT_MAX;

                    if ($leftStatusPosition !== $rightStatusPosition) {
                        return $leftStatusPosition <=> $rightStatusPosition;
                    }

                    return strnatcasecmp($leftStatus, $rightStatus);
                });
            }

            $fundSourceCounts = collect();
            foreach ($fundSourceOptions as $source) {
                $fundSourceCounts[$source] = (int) ($fundSourceCountsMap[$source] ?? 0);
            }

            foreach ($fundSourceCountsMap as $source => $count) {
                if (!in_array($source, $fundSourceOptions, true)) {
                    $fundSourceCounts[$source] = (int) $count;
                }
            }

            foreach (array_keys($statusSubaybayanProjectsMap) as $statusLabel) {
                $statusSubaybayanProjectsMap[$statusLabel] = collect($statusSubaybayanProjectsMap[$statusLabel])
                    ->sort(function ($leftRow, $rightRow) {
                        $leftCode = strtoupper(trim((string) ($leftRow->project_code ?? '')));
                        $rightCode = strtoupper(trim((string) ($rightRow->project_code ?? '')));

                        if ($leftCode !== $rightCode) {
                            return $leftCode < $rightCode ? -1 : 1;
                        }

                        $leftTitle = strtoupper(trim((string) ($leftRow->project_title ?? '')));
                        $rightTitle = strtoupper(trim((string) ($rightRow->project_title ?? '')));

                        if ($leftTitle === $rightTitle) {
                            return 0;
                        }

                        return $leftTitle < $rightTitle ? -1 : 1;
                    })
                    ->values();
            }

            $carProvinceProjectMaxCount = !empty($carProvinceProjectCounts)
                ? (int) max($carProvinceProjectCounts)
                : 0;

            $dashboardViewData = compact(
                'totalProjects',
                'statusActualCounts',
                'statusSubaybayanCounts',
                'statusSubaybayanProjectsMap',
                'statusSubaybayanLocationReport',
                'provinceFundingYearProgramStatusReport',
                'provinceFundingYearProgramStatusSourceRows',
                'statusDisplayOrder',
                'subayUploadDateLabel',
                'fundSourceCounts',
                'filters',
                'filterOptions',
                'totalLgsfAllocationAmount',
                'totalObligationAmount',
                'totalDisbursementAmount',
                'totalBalanceAmount',
                'utilizationPercentage',
                'expectedCompletionMonthLabel',
                'projectsExpectedCompletionThisMonth',
                'projectAtRiskCounts',
                'projectAtRiskAgingCounts',
                'projectAtRiskSlippageProjects',
                'projectAtRiskAgingProjects',
                'projectUpdateStatusCounts',
                'projectUpdateRiskProjects',
                'projectsWithBalance',
                'financialStatusProjects',
                'fundSourceProjectsMap',
                'carProvinceProjectCounts',
                'carProvinceProjectMaxCount',
                'activeProjectTab'
            );

            Cache::put($dashboardCacheKey, $dashboardViewData, now()->addMinutes(5));

            if ($modalId !== null) {
                return view('dashboard.partials.modal-body', array_merge($dashboardViewData, ['modalId' => $modalId]));
            }

            return view('dashboard.index', $dashboardViewData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Dashboard view error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}