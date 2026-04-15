<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardFilterController extends Controller
{
    public function getFilterOptions(Request $request, string $type): JsonResponse
    {
        $user = Auth::user();
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $isLguScopedUser = $user->isLguScopedUser();
        $isDilgUser = $user->isDilgUser();
        $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();

        $currentFilters = [
            'province' => $request->input('province', []),
            'city_municipality' => $request->input('city_municipality', []),
        ];

        $filterType = strtolower(trim($type));
        $validTypes = ['cities', 'barangays', 'programs', 'funding_years', 'project_types', 'project_statuses'];
        if (!in_array($filterType, $validTypes)) {
            return response()->json(['error' => 'Invalid filter type'], 400);
        }

        $options = collect();

        if (Schema::hasTable('subay_project_profiles')) {
            $baseQuery = DB::table('subay_project_profiles as spp')
                ->whereNotNull('spp.project_code')
                ->whereRaw('TRIM(spp.project_code) <> ""');

            // Apply user scopes (reuse from dashboard logic)
            if ($isLguScopedUser && $officeLower !== '') {
                $baseQuery->where(function ($q) use ($officeLower, $provinceLower, $isLguScopedUser) {
                    if ($provinceLower !== '') {
                        $q->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
                    }
                    $q->whereRaw('LOWER(TRIM(COALESCE(spp.city_municipality, ""))) = ?', [$officeLower]);
                });
            } elseif ($isDilgUser && !$isRegionalOfficeUser && $provinceLower !== '') {
                $baseQuery->whereRaw('LOWER(TRIM(COALESCE(spp.province, ""))) = ?', [$provinceLower]);
            }

            // Apply current filters for cascading
            if (!empty($currentFilters['province'])) {
                $baseQuery->whereIn('spp.province', $currentFilters['province']);
            }
            if (!empty($currentFilters['city_municipality'])) {
                $baseQuery->whereIn('spp.city_municipality', $currentFilters['city_municipality']);
            }

            switch ($filterType) {
                case 'cities':
                    $options = $baseQuery->select('spp.city_municipality')
                        ->whereNotNull('spp.city_municipality')
                        ->whereRaw('TRIM(spp.city_municipality) <> ""')
                        ->distinct()
                        ->orderBy('spp.city_municipality')
                        ->pluck('spp.city_municipality');
                    break;

                case 'barangays':
                    $barangays = $baseQuery->select('spp.barangay')
                        ->whereNotNull('spp.barangay')
                        ->whereRaw('TRIM(spp.barangay) <> ""')
                        ->pluck('spp.barangay')
                        ->flatMap(function ($value) {
                            return preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
                        })
                        ->map('trim')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();
                    $options = $barangays;
                    break;

                case 'programs':
                    $options = $baseQuery->select('spp.program')
                        ->whereNotNull('spp.program')
                        ->whereRaw('TRIM(spp.program) <> ""')
                        ->distinct()
                        ->orderBy('spp.program')
                        ->pluck('spp.program');
                    break;

                case 'funding_years':
                    $options = $baseQuery->select('spp.funding_year')
                        ->whereNotNull('spp.funding_year')
                        ->whereRaw('TRIM(spp.funding_year) <> ""')
                        ->distinct()
                        ->orderByRaw('CAST(spp.funding_year AS UNSIGNED) DESC')
                        ->pluck('spp.funding_year');
                    break;

                case 'project_types':
                    $options = $baseQuery->select('spp.type_of_project')
                        ->whereNotNull('spp.type_of_project')
                        ->whereRaw('TRIM(spp.type_of_project) <> ""')
                        ->distinct()
                        ->orderBy('spp.type_of_project')
                        ->pluck('spp.type_of_project');
                    break;

                case 'project_statuses':
                    $options = $baseQuery->select('spp.status')
                        ->whereNotNull('spp.status')
                        ->whereRaw('TRIM(spp.status) <> ""')
                        ->distinct()
                        ->orderBy('spp.status')
                        ->pluck('spp.status');
                    break;
            }
        }

        // Preserve currently selected values that still exist in options
        $preserve = collect($request->input("preserve_{$filterType}", []))
            ->intersect($options)
            ->values()
            ->all();

        return response()->json([
            'options' => $options->values()->all(),
            'preserve' => $preserve
        ]);
    }
}

