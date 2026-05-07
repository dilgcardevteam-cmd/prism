@extends('layouts.dashboard')

@section('title', 'NADAI Management')
@section('page-title', 'NADAI Management')

@section('content')
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
        ->map(fn ($city) => trim((string) $city))
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<style>
    .nadai-index-page {
        color: #0f172a;
    }

    .nadai-index-shell {
        display: grid;
        gap: 22px;
    }

    .nadai-index-hero {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        padding: 28px 30px;
        background:
            radial-gradient(circle at top right, rgba(125, 211, 252, 0.22), transparent 34%),
            linear-gradient(135deg, #0b1f52 0%, #12398d 52%, #1d4ed8 100%);
        box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18);
    }

    .nadai-index-hero::after {
        content: '';
        position: absolute;
        inset: auto -80px -90px auto;
        width: 230px;
        height: 230px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(10px);
    }

    .nadai-index-hero-grid {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 22px;
        flex-wrap: wrap;
    }

    .nadai-index-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.86);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .nadai-index-title {
        margin: 16px 0 12px;
        color: #fff;
        font-size: clamp(30px, 4vw, 40px);
        line-height: 1.08;
        font-weight: 800;
    }

    .nadai-index-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .nadai-index-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .nadai-index-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 18px;
        background: linear-gradient(180deg, #ecfdf5 0%, #dcfce7 100%);
        border: 1px solid #86efac;
        color: #166534;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .nadai-index-panel {
        overflow: hidden;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .nadai-index-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        padding: 24px 26px 18px;
        border-bottom: 1px solid #e2e8f0;
    }

    .nadai-index-section-label {
        margin: 0 0 8px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .nadai-index-section-title {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        line-height: 1.2;
        font-weight: 800;
    }

    .nadai-index-section-copy {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.7;
    }

    .nadai-index-summary {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .nadai-index-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
    }

    .nadai-index-filter-body {
        padding: 20px 26px 26px;
    }

    .nadai-index-filter-form {
        display: grid;
        gap: 16px;
    }

    .nadai-index-filter-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 12px;
        align-items: end;
    }

    .nadai-index-field {
        display: grid;
        gap: 7px;
    }

    .nadai-index-filter-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .nadai-index-label {
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .nadai-index-select {
        width: 100%;
        min-height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #fff;
        color: #0f172a;
        font-size: 13px;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .nadai-index-select:focus {
        outline: none;
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18);
    }

    .nadai-index-btn-filter {
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(29, 78, 216, 0.22);
    }

    .nadai-index-btn-clear {
        background: #e2e8f0;
        color: #0f172a;
        box-shadow: inset 0 0 0 1px #cbd5e1;
    }

    .nadai-index-table-wrap {
        overflow-x: auto;
        padding: 0 10px 10px;
    }

    .nadai-index-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .nadai-index-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        padding: 15px 16px;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-bottom: 1px solid #dbeafe;
        text-align: center;
    }

    .nadai-index-table tbody tr {
        transition: background-color 0.16s ease;
    }

    .nadai-index-table tbody tr:nth-child(odd) {
        background: rgba(248, 250, 252, 0.55);
    }

    .nadai-index-table tbody tr:hover {
        background: #eff6ff;
    }

    .nadai-index-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 13px;
        vertical-align: middle;
        text-align: center;
    }

    .nadai-index-table tbody tr:last-child td {
        border-bottom: none;
    }

    .nadai-index-office {
        display: grid;
        gap: 4px;
        justify-items: center;
        text-align: center;
    }

    .nadai-index-office-name {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.45;
    }

    .nadai-index-office-meta {
        margin: 0;
        color: #64748b;
        font-size: 12px;
    }

    .nadai-index-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .nadai-index-chip-muted {
        background: #e2e8f0;
        color: #475569;
    }

    .nadai-index-date {
        font-weight: 700;
        white-space: nowrap;
    }

    .nadai-index-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 16px;
        border-radius: 999px;
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 12px 24px rgba(29, 78, 216, 0.2);
        transition: transform 0.16s ease, box-shadow 0.16s ease;
    }

    .nadai-index-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(29, 78, 216, 0.24);
    }

    .nadai-index-link-icon {
        width: 40px;
        min-width: 40px;
        padding: 0;
        border-radius: 12px;
    }

    .nadai-index-empty {
        padding: 54px 24px;
        text-align: center;
        color: #64748b;
    }

    .nadai-index-empty i {
        display: block;
        margin-bottom: 14px;
        color: #94a3b8;
        font-size: 36px;
    }

    .nadai-index-empty-title {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }

    .nadai-index-empty-copy {
        margin: 0;
        font-size: 13px;
        line-height: 1.7;
    }

    .nadai-index-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        padding: 18px 26px 24px;
        border-top: 1px solid #e2e8f0;
    }

    .nadai-index-pagination-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        color: #64748b;
        font-size: 12px;
    }

    .nadai-index-pagination-nav {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .nadai-index-pagination-btn,
    .nadai-index-pagination-disabled {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .nadai-index-pagination-btn {
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .nadai-index-pagination-btn-primary {
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        border-color: transparent;
    }

    .nadai-index-pagination-disabled {
        background: #e2e8f0;
        color: #94a3b8;
    }

    @media (max-width: 980px) {
        .nadai-index-panel-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .nadai-index-filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .nadai-index-filter-actions {
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }

    @media (max-width: 720px) {
        .nadai-index-hero {
            padding: 24px 20px;
            border-radius: 22px;
        }

        .nadai-index-filter-grid {
            grid-template-columns: 1fr;
        }

        .nadai-index-filter-actions .nadai-index-btn {
            width: 100%;
        }

        .nadai-index-panel-head,
        .nadai-index-filter-body,
        .nadai-index-pagination {
            padding-left: 18px;
            padding-right: 18px;
        }

        .nadai-index-pagination {
            align-items: flex-start;
            flex-direction: column;
        }

        .nadai-index-pagination-nav {
            justify-content: flex-start;
        }
    }
</style>

<div class="nadai-index-page">
    <div class="nadai-index-shell">
        <section class="nadai-index-hero">
            <div class="nadai-index-hero-grid">
                <div>
                    <div class="nadai-index-eyebrow">
                        <i class="fas fa-landmark"></i>
                        NADAI Directory
                    </div>
                    <h1 class="nadai-index-title">Notice of Authority to Debit Account Issued (NADAI) Management</h1>
                    <div class="nadai-index-pills">
                        <span class="nadai-index-pill">
                            <i class="fas fa-map"></i>
                            {{ $totalProvinces }} {{ \Illuminate\Support\Str::plural('province', $totalProvinces) }}
                        </span>
                        <span class="nadai-index-pill">
                            <i class="fas fa-building"></i>
                            {{ $totalOffices }} {{ \Illuminate\Support\Str::plural('office', $totalOffices) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="nadai-index-alert">
                <div style="flex: 0 0 20px; font-size: 18px; line-height: 1.2;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 800;">Action completed</p>
                    <p style="margin: 6px 0 0; font-size: 13px; line-height: 1.65;">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <section class="nadai-index-panel">
            <div class="nadai-index-panel-head">
                <div>
                    <p class="nadai-index-section-label">Filter Offices</p>
                    <h2 class="nadai-index-section-title">Search by location</h2>
                    <p class="nadai-index-section-copy">
                        Narrow the NADAI directory to a province or municipality and jump directly into an office profile.
                    </p>
                </div>
                <div class="nadai-index-summary">
                    <span class="nadai-index-summary-chip">
                        <i class="fas fa-filter"></i>
                        {{ $activeFilters['province'] !== '' || $activeFilters['city'] !== '' ? 'Filtered view' : 'All offices' }}
                    </span>
                </div>
            </div>
            <div class="nadai-index-filter-body">
                <form method="GET" action="{{ route('nadai-management.index') }}">
                    <input type="hidden" name="per_page" value="{{ $perPage ?? 15 }}">
                    <div class="nadai-index-filter-form">
                        <div class="nadai-index-filter-grid">
                            <div class="nadai-index-field">
                                <label for="nadai-filter-province" class="nadai-index-label">Province</label>
                                <select id="nadai-filter-province" name="province" class="nadai-index-select">
                                    <option value="">All Provinces</option>
                                    @foreach(($filterOptions['provinces'] ?? []) as $option)
                                        <option value="{{ $option }}" @selected((string) $activeFilters['province'] === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="nadai-index-field">
                                <label for="nadai-filter-city" class="nadai-index-label">City / Municipality</label>
                                <select id="nadai-filter-city" name="city" class="nadai-index-select">
                                    <option value="">All Cities / Municipalities</option>
                                    @foreach($cityOptions as $city)
                                        <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="nadai-index-filter-actions">
                                <button type="submit" class="nadai-index-btn nadai-index-btn-filter">
                                    <i class="fas fa-filter"></i>
                                    Apply
                                </button>
                                <a href="{{ route('nadai-management.index', ['per_page' => $perPage ?? 15]) }}" class="nadai-index-btn nadai-index-btn-clear">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="nadai-index-panel">
            <div class="nadai-index-panel-head">
                <div>
                    <p class="nadai-index-section-label">Office List</p>
                    <h2 class="nadai-index-section-title">NADAI office profiles</h2>
                    <p class="nadai-index-section-copy">
                        View submission counts, latest NADAI activity, and open an office profile for document management.
                    </p>
                </div>
            </div>

            <div class="nadai-index-table-wrap">
                <table class="nadai-index-table">
                    <thead>
                        <tr>
                            <th>Province</th>
                            <th>City / Municipality / PLGU</th>
                            <th>Total NADAI Submissions</th>
                            <th>Latest Project Title</th>
                            <th>Latest NADAI Date</th>
                            <th>Last Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($officeRows as $row)
                            @php
                                $latestDocument = $latestDocumentsByOffice->get($row['city_municipality']);
                                $submissionCount = (int) ($submissionCountsByOffice[$row['city_municipality']] ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    <span class="nadai-index-chip nadai-index-chip-muted">{{ $row['province'] }}</span>
                                </td>
                                <td>
                                    <div class="nadai-index-office">
                                        <p class="nadai-index-office-name">{{ $row['city_municipality'] }}</p>
                                        <p class="nadai-index-office-meta">{{ $row['province'] }}</p>
                                    </div>
                                </td>
                                <td>
                                    <span class="nadai-index-chip">{{ $submissionCount }}</span>
                                </td>
                                <td>
                                    @if ($latestDocument?->project_title)
                                        <div class="nadai-index-office">
                                            <p class="nadai-index-office-name" style="font-size: 13px;">{{ $latestDocument->project_title }}</p>
                                            <p class="nadai-index-office-meta">{{ $latestDocument->original_filename ?: 'PDF document' }}</p>
                                        </div>
                                    @else
                                        <span class="nadai-index-chip nadai-index-chip-muted">No NADAI uploaded yet</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="nadai-index-date">{{ $latestDocument?->nadai_date ? $latestDocument->nadai_date->format('M d, Y') : '—' }}</span>
                                </td>
                                <td>
                                    <span class="nadai-index-date">{{ $latestDocument?->uploaded_at ? $latestDocument->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}</span>
                                </td>
                                <td>
                                    <a
                                        href="{{ route('nadai-management.show', ['office' => $row['city_municipality']]) }}"
                                        class="nadai-index-link nadai-index-link-icon"
                                        title="View profile"
                                        aria-label="View profile"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="nadai-index-empty">
                                        <i class="fas fa-inbox"></i>
                                        <p class="nadai-index-empty-title">No offices found</p>
                                        <p class="nadai-index-empty-copy">Try clearing the current filters or selecting a different province or municipality.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($officeRows->count() > 0)
                <div class="nadai-index-pagination">
                    <div class="nadai-index-pagination-meta">
                        <span>
                            Page {{ $officeRows->currentPage() }} of {{ $officeRows->lastPage() }} ·
                            Showing {{ $officeRows->firstItem() ?? 0 }}-{{ $officeRows->lastItem() ?? 0 }} of {{ $officeRows->total() }}
                        </span>
                        <form method="GET" style="display: inline-flex; align-items: center; gap: 8px;">
                            @foreach (request()->except(['page', 'per_page']) as $queryKey => $queryValue)
                                @if (is_array($queryValue))
                                    @foreach ($queryValue as $nestedValue)
                                        <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                @endif
                            @endforeach
                            <select name="per_page" onchange="this.form.submit()" aria-label="Rows per page" title="Rows per page" class="nadai-index-select" style="min-height: 38px; padding-right: 34px;">
                                @foreach ([10, 15, 25, 50] as $option)
                                    <option value="{{ $option }}" @selected((int) ($perPage ?? 15) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="nadai-index-pagination-nav">
                        @if ($officeRows->onFirstPage())
                            <span class="nadai-index-pagination-disabled">
                                <i class="fas fa-chevron-left"></i>
                                Back
                            </span>
                        @else
                            <a href="{{ $officeRows->previousPageUrl() }}" class="nadai-index-pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                                Back
                            </a>
                        @endif

                        @if ($officeRows->hasMorePages())
                            <a href="{{ $officeRows->nextPageUrl() }}" class="nadai-index-pagination-btn nadai-index-pagination-btn-primary">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="nadai-index-pagination-disabled">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>

<script>
    const nadaiProvinceMunicipalities = @json($provinceMunicipalities);
    const nadaiProvinceFilter = document.getElementById('nadai-filter-province');
    const nadaiCityFilter = document.getElementById('nadai-filter-city');
    const nadaiSelectedCity = @json((string) ($activeFilters['city'] ?? ''));

    function rebuildNadaiIndexCityOptions(selectedProvince, selectedCity) {
        if (!nadaiCityFilter) {
            return;
        }

        const values = selectedProvince && nadaiProvinceMunicipalities[selectedProvince]
            ? nadaiProvinceMunicipalities[selectedProvince]
            : Object.values(nadaiProvinceMunicipalities).flat();

        const normalizedValues = [...new Set(
            (values || [])
                .map((value) => String(value || '').trim())
                .filter(Boolean)
                .sort((left, right) => left.localeCompare(right))
        )];

        nadaiCityFilter.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = 'All Cities / Municipalities';
        nadaiCityFilter.appendChild(placeholderOption);

        normalizedValues.forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            option.selected = value === selectedCity;
            nadaiCityFilter.appendChild(option);
        });
    }

    if (nadaiProvinceFilter) {
        nadaiProvinceFilter.addEventListener('change', function () {
            rebuildNadaiIndexCityOptions(this.value, '');
        });

        rebuildNadaiIndexCityOptions(nadaiProvinceFilter.value, nadaiSelectedCity);
    }
</script>
@endsection
