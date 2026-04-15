@extends('layouts.dashboard')

@section('title', 'Project At Risk')
@section('page-title', 'Project At Risk')

@section('content')
    @php
        $isRegionalDilg = strtoupper(trim((string) (Auth::user()->agency ?? ''))) === 'DILG'
            && strtolower(trim((string) (Auth::user()->province ?? ''))) === 'regional office';

        $activeFilters = array_merge([
            'search' => '',
            'province' => '',
            'city_municipality' => '',
            'funding_year' => '',
            'program' => '',
            'risk_level' => '',
            'aging_range' => '',
            'extraction_month' => '',
            'extraction_year' => '',
        ], $filters ?? []);

        $selectedExtractionMonth = $activeFilters['extraction_month'];
        $selectedExtractionYear = $activeFilters['extraction_year'];
        $allExtractionMonth = $selectedExtractionMonth === 'all';
        $allExtractionYear = $selectedExtractionYear === 'all';

        if ($selectedExtractionMonth === '' || $selectedExtractionMonth === null) {
            $selectedExtractionMonth = now()->month;
        }

        if ($selectedExtractionYear === '' || $selectedExtractionYear === null) {
            $selectedExtractionYear = now()->year;
        }

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $riskLegend = [
            ['label' => 'Ahead', 'range' => '(+ value of slippage)', 'color' => '#16a34a'],
            ['label' => 'On Schedule', 'range' => '(0%)', 'color' => '#3b82f6'],
            ['label' => 'No Risk', 'range' => '(-0.01% to -4.99% slippage)', 'color' => '#0ea5e9'],
            ['label' => 'Low Risk', 'range' => '(-5% to -9.99% slippage)', 'color' => '#f59e0b'],
            ['label' => 'Moderate Risk', 'range' => '(-10% to -14.99% slippage)', 'color' => '#f97316'],
            ['label' => 'High Risk', 'range' => '(-15% and higher slippage)', 'color' => '#dc2626'],
        ];

        $riskColors = [
            'ahead' => ['bg' => '#16a34a', 'text' => '#ffffff'],
            'on schedule' => ['bg' => '#3b82f6', 'text' => '#ffffff'],
            'no risk' => ['bg' => '#0ea5e9', 'text' => '#ffffff'],
            'low risk' => ['bg' => '#f59e0b', 'text' => '#ffffff'],
            'moderate risk' => ['bg' => '#f97316', 'text' => '#ffffff'],
            'high risk' => ['bg' => '#dc2626', 'text' => '#ffffff'],
        ];
    @endphp

    <div class="content-header">
        <h1>Project At Risk</h1>
        <p>Monitor projects flagged as at risk.</p>
    </div>

    @if (session('success'))
        <div class="risk-alert risk-alert--success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="risk-alert risk-alert--error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="risk-alert risk-alert--error">
            <ul class="risk-alert-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="risk-legend-card">
        <div class="risk-legend-header">
            <div>
                <h2>Risk as to Slippage</h2>
                <p>Quick guide for interpreting slippage-based risk levels.</p>
            </div>
        </div>
        <div class="risk-legend-grid">
            @foreach($riskLegend as $legend)
                <div class="risk-legend-item">
                    <span class="risk-legend-dot" style="background-color: {{ $legend['color'] }};"></span>
                    <div>
                        <strong>{{ $legend['label'] }}</strong>
                        <span>{{ $legend['range'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="risk-page-card">
        <div class="risk-card-header">
            <h2>Projects</h2>
            <div class="risk-card-actions">
                <a id="risk-export" href="{{ route('projects.at-risk.export', request()->query()) }}" class="risk-btn risk-btn--success" data-page-loading="false">
                    Export Excel
                </a>
            </div>
        </div>

        <details id="risk-filters-panel" class="risk-filters-panel" open>
            <summary class="risk-filters-summary">
                <span>Filters</span>
                <span class="risk-filters-summary-icon" aria-hidden="true"></span>
            </summary>
            <div class="risk-filters-body">
                <form id="risk-filters-form" method="GET" action="{{ route('projects.at-risk') }}" class="risk-filters-form">
                    <div class="risk-filter-field risk-filter-field--search">
                        <label for="risk-filter-search">Search</label>
                        <input id="risk-filter-search" name="search" type="text" value="{{ $activeFilters['search'] }}" placeholder="Search project code, LGU, barangay, title...">
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-province">Province</label>
                        <select id="risk-filter-province" name="province">
                            <option value="">All</option>
                            @foreach(($filterOptions['provinces'] ?? []) as $provinceOption)
                                <option value="{{ $provinceOption }}" {{ $activeFilters['province'] === $provinceOption ? 'selected' : '' }}>{{ $provinceOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-city">City/Municipality</label>
                        <select id="risk-filter-city" name="city_municipality">
                            <option value="">All</option>
                            @foreach(($filterOptions['cities'] ?? []) as $cityOption)
                                <option value="{{ $cityOption }}" {{ $activeFilters['city_municipality'] === $cityOption ? 'selected' : '' }}>{{ $cityOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-year">Funding Year</label>
                        <select id="risk-filter-year" name="funding_year">
                            <option value="">All</option>
                            @foreach(($filterOptions['funding_years'] ?? []) as $yearOption)
                                <option value="{{ $yearOption }}" {{ (string) $activeFilters['funding_year'] === (string) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-program">Program</label>
                        <select id="risk-filter-program" name="program">
                            <option value="">All</option>
                            @foreach(($filterOptions['programs'] ?? []) as $programOption)
                                <option value="{{ $programOption }}" {{ $activeFilters['program'] === $programOption ? 'selected' : '' }}>{{ $programOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-level">Risk Level</label>
                        <select id="risk-filter-level" name="risk_level">
                            <option value="">All</option>
                            @foreach(($filterOptions['risk_levels'] ?? []) as $riskOption)
                                <option value="{{ $riskOption }}" {{ $activeFilters['risk_level'] === $riskOption ? 'selected' : '' }}>{{ $riskOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-aging">Aging</label>
                        <select id="risk-filter-aging" name="aging_range">
                            <option value="">All</option>
                            <option value="gt_30" {{ $activeFilters['aging_range'] === 'gt_30' ? 'selected' : '' }}>Greater than 30</option>
                            <option value="between_11_30" {{ $activeFilters['aging_range'] === 'between_11_30' ? 'selected' : '' }}>11 to 30</option>
                            <option value="lte_10" {{ $activeFilters['aging_range'] === 'lte_10' ? 'selected' : '' }}>10 and below</option>
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-extraction-month">Extraction Month</label>
                        <select id="risk-filter-extraction-month" name="extraction_month">
                            <option value="all" {{ $allExtractionMonth ? 'selected' : '' }}>All</option>
                            @foreach(($filterOptions['extraction_months'] ?? []) as $monthOption)
                                @php $monthOption = (int) $monthOption; @endphp
                                <option value="{{ $monthOption }}" {{ !$allExtractionMonth && (int) $selectedExtractionMonth === $monthOption ? 'selected' : '' }}>
                                    {{ $monthNames[$monthOption] ?? $monthOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-field">
                        <label for="risk-filter-extraction-year">Extraction Year</label>
                        <select id="risk-filter-extraction-year" name="extraction_year">
                            <option value="all" {{ $allExtractionYear ? 'selected' : '' }}>All</option>
                            @foreach(($filterOptions['extraction_years'] ?? []) as $yearOption)
                                <option value="{{ $yearOption }}" {{ !$allExtractionYear && (string) $selectedExtractionYear === (string) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="risk-filter-actions">
                        <button type="submit" class="risk-btn risk-btn--primary">Apply</button>
                        <a href="{{ route('projects.at-risk') }}" class="risk-btn risk-btn--muted">Reset</a>
                    </div>
                </form>
            </div>
        </details>

        <div id="risk-data-stage" class="risk-data-stage" aria-live="polite">
        @if(($records ?? collect())->isEmpty())
            <p class="risk-empty-state">No records found.</p>
        @else
            <div class="risk-table-wrap" role="region" aria-label="Project At Risk table" tabindex="0">
                <table id="project-at-risk-table">
                    <thead>
                        <tr>
                            <th>Project Code</th>
                            <th>LGU</th>
                            <th>Barangay/s</th>
                            <th>Funding Year</th>
                            <th>Program</th>
                            <th>Project Title</th>
                            <th>National Subsidy</th>
                            <th>Slippage</th>
                            <th>Risk Level</th>
                            <th>Aging</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            @php
                                $city = trim((string) ($record->city_municipality ?? ''));
                                $province = trim((string) ($record->province ?? ''));
                                $region = trim((string) ($record->region ?? ''));
                                $riskLevel = trim((string) ($record->risk_level ?? ''));
                                $riskKey = strtolower($riskLevel);
                                $riskColor = $riskColors[$riskKey] ?? null;
                                $agingValue = $record->aging ?? null;
                                $agingNumber = is_numeric($agingValue) ? (float) $agingValue : null;
                                $agingColor = '#374151';

                                if ($agingNumber !== null) {
                                    if ($agingNumber > 30) {
                                        $agingColor = '#dc2626';
                                    } elseif ($agingNumber > 10) {
                                        $agingColor = '#f59e0b';
                                    } else {
                                        $agingColor = '#16a34a';
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="risk-table-code">{{ $record->project_code ?? '-' }}</td>
                                <td>
                                    @if($city !== '' || $province !== '' || $region !== '')
                                        <div class="risk-lgu-cell">
                                            @if($city !== '')
                                                <strong>{{ $city }}</strong>
                                            @endif
                                            @if($province !== '')
                                                <span>{{ $province }}</span>
                                            @endif
                                            @if($region !== '')
                                                <span>{{ $region }}</span>
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $record->barangays ?? '-' }}</td>
                                <td class="risk-align-center">{{ $record->funding_year ?? '-' }}</td>
                                <td>{{ $record->name_of_program ?? '-' }}</td>
                                <td class="risk-title-cell">{{ $record->project_title ?? '-' }}</td>
                                <td class="risk-align-center">{{ $record->national_subsidy !== null ? '₱' . number_format((float) $record->national_subsidy, 2) : '-' }}</td>
                                <td class="risk-align-center">{{ $record->slippage !== null && $record->slippage !== '' ? rtrim(rtrim(number_format((float) $record->slippage, 2), '0'), '.') . '%' : '-' }}</td>
                                <td class="risk-align-center">
                                    @if($riskLevel !== '')
                                        <span class="risk-badge" style="{{ $riskColor ? 'background-color: ' . $riskColor['bg'] . '; color: ' . $riskColor['text'] . ';' : 'background-color: #e5e7eb; color: #374151;' }}">
                                            {{ $riskLevel }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="risk-align-center">
                                    @if($agingNumber !== null)
                                        <span class="risk-badge" style="background-color: {{ $agingColor }}; color: #ffffff;">
                                            {{ rtrim(rtrim(number_format($agingNumber, 2), '0'), '.') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="risk-mobile-cards" aria-label="Project At Risk cards">
                @foreach($records as $record)
                    @php
                        $city = trim((string) ($record->city_municipality ?? ''));
                        $province = trim((string) ($record->province ?? ''));
                        $region = trim((string) ($record->region ?? ''));
                        $riskLevel = trim((string) ($record->risk_level ?? ''));
                        $riskKey = strtolower($riskLevel);
                        $riskColor = $riskColors[$riskKey] ?? null;
                        $agingValue = $record->aging ?? null;
                        $agingNumber = is_numeric($agingValue) ? (float) $agingValue : null;
                        $agingColor = '#374151';

                        if ($agingNumber !== null) {
                            if ($agingNumber > 30) {
                                $agingColor = '#dc2626';
                            } elseif ($agingNumber > 10) {
                                $agingColor = '#f59e0b';
                            } else {
                                $agingColor = '#16a34a';
                            }
                        }
                    @endphp
                    <details class="risk-mobile-card">
                        <summary class="risk-mobile-card-summary">
                            <div class="risk-mobile-card-summary-main">
                                <div class="risk-mobile-card-code">{{ $record->project_code ?? '-' }}</div>
                                <h3 class="risk-mobile-card-title">{{ $record->project_title ?? '-' }}</h3>
                                <div class="risk-mobile-card-subtitle">{{ $city !== '' ? $city : ($province !== '' ? $province : 'Unspecified LGU') }}</div>
                            </div>
                            <span class="risk-mobile-card-chevron" aria-hidden="true"></span>
                        </summary>
                        <div class="risk-mobile-card-body">
                            <div class="risk-mobile-card-body-inner">
                                <div class="risk-mobile-card-grid">
                                    <div class="risk-mobile-card-item"><span>LGU</span><strong>@if($city !== '' || $province !== '' || $region !== ''){{ implode(', ', array_filter([$city, $province, $region])) }}@else-@endif</strong></div>
                                    <div class="risk-mobile-card-item"><span>Barangay/s</span><strong>{{ $record->barangays ?? '-' }}</strong></div>
                                    <div class="risk-mobile-card-item"><span>Funding Year</span><strong>{{ $record->funding_year ?? '-' }}</strong></div>
                                    <div class="risk-mobile-card-item"><span>Program</span><strong>{{ $record->name_of_program ?? '-' }}</strong></div>
                                    <div class="risk-mobile-card-item"><span>National Subsidy</span><strong>{{ $record->national_subsidy !== null ? '₱' . number_format((float) $record->national_subsidy, 2) : '-' }}</strong></div>
                                    <div class="risk-mobile-card-item"><span>Slippage</span><strong>{{ $record->slippage !== null && $record->slippage !== '' ? rtrim(rtrim(number_format((float) $record->slippage, 2), '0'), '.') . '%' : '-' }}</strong></div>
                                    <div class="risk-mobile-card-item"><span>Risk Level</span><strong>@if($riskLevel !== '')<span class="risk-badge" style="{{ $riskColor ? 'background-color: ' . $riskColor['bg'] . '; color: ' . $riskColor['text'] . ';' : 'background-color: #e5e7eb; color: #374151;' }}">{{ $riskLevel }}</span>@else-@endif</strong></div>
                                    <div class="risk-mobile-card-item"><span>Aging</span><strong>@if($agingNumber !== null)<span class="risk-badge" style="background-color: {{ $agingColor }}; color: #ffffff;">{{ rtrim(rtrim(number_format($agingNumber, 2), '0'), '.') }}</span>@else-@endif</strong></div>
                                </div>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>

            @if($records->hasPages())
                <div class="risk-pagination">
                    <div class="risk-pagination-summary">
                        Page {{ $records->currentPage() }} of {{ $records->lastPage() }} ·
                        Showing {{ $records->firstItem() ?? 0 }}-{{ $records->lastItem() ?? 0 }} of {{ $records->total() }}
                    </div>
                    <div class="risk-pagination-actions">
                        @if($records->onFirstPage())
                            <span class="risk-page-link risk-page-link--disabled"><i class="fas fa-chevron-left"></i> Back</span>
                        @else
                            <a href="{{ $records->previousPageUrl() }}" class="risk-page-link risk-page-link--secondary"><i class="fas fa-chevron-left"></i> Back</a>
                        @endif

                        @if($records->hasMorePages())
                            <a href="{{ $records->nextPageUrl() }}" class="risk-page-link risk-page-link--primary">Next <i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="risk-page-link risk-page-link--disabled">Next <i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
        </div>
    </div>

    <style>
        .risk-alert {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .risk-alert--success {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .risk-alert--error {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .risk-alert-list {
            margin: 0;
            padding-left: 18px;
        }

        .risk-legend-card,
        .risk-page-card {
            margin-top: 16px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .risk-legend-card {
            padding: 20px;
        }

        .risk-legend-header {
            margin-bottom: 14px;
        }

        .risk-legend-header h2,
        .risk-card-header h2 {
            margin: 0;
            color: #002C76;
            font-size: 18px;
        }

        .risk-legend-header p {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .risk-legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px 14px;
        }

        .risk-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #374151;
            font-size: 12px;
            line-height: 1.5;
        }

        .risk-legend-item strong {
            display: block;
            color: #1f2937;
            font-size: 12px;
        }

        .risk-legend-item span {
            display: block;
        }

        .risk-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-top: 3px;
            flex: 0 0 auto;
        }

        .risk-page-card {
            padding: 24px;
        }

        .risk-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .risk-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .risk-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .risk-btn--primary {
            background: #002C76;
            color: #ffffff;
        }

        .risk-btn--success {
            background: #15803d;
            color: #ffffff;
        }

        .risk-btn--muted {
            background: #6b7280;
            color: #ffffff;
        }

        .risk-filters-panel {
            margin-bottom: 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            overflow: hidden;
        }

        .risk-filters-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            color: #1f2937;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            list-style: none;
            user-select: none;
        }

        .risk-filters-summary::-webkit-details-marker {
            display: none;
        }

        .risk-filters-summary-icon::before {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #e5e7eb;
            color: #374151;
            font-size: 16px;
            line-height: 1;
        }

        .risk-filters-panel[open] .risk-filters-summary {
            border-bottom: 1px solid #e5e7eb;
        }

        .risk-filters-panel[open] .risk-filters-summary-icon::before {
            content: '-';
        }

        .risk-filters-body {
            padding: 16px;
        }

        .risk-filters-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .risk-filter-field {
            min-width: 0;
        }

        .risk-filter-field--search {
            grid-column: span 2;
        }

        .risk-filter-field label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
        }

        .risk-filter-field input,
        .risk-filter-field select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            color: #111827;
            background: #ffffff;
        }

        .risk-filter-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .risk-table-wrap {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        .risk-data-stage {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 280ms ease, transform 320ms cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: opacity, transform;
        }

        .risk-data-stage.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        #project-at-risk-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            font-size: 12px;
        }

        #project-at-risk-table th,
        #project-at-risk-table td {
            padding: 10px 12px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        #project-at-risk-table thead th {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-align: center;
            border-bottom: 2px solid #d1d5db;
        }

        #project-at-risk-table tbody tr:hover {
            background: #f9fafb;
        }

        #project-at-risk-table th:nth-child(1),
        #project-at-risk-table td:nth-child(1) {
            min-width: 170px;
        }

        #project-at-risk-table th:nth-child(2),
        #project-at-risk-table td:nth-child(2),
        #project-at-risk-table th:nth-child(6),
        #project-at-risk-table td:nth-child(6) {
            min-width: 220px;
        }

        .risk-table-code {
            font-weight: 600;
            text-align: center;
        }

        .risk-lgu-cell {
            display: grid;
            gap: 2px;
            text-align: center;
        }

        .risk-lgu-cell strong {
            color: #1f2937;
        }

        .risk-title-cell,
        .risk-align-center {
            text-align: center;
        }

        .risk-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
        }

        .risk-mobile-cards {
            display: none;
            gap: 12px;
        }

        .risk-mobile-card {
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .risk-mobile-card-summary {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 14px;
            cursor: pointer;
            list-style: none;
        }

        .risk-mobile-card-summary::-webkit-details-marker {
            display: none;
        }

        .risk-mobile-card-summary-main {
            min-width: 0;
        }

        .risk-mobile-card-code {
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .risk-mobile-card-title {
            margin: 4px 0 0;
            color: #111827;
            font-size: 15px;
            line-height: 1.4;
        }

        .risk-mobile-card-subtitle {
            margin-top: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        .risk-mobile-card-chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #eff6ff;
            color: #0369a1;
            flex: 0 0 auto;
            transition: transform 0.25s ease;
        }

        .risk-mobile-card-chevron::before {
            content: '+';
            font-size: 18px;
            line-height: 1;
        }

        .risk-mobile-card[open] .risk-mobile-card-chevron {
            transform: rotate(135deg);
        }

        .risk-mobile-card-body {
            display: grid;
            grid-template-rows: 0fr;
            opacity: 0;
            transition: grid-template-rows 0.28s ease, opacity 0.22s ease;
        }

        .risk-mobile-card[open] .risk-mobile-card-body {
            grid-template-rows: 1fr;
            opacity: 1;
        }

        .risk-mobile-card-body-inner {
            min-height: 0;
            overflow: hidden;
            padding: 0 14px 14px;
        }

        .risk-mobile-card-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .risk-mobile-card-item {
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }

        .risk-mobile-card-item span {
            display: block;
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .risk-mobile-card-item strong {
            color: #111827;
            font-size: 13px;
            line-height: 1.5;
        }

        .risk-pagination {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .risk-pagination-summary {
            color: #6b7280;
            font-size: 12px;
        }

        .risk-pagination-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .risk-page-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .risk-page-link--primary {
            background: #002C76;
            border: 1px solid #002C76;
            color: #ffffff;
        }

        .risk-page-link--secondary {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        .risk-page-link--disabled {
            background: #e5e7eb;
            color: #9ca3af;
        }

        .risk-empty-state {
            margin: 0;
            padding: 40px 0;
            color: #6b7280;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .risk-filters-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .risk-filter-field--search {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .risk-page-card {
                padding: 16px;
            }

            .risk-card-header,
            .risk-card-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .risk-btn {
                width: 100%;
            }

            .risk-filters-summary,
            .risk-filters-body {
                padding-left: 12px;
                padding-right: 12px;
            }

            .risk-filters-form {
                grid-template-columns: 1fr;
            }

            .risk-filter-field--search {
                grid-column: span 1;
            }

            .risk-filter-actions {
                justify-content: stretch;
            }

            .risk-filter-actions .risk-btn {
                flex: 1 1 100%;
            }

            .risk-table-wrap {
                display: none;
            }

            .risk-mobile-cards {
                display: grid;
            }

            .risk-pagination {
                align-items: stretch;
            }

            .risk-pagination-actions,
            .risk-page-link {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .content-header h1 {
                font-size: 20px;
            }

            .content-header p {
                font-size: 12px;
            }

            .risk-page-card {
                padding: 14px;
            }

            .risk-mobile-card {
                border-radius: 10px;
            }

            .risk-mobile-card-summary {
                padding: 12px;
            }

            .risk-mobile-card-body-inner {
                padding: 0 12px 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .risk-data-stage {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filtersPanel = document.getElementById('risk-filters-panel');
            const filtersForm = document.getElementById('risk-filters-form');
            const searchInput = document.getElementById('risk-filter-search');
            const dataStage = document.getElementById('risk-data-stage');
            let searchTimer = null;

            if (dataStage) {
                requestAnimationFrame(function () {
                    dataStage.classList.add('is-visible');
                });
            }

            if (filtersPanel && window.matchMedia('(max-width: 768px)').matches) {
                filtersPanel.removeAttribute('open');
            }

            if (!filtersForm) {
                return;
            }

            function submitFilters() {
                filtersForm.requestSubmit();
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(submitFilters, 450);
                });
            }
        });
    </script>
@endsection
