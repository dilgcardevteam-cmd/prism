<?php

namespace App\Http\Controllers;

use App\Support\ProjectLocationFilterHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RssaDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $filters = [
            'province' => trim((string) $request->query('province', '')),
            'city_municipality' => trim((string) $request->query('city_municipality', '')),
            'program' => trim((string) $request->query('program', '')),
            'funding_year' => trim((string) $request->query('funding_year', '')),
            'project_type' => trim((string) $request->query('project_type', '')),
            'project_status' => trim((string) $request->query('project_status', '')),
            'functional' => trim((string) $request->query('functional', '')),
            'operational' => trim((string) $request->query('operational', '')),
        ];

        if ($filters['province'] === '') {
            $filters['city_municipality'] = '';
        }

        if (!Schema::hasTable('rssa_project_profiles')) {
            return view('dashboard.rssa', $this->emptyPayload($filters));
        }

        $baseQuery = $this->buildScopedBaseQuery();

        $provinces = (clone $baseQuery)
            ->select('rpp.province')
            ->whereNotNull('rpp.province')
            ->whereRaw('TRIM(rpp.province) <> ""')
            ->distinct()
            ->orderBy('rpp.province')
            ->pluck('rpp.province');

        $provinceMunicipalities = ProjectLocationFilterHelper::buildProvinceCityMap(
            clone $baseQuery,
            $provinces->all(),
            'rpp.province',
            'rpp.city_municipality'
        );

        $cityOptions = $filters['province'] !== ''
            ? collect($provinceMunicipalities[$filters['province']] ?? [])
            : collect();

        $programOptions = (clone $baseQuery)
            ->select('rpp.program')
            ->whereNotNull('rpp.program')
            ->whereRaw('TRIM(rpp.program) <> ""')
            ->distinct()
            ->orderBy('rpp.program')
            ->pluck('rpp.program');

        $fundingYearOptions = (clone $baseQuery)
            ->select('rpp.funding_year')
            ->whereNotNull('rpp.funding_year')
            ->whereRaw('TRIM(rpp.funding_year) <> ""')
            ->distinct()
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(rpp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->pluck('rpp.funding_year');

        $projectTypeOptions = (clone $baseQuery)
            ->select('rpp.type_of_project')
            ->whereNotNull('rpp.type_of_project')
            ->whereRaw('TRIM(rpp.type_of_project) <> ""')
            ->distinct()
            ->orderBy('rpp.type_of_project')
            ->pluck('rpp.type_of_project');

        $projectStatusOptions = (clone $baseQuery)
            ->select('rpp.status')
            ->whereNotNull('rpp.status')
            ->whereRaw('TRIM(rpp.status) <> ""')
            ->distinct()
            ->orderBy('rpp.status')
            ->pluck('rpp.status');

        $query = clone $baseQuery;
        $this->applyFilters($query, $filters);

        $rows = $query
            ->select([
                'rpp.project_code',
                'rpp.project_title',
                'rpp.region',
                'rpp.province',
                'rpp.city_municipality',
                'rpp.program',
                'rpp.funding_year',
                'rpp.type_of_project',
                'rpp.status',
                'rpp.national_subsidy_original_allocation',
                'rpp.total_project_cost',
                'rpp.date_of_project_completion',
                'rpp.date_assessed',
                'rpp.project_is_functional',
                'rpp.if_functional_yes',
                'rpp.is_project_operational',
                'rpp.if_operational_yes',
                'rpp.is_regularly_maintained',
                'rpp.category_of_non_functionality',
                'rpp.if_non_functional_state_the_reasons',
                'rpp.if_no_state_the_reason',
                'rpp.encoded_improvements',
                'rpp.annual_maintenance_budget',
                'rpp.updated_at',
            ])
            ->orderBy('rpp.province')
            ->orderBy('rpp.city_municipality')
            ->orderBy('rpp.project_title')
            ->get()
            ->map(function ($row) {
                $row->project_cost_value = $this->toNumber($row->total_project_cost ?? null);
                $row->subsidy_value = $this->toNumber($row->national_subsidy_original_allocation ?? null);
                $row->completion_date = $this->parseDate($row->date_of_project_completion ?? null);
                $row->assessment_date = $this->parseDate($row->date_assessed ?? null);
                $row->is_functional_flag = $this->isAffirmative($row->if_functional_yes ?? null)
                    || $this->isAffirmative($row->project_is_functional ?? null);
                $row->is_operational_flag = $this->isAffirmative($row->if_operational_yes ?? null)
                    || $this->isAffirmative($row->is_project_operational ?? null);
                $row->is_maintained_flag = $this->isAffirmative($row->is_regularly_maintained ?? null);

                return $row;
            })
            ->values();

        $now = now();
        $currentMonthLabel = strtoupper($now->format('F Y'));

        $totalProjects = $rows->count();
        $assessedProjects = $rows->filter(fn ($row) => $row->assessment_date instanceof Carbon)->count();
        $functionalProjects = $rows->filter(fn ($row) => $row->is_functional_flag)->count();
        $operationalProjects = $rows->filter(fn ($row) => $row->is_operational_flag)->count();
        $maintainedProjects = $rows->filter(fn ($row) => $row->is_maintained_flag)->count();
        $totalProjectCost = (float) $rows->sum('project_cost_value');
        $totalOriginalSubsidy = (float) $rows->sum('subsidy_value');

        $fundSourceCounts = $rows
            ->groupBy(function ($row) {
                $label = trim((string) ($row->program ?? ''));
                return $label !== '' ? $label : 'UNSPECIFIED';
            })
            ->map->count()
            ->sortDesc()
            ->all();

        $projectsExpectedCompletionThisMonth = $rows
            ->filter(function ($row) use ($now) {
                return $row->completion_date instanceof Carbon
                    && $row->completion_date->month === $now->month
                    && $row->completion_date->year === $now->year;
            })
            ->sortBy(function ($row) {
                return $row->completion_date?->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        $projectStatusCounts = $rows
            ->groupBy(function ($row) {
                $label = trim((string) ($row->status ?? ''));
                return $label !== '' ? $label : 'UNSPECIFIED';
            })
            ->map->count()
            ->sortDesc()
            ->all();

        $functionalCounts = [
            'Functional' => $functionalProjects,
            'Non-Functional' => max($totalProjects - $functionalProjects, 0),
        ];

        $operationalCounts = [
            'Operational' => $operationalProjects,
            'Non-Operational' => max($totalProjects - $operationalProjects, 0),
        ];

        $maintenanceCounts = [
            'Maintained' => $maintainedProjects,
            'Not Maintained' => max($totalProjects - $maintainedProjects, 0),
        ];

        $nonFunctionalCategoryCounts = $rows
            ->filter(function ($row) {
                return trim((string) ($row->category_of_non_functionality ?? '')) !== '';
            })
            ->groupBy(function ($row) {
                return trim((string) $row->category_of_non_functionality);
            })
            ->map->count()
            ->sortDesc()
            ->take(8)
            ->all();

        return view('dashboard.rssa', [
            'activeProjectTab' => 'rssa',
            'filters' => $filters,
            'provinces' => $provinces,
            'provinceMunicipalities' => $provinceMunicipalities,
            'cityOptions' => $cityOptions,
            'programOptions' => $programOptions,
            'fundingYearOptions' => $fundingYearOptions,
            'projectTypeOptions' => $projectTypeOptions,
            'projectStatusOptions' => $projectStatusOptions,
            'functionalOptions' => collect(['Yes', 'No']),
            'operationalOptions' => collect(['Yes', 'No']),
            'totalProjects' => $totalProjects,
            'assessedProjects' => $assessedProjects,
            'functionalProjects' => $functionalProjects,
            'operationalProjects' => $operationalProjects,
            'maintainedProjects' => $maintainedProjects,
            'totalProjectCost' => $totalProjectCost,
            'totalOriginalSubsidy' => $totalOriginalSubsidy,
            'fundSourceCounts' => $fundSourceCounts,
            'projectsExpectedCompletionThisMonth' => $projectsExpectedCompletionThisMonth,
            'currentMonthLabel' => $currentMonthLabel,
            'projectStatusCounts' => $projectStatusCounts,
            'functionalCounts' => $functionalCounts,
            'operationalCounts' => $operationalCounts,
            'maintenanceCounts' => $maintenanceCounts,
            'nonFunctionalCategoryCounts' => $nonFunctionalCategoryCounts,
            'tableMissing' => false,
        ]);
    }

    private function emptyPayload(array $filters): array
    {
        return [
            'activeProjectTab' => 'rssa',
            'filters' => $filters,
            'provinces' => collect(),
            'provinceMunicipalities' => [],
            'cityOptions' => collect(),
            'programOptions' => collect(),
            'fundingYearOptions' => collect(),
            'projectTypeOptions' => collect(),
            'projectStatusOptions' => collect(),
            'functionalOptions' => collect(['Yes', 'No']),
            'operationalOptions' => collect(['Yes', 'No']),
            'totalProjects' => 0,
            'assessedProjects' => 0,
            'functionalProjects' => 0,
            'operationalProjects' => 0,
            'maintainedProjects' => 0,
            'totalProjectCost' => 0.0,
            'totalOriginalSubsidy' => 0.0,
            'fundSourceCounts' => [],
            'projectsExpectedCompletionThisMonth' => collect(),
            'currentMonthLabel' => strtoupper(now()->format('F Y')),
            'projectStatusCounts' => [],
            'functionalCounts' => ['Functional' => 0, 'Non-Functional' => 0],
            'operationalCounts' => ['Operational' => 0, 'Non-Operational' => 0],
            'maintenanceCounts' => ['Maintained' => 0, 'Not Maintained' => 0],
            'nonFunctionalCategoryCounts' => [],
            'tableMissing' => true,
        ];
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

    private function applyFilters($query, array $filters): void
    {
        $exactFilters = [
            'province' => 'rpp.province',
            'city_municipality' => 'rpp.city_municipality',
            'program' => 'rpp.program',
            'funding_year' => 'rpp.funding_year',
            'project_type' => 'rpp.type_of_project',
            'project_status' => 'rpp.status',
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
        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($normalized === null || $normalized === '' || !is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }

    private function parseDate($value): ?Carbon
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }
}
