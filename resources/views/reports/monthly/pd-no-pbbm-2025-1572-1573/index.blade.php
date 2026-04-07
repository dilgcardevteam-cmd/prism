@extends('layouts.dashboard')

@section('title', 'Report on PD No. PBBM-2025-1572-1573')
@section('page-title', 'Report on PD No. PBBM-2025-1572-1573')

@section('content')
<div class="content-header">
    <h1>Report on PD No. PBBM-2025-1572-1573</h1>
    <p>Monthly submission monitoring for all provinces, cities, and municipalities.</p>
</div>

@php
    $activeFilters = array_merge([
        'province' => '',
        'city' => '',
    ], $filters ?? []);
    $provinceMunicipalities = $filterOptions['provinceMunicipalities'] ?? [];
    $selectedProvinceFilter = trim((string) ($activeFilters['province'] ?? ''));
    if ($selectedProvinceFilter !== '' && array_key_exists($selectedProvinceFilter, $provinceMunicipalities)) {
        $cityOptions = collect($provinceMunicipalities[$selectedProvinceFilter] ?? []);
    } else {
        $cityOptions = collect($provinceMunicipalities)->flatten(1);
    }
    $cityOptions = $cityOptions
        ->map(fn($city) => trim((string) $city))
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px; border: 1px solid #e5e7eb;">
                    <form id="pd-monthly-filters-form" method="GET" action="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                        <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
                        <div style="flex: 0 0 120px; min-width: 120px;">
                            <label for="pd-monthly-year" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Year</label>
                            <select id="pd-monthly-year" name="year" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                                @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                                    <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                                @endfor
                            </select>
                        </div>
                        <div style="flex: 1 1 180px; min-width: 180px;">
                            <label for="pd-monthly-filter-province" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Province</label>
                            <select id="pd-monthly-filter-province" name="province" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                                <option value="">All Provinces</option>
                                @foreach(($filterOptions['provinces'] ?? []) as $option)
                                    <option value="{{ $option }}" @selected((string) $activeFilters['province'] === (string) $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 1 1 220px; min-width: 220px;">
                            <label for="pd-monthly-filter-city" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">City / Municipality</label>
                            <select id="pd-monthly-filter-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="width: 100%; height: 42px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #f9fafb; color: #374151;">
                                <option value="">All Cities / Municipalities</option>
                                @foreach($cityOptions as $city)
                                    <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #2563eb; color: white; border: 1px solid #2563eb; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                        <a href="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573', ['year' => $reportingYear, 'per_page' => $perPage ?? 15]) }}" style="flex: 0 0 auto; height: 42px; padding: 0 18px; background-color: #6b7280; color: white; border: 1px solid #6b7280; border-radius: 8px; font-size: 13px; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; text-decoration: none;">
                            Clear
                        </a>
                    </form>
                </div>

                <div class="table-responsive report-table-shell" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <table id="pd-monthly-table" style="width: 100%; border-collapse: collapse; min-width: 1400px;">
                        <thead>
                            <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px; white-space: nowrap;">Province</th>
                                <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px; white-space: nowrap;">City/Municipality</th>
                                @foreach ($months as $monthCode => $monthLabel)
                                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 12px; white-space: nowrap;">
                                        {{ strtoupper(substr($monthLabel, 0, 3)) }}
                                    </th>
                                @endforeach
                                <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px; white-space: nowrap;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pd-monthly-table-body">
                            @forelse ($officeRows as $row)
                                @php
                                    $officeDocs = $documentsByOffice[$row['city_municipality']] ?? [];
                                    $statusIcon = function ($doc) {
                                        if (!$doc) {
                                            return '<span style="color: #9ca3af;">-</span>';
                                        }

                                        if ($doc->status === 'approved') {
                                            return '<i class="fas fa-check-circle" title="Approved" style="color: #10b981;"></i>';
                                        }

                                        if ($doc->status === 'returned') {
                                            return '<i class="fas fa-undo" title="Returned" style="color: #dc2626;"></i>';
                                        }

                                        return '<i class="fas fa-clock" title="For Validation" style="color: #3b82f6;"></i>';
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 12px; color: #111827; font-size: 13px; white-space: nowrap;">{{ $row['province'] }}</td>
                                    <td style="padding: 12px; color: #111827; font-size: 13px; white-space: nowrap;">{{ $row['city_municipality'] }}</td>
                                    @foreach ($months as $monthCode => $monthLabel)
                                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 13px;">
                                            {!! $statusIcon($officeDocs['pd_no_pbbm_2025_1572_1573|' . $reportingYear . '|' . $monthCode] ?? null) !!}
                                        </td>
                                    @endforeach
                                    <td style="padding: 12px; text-align: center; white-space: nowrap;">
                                        <a
                                            href="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573.edit', ['office' => $row['city_municipality'], 'year' => $reportingYear]) }}"
                                            style="display: inline-block; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none;"
                                        >
                                            <i class="fas fa-eye" style="margin-right: 4px;"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td colspan="{{ count($months) + 3 }}" style="padding: 40px; text-align: center; color: #6b7280;">
                                        <i class="fas fa-table" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                        No records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($officeRows->count() > 0)
                    <div class="table-pagination-row" style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div style="font-size: 12px; color: #6b7280;">
                                Page {{ $officeRows->currentPage() }} of {{ $officeRows->lastPage() }} &middot;
                                Showing {{ $officeRows->firstItem() ?? 0 }}-{{ $officeRows->lastItem() ?? 0 }} of {{ $officeRows->total() }}
                            </div>
                            <form method="GET" style="display: inline-flex; align-items: center;">
                                @foreach (request()->except(['page', 'per_page']) as $queryKey => $queryValue)
                                    @if (is_array($queryValue))
                                        @foreach ($queryValue as $nestedValue)
                                            <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                    @endif
                                @endforeach
                                <select name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                                    @foreach ([10, 15, 25, 50] as $option)
                                        <option value="{{ $option }}" @selected((int) ($perPage ?? 15) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                            @if ($officeRows->onFirstPage())
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </span>
                            @else
                                <a href="{{ $officeRows->previousPageUrl() }}" style="padding: 8px 12px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </a>
                            @endif

                            @if ($officeRows->hasMorePages())
                                <a href="{{ $officeRows->nextPageUrl() }}" style="padding: 8px 12px; background-color: #002C76; color: white; border: 1px solid #002C76; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            @else
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    #pd-monthly-table-body tr:hover {
        background-color: #eef4ff !important;
    }

    .report-table-shell {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 768px) {
        .report-table-shell {
            padding: 16px !important;
        }

        .table-pagination-row {
            flex-direction: column;
            align-items: flex-start !important;
        }
    }
</style>

<script>
    (function () {
        const provinceSelect = document.getElementById('pd-monthly-filter-province');
        const citySelect = document.getElementById('pd-monthly-filter-city');
        const locationData = @json($provinceMunicipalities ?? []);

        if (!provinceSelect || !citySelect) return;

        const getAllCities = () => Object.keys(locationData).reduce((all, province) => {
            return all.concat(locationData[province] || []);
        }, []);

        const rebuildCityOptions = (selectedProvince, preserveSelection = true) => {
            const selectedCity = preserveSelection ? (citySelect.value || citySelect.dataset.selectedCity || '') : '';
            const cityList = selectedProvince && Object.prototype.hasOwnProperty.call(locationData, selectedProvince)
                ? (locationData[selectedProvince] || [])
                : getAllCities();

            const uniqueCities = Array.from(new Set(cityList
                .map((city) => (city || '').trim())
                .filter(Boolean)))
                .sort((left, right) => left.localeCompare(right));

            citySelect.innerHTML = '<option value="">All Cities / Municipalities</option>';

            uniqueCities.forEach((city) => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });

            if (selectedCity && uniqueCities.includes(selectedCity)) {
                citySelect.value = selectedCity;
            }
        };

        provinceSelect.addEventListener('change', function () {
            rebuildCityOptions(this.value, false);
        });

        rebuildCityOptions(provinceSelect.value, true);
    })();
</script>
@endsection
