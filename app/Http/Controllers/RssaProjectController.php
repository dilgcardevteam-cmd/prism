<?php

namespace App\Http\Controllers;

use App\Support\ProjectLocationFilterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RssaProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
            'funding_year' => trim((string) $request->query('funding_year', '')),
            'type' => trim((string) $request->query('type', '')),
            'status' => trim((string) $request->query('status', '')),
            'functional' => trim((string) $request->query('functional', '')),
            'operational' => trim((string) $request->query('operational', '')),
        ];

        if ($filters['province'] === '') {
            $filters['city'] = '';
        }

        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        if (!Schema::hasTable('rssa_project_profiles')) {
            return view('projects.rssa', [
                'activeTab' => 'rssa',
                'filters' => $filters,
                'perPage' => $perPage,
                'rows' => collect(),
                'fundingYears' => collect(),
                'provinces' => collect(),
                'provinceMunicipalities' => [],
                'cityOptions' => collect(),
                'typeOptions' => collect(),
                'statusOptions' => collect(),
                'functionalOptions' => collect(['Yes', 'No']),
                'operationalOptions' => collect(['Yes', 'No']),
                'totalProjects' => 0,
                'assessedProjects' => 0,
                'functionalProjects' => 0,
                'operationalProjects' => 0,
                'totalProjectCostAmount' => 0.0,
                'latestAssessmentDate' => null,
                'tableMissing' => true,
            ]);
        }

        $baseQuery = $this->buildScopedBaseQuery();

        $fundingYears = (clone $baseQuery)
            ->select('rpp.funding_year')
            ->whereNotNull('rpp.funding_year')
            ->whereRaw('TRIM(rpp.funding_year) <> ""')
            ->distinct()
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(rpp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->pluck('rpp.funding_year');

        $provinces = (clone $baseQuery)
            ->select('rpp.province')
            ->whereNotNull('rpp.province')
            ->whereRaw('TRIM(rpp.province) <> ""')
            ->distinct()
            ->orderBy('rpp.province')
            ->pluck('rpp.province');

        $typeOptions = (clone $baseQuery)
            ->select('rpp.type_of_project')
            ->whereNotNull('rpp.type_of_project')
            ->whereRaw('TRIM(rpp.type_of_project) <> ""')
            ->distinct()
            ->orderBy('rpp.type_of_project')
            ->pluck('rpp.type_of_project');

        $statusOptions = (clone $baseQuery)
            ->select('rpp.status')
            ->whereNotNull('rpp.status')
            ->whereRaw('TRIM(rpp.status) <> ""')
            ->distinct()
            ->orderBy('rpp.status')
            ->pluck('rpp.status');

        $provinceMunicipalities = ProjectLocationFilterHelper::buildProvinceCityMap(
            clone $baseQuery,
            $provinces->all(),
            'rpp.province',
            'rpp.city_municipality'
        );

        $cityOptions = $filters['province'] !== ''
            ? collect($provinceMunicipalities[$filters['province']] ?? [])
            : collect();

        $filteredBaseQuery = clone $baseQuery;
        $this->applyFiltersToQuery($filteredBaseQuery, $filters);

        $summaryRows = (clone $filteredBaseQuery)
            ->select([
                'rpp.date_assessed',
                'rpp.project_is_functional',
                'rpp.if_functional_yes',
                'rpp.is_project_operational',
                'rpp.if_operational_yes',
                'rpp.total_project_cost',
            ])
            ->get();

        $totalProjects = $summaryRows->count();
        $assessedProjects = $summaryRows->filter(fn ($row) => trim((string) ($row->date_assessed ?? '')) !== '')->count();
        $functionalProjects = $summaryRows->filter(function ($row) {
            return $this->isAffirmative($row->if_functional_yes ?? null)
                || $this->isAffirmative($row->project_is_functional ?? null);
        })->count();
        $operationalProjects = $summaryRows->filter(function ($row) {
            return $this->isAffirmative($row->if_operational_yes ?? null)
                || $this->isAffirmative($row->is_project_operational ?? null);
        })->count();
        $totalProjectCostAmount = (float) $summaryRows->sum(function ($row) {
            return $this->toNumber($row->total_project_cost ?? null);
        });
        $latestAssessmentDate = $summaryRows
            ->pluck('date_assessed')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->sortDesc()
            ->first();

        $rows = (clone $filteredBaseQuery)
            ->select([
                'rpp.project_code',
                'rpp.project_title',
                'rpp.program',
                'rpp.region',
                'rpp.province',
                'rpp.city_municipality',
                'rpp.funding_year',
                'rpp.type_of_project',
                'rpp.status',
                'rpp.total_project_cost',
                'rpp.date_of_project_completion',
                'rpp.date_assessed',
                'rpp.project_is_functional',
                'rpp.if_functional_yes',
                'rpp.encoded_improvements',
                'rpp.if_non_functional_state_the_reasons',
                'rpp.no_of_months_non_functional',
                'rpp.category_of_non_functionality',
                'rpp.is_project_operational',
                'rpp.if_operational_yes',
                'rpp.who_maintains_the_facility',
                'rpp.is_regularly_maintained',
                'rpp.annual_maintenance_budget',
                'rpp.if_no_state_the_reason',
                'rpp.no_of_months_non_operational',
                'rpp.updated_at',
            ])
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(rpp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->orderBy('rpp.province')
            ->orderBy('rpp.city_municipality')
            ->orderBy('rpp.project_title')
            ->paginate($perPage)
            ->withQueryString();

        return view('projects.rssa', [
            'activeTab' => 'rssa',
            'filters' => $filters,
            'perPage' => $perPage,
            'rows' => $rows,
            'fundingYears' => $fundingYears,
            'provinces' => $provinces,
            'provinceMunicipalities' => $provinceMunicipalities,
            'cityOptions' => $cityOptions,
            'typeOptions' => $typeOptions,
            'statusOptions' => $statusOptions,
            'functionalOptions' => collect(['Yes', 'No']),
            'operationalOptions' => collect(['Yes', 'No']),
            'totalProjects' => $totalProjects,
            'assessedProjects' => $assessedProjects,
            'functionalProjects' => $functionalProjects,
            'operationalProjects' => $operationalProjects,
            'totalProjectCostAmount' => $totalProjectCostAmount,
            'latestAssessmentDate' => $latestAssessmentDate,
            'tableMissing' => false,
        ]);
    }

    private function buildScopedBaseQuery()
    {
        $query = DB::table('rssa_project_profiles as rpp');

        $user = Auth::user();
        if (!$user) {
            return $query;
        }

        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $regionLower = $user->normalizedRegion();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $cityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(rpp.city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

        $applyOfficeScope = function ($query) use ($officeLower, $officeComparableLower, $cityComparableExpression) {
            if ($officeLower === '') {
                return;
            }

            $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;

            $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $cityComparableExpression) {
                $subQuery
                    ->whereRaw('LOWER(TRIM(COALESCE(rpp.city_municipality, ""))) = ?', [$officeLower])
                    ->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
            });
        };

        if ($user->isLguScopedUser()) {
            if ($provinceLower !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(rpp.province, ""))) = ?', [$provinceLower]);
            }

            $applyOfficeScope($query);

            return $query;
        }

        if (!$user->isDilgUser()) {
            return $query;
        }

        if ($user->isRegionalOfficeAssignment()) {
            return $query;
        }

        if ($provinceLower !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(rpp.province, ""))) = ?', [$provinceLower]);
        } elseif ($regionLower !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(rpp.region, ""))) = ?', [$regionLower]);
        }

        return $query;
    }

    private function applyFiltersToQuery($query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $keyword = '%' . mb_strtolower($filters['search']) . '%';
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->whereRaw('LOWER(COALESCE(rpp.project_code, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(rpp.project_title, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(rpp.program, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(rpp.province, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(rpp.city_municipality, "")) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(COALESCE(rpp.type_of_project, "")) LIKE ?', [$keyword]);
            });
        }

        $exactFilters = [
            'province' => 'rpp.province',
            'city' => 'rpp.city_municipality',
            'funding_year' => 'rpp.funding_year',
            'type' => 'rpp.type_of_project',
            'status' => 'rpp.status',
        ];

        foreach ($exactFilters as $filterKey => $column) {
            $value = trim((string) ($filters[$filterKey] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        if (trim((string) ($filters['functional'] ?? '')) !== '') {
            $expected = trim((string) $filters['functional']);
            $query->where(function ($subQuery) use ($expected) {
                $subQuery
                    ->where('rpp.if_functional_yes', $expected)
                    ->orWhere('rpp.project_is_functional', $expected);
            });
        }

        if (trim((string) ($filters['operational'] ?? '')) !== '') {
            $expected = trim((string) $filters['operational']);
            $query->where(function ($subQuery) use ($expected) {
                $subQuery
                    ->where('rpp.if_operational_yes', $expected)
                    ->orWhere('rpp.is_project_operational', $expected);
            });
        }
    }

    private function isAffirmative($value): bool
    {
        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['yes', 'y', 'true', '1', 'functional', 'operational'], true);
    }

    private function toNumber($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($normalized === null || $normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }
}
