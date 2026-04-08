@extends('layouts.dashboard')

@section('title', 'SWA- Annex F')
@section('page-title', 'SWA- Annex F')

@section('content')
    @php
        $activeFilters = array_merge([
            'search' => '',
            'province' => '',
            'city' => '',
            'funding_year' => '',
            'level' => '',
            'type' => '',
            'status' => 'ongoing',
        ], $filters ?? []);

        $currentSortBy = $sortBy ?? request('sort_by', 'funding_year');
        $currentSortDir = strtolower((string) ($sortDir ?? request('sort_dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $nextSortDirection = function (string $column, string $defaultDirection = 'asc') use ($currentSortBy, $currentSortDir): string {
            if ($currentSortBy === $column) {
                return $currentSortDir === 'asc' ? 'desc' : 'asc';
            }

            return $defaultDirection;
        };

        $sortIndicator = function (string $column) use ($currentSortBy, $currentSortDir): string {
            if ($currentSortBy !== $column) {
                return '';
            }

            return $currentSortDir === 'asc' ? '▲' : '▼';
        };

        $sortUrl = function (string $column, string $defaultDirection = 'asc') use ($nextSortDirection) {
            $query = array_merge(request()->query(), [
                'sort_by' => $column,
                'sort_dir' => $nextSortDirection($column, $defaultDirection),
            ]);
            unset($query['page']);

            return route('reports.monthly.swa-annex-f', $query);
        };

        $statusBadgeClass = function ($value) {
            $normalized = strtolower(trim((string) $value));

            if ($normalized === 'completed') {
                return 'sglgif-pill--complete';
            }

            if ($normalized === 'ongoing') {
                return 'sglgif-pill--ongoing';
            }

            if ($normalized === '' || $normalized === '-') {
                return 'sglgif-pill--muted';
            }

            return 'sglgif-pill--default';
        };

        $renderPercentMeter = function ($value, string $tone = 'primary') {
            if (!is_numeric($value)) {
                return '<span class="sglgif-table-null">-</span>';
            }

            $normalized = max(0, min(100, (float) $value));

            return '
                <div class="sglgif-meter">
                    <span>' . e(number_format($normalized, 2) . '%') . '</span>
                    <div class="sglgif-meter-track">
                        <div class="sglgif-meter-fill sglgif-meter-fill--' . e($tone) . '" style="width: ' . number_format($normalized, 2, '.', '') . '%;"></div>
                    </div>
                </div>
            ';
        };
    @endphp

    <div class="content-header">
        <h1>SWA- Annex F</h1>
        <p>
            Ongoing SGLGIF projects prepared for Annex F submission review.
            @if($latestUpdateAt)
                Snapshot refreshed {{ \Illuminate\Support\Carbon::parse($latestUpdateAt)->format('F j, Y g:i A') }}.
            @endif
        </p>
    </div>

    <div class="sglgif-table-page">
        <section class="sglgif-shell-card sglgif-shell-card--hero">
            <div class="sglgif-shell-copy">
                <h2>Ongoing SGLGIF Records</h2>
                <p>{{ number_format((int) ($totalProjects ?? 0)) }} ongoing project rows in the current Annex F scope.</p>
            </div>
            <div class="sglgif-shell-actions">
                <a href="{{ route('projects.sglgif.table', request()->query()) }}" class="sglgif-action-btn sglgif-action-btn--accent">
                    <i class="fas fa-table" aria-hidden="true"></i>
                    <span>Open SGLGIF Table</span>
                </a>
            </div>
        </section>

        <form method="GET" action="{{ route('reports.monthly.swa-annex-f') }}" class="sglgif-shell-card sglgif-filter-card">
            <input type="hidden" name="sort_by" value="{{ $currentSortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $currentSortDir }}">

            <div class="sglgif-filter-grid">
                <div class="sglgif-filter-field sglgif-filter-field--search">
                    <label for="swa-search">Search</label>
                    <input id="swa-search" type="text" name="search" value="{{ $activeFilters['search'] }}" placeholder="Project code, title, province, LGU, category">
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-province">Province</label>
                    <select id="swa-province" name="province">
                        <option value="">All</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" @selected((string) $activeFilters['province'] === (string) $province)>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-city">City/Municipality</label>
                    <select id="swa-city" name="city">
                        <option value="">All</option>
                        @foreach($cityOptions as $city)
                            <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-year">Funding Year</label>
                    <select id="swa-year" name="funding_year">
                        <option value="">All</option>
                        @foreach($fundingYears as $year)
                            <option value="{{ $year }}" @selected((string) $activeFilters['funding_year'] === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-level">Level</label>
                    <select id="swa-level" name="level">
                        <option value="">All</option>
                        @foreach($levelOptions as $level)
                            <option value="{{ $level }}" @selected((string) $activeFilters['level'] === (string) $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-type">Project Type</label>
                    <select id="swa-type" name="type">
                        <option value="">All</option>
                        @foreach($typeOptions as $type)
                            <option value="{{ $type }}" @selected((string) $activeFilters['type'] === (string) $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sglgif-filter-field">
                    <label for="swa-per-page">Rows</label>
                    <select id="swa-per-page" name="per_page">
                        @foreach([10, 15, 25, 50] as $option)
                            <option value="{{ $option }}" @selected((int) ($perPage ?? 15) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="sglgif-filter-actions">
                <button type="submit" class="sglgif-action-btn sglgif-action-btn--primary">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    <span>Apply Filters</span>
                </button>
                <a href="{{ route('reports.monthly.swa-annex-f') }}" class="sglgif-action-btn sglgif-action-btn--muted">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>

        <section class="sglgif-shell-card sglgif-table-card">
            @if($projects->isEmpty())
                <div class="sglgif-empty-state">
                    <i class="fas fa-table" aria-hidden="true"></i>
                    <strong>No ongoing SGLGIF records found.</strong>
                    <span>Adjust the current filters or reload the imported SGLGIF dataset.</span>
                </div>
            @else
                <div class="sglgif-table-wrap">
                    <table class="sglgif-table sglgif-table--full">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ $sortUrl('project_title') }}" class="sglgif-sort-link">
                                        <span>Project</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('project_title') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('province') }}" class="sglgif-sort-link">
                                        <span>Province</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('province') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('city') }}" class="sglgif-sort-link">
                                        <span>LGU</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('city') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('funding_year', 'desc') }}" class="sglgif-sort-link">
                                        <span>FY</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('funding_year') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('level') }}" class="sglgif-sort-link">
                                        <span>Level</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('level') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('type') }}" class="sglgif-sort-link">
                                        <span>Type</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('type') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('category') }}" class="sglgif-sort-link">
                                        <span>Category</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('category') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('subsidy', 'desc') }}" class="sglgif-sort-link">
                                        <span>Subsidy</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('subsidy') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('project_cost', 'desc') }}" class="sglgif-sort-link">
                                        <span>Project Cost</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('project_cost') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('financial', 'desc') }}" class="sglgif-sort-link">
                                        <span>Financial</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('financial') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('physical', 'desc') }}" class="sglgif-sort-link">
                                        <span>Physical</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('physical') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('attachment', 'desc') }}" class="sglgif-sort-link">
                                        <span>Attachment</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('attachment') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('overall', 'desc') }}" class="sglgif-sort-link">
                                        <span>Overall</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('overall') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('status') }}" class="sglgif-sort-link">
                                        <span>Status</span>
                                        <span class="sglgif-sort-indicator">{{ $sortIndicator('status') }}</span>
                                    </a>
                                </th>

                                <th>Submission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $row)
                                @php
                                    $projectTitle = trim((string) ($row['project_title'] ?? '')) ?: 'Untitled Project';
                                    $submissionOffice = trim((string) ($row['submission_office'] ?? ''));
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $projectTitle }}</strong>
                                        <div class="sglgif-subline">{{ $row['project_code'] !== '' ? $row['project_code'] : '-' }}</div>
                                    </td>
                                    <td>{{ $row['province'] !== '' ? $row['province'] : '-' }}</td>
                                    <td>{{ $row['city_municipality'] !== '' ? $row['city_municipality'] : '-' }}</td>
                                    <td>{{ $row['funding_year'] !== '' ? $row['funding_year'] : '-' }}</td>
                                    <td>
                                        <span class="sglgif-pill sglgif-pill--muted">{{ $row['sglgif_level'] !== '' ? $row['sglgif_level'] : '-' }}</span>
                                    </td>
                                    <td>{{ $row['type_of_project'] !== '' ? $row['type_of_project'] : '-' }}</td>
                                    <td>{{ $row['sub_type_of_project'] !== '' ? $row['sub_type_of_project'] : '-' }}</td>
                                    <td class="sglgif-table-number">&#8369; {{ number_format((float) ($row['subsidy_value'] ?? 0), 2) }}</td>
                                    <td class="sglgif-table-number">&#8369; {{ number_format((float) ($row['project_cost_value'] ?? 0), 2) }}</td>
                                    <td>{!! $renderPercentMeter($row['financial_pct'] ?? null, 'primary') !!}</td>
                                    <td>{!! $renderPercentMeter($row['physical_pct'] ?? null, 'secondary') !!}</td>
                                    <td>{!! $renderPercentMeter($row['attachment_pct'] ?? null, 'tertiary') !!}</td>
                                    <td>{!! $renderPercentMeter($row['overall_pct'] ?? null, 'quaternary') !!}</td>
                                    <td>
                                        <span class="sglgif-pill {{ $statusBadgeClass($row['status'] ?? '') }}">
                                            {{ trim((string) ($row['status'] ?? '')) !== '' ? $row['status'] : '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($submissionOffice !== '')
                                            <a href="{{ route('reports.monthly.swa-annex-f.edit', ['office' => $submissionOffice, 'year' => $reportingYear]) }}" class="sglgif-action-btn sglgif-action-btn--primary sglgif-action-btn--table">
                                                <i class="fas fa-upload" aria-hidden="true"></i>
                                                <span>Submit</span>
                                            </a>
                                        @else
                                            <span class="sglgif-pill sglgif-pill--muted">Unavailable</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sglgif-pagination-shell">
                    {{ $projects->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </div>

    <style>
        .sglgif-table-page {
            display: grid;
            gap: 18px;
        }

        .sglgif-shell-card {
            background: #ffffff;
            border: 1px solid #dbe4ef;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .sglgif-shell-card--hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            background:
                radial-gradient(circle at top right, rgba(255, 222, 21, 0.20), transparent 26%),
                radial-gradient(circle at bottom left, rgba(0, 44, 118, 0.10), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .sglgif-shell-copy h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
        }

        .sglgif-shell-copy p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .sglgif-shell-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sglgif-filter-card {
            padding: 18px 20px;
        }

        .sglgif-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sglgif-filter-field {
            display: grid;
            gap: 6px;
        }

        .sglgif-filter-field--search {
            grid-column: span 2;
        }

        .sglgif-filter-field label {
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .sglgif-filter-field input,
        .sglgif-filter-field select {
            width: 100%;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 13px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .sglgif-filter-field input:focus,
        .sglgif-filter-field select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background: #ffffff;
        }

        .sglgif-filter-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .sglgif-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid transparent;
            border-radius: 999px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.03em;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .sglgif-action-btn:hover {
            transform: translateY(-1px);
        }

        .sglgif-action-btn--primary {
            background: linear-gradient(135deg, #002c76 0%, #0a4ba8 100%);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.20);
        }

        .sglgif-action-btn--accent {
            background: linear-gradient(135deg, #facc15 0%, #f59e0b 100%);
            color: #1f2937;
            box-shadow: 0 12px 24px rgba(245, 158, 11, 0.18);
        }

        .sglgif-action-btn--muted {
            border-color: #cbd5e1;
            background: #ffffff;
            color: #334155;
        }

        .sglgif-action-btn--table {
            padding: 8px 12px;
            font-size: 11px;
            box-shadow: none;
            white-space: nowrap;
        }

        .sglgif-table-card {
            overflow: hidden;
        }

        .sglgif-table-wrap {
            overflow: auto;
        }

        .sglgif-table {
            width: 100%;
            min-width: 1560px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .sglgif-table th,
        .sglgif-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
            color: #334155;
            background: #ffffff;
        }

        .sglgif-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .sglgif-table tbody tr:hover td {
            background: #f8fbff;
        }

        .sglgif-sort-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: inherit;
            text-decoration: none;
        }

        .sglgif-sort-indicator {
            font-size: 10px;
            color: #64748b;
            min-width: 10px;
            text-align: center;
        }

        .sglgif-subline {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        .sglgif-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .sglgif-pill--muted {
            color: #334155;
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        .sglgif-pill--complete {
            color: #166534;
            background: #dcfce7;
            border-color: #86efac;
        }

        .sglgif-pill--ongoing {
            color: #1d4ed8;
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .sglgif-pill--default {
            color: #92400e;
            background: #fef3c7;
            border-color: #fcd34d;
        }

        .sglgif-table-number {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .sglgif-meter {
            display: grid;
            gap: 6px;
            min-width: 118px;
        }

        .sglgif-meter span {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        .sglgif-meter-track {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .sglgif-meter-fill {
            height: 100%;
            border-radius: inherit;
        }

        .sglgif-meter-fill--primary {
            background: linear-gradient(90deg, #002c76, #0a4ba8);
        }

        .sglgif-meter-fill--secondary {
            background: linear-gradient(90deg, #0e7490, #14b8a6);
        }

        .sglgif-meter-fill--tertiary {
            background: linear-gradient(90deg, #c9282d, #e05358);
        }

        .sglgif-meter-fill--quaternary {
            background: linear-gradient(90deg, #f59e0b, #facc15);
        }

        .sglgif-table-null {
            color: #94a3b8;
            font-size: 11px;
        }

        .sglgif-empty-state {
            display: grid;
            justify-items: center;
            gap: 8px;
            padding: 48px 20px;
            text-align: center;
        }

        .sglgif-empty-state i {
            font-size: 30px;
            color: #94a3b8;
        }

        .sglgif-empty-state strong {
            color: #0f172a;
            font-size: 16px;
        }

        .sglgif-empty-state span {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .sglgif-pagination-shell {
            padding: 16px 18px 18px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        @media (max-width: 1100px) {
            .sglgif-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sglgif-filter-field--search {
                grid-column: span 2;
            }

            .sglgif-shell-card--hero {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 720px) {
            .sglgif-filter-grid {
                grid-template-columns: 1fr;
            }

            .sglgif-filter-field--search {
                grid-column: span 1;
            }

            .sglgif-filter-actions,
            .sglgif-shell-actions {
                width: 100%;
                justify-content: stretch;
            }

            .sglgif-action-btn {
                width: 100%;
            }

            .sglgif-action-btn--table {
                width: auto;
            }

            .sglgif-shell-card--hero,
            .sglgif-filter-card {
                padding: 16px;
            }

            .sglgif-table {
                min-width: 1260px;
            }
        }
    </style>
@endsection



