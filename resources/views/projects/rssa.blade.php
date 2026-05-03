@extends('layouts.dashboard')

@section('title', 'Rapid Subproject Sustainability Assessment')
@section('page-title', 'Rapid Subproject Sustainability Assessment')

@section('content')
    @php
        $currency = function ($value) {
            if (!is_numeric($value)) {
                return '-';
            }

            return 'PHP ' . number_format((float) $value, 2);
        };

        $yesNoBadge = function ($value) {
            $normalized = strtolower(trim((string) $value));

            if (in_array($normalized, ['yes', 'y', 'true', '1', 'functional', 'operational'], true)) {
                return '<span class="rssa-badge rssa-badge--good">Yes</span>';
            }

            if ($normalized === '' || $normalized === '-') {
                return '<span class="rssa-badge rssa-badge--muted">-</span>';
            }

            return '<span class="rssa-badge rssa-badge--warn">No</span>';
        };

        $statusBadge = function ($value) {
            $text = trim((string) $value);
            $normalized = strtolower($text);

            if ($text === '') {
                return '<span class="rssa-badge rssa-badge--muted">-</span>';
            }

            if (str_contains($normalized, 'complete') || str_contains($normalized, 'operational')) {
                return '<span class="rssa-badge rssa-badge--good">' . e($text) . '</span>';
            }

            if (str_contains($normalized, 'non') || str_contains($normalized, 'not') || str_contains($normalized, 'issue')) {
                return '<span class="rssa-badge rssa-badge--warn">' . e($text) . '</span>';
            }

            return '<span class="rssa-badge rssa-badge--info">' . e($text) . '</span>';
        };
    @endphp

    @once
        <style>
            .rssa-page {
                display: grid;
                gap: 20px;
            }

            .rssa-hero {
                display: grid;
                grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.9fr);
                gap: 18px;
                padding: 24px;
                border-radius: 22px;
                background:
                    radial-gradient(circle at top right, rgba(249, 115, 22, 0.18), transparent 30%),
                    linear-gradient(135deg, #0b2a4a 0%, #0b3d91 58%, #1d4ed8 100%);
                box-shadow: 0 18px 38px rgba(11, 42, 74, 0.18);
                color: #ffffff;
            }

            .rssa-hero-copy {
                display: grid;
                gap: 10px;
                align-content: center;
            }

            .rssa-hero-kicker {
                margin: 0;
                color: rgba(255, 255, 255, 0.72);
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.22em;
                text-transform: uppercase;
            }

            .rssa-hero h1 {
                margin: 0;
                font-size: clamp(2rem, 3vw, 2.8rem);
                line-height: 1.02;
                color: #ffffff;
            }

            .rssa-hero-subcopy {
                margin: 0;
                max-width: 760px;
                color: rgba(255, 255, 255, 0.84);
                font-size: 14px;
                line-height: 1.65;
            }

            .rssa-hero-meta {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                align-content: start;
            }

            .rssa-hero-chip {
                display: grid;
                gap: 6px;
                padding: 14px 16px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.08);
                backdrop-filter: blur(8px);
            }

            .rssa-hero-chip-label {
                color: rgba(255, 255, 255, 0.68);
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            .rssa-hero-chip strong {
                color: #ffffff;
                font-size: 14px;
                line-height: 1.35;
                word-break: break-word;
            }

            .rssa-cards {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }

            .rssa-card {
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                border: 1px solid #dbe7ff;
                border-radius: 16px;
                padding: 18px;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            }

            .rssa-card--accent {
                background:
                    radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent 35%),
                    linear-gradient(180deg, #ffffff 0%, #f5f9ff 100%);
            }

            .rssa-card-label {
                color: #47607e;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .rssa-card-value {
                margin-top: 10px;
                color: #0f172a;
                font-size: clamp(1.55rem, 2.1vw, 1.95rem);
                font-weight: 800;
                line-height: 1.08;
                word-break: break-word;
            }

            .rssa-card-copy {
                margin-top: 8px;
                color: #64748b;
                font-size: 12px;
                line-height: 1.5;
            }

            .rssa-card-copy strong {
                color: #0b3d91;
            }

            .rssa-panel {
                background: #ffffff;
                border: 1px solid #dbe7ff;
                border-radius: 18px;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .rssa-panel-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 20px 22px 0;
                flex-wrap: wrap;
            }

            .rssa-panel-head h2 {
                margin: 0;
                color: #0b3d91;
                font-size: 19px;
            }

            .rssa-panel-head p {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 13px;
            }

            .rssa-filter-form {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
                padding: 18px 22px 22px;
                border-top: 1px solid #e2e8f0;
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            }

            .rssa-filter-field--search {
                grid-column: span 2;
            }

            .rssa-filter-field label {
                display: block;
                margin-bottom: 6px;
                color: #334155;
                font-size: 12px;
                font-weight: 700;
            }

            .rssa-filter-field input,
            .rssa-filter-field select {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 9px 11px;
                font-size: 13px;
                color: #0f172a;
                background: #fff;
                box-sizing: border-box;
            }

            .rssa-filter-actions {
                display: flex;
                gap: 10px;
                align-items: end;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .rssa-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 10px;
                padding: 10px 14px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                border: 1px solid transparent;
            }

            .rssa-button--primary {
                background: #0b3d91;
                color: #fff;
            }

            .rssa-button--ghost {
                background: #eff6ff;
                border-color: #bfdbfe;
                color: #0b3d91;
            }

            .rssa-table-wrap {
                overflow: auto;
                border-top: 1px solid #e2e8f0;
            }

            .rssa-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 1280px;
            }

            .rssa-table tbody tr:nth-child(even) {
                background: #fbfdff;
            }

            .rssa-table tbody tr:hover {
                background: #f1f6ff;
            }

            .rssa-table th,
            .rssa-table td {
                padding: 12px 14px;
                border-bottom: 1px solid #e2e8f0;
                vertical-align: top;
                text-align: left;
                font-size: 13px;
                color: #0f172a;
            }

            .rssa-table th {
                position: sticky;
                top: 0;
                z-index: 1;
                background: #f8fafc;
                color: #334155;
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .rssa-table th:first-child {
                z-index: 2;
            }

            .rssa-code {
                font-weight: 800;
                color: #0b3d91;
            }

            .rssa-title {
                font-weight: 700;
                color: #0f172a;
            }

            .rssa-subcopy {
                margin-top: 4px;
                color: #64748b;
                font-size: 12px;
                line-height: 1.5;
            }

            .rssa-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 5px 10px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.02em;
                white-space: nowrap;
            }

            .rssa-badge--good {
                background: #dcfce7;
                color: #166534;
            }

            .rssa-badge--warn {
                background: #fee2e2;
                color: #991b1b;
            }

            .rssa-badge--info {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .rssa-badge--muted {
                background: #e2e8f0;
                color: #475569;
            }

            .rssa-empty {
                padding: 42px 24px;
                text-align: center;
                color: #64748b;
            }

            .rssa-pagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 16px 22px 22px;
                flex-wrap: wrap;
            }

            .rssa-pagination-copy {
                color: #64748b;
                font-size: 12px;
            }

            .rssa-pagination-links {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            @media (max-width: 1200px) {
                .rssa-hero {
                    grid-template-columns: minmax(0, 1fr);
                }

                .rssa-cards {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .rssa-filter-form {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 700px) {
                .rssa-hero {
                    padding: 18px;
                }

                .rssa-hero-meta {
                    grid-template-columns: minmax(0, 1fr);
                }

                .rssa-cards,
                .rssa-filter-form {
                    grid-template-columns: minmax(0, 1fr);
                }

                .rssa-filter-field--search {
                    grid-column: auto;
                }

                .rssa-panel-head,
                .rssa-pagination {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .rssa-filter-form {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .rssa-filter-actions {
                    justify-content: flex-start;
                }
            }
        </style>
    @endonce

    <div class="rssa-page">
        <section class="rssa-hero">
            <div class="rssa-hero-copy">
                <p class="rssa-hero-kicker">Rapid Subproject Sustainability Assessment</p>
                <h1>RSSA Project Registry</h1>
                <p class="rssa-hero-subcopy">
                    Review imported RSSA records, assessment status, and sustainability indicators in a cleaner project registry view.
                    Filter by location, year, project type, and operating condition to inspect the current portfolio.
                </p>
            </div>
            <div class="rssa-hero-meta">
                <div class="rssa-hero-chip">
                    <span class="rssa-hero-chip-label">Province Scope</span>
                    <strong>{{ ($filters['province'] ?? '') !== '' ? $filters['province'] : 'All Provinces' }}</strong>
                </div>
                <div class="rssa-hero-chip">
                    <span class="rssa-hero-chip-label">City/Municipality</span>
                    <strong>{{ ($filters['city'] ?? '') !== '' ? $filters['city'] : 'All Cities/Municipalities' }}</strong>
                </div>
                <div class="rssa-hero-chip">
                    <span class="rssa-hero-chip-label">Funding Year</span>
                    <strong>{{ ($filters['funding_year'] ?? '') !== '' ? $filters['funding_year'] : 'All Years' }}</strong>
                </div>
                <div class="rssa-hero-chip">
                    <span class="rssa-hero-chip-label">Status Scope</span>
                    <strong>{{ ($filters['status'] ?? '') !== '' ? $filters['status'] : 'All Statuses' }}</strong>
                </div>
            </div>
        </section>

        <section class="rssa-cards">
            <article class="rssa-card rssa-card--accent">
                <div class="rssa-card-label">Projects</div>
                <div class="rssa-card-value">{{ number_format((int) $totalProjects) }}</div>
                <div class="rssa-card-copy">Records in the current filtered RSSA view.</div>
            </article>
            <article class="rssa-card">
                <div class="rssa-card-label">Assessed</div>
                <div class="rssa-card-value">{{ number_format((int) $assessedProjects) }}</div>
                <div class="rssa-card-copy">Projects with a recorded assessment date.</div>
            </article>
            <article class="rssa-card">
                <div class="rssa-card-label">Functional</div>
                <div class="rssa-card-value">{{ number_format((int) $functionalProjects) }}</div>
                <div class="rssa-card-copy">Projects marked functional in the imported sheet.</div>
            </article>
            <article class="rssa-card">
                <div class="rssa-card-label">Operational</div>
                <div class="rssa-card-value">{{ number_format((int) $operationalProjects) }}</div>
                <div class="rssa-card-copy">Projects marked operational in the imported sheet.</div>
            </article>
            <article class="rssa-card rssa-card--accent">
                <div class="rssa-card-label">Portfolio Snapshot</div>
                <div class="rssa-card-value" style="font-size: 22px;">{{ $currency($totalProjectCostAmount) }}</div>
                <div class="rssa-card-copy">
                    Latest assessment:
                    <strong>{{ $latestAssessmentDate !== null && $latestAssessmentDate !== '' ? e($latestAssessmentDate) : '-' }}</strong>
                </div>
            </article>
        </section>

        <section class="rssa-panel">
            <div class="rssa-panel-head">
                <div>
                    <h2>RSSA Project List</h2>
                    <p>Filter and review the imported Rapid Subproject Sustainability Assessment records.</p>
                </div>
                <a href="{{ route('system-management.upload-rssa') }}" class="rssa-button rssa-button--ghost">
                    <i class="fas fa-file-upload" aria-hidden="true"></i>
                    <span>Manage Imports</span>
                </a>
            </div>

            <form method="GET" action="{{ route('projects.rssa') }}" class="rssa-filter-form">
                <div class="rssa-filter-field rssa-filter-field--search">
                    <label for="rssa-search">Search</label>
                    <input id="rssa-search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Project code, title, province, city...">
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-year">Funding Year</label>
                    <select id="rssa-year" name="funding_year">
                        <option value="">All</option>
                        @foreach($fundingYears as $year)
                            <option value="{{ $year }}" {{ (string) ($filters['funding_year'] ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-province">Province</label>
                    <select id="rssa-province" name="province" data-selected="{{ $filters['province'] ?? '' }}">
                        <option value="">All</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" {{ (string) ($filters['province'] ?? '') === (string) $province ? 'selected' : '' }}>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-city">City/Municipality</label>
                    <select id="rssa-city" name="city" data-selected="{{ $filters['city'] ?? '' }}">
                        <option value="">All</option>
                        @foreach($cityOptions as $city)
                            <option value="{{ $city }}" {{ (string) ($filters['city'] ?? '') === (string) $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-type">Type of Project</label>
                    <select id="rssa-type" name="type">
                        <option value="">All</option>
                        @foreach($typeOptions as $type)
                            <option value="{{ $type }}" {{ (string) ($filters['type'] ?? '') === (string) $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-status">Status</label>
                    <select id="rssa-status" name="status">
                        <option value="">All</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" {{ (string) ($filters['status'] ?? '') === (string) $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-functional">Functional</label>
                    <select id="rssa-functional" name="functional">
                        <option value="">All</option>
                        @foreach($functionalOptions as $option)
                            <option value="{{ $option }}" {{ (string) ($filters['functional'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-operational">Operational</label>
                    <select id="rssa-operational" name="operational">
                        <option value="">All</option>
                        @foreach($operationalOptions as $option)
                            <option value="{{ $option }}" {{ (string) ($filters['operational'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-field">
                    <label for="rssa-per-page">Rows Per Page</label>
                    <select id="rssa-per-page" name="per_page">
                        @foreach([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rssa-filter-actions">
                    <button type="submit" class="rssa-button rssa-button--primary">Apply Filters</button>
                    <a href="{{ route('projects.rssa') }}" class="rssa-button rssa-button--ghost">Reset</a>
                </div>
            </form>

            @if($tableMissing ?? false)
                <div class="rssa-empty">
                    RSSA data table is not available yet. Run the RSSA migration or import the dataset first.
                </div>
            @elseif($rows->count() === 0)
                <div class="rssa-empty">
                    No RSSA records matched the current filters.
                </div>
            @else
                <div class="rssa-table-wrap">
                    <table class="rssa-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Location</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Total Cost</th>
                                <th>Completion</th>
                                <th>Assessed</th>
                                <th>Functional</th>
                                <th>Operational</th>
                                <th>Maintenance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>
                                        <div class="rssa-code">{{ $row->project_code ?: '-' }}</div>
                                        <div class="rssa-title">{{ $row->project_title ?: '-' }}</div>
                                        <div class="rssa-subcopy">{{ $row->program ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $row->province ?: '-' }}</div>
                                        <div class="rssa-subcopy">{{ $row->city_municipality ?: '-' }}</div>
                                        <div class="rssa-subcopy">{{ $row->region ?: '-' }}</div>
                                    </td>
                                    <td>{{ $row->funding_year ?: '-' }}</td>
                                    <td>{{ $row->type_of_project ?: '-' }}</td>
                                    <td>{!! $statusBadge($row->status) !!}</td>
                                    <td>{{ $currency(preg_replace('/[^0-9.\-]/', '', (string) ($row->total_project_cost ?? ''))) }}</td>
                                    <td>{{ $row->date_of_project_completion ?: '-' }}</td>
                                    <td>{{ $row->date_assessed ?: '-' }}</td>
                                    <td>
                                        {!! $yesNoBadge($row->if_functional_yes ?: $row->project_is_functional) !!}
                                        @if(trim((string) ($row->encoded_improvements ?? '')) !== '')
                                            <div class="rssa-subcopy">{{ $row->encoded_improvements }}</div>
                                        @elseif(trim((string) ($row->if_non_functional_state_the_reasons ?? '')) !== '')
                                            <div class="rssa-subcopy">{{ $row->if_non_functional_state_the_reasons }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {!! $yesNoBadge($row->if_operational_yes ?: $row->is_project_operational) !!}
                                        @if(trim((string) ($row->if_no_state_the_reason ?? '')) !== '')
                                            <div class="rssa-subcopy">{{ $row->if_no_state_the_reason }}</div>
                                        @elseif(trim((string) ($row->who_maintains_the_facility ?? '')) !== '')
                                            <div class="rssa-subcopy">{{ $row->who_maintains_the_facility }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $currency(preg_replace('/[^0-9.\-]/', '', (string) ($row->annual_maintenance_budget ?? ''))) }}</div>
                                        <div class="rssa-subcopy">Regularly maintained: {!! $yesNoBadge($row->is_regularly_maintained) !!}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rssa-pagination">
                    <div class="rssa-pagination-copy">
                        Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}.
                        Showing {{ $rows->firstItem() ?? 0 }}-{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }} rows.
                    </div>
                    <div class="rssa-pagination-links">
                        @if($rows->onFirstPage())
                            <span class="rssa-button rssa-button--ghost" style="opacity: .55; pointer-events: none;">Previous</span>
                        @else
                            <a href="{{ $rows->previousPageUrl() }}" class="rssa-button rssa-button--ghost">Previous</a>
                        @endif

                        @if($rows->hasMorePages())
                            <a href="{{ $rows->nextPageUrl() }}" class="rssa-button rssa-button--primary">Next</a>
                        @else
                            <span class="rssa-button rssa-button--ghost" style="opacity: .55; pointer-events: none;">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const provinceMunicipalities = @json($provinceMunicipalities ?? []);
            const provinceSelect = document.getElementById('rssa-province');
            const citySelect = document.getElementById('rssa-city');

            if (!provinceSelect || !citySelect) {
                return;
            }

            const renderCities = () => {
                const province = provinceSelect.value;
                const cities = province && Array.isArray(provinceMunicipalities[province]) ? provinceMunicipalities[province] : [];
                const selectedCity = citySelect.dataset.selected || '';

                citySelect.innerHTML = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'All';
                citySelect.appendChild(defaultOption);

                cities.forEach((city) => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    if (city === selectedCity) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });

                if (!province) {
                    citySelect.value = '';
                }
            };

            provinceSelect.addEventListener('change', () => {
                citySelect.dataset.selected = '';
                renderCities();
            });

            renderCities();
        })();
    </script>
@endsection
