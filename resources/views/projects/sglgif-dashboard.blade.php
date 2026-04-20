@extends('layouts.dashboard')

@section('title', 'SGLGIF Dashboard')
@section('page-title', 'SGLGIF Dashboard')

@section('content')
    @php
        $activeFilters = array_merge([
            'search' => '',
            'province' => '',
            'city' => '',
            'funding_year' => '',
            'level' => '',
            'type' => '',
            'status' => '',
        ], $filters ?? []);

        $topCategoryCount = (int) ($categoryBreakdown->first()['count'] ?? 0);
        $topCategoryFundingAmount = (float) ($categoryFundingBreakdown->first()['amount'] ?? 0);
        $topProvinceCount = (int) ($provinceBreakdown->first()['count'] ?? 0);
        $topProvinceFundingAmount = (float) ($provinceFundingBreakdown->first()['amount'] ?? 0);
        $topYearFundingAmount = (float) ($fundingYearBreakdown->max('amount') ?? 0);
        $progressTotal = (int) (($progressBandBreakdown ?? collect())->sum('count') ?? 0);
        $avgSubsidyPerProject = $totalProjects > 0 ? $totalSubsidyAmount / $totalProjects : 0;

        $progressSegments = [];
        if (($progressBandBreakdown ?? collect())->isNotEmpty() && $progressTotal > 0) {
            $gap = 1.2;
            $available = max(0, 100 - ($progressBandBreakdown->count() * $gap));
            $running = 0.0;

            foreach ($progressBandBreakdown as $item) {
                $count = (int) ($item['count'] ?? 0);
                if ($count < 1) {
                    continue;
                }

                $length = (($count / $progressTotal) * $available);
                $progressSegments[] = [
                    'start' => $running,
                    'length' => $length,
                    'label' => $item['label'],
                    'count' => $count,
                    'color' => $item['color'],
                    'bg' => $item['bg'],
                    'copy' => $item['copy'],
                ];
                $running += $length + $gap;
            }
        }

        $statusStyles = [
            'Completed' => ['color' => '#166534', 'bg' => '#dcfce7', 'border' => '#86efac'],
            'Ongoing' => ['color' => '#1d4ed8', 'bg' => '#dbeafe', 'border' => '#93c5fd'],
        ];

        $graphPalette = [
            'primary' => '#002C76',
            'secondary' => '#FFDE15',
            'tertiary' => '#C9282D',
            'quaternary' => '#0E7490',
            'quinary' => '#7C3AED',
            'senary' => '#EA580C',
        ];

        $graphGradients = [
            'primary' => 'linear-gradient(90deg, #002C76, #0A4BA8)',
            'secondary' => 'linear-gradient(90deg, #FFDE15, #E3B600)',
            'tertiary' => 'linear-gradient(90deg, #C9282D, #E05358)',
            'quaternary' => 'linear-gradient(90deg, #0E7490, #14B8A6)',
        ];

        $buildMixChart = function (string $ariaLabel, array $segments): array {
            $filteredSegments = array_values(array_filter($segments, function (array $segment) {
                return (int) ($segment['count'] ?? 0) > 0;
            }));

            $total = array_sum(array_map(function (array $segment) {
                return (int) ($segment['count'] ?? 0);
            }, $filteredSegments));

            $preparedSegments = [];
            $sweepDurationMs = 1400.0;
            $minSegmentDurationMs = 120.0;
            $calloutStepMs = 140.0;
            $sweepEndMs = 0.0;

            if ($total > 0) {
                $segmentCount = count($filteredSegments);
                $gapPercent = $segmentCount > 1 ? 0.8 : 0.0;
                $availablePercent = max(0.0, 100.0 - ($segmentCount * $gapPercent));
                $runningPercent = 0.0;

                foreach ($filteredSegments as $segment) {
                    $count = (int) ($segment['count'] ?? 0);
                    $segmentRawPercent = ($count / $total) * 100;
                    $segmentLength = ($segmentRawPercent / 100) * $availablePercent;
                    if ($segmentLength <= 0.01) {
                        continue;
                    }

                    $segmentDelayMs = ($runningPercent / 100) * $sweepDurationMs;
                    $segmentDurationMs = max(
                        $minSegmentDurationMs,
                        ($segmentLength / 100) * $sweepDurationMs
                    );
                    $sweepEndMs = max($sweepEndMs, $segmentDelayMs + $segmentDurationMs);

                    $preparedSegments[] = [
                        'start' => $runningPercent,
                        'length' => $segmentLength,
                        'color' => $segment['color'],
                        'label' => $segment['label'],
                        'count' => $count,
                        'percentage' => $segmentRawPercent,
                        'segmentDelayMs' => $segmentDelayMs,
                        'segmentDurationMs' => $segmentDurationMs,
                    ];

                    $runningPercent += $segmentLength + $gapPercent;
                }
            }

            return [
                'ariaLabel' => $ariaLabel,
                'segments' => $preparedSegments,
                'total' => $total,
                'calloutStartDelayMs' => $sweepEndMs + 120.0,
                'calloutStepMs' => $calloutStepMs,
            ];
        };

        $typeMixChart = $buildMixChart('SGLGIF project type split donut chart', [
            ['label' => 'Infrastructure', 'count' => $infrastructureCount, 'color' => $graphPalette['primary']],
            ['label' => 'Non-Infrastructure', 'count' => $nonInfrastructureCount, 'color' => $graphPalette['secondary']],
        ]);

        $levelMixChart = $buildMixChart('SGLGIF implementation level split donut chart', [
            ['label' => 'Municipality', 'count' => $municipalityCount, 'color' => $graphPalette['primary']],
            ['label' => 'Province', 'count' => $provinceLevelCount, 'color' => $graphPalette['secondary']],
            ['label' => 'City', 'count' => $cityLevelCount, 'color' => $graphPalette['tertiary']],
        ]);
    @endphp

    <div class="content-header">
        <h1>SGLG Incentive Fund Dashboard</h1>
        <p>
            Interactive portfolio infographics from the imported SGLGIF database.
            @if($latestUpdateAt)
                Snapshot refreshed {{ \Illuminate\Support\Carbon::parse($latestUpdateAt)->format('F j, Y g:i A') }}.
            @endif
        </p>
    </div>

    @include('projects.partials.project-section-tabs', ['activeTab' => $activeTab ?? 'sglgif'])

    <div class="dashboard-main-layout sglgif-dashboard-shell">
        <form method="GET" action="{{ route('projects.sglgif') }}" class="dashboard-card project-filter-form dashboard-main-layout-filter collapsed" style="background: #ffffff; padding: 16px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(15,23,42,0.08); margin-bottom: 0;">
            <button type="button" class="project-filter-toggle" onclick="toggleProjectFilter(this)" aria-expanded="false" aria-controls="sglgif-filter-body">
                <span class="sglgif-filter-title">
                    <i class="fas fa-filter" aria-hidden="true" style="font-size: 16px;"></i>
                    <span>PROJECT FILTER</span>
                </span>
                <span class="project-filter-chevron">
                    <i class="fas fa-chevron-up"></i>
                </span>
            </button>

            <div id="sglgif-filter-body" class="project-filter-body" style="max-height: 0px;">
                <div class="dashboard-filter-grid sglgif-filter-grid">
                    <div class="sglgif-filter-summary">
                        <strong>{{ number_format($totalProjects) }}</strong> projects in the current SGLGIF scope, covering
                        <strong>{{ number_format($uniqueLguCount) }}</strong> LGUs and
                        <strong>{{ number_format($uniqueProvinceCount) }}</strong> provinces.
                    </div>

                    <div class="sglgif-filter-field sglgif-filter-field--search">
                        <label for="sglgif-search">Search</label>
                        <input id="sglgif-search" type="text" name="search" value="{{ $activeFilters['search'] }}" placeholder="Project code, title, province, LGU, category">
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-province">Province</label>
                        <select id="sglgif-province" name="province">
                            <option value="">All</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" @selected((string) $activeFilters['province'] === (string) $province)>{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-city">City/Municipality</label>
                        <select id="sglgif-city" name="city" @disabled(($activeFilters['province'] ?? '') === '')>
                            <option value="">All</option>
                            @foreach($cityOptions as $city)
                                <option value="{{ $city }}" @selected((string) $activeFilters['city'] === (string) $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-year">Funding Year</label>
                        <select id="sglgif-year" name="funding_year">
                            <option value="">All</option>
                            @foreach($fundingYears as $year)
                                <option value="{{ $year }}" @selected((string) $activeFilters['funding_year'] === (string) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-level">Level</label>
                        <select id="sglgif-level" name="level">
                            <option value="">All</option>
                            @foreach($levelOptions as $level)
                                <option value="{{ $level }}" @selected((string) $activeFilters['level'] === (string) $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-type">Project Type</label>
                        <select id="sglgif-type" name="type">
                            <option value="">All</option>
                            @foreach($typeOptions as $type)
                                <option value="{{ $type }}" @selected((string) $activeFilters['type'] === (string) $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sglgif-filter-field">
                        <label for="sglgif-status">Project Status</label>
                        <select id="sglgif-status" name="status">
                            <option value="">All</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected((string) $activeFilters['status'] === (string) $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dashboard-filter-reset sglgif-filter-actions">
                        <button type="submit" class="sglgif-action-btn sglgif-action-btn--primary">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            Apply Filters
                        </button>
                        <a href="{{ route('projects.sglgif') }}" class="sglgif-action-btn sglgif-action-btn--muted">
                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                            Reset
                        </a>
                        <a href="{{ route('projects.sglgif.table', request()->query()) }}" class="sglgif-action-btn sglgif-action-btn--accent">
                            <i class="fas fa-table" aria-hidden="true"></i>
                            Open SGLGIF Table
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="dashboard-top-cards">
            <section class="dashboard-card sglgif-card">
                <div class="sglgif-card-head">
                    <div>
                        <h2>PROJECT MIX</h2>
                        <p>Track what the SGLGIF portfolio is funding, where it is concentrated, and which years dominate the current scope.</p>
                    </div>
                </div>

                <div class="sglgif-mix-chart-grid">
                    <article class="sglgif-mix-chart-card">
                        <div class="sglgif-mix-chart-head">
                            <div>
                                <h3>Project Types</h3>
                                <p>Infrastructure versus non-infrastructure projects in the current scope.</p>
                            </div>
                            <strong>{{ number_format($typeMixChart['total']) }} projects</strong>
                        </div>
                        <div class="sglgif-mix-chart-body">
                            @if(!empty($typeMixChart['segments']))
                                <div class="sglgif-mix-donut-layout">
                                    <div class="sglgif-mix-donut-wrap">
                                        <svg
                                            class="sglgif-mix-donut"
                                            viewBox="0 0 100 100"
                                            preserveAspectRatio="xMidYMid meet"
                                            aria-label="{{ $typeMixChart['ariaLabel'] }}"
                                        >
                                            <circle class="sglgif-mix-donut-track" cx="50" cy="50" r="36" pathLength="100"></circle>
                                            @foreach($typeMixChart['segments'] as $segment)
                                                @php
                                                    $segmentMidAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                                    $segmentMidRadians = deg2rad($segmentMidAngle);
                                                    $segmentHoverOffset = 2.2;
                                                    $segmentHoverX = $segmentHoverOffset * cos($segmentMidRadians);
                                                    $segmentHoverY = $segmentHoverOffset * sin($segmentMidRadians);
                                                @endphp
                                                <circle
                                                    class="sglgif-mix-donut-segment"
                                                    cx="50"
                                                    cy="50"
                                                    r="36"
                                                    pathLength="100"
                                                    @style([
                                                        '--segment-length: ' . number_format($segment['length'], 4, '.', ''),
                                                        '--segment-delay: ' . number_format($segment['segmentDelayMs'], 2, '.', '') . 'ms',
                                                        '--segment-duration: ' . number_format($segment['segmentDurationMs'], 2, '.', '') . 'ms',
                                                        '--segment-hover-x: ' . number_format($segmentHoverX, 3, '.', '') . 'px',
                                                        '--segment-hover-y: ' . number_format($segmentHoverY, 3, '.', '') . 'px',
                                                        'stroke: ' . $segment['color'],
                                                        'stroke-dashoffset: -' . number_format($segment['start'], 4, '.', ''),
                                                    ])
                                                >
                                                    <title>{{ $segment['label'] }}: {{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%</title>
                                                </circle>
                                            @endforeach
                                        </svg>
                                        <div class="sglgif-mix-donut-center">
                                            <strong>{{ number_format($typeMixChart['total']) }}</strong>
                                            <span>Total</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sglgif-mix-chart-legend" style="--sglgif-legend-columns: {{ max(1, count($typeMixChart['segments'])) }};">
                                    @foreach($typeMixChart['segments'] as $segment)
                                        <div class="sglgif-mix-chart-legend-item">
                                            <span class="sglgif-mix-chart-dot" style="background: {{ $segment['color'] }};"></span>
                                            <div>
                                                <strong>{{ $segment['label'] }}</strong>
                                                <p>{{ number_format((int) ($segment['count'] ?? 0)) }} projects · {{ number_format((float) ($segment['percentage'] ?? 0), 1) }}%</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="sglgif-empty">No type data for this filter set.</p>
                            @endif
                        </div>
                    </article>

                    <article class="sglgif-mix-chart-card">
                        <div class="sglgif-mix-chart-head">
                            <div>
                                <h3>Implementation Level</h3>
                                <p>Municipality, province, and city level distribution for filtered projects.</p>
                            </div>
                            <strong>{{ number_format($levelMixChart['total']) }} projects</strong>
                        </div>
                        <div class="sglgif-mix-chart-body">
                            @if(!empty($levelMixChart['segments']))
                                <div class="sglgif-mix-donut-layout">
                                    <div class="sglgif-mix-donut-wrap">
                                        <svg
                                            class="sglgif-mix-donut"
                                            viewBox="0 0 100 100"
                                            preserveAspectRatio="xMidYMid meet"
                                            aria-label="{{ $levelMixChart['ariaLabel'] }}"
                                        >
                                            <circle class="sglgif-mix-donut-track" cx="50" cy="50" r="36" pathLength="100"></circle>
                                            @foreach($levelMixChart['segments'] as $segment)
                                                @php
                                                    $segmentMidAngle = (($segment['start'] + ($segment['length'] / 2)) * 3.6) - 90;
                                                    $segmentMidRadians = deg2rad($segmentMidAngle);
                                                    $segmentHoverOffset = 2.2;
                                                    $segmentHoverX = $segmentHoverOffset * cos($segmentMidRadians);
                                                    $segmentHoverY = $segmentHoverOffset * sin($segmentMidRadians);
                                                @endphp
                                                <circle
                                                    class="sglgif-mix-donut-segment"
                                                    cx="50"
                                                    cy="50"
                                                    r="36"
                                                    pathLength="100"
                                                    @style([
                                                        '--segment-length: ' . number_format($segment['length'], 4, '.', ''),
                                                        '--segment-delay: ' . number_format($segment['segmentDelayMs'], 2, '.', '') . 'ms',
                                                        '--segment-duration: ' . number_format($segment['segmentDurationMs'], 2, '.', '') . 'ms',
                                                        '--segment-hover-x: ' . number_format($segmentHoverX, 3, '.', '') . 'px',
                                                        '--segment-hover-y: ' . number_format($segmentHoverY, 3, '.', '') . 'px',
                                                        'stroke: ' . $segment['color'],
                                                        'stroke-dashoffset: -' . number_format($segment['start'], 4, '.', ''),
                                                    ])
                                                >
                                                    <title>{{ $segment['label'] }}: {{ number_format((float) ($segment['percentage'] ?? 0), 2) }}%</title>
                                                </circle>
                                            @endforeach
                                        </svg>
                                        <div class="sglgif-mix-donut-center">
                                            <strong>{{ number_format($levelMixChart['total']) }}</strong>
                                            <span>Total</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sglgif-mix-chart-legend" style="--sglgif-legend-columns: {{ max(1, count($levelMixChart['segments'])) }};">
                                    @foreach($levelMixChart['segments'] as $segment)
                                        <div class="sglgif-mix-chart-legend-item">
                                            <span class="sglgif-mix-chart-dot" style="background: {{ $segment['color'] }};"></span>
                                            <div>
                                                <strong>{{ $segment['label'] }}</strong>
                                                <p>{{ number_format((int) ($segment['count'] ?? 0)) }} projects · {{ number_format((float) ($segment['percentage'] ?? 0), 1) }}%</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="sglgif-empty">No level data for this filter set.</p>
                            @endif
                        </div>
                    </article>
                </div>

            </section>

            <section class="dashboard-card sglgif-card">
                <div class="sglgif-card-head">
                    <div>
                        <h2>ACCOMPLISHMENT STATUS</h2>
                        <p>Read the portfolio through allocation, delivery, documentation, and overall accomplishment.</p>
                    </div>
                </div>

                <div class="sglgif-gauge-grid">
                    <article class="sglgif-gauge-card">
                        <div class="sglgif-gauge" data-sg-gauge data-sg-gauge-value="{{ max(0, min(100, (float) $averageFinancialPercent)) }}" style="--p: {{ max(0, min(100, (float) $averageFinancialPercent)) }}; --c: {{ $graphPalette['primary'] }};">
                            <span data-sg-gauge-label>{{ number_format($averageFinancialPercent, 2) }}%</span>
                        </div>
                        <h3>Financial</h3>
                        <p>Average financial accomplishment across filtered SGLGIF rows.</p>
                    </article>
                    <article class="sglgif-gauge-card">
                        <div class="sglgif-gauge" data-sg-gauge data-sg-gauge-value="{{ max(0, min(100, (float) $averagePhysicalPercent)) }}" style="--p: {{ max(0, min(100, (float) $averagePhysicalPercent)) }}; --c: {{ $graphPalette['secondary'] }};">
                            <span data-sg-gauge-label>{{ number_format($averagePhysicalPercent, 2) }}%</span>
                        </div>
                        <h3>Physical</h3>
                        <p>Average physical accomplishment from the uploaded project records.</p>
                    </article>
                    <article class="sglgif-gauge-card">
                        <div class="sglgif-gauge" data-sg-gauge data-sg-gauge-value="{{ max(0, min(100, (float) $averageAttachmentPercent)) }}" style="--p: {{ max(0, min(100, (float) $averageAttachmentPercent)) }}; --c: {{ $graphPalette['tertiary'] }};">
                            <span data-sg-gauge-label>{{ number_format($averageAttachmentPercent, 2) }}%</span>
                        </div>
                        <h3>Attachment</h3>
                        <p>Readiness of required attachments and supporting documents.</p>
                    </article>
                    <article class="sglgif-gauge-card">
                        <div class="sglgif-gauge" data-sg-gauge data-sg-gauge-value="{{ max(0, min(100, (float) $averageOverallPercent)) }}" style="--p: {{ max(0, min(100, (float) $averageOverallPercent)) }}; --c: {{ $graphPalette['quaternary'] }};">
                            <span data-sg-gauge-label>{{ number_format($averageOverallPercent, 2) }}%</span>
                        </div>
                        <h3>Overall</h3>
                        <p>Composite delivery score from the SGLGIF overall field.</p>
                    </article>
                </div>

            </section>

            <section class="dashboard-card sglgif-card">
                <div class="sglgif-dual-panel">
                    <div class="sglgif-panel">
                        <div class="sglgif-panel-head">
                            <h3>Project Status by Category</h3>
                            <div class="sglgif-switch" data-sg-switch-group="category-focus">
                                <button type="button" class="is-active" data-sg-switch-target="count">Count</button>
                                <button type="button" data-sg-switch-target="funding">Funding</button>
                            </div>
                        </div>

                        <div class="sglgif-switch-panel is-active" data-sg-switch-panel="category-focus:count">
                            @forelse($categoryBreakdown as $item)
                                @php $barWidth = $topCategoryCount > 0 ? round(($item['count'] / $topCategoryCount) * 100, 2) : 0; @endphp
                                <div class="sglgif-bar-row" data-sg-bar-animate="count">
                                    <div class="sglgif-bar-head"><span>{{ $item['label'] }}</span><strong data-sg-bar-number data-format="integer" data-value="{{ (int) ($item['count'] ?? 0) }}">{{ number_format($item['count']) }}</strong></div>
                                    <div class="sglgif-bar-track"><div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: {{ $graphGradients['primary'] }};"></div></div>
                                </div>
                            @empty
                                <p class="sglgif-empty">No category data for this filter set.</p>
                            @endforelse
                        </div>

                        <div class="sglgif-switch-panel" data-sg-switch-panel="category-focus:funding">
                            @forelse($categoryFundingBreakdown as $item)
                                @php $barWidth = $topCategoryFundingAmount > 0 ? round((($item['amount'] ?? 0) / $topCategoryFundingAmount) * 100, 2) : 0; @endphp
                                <div class="sglgif-bar-row" data-sg-bar-animate="funding">
                                    <div class="sglgif-bar-head"><span>{{ $item['label'] }}</span><strong data-sg-bar-number data-format="currency" data-value="{{ (float) ($item['amount'] ?? 0) }}">&#8369; {{ number_format((float) ($item['amount'] ?? 0), 2) }}</strong></div>
                                    <div class="sglgif-bar-track"><div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: {{ $graphGradients['secondary'] }};"></div></div>
                                    <div class="sglgif-note"><span data-sg-bar-number data-format="integer" data-value="{{ (int) ($item['count'] ?? 0) }}">{{ number_format((int) ($item['count'] ?? 0)) }}</span> projects</div>
                                </div>
                            @empty
                                <p class="sglgif-empty">No funding data for this filter set.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="sglgif-panel" data-sg-bar-container="funding-year">
                        <div class="sglgif-panel-head">
                            <h3>Funding by Year</h3>
                        </div>
                        @forelse($fundingYearBreakdown as $item)
                            @php $barWidth = $topYearFundingAmount > 0 ? round((($item['amount'] ?? 0) / $topYearFundingAmount) * 100, 2) : 0; @endphp
                            <div class="sglgif-bar-row" data-sg-bar-animate="funding">
                                <div class="sglgif-bar-head"><span>{{ $item['label'] }}</span><strong data-sg-bar-number data-format="currency" data-value="{{ (float) ($item['amount'] ?? 0) }}">&#8369; {{ number_format((float) ($item['amount'] ?? 0), 2) }}</strong></div>
                                <div class="sglgif-bar-track"><div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: {{ $graphGradients['tertiary'] }};"></div></div>
                                <div class="sglgif-note"><span data-sg-bar-number data-format="integer" data-value="{{ (int) ($item['count'] ?? 0) }}">{{ number_format((int) ($item['count'] ?? 0)) }}</span> projects</div>
                            </div>
                        @empty
                            <p class="sglgif-empty">No funding-year data for this filter set.</p>
                        @endforelse
                    </div>
                </div>
            </section>

        </div>

        <div class="dashboard-status-row">
            <section class="dashboard-card sglgif-card sglgif-status-card">
                <div class="sglgif-card-head sglgif-status-card-head">
                    <div class="sglgif-status-card-copy">
                        <h2>STATUS OF PROJECT</h2>
                        <p>Click a tile to open the filtered SGLGIF table directly from the dashboard.</p>
                    </div>
                </div>

                <div class="sglgif-status-grid">
                    @forelse($statusBreakdown as $item)
                        @php
                            $statusStyle = $statusStyles[$item['label']] ?? ['color' => '#334155', 'bg' => '#f8fafc', 'border' => '#cbd5e1'];
                            $statusUrl = route('projects.sglgif.table', array_merge(request()->query(), ['status' => $item['label']]));
                            $statusCount = (int) ($item['count'] ?? 0);
                            $statusPercent = $totalProjects > 0 ? round(($statusCount / $totalProjects) * 100, 1) : 0;
                            $statusMeta = [
                                'Completed' => [
                                    'icon' => 'fa-circle-check',
                                    'copy' => 'Projects already tagged as delivered.',
                                ],
                                'Ongoing' => [
                                    'icon' => 'fa-hourglass-half',
                                    'copy' => 'Projects still active in implementation.',
                                ],
                            ][$item['label']] ?? [
                                'icon' => 'fa-diagram-project',
                                'copy' => 'Projects within the current dashboard scope.',
                            ];
                        @endphp
                        <a href="{{ $statusUrl }}" class="sglgif-status-tile" style="--status-color: {{ $statusStyle['color'] }}; --status-bg: {{ $statusStyle['bg'] }}; --status-border: {{ $statusStyle['border'] }};">
                            <div class="sglgif-status-tile-top">
                                <span class="sglgif-status-icon" aria-hidden="true">
                                    <i class="fas {{ $statusMeta['icon'] }}"></i>
                                </span>
                                <span class="sglgif-status-pill">{{ number_format($statusPercent, 1) }}%</span>
                            </div>
                            <div class="sglgif-status-tile-main">
                                <span class="sglgif-status-label">{{ $item['label'] }}</span>
                                <strong>{{ number_format($statusCount) }}</strong>
                                <small>{{ $statusMeta['copy'] }}</small>
                            </div>
                            <div class="sglgif-status-meter" aria-hidden="true">
                                <div style="width: {{ $statusPercent }}%;"></div>
                            </div>
                            <div class="sglgif-status-footer">
                                <span>{{ number_format($statusPercent, 1) }}% of portfolio</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </div>
                        </a>
                    @empty
                        <p class="sglgif-empty">No status data for this filter set.</p>
                    @endforelse
                </div>

            </section>

            <section class="dashboard-card sglgif-card sglgif-financial-status-card">
                <div class="sglgif-financial-status-head">
                    <div>
                        <h3>FINANCIAL STATUS</h3>
                        <p>Allocation, project cost, and balance for the current dashboard scope.</p>
                    </div>
                </div>

                <div class="sglgif-financial-summary">
                    <div class="sglgif-financial-tile">
                        <span>Original Subsidy Allocation</span>
                        <strong>&#8369; {{ number_format($totalSubsidyAmount, 2) }}</strong>
                    </div>
                    <div class="sglgif-financial-tile">
                        <span>Total Project Cost</span>
                        <strong>&#8369; {{ number_format($totalProjectCostAmount, 2) }}</strong>
                    </div>
                    <div class="sglgif-financial-tile">
                        <span>Balance</span>
                        <strong>&#8369; {{ number_format($subsidyBalanceAmount, 2) }}</strong>
                    </div>
                </div>
            </section>

            <section class="dashboard-card sglgif-card">
                <div class="sglgif-card-head">
                    <div>
                        <h2>NUMBER OF PROJECTS</h2>
                        <p>Switch between provincial project counts and subsidy intensity.</p>
                    </div>
                </div>

                <div class="sglgif-switch" data-sg-switch-group="province-footprint">
                    <button type="button" class="is-active" data-sg-switch-target="count">BY PROVINCE</button>
                    <button type="button" data-sg-switch-target="funding">BY AMOUNT</button>
                </div>

                <div class="sglgif-switch-panel is-active" data-sg-switch-panel="province-footprint:count">
                    @forelse($provinceBreakdown as $item)
                        @php
                            $provinceLabel = (string) ($item['label'] ?? 'Unspecified');
                            $provinceModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $provinceLabel), '-');
                            $provinceModalId = 'sglgif-province-' . ($provinceModalKey !== '' ? $provinceModalKey : 'unspecified') . '-modal';
                        @endphp
                        @php $barWidth = $topProvinceCount > 0 ? round(($item['count'] / $topProvinceCount) * 100, 2) : 0; @endphp
                        <div
                            class="sglgif-bar-row sglgif-bar-trigger"
                            role="button"
                            tabindex="0"
                            aria-haspopup="dialog"
                            aria-controls="{{ $provinceModalId }}"
                            data-sglgif-modal-target="{{ $provinceModalId }}"
                            data-sg-bar-animate="count"
                            title="View {{ $provinceLabel }} projects"
                        >
                            <div class="sglgif-bar-head">
                                <span>{{ $item['label'] }}</span>
                                <strong data-sg-bar-number data-format="integer" data-value="{{ (int) ($item['count'] ?? 0) }}">{{ number_format($item['count']) }}</strong>
                            </div>
                            <div class="sglgif-bar-track">
                                <div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: {{ $graphGradients['primary'] }};"></div>
                            </div>
                        </div>
                    @empty
                        <p class="sglgif-empty">No province counts for this filter set.</p>
                    @endforelse
                </div>

                <div class="sglgif-switch-panel" data-sg-switch-panel="province-footprint:funding">
                    @forelse($provinceFundingBreakdown as $item)
                        @php
                            $provinceLabel = (string) ($item['label'] ?? 'Unspecified');
                            $provinceModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $provinceLabel), '-');
                            $provinceModalId = 'sglgif-province-' . ($provinceModalKey !== '' ? $provinceModalKey : 'unspecified') . '-modal';
                        @endphp
                        @php $barWidth = $topProvinceFundingAmount > 0 ? round((($item['amount'] ?? 0) / $topProvinceFundingAmount) * 100, 2) : 0; @endphp
                        <div
                            class="sglgif-bar-row sglgif-bar-trigger"
                            role="button"
                            tabindex="0"
                            aria-haspopup="dialog"
                            aria-controls="{{ $provinceModalId }}"
                            data-sglgif-modal-target="{{ $provinceModalId }}"
                            data-sg-bar-animate="funding"
                            title="View {{ $provinceLabel }} projects"
                        >
                            <div class="sglgif-bar-head">
                                <span>{{ $item['label'] }}</span>
                                <strong data-sg-bar-number data-format="currency" data-value="{{ (float) ($item['amount'] ?? 0) }}">&#8369; {{ number_format((float) ($item['amount'] ?? 0), 2) }}</strong>
                            </div>
                            <div class="sglgif-bar-track">
                                <div data-sg-bar-fill data-target-width="{{ $barWidth }}" style="width: {{ $barWidth }}%; background: {{ $graphGradients['secondary'] }};"></div>
                            </div>
                            <div class="sglgif-note"><span data-sg-bar-number data-format="integer" data-value="{{ (int) ($item['count'] ?? 0) }}">{{ number_format((int) ($item['count'] ?? 0)) }}</span> projects</div>
                        </div>
                    @empty
                        <p class="sglgif-empty">No province funding data for this filter set.</p>
                    @endforelse
                </div>

            </section>

            <section class="dashboard-card sglgif-card">
                <div class="sglgif-card-head">
                    <div>
                        <h2>ONGOING PROJECTS</h2>
                        <p>Financial, physical, attachment, and overall accomplishment across ongoing SGLGIF projects.</p>
                    </div>
                </div>

                <div class="sglgif-alert-grid">
                    <div class="sglgif-alert-card">
                        <span>Financial</span>
                        <strong>{{ number_format($ongoingAverageFinancialPercent, 2) }}%</strong>
                    </div>
                    <div class="sglgif-alert-card">
                        <span>Physical</span>
                        <strong>{{ number_format($ongoingAveragePhysicalPercent, 2) }}%</strong>
                    </div>
                    <div class="sglgif-alert-card">
                        <span>Attachment</span>
                        <strong>{{ number_format($ongoingAverageAttachmentPercent, 2) }}%</strong>
                    </div>
                    <div class="sglgif-alert-card">
                        <span>Overall</span>
                        <strong>{{ number_format($ongoingAverageOverallPercent, 2) }}%</strong>
                    </div>
                </div>

                <div class="sglgif-table-wrap">
                    <table class="sglgif-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>LGU</th>
                                <th>Financial</th>
                                <th>Physical</th>
                                <th>Attachment</th>
                                <th>Overall</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($watchlistRows as $row)
                                @php
                                    $projectModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (($row['project_code'] ?: $row['project_title']) ?: 'project')), '-');
                                    $projectModalId = 'sglgif-project-' . ($projectModalKey !== '' ? $projectModalKey : 'item') . '-' . $loop->index . '-modal';
                                    $projectDisplayTitle = $row['project_title'] ?: ($row['project_code'] ?: 'Untitled Project');
                                @endphp
                                <tr
                                    class="sglgif-project-row"
                                    tabindex="0"
                                    role="button"
                                    aria-controls="{{ $projectModalId }}"
                                    aria-label="Open project information for {{ $projectDisplayTitle }}"
                                    data-sglgif-modal-target="{{ $projectModalId }}"
                                >
                                    <td>
                                        <strong>{{ $projectDisplayTitle }}</strong>
                                        <div class="sglgif-subline">{{ $row['project_code'] }}</div>
                                    </td>
                                    <td>{{ $row['city_municipality'] ?: '-' }}</td>
                                    <td>{{ $row['financial_pct'] !== null ? number_format($row['financial_pct'], 2) . '%' : '-' }}</td>
                                    <td>{{ $row['physical_pct'] !== null ? number_format($row['physical_pct'], 2) . '%' : '-' }}</td>
                                    <td>{{ $row['attachment_pct'] !== null ? number_format($row['attachment_pct'], 2) . '%' : '-' }}</td>
                                    <td>{{ $row['overall_pct'] !== null ? number_format($row['overall_pct'], 2) . '%' : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="sglgif-empty-cell">No ongoing projects for the current filter set.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        @php
            $provinceModalLabels = collect($provinceBreakdown ?? collect())
                ->pluck('label')
                ->merge(collect($provinceFundingBreakdown ?? collect())->pluck('label'))
                ->filter(fn ($label) => trim((string) $label) !== '')
                ->unique()
                ->values();
        @endphp
        @foreach($provinceModalLabels as $provinceLabel)
            @php
                $provinceLabel = (string) $provinceLabel;
                $provinceModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $provinceLabel), '-');
                $provinceModalId = 'sglgif-province-' . ($provinceModalKey !== '' ? $provinceModalKey : 'unspecified') . '-modal';
                $provinceModalTitleId = $provinceModalId . '-title';
                $provinceProjects = collect(($provinceProjectsModalMap ?? collect())->get($provinceLabel, collect()));
                $provinceSubsidyTotal = (float) $provinceProjects->sum('subsidy_value');
            @endphp
            <div id="{{ $provinceModalId }}" class="sglgif-modal" aria-hidden="true">
                <div class="sglgif-modal-backdrop" data-sglgif-close-modal></div>
                <div class="sglgif-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $provinceModalTitleId }}">
                    <div class="sglgif-modal-header">
                        <h3 id="{{ $provinceModalTitleId }}">{{ $provinceLabel }} Projects</h3>
                        <button type="button" class="sglgif-modal-close" data-sglgif-close-modal aria-label="Close">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="sglgif-modal-subtitle">
                        {{ number_format($provinceProjects->count()) }} SGLGIF projects in {{ $provinceLabel }} for the current dashboard filters.
                        Total subsidy: &#8369; {{ number_format($provinceSubsidyTotal, 2) }}.
                    </p>
                    <div class="sglgif-modal-body">
                        @if ($provinceProjects->isNotEmpty())
                            <div class="sglgif-modal-table-wrap">
                                <table class="sglgif-modal-table">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>LGU</th>
                                            <th>Status</th>
                                            <th>Funding Year</th>
                                            <th>Subsidy</th>
                                            <th>Overall</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($provinceProjects as $projectRow)
                                            <tr>
                                                <td>
                                                    <strong>{{ $projectRow['project_title'] !== '' ? $projectRow['project_title'] : 'Untitled Project' }}</strong>
                                                    <div class="sglgif-subline">{{ $projectRow['project_code'] !== '' ? $projectRow['project_code'] : '-' }}</div>
                                                </td>
                                                <td>{{ $projectRow['city_municipality'] !== '' ? $projectRow['city_municipality'] : '-' }}</td>
                                                <td>{{ $projectRow['status'] !== '' ? $projectRow['status'] : '-' }}</td>
                                                <td>{{ $projectRow['funding_year'] !== '' ? $projectRow['funding_year'] : '-' }}</td>
                                                <td>&#8369; {{ number_format((float) ($projectRow['subsidy_value'] ?? 0), 2) }}</td>
                                                <td>
                                                    @if (($projectRow['overall_pct'] ?? null) !== null)
                                                        {{ number_format((float) $projectRow['overall_pct'], 2) }}%
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="sglgif-modal-empty-state">No projects found for this province.</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($watchlistRows as $row)
            @php
                $projectModalKey = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (($row['project_code'] ?: $row['project_title']) ?: 'project')), '-');
                $projectModalId = 'sglgif-project-' . ($projectModalKey !== '' ? $projectModalKey : 'item') . '-' . $loop->index . '-modal';
                $projectModalTitleId = $projectModalId . '-title';
                $projectDisplayTitle = $row['project_title'] ?: ($row['project_code'] ?: 'Untitled Project');
                $projectStatusLabel = $row['status'] !== '' ? $row['status'] : 'Status unavailable';
                $projectStatusStyle = $statusStyles[$projectStatusLabel] ?? ['color' => '#334155', 'bg' => '#e2e8f0', 'border' => '#cbd5e1'];
                $beneficiariesDisplay = $row['beneficiaries'] !== ''
                    ? (is_numeric($row['beneficiaries']) ? number_format((float) $row['beneficiaries']) : $row['beneficiaries'])
                    : '-';
                $projectSummaryLocation = collect([
                    $row['province'] !== '' ? $row['province'] : null,
                    $row['city_municipality'] !== '' ? $row['city_municipality'] : null,
                    $row['sglgif_level'] !== '' ? $row['sglgif_level'] . ' level' : null,
                ])->filter()->implode(' • ');
                $projectProgressMetrics = [
                    ['label' => 'Financial', 'value' => $row['financial_pct'], 'color' => '#2563eb'],
                    ['label' => 'Physical', 'value' => $row['physical_pct'], 'color' => '#0891b2'],
                    ['label' => 'Attachment', 'value' => $row['attachment_pct'], 'color' => '#d97706'],
                    ['label' => 'Overall', 'value' => $row['overall_pct'], 'color' => '#002C76'],
                ];
            @endphp
            <div id="{{ $projectModalId }}" class="sglgif-modal" aria-hidden="true">
                <div class="sglgif-modal-backdrop" data-sglgif-close-modal></div>
                <div class="sglgif-modal-dialog sglgif-project-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $projectModalTitleId }}">
                    <div class="sglgif-modal-header sglgif-project-modal-header">
                        <div class="sglgif-project-modal-head-copy">
                            <span
                                class="sglgif-project-status-badge"
                                @style([
                                    'color: ' . $projectStatusStyle['color'],
                                    'background: ' . $projectStatusStyle['bg'],
                                    'border-color: ' . $projectStatusStyle['border'],
                                ])
                            >
                                {{ $projectStatusLabel }}
                            </span>
                            <h3 id="{{ $projectModalTitleId }}">{{ $projectDisplayTitle }}</h3>
                            <p class="sglgif-project-modal-meta">
                                <span>Code: {{ $row['project_code'] !== '' ? $row['project_code'] : '-' }}</span>
                                <span>FY {{ $row['funding_year'] !== '' ? $row['funding_year'] : '-' }}</span>
                                <span>{{ $projectSummaryLocation !== '' ? $projectSummaryLocation : 'Location information unavailable' }}</span>
                            </p>
                        </div>
                        <button type="button" class="sglgif-modal-close" data-sglgif-close-modal aria-label="Close">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="sglgif-modal-body">
                        <div class="sglgif-project-modal-grid">
                            <section class="sglgif-project-modal-section sglgif-project-modal-section--summary">
                                <h4>Quick View</h4>
                                <div class="sglgif-project-summary-grid">
                                    <article class="sglgif-project-summary-card">
                                        <span>Overall</span>
                                        <strong>{{ $row['overall_pct'] !== null ? number_format((float) $row['overall_pct'], 2) . '%' : '-' }}</strong>
                                    </article>
                                    <article class="sglgif-project-summary-card">
                                        <span>National Subsidy</span>
                                        <strong>&#8369; {{ number_format((float) ($row['subsidy_value'] ?? 0), 2) }}</strong>
                                    </article>
                                    <article class="sglgif-project-summary-card">
                                        <span>Total Project Cost</span>
                                        <strong>&#8369; {{ number_format((float) ($row['project_cost_value'] ?? 0), 2) }}</strong>
                                    </article>
                                    <article class="sglgif-project-summary-card">
                                        <span>Beneficiaries</span>
                                        <strong>{{ $beneficiariesDisplay }}</strong>
                                    </article>
                                </div>
                            </section>
                            <section class="sglgif-project-modal-section sglgif-project-modal-section--details">
                                <h4>Project Details</h4>
                                <div class="sglgif-project-detail-grid">
                                    <div class="sglgif-project-detail-card">
                                        <span>Region</span>
                                        <strong>{{ $row['region'] !== '' ? $row['region'] : '-' }}</strong>
                                    </div>
                                    <div class="sglgif-project-detail-card">
                                        <span>Province</span>
                                        <strong>{{ $row['province'] !== '' ? $row['province'] : '-' }}</strong>
                                    </div>
                                    <div class="sglgif-project-detail-card">
                                        <span>LGU</span>
                                        <strong>{{ $row['city_municipality'] !== '' ? $row['city_municipality'] : '-' }}</strong>
                                    </div>
                                    <div class="sglgif-project-detail-card">
                                        <span>Implementation Level</span>
                                        <strong>{{ $row['sglgif_level'] !== '' ? $row['sglgif_level'] : '-' }}</strong>
                                    </div>
                                    <div class="sglgif-project-detail-card">
                                        <span>Project Type</span>
                                        <strong>{{ $row['type_of_project'] !== '' ? $row['type_of_project'] : '-' }}</strong>
                                    </div>
                                    <div class="sglgif-project-detail-card">
                                        <span>Category</span>
                                        <strong>{{ $row['sub_type_of_project'] !== '' ? $row['sub_type_of_project'] : '-' }}</strong>
                                    </div>
                                </div>
                            </section>
                            <section class="sglgif-project-modal-section sglgif-project-modal-section--accomplishment">
                                <h4>Accomplishment</h4>
                                <div class="sglgif-project-progress-list">
                                    @foreach($projectProgressMetrics as $metric)
                                        @php
                                            $metricValue = $metric['value'];
                                            $metricPercent = $metricValue !== null ? max(0, min(100, (float) $metricValue)) : 0;
                                        @endphp
                                        <article class="sglgif-project-progress-card">
                                            <div class="sglgif-project-progress-head">
                                                <span>{{ $metric['label'] }}</span>
                                                <strong>{{ $metricValue !== null ? number_format((float) $metricValue, 2) . '%' : '-' }}</strong>
                                            </div>
                                            <div class="sglgif-project-progress-track">
                                                <div
                                                    class="sglgif-project-progress-fill"
                                                    @style([
                                                        'width: ' . number_format($metricPercent, 2, '.', '') . '%',
                                                        'background: linear-gradient(90deg, ' . $metric['color'] . ', color-mix(in srgb, ' . $metric['color'] . ' 58%, white))',
                                                    ])
                                                ></div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .project-filter-toggle {
            width: 100%;
            border: none;
            background: transparent;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            text-align: left;
        }

        .sglgif-filter-title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .project-filter-chevron,
        .project-filter-body {
            transition: all 0.28s ease;
        }

        .project-filter-body {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
        }

        .project-filter-form.collapsed .project-filter-body {
            max-height: 0;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
        }

        .project-filter-form.collapsed .project-filter-chevron {
            transform: rotate(180deg);
        }

        .dashboard-main-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, 1fr);
            gap: 20px;
            align-items: start;
            padding: 24px;
            border: 1px solid #dbe4ff;
            border-radius: 18px;
            background:
                radial-gradient(circle at top left, rgba(255, 215, 128, 0.25), transparent 32%),
                linear-gradient(180deg, #fbfdff 0%, #f3f7fb 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            margin-bottom: 24px;
        }

        .dashboard-main-layout-filter {
            grid-column: 1 / -1;
        }

        .dashboard-main-layout > * {
            min-width: 0;
        }

        .dashboard-top-cards,
        .dashboard-status-row {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr;
        }

        .dashboard-status-row {
            gap: 16px;
            min-width: 0;
        }

        .dashboard-status-row .sglgif-card {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            padding: 16px 16px 14px;
            border-radius: 15px;
        }

        .dashboard-status-row .sglgif-card-head {
            margin-bottom: 12px;
        }

        .dashboard-status-row .sglgif-card-head p {
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.45;
        }

        .sglgif-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px 16px;
            align-items: end;
            margin-top: 18px;
        }

        .sglgif-filter-summary {
            grid-column: 1 / -1;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff6ff, #eef2ff);
            color: #1e3a8a;
            border: 1px solid #bfdbfe;
            font-size: 12px;
        }

        .sglgif-filter-field label {
            display: block;
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sglgif-filter-field input,
        .sglgif-filter-field select {
            width: 100%;
            height: 38px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background-color: #ffffff;
            color: #111827;
            padding: 0 10px;
            font-size: 12px;
        }

        .sglgif-filter-field--search {
            grid-column: span 2;
        }

        .sglgif-filter-actions {
            display: flex;
            align-items: end;
            grid-column: 1 / -1;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .sglgif-action-btn {
            min-height: 38px;
            min-width: 148px;
            width: auto;
            flex: 0 0 auto;
            border-radius: 10px;
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 0 14px;
            border: none;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .sglgif-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
        }

        .sglgif-action-btn--primary {
            background: linear-gradient(135deg, #0f4fa8, #082f76);
        }

        .sglgif-action-btn--muted {
            background: linear-gradient(135deg, #64748b, #475569);
        }

        .sglgif-action-btn--accent {
            background: linear-gradient(135deg, #0f766e, #115e59);
        }

        .sglgif-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            padding: 20px;
        }

        .sglgif-status-card {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.78), rgba(255, 255, 255, 0.46)),
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.42), transparent 42%);
            border: 1px solid rgba(255, 255, 255, 0.62);
            box-shadow:
                0 20px 40px rgba(15, 23, 42, 0.10),
                inset 0 1px 0 rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .sglgif-status-card::before {
            content: '';
            position: absolute;
            inset: -30% auto auto -18%;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.34), transparent 68%);
            pointer-events: none;
        }

        .sglgif-status-card > * {
            position: relative;
            z-index: 1;
        }

        .sglgif-status-card-head {
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .sglgif-status-card-copy {
            flex: 1 1 220px;
            min-width: 0;
        }

        .dashboard-status-row .sglgif-status-card-head {
            margin-bottom: 10px;
        }

        .sglgif-financial-status-card {
            position: relative;
            overflow: hidden;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.68);
            background:
                linear-gradient(160deg, rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.42)),
                radial-gradient(circle at top right, rgba(219, 234, 254, 0.46), transparent 68%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.82),
                0 14px 28px rgba(15, 23, 42, 0.07);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .sglgif-financial-status-card > * {
            position: relative;
            z-index: 1;
        }

        .sglgif-financial-status-head {
            margin-bottom: 10px;
        }

        .sglgif-financial-status-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sglgif-financial-status-head p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
        }

        .sglgif-mini-stat span,
        .sglgif-financial-tile span,
        .sglgif-alert-card span {
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sglgif-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .sglgif-card-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .sglgif-card-head p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
        }

        .sglgif-mix-chart-grid,
        .sglgif-alert-grid,
        .sglgif-status-grid,
        .sglgif-financial-summary {
            display: grid;
            gap: 12px;
        }

        .sglgif-alert-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .dashboard-status-row .sglgif-alert-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-status-row .sglgif-status-grid {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
        }

        .dashboard-status-row .sglgif-financial-summary {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            margin-top: 0;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }

        .sglgif-mix-chart-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .sglgif-status-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sglgif-financial-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 16px;
        }

        .sglgif-mix-chart-card,
        .sglgif-alert-card,
        .sglgif-financial-tile {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .sglgif-alert-card strong,
        .sglgif-financial-tile strong {
            display: block;
            margin-top: 10px;
            color: #0f172a;
            font-size: 24px;
            line-height: 1.12;
        }

        .dashboard-status-row .sglgif-financial-tile {
            min-width: 0;
            padding: 12px;
            border-radius: 12px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.88));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
        }

        .dashboard-status-row .sglgif-financial-tile span {
            font-size: 10px;
            line-height: 1.35;
        }

        .dashboard-status-row .sglgif-financial-tile strong {
            margin-top: 8px;
            font-size: 18px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .sglgif-mix-chart-card {
            padding: 18px;
            background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
            overflow: hidden;
            min-width: 0;
        }

        .sglgif-mix-chart-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .sglgif-mix-chart-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .sglgif-mix-chart-head p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
        }

        .sglgif-mix-chart-head > strong {
            color: #002C76;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .sglgif-mix-chart-body {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: start;
        }

        .sglgif-mix-chart-body > .sglgif-empty {
            grid-column: 1 / -1;
        }

        .sglgif-mix-donut-layout {
            display: grid;
            place-items: center;
            width: 100%;
            min-height: 240px;
            padding: 16px 14px;
            gap: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            overflow: visible;
        }

        .sglgif-mix-donut-wrap {
            position: relative;
            width: min(190px, 78%);
            max-width: 190px;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        .sglgif-mix-donut {
            width: 100%;
            height: 100%;
            display: block;
            overflow: visible;
        }

        .sglgif-mix-donut-track {
            fill: none;
            stroke: #ffffff;
            stroke-width: 20;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .sglgif-mix-donut-segment {
            fill: none;
            stroke-width: 20;
            stroke-linecap: butt;
            shape-rendering: geometricPrecision;
            cursor: default;
            filter: drop-shadow(0 1.4px 1.5px rgba(15, 23, 42, 0.24)) drop-shadow(0 0 3px rgba(15, 23, 42, 0.16));
            stroke-dasharray: 0 100;
            animation-name: sglgif-donut-sweep;
            animation-timing-function: linear;
            animation-fill-mode: forwards;
            animation-duration: var(--segment-duration, 0ms);
            animation-delay: var(--segment-delay, 0ms);
            transition: transform 180ms ease-out, filter 180ms ease-out;
            transform: translate(var(--segment-shift-x, 0px), var(--segment-shift-y, 0px)) rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .sglgif-mix-donut-segment:hover {
            --segment-shift-x: var(--segment-hover-x, 0px);
            --segment-shift-y: var(--segment-hover-y, 0px);
            filter: drop-shadow(0 2.2px 2.4px rgba(15, 23, 42, 0.26)) drop-shadow(0 0 4.5px rgba(15, 23, 42, 0.18));
        }

        .sglgif-mix-donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 84px;
            height: 84px;
            border-radius: 999px;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12), inset 0 0 0 1px rgba(226, 232, 240, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            z-index: 1;
        }

        .sglgif-mix-donut-center strong {
            display: block;
            color: #0f172a;
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
        }

        .sglgif-mix-donut-center span {
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @keyframes sglgif-donut-sweep {
            from {
                stroke-dasharray: 0 100;
            }

            to {
                stroke-dasharray: var(--segment-length, 0) 100;
            }
        }

        .sglgif-mix-chart-legend {
            display: grid;
            grid-template-columns: repeat(var(--sglgif-legend-columns, 1), minmax(0, 1fr));
            gap: 10px;
            min-width: 0;
        }

        .sglgif-mix-chart-legend-item {
            display: grid;
            grid-template-columns: 12px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 10px 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            min-width: 0;
        }

        .sglgif-mix-chart-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            margin-top: 3px;
        }

        .sglgif-mix-chart-legend-item strong {
            display: block;
            margin: 0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
        }

        .sglgif-mix-chart-legend-item p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
            word-break: break-word;
        }

        .sglgif-dual-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .sglgif-panel {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: #fbfdff;
        }

        .sglgif-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sglgif-panel-head h3 {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .sglgif-switch {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .dashboard-status-row .sglgif-switch {
            padding: 3px;
        }

        .sglgif-switch button {
            border: none;
            background: transparent;
            color: #1e3a8a;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .dashboard-status-row .sglgif-switch button {
            padding: 6px 10px;
            font-size: 10px;
        }

        .sglgif-switch button.is-active {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.25);
        }

        .sglgif-switch-panel {
            display: none;
        }

        .sglgif-switch-panel.is-active {
            display: block;
        }

        .sglgif-bar-row {
            margin-bottom: 10px;
        }

        .dashboard-status-row .sglgif-bar-row {
            margin-bottom: 8px;
        }

        .sglgif-bar-grid {
            display: grid;
            gap: 14px 16px;
        }

        .sglgif-bar-trigger {
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .dashboard-status-row .sglgif-bar-trigger {
            padding: 8px 10px;
        }

        .sglgif-bar-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
            border-color: #bfdbfe;
            background: #f8fbff;
        }

        .sglgif-bar-trigger:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .sglgif-bar-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4px;
            color: #334155;
            font-size: 12px;
        }

        .dashboard-status-row .sglgif-bar-head {
            margin-bottom: 3px;
            font-size: 11px;
        }

        .sglgif-bar-head strong {
            color: #0f172a;
        }

        .sglgif-bar-head span,
        .sglgif-table td strong,
        .sglgif-modal-table tbody td strong {
            overflow-wrap: anywhere;
        }

        .sglgif-bar-track {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .dashboard-status-row .sglgif-bar-track {
            height: 7px;
        }

        .sglgif-bar-track > div {
            height: 100%;
            border-radius: 999px;
        }

        .sglgif-note,
        .sglgif-subline {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
        }

        .dashboard-status-row .sglgif-note,
        .dashboard-status-row .sglgif-subline {
            margin-top: 3px;
            font-size: 10px;
        }

        .sglgif-gauge-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sglgif-gauge-card {
            text-align: center;
            padding: 16px 12px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #fbfdff;
        }

        .sglgif-gauge {
            --p: 0;
            --gauge-progress: 0;
            --c: #002C76;
            width: 126px;
            height: 126px;
            margin: 0 auto 12px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background:
                radial-gradient(closest-side, #ffffff 69%, transparent 71% 100%),
                conic-gradient(var(--c) calc(var(--gauge-progress) * 1%), #e2e8f0 0);
        }

        .sglgif-gauge span {
            color: #0f172a;
            font-size: 19px;
            font-weight: 800;
        }

        .sglgif-gauge-card h3 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .sglgif-gauge-card p {
            margin: 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
        }

        .sglgif-alert-card {
            background: linear-gradient(180deg, #fffaf0 0%, #ffffff 100%);
        }

        .dashboard-status-row .sglgif-alert-card {
            padding: 12px;
            border-radius: 12px;
        }

        .sglgif-alert-card span {
            color: #92400e;
        }

        .dashboard-status-row .sglgif-alert-card span {
            font-size: 10px;
        }

        .sglgif-alert-card strong {
            color: #7c2d12;
        }

        .dashboard-status-row .sglgif-alert-card strong {
            margin-top: 8px;
            font-size: 20px;
        }

        .sglgif-status-tile {
            display: block;
            position: relative;
            overflow: hidden;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid color-mix(in srgb, var(--status-border) 72%, white);
            background:
                linear-gradient(155deg, rgba(255, 255, 255, 0.84), rgba(255, 255, 255, 0.36)),
                radial-gradient(circle at top right, color-mix(in srgb, var(--status-bg) 86%, white), transparent 74%);
            color: var(--status-color);
            text-decoration: none;
            box-shadow:
                0 20px 34px rgba(15, 23, 42, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }

        .dashboard-status-row .sglgif-status-tile {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            padding: 14px;
            border-radius: 16px;
        }

        .sglgif-status-tile:hover {
            transform: translateY(-4px);
            border-color: var(--status-color);
            box-shadow:
                0 24px 38px rgba(15, 23, 42, 0.14),
                inset 0 1px 0 rgba(255, 255, 255, 0.90);
        }

        .sglgif-status-tile::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.26), transparent 58%);
            pointer-events: none;
        }

        .sglgif-status-tile > * {
            position: relative;
            z-index: 1;
        }

        .sglgif-status-tile-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .dashboard-status-row .sglgif-status-tile-top {
            gap: 10px;
            margin-bottom: 10px;
        }

        .sglgif-status-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.86), var(--status-bg));
            border: 1px solid color-mix(in srgb, var(--status-border) 65%, white);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
            font-size: 16px;
        }

        .dashboard-status-row .sglgif-status-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            font-size: 14px;
        }

        .sglgif-status-pill,
        .sglgif-status-label,
        .sglgif-status-footer span {
            display: block;
            font-weight: 800;
        }

        .sglgif-status-pill {
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.66);
            border: 1px solid rgba(255, 255, 255, 0.84);
            color: var(--status-color);
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dashboard-status-row .sglgif-status-pill {
            padding: 6px 8px;
            font-size: 9px;
        }

        .sglgif-status-tile-main {
            display: grid;
            gap: 8px;
        }

        .dashboard-status-row .sglgif-status-tile-main {
            gap: 5px;
            min-width: 0;
        }

        .sglgif-status-label {
            font-size: 12px;
            letter-spacing: 0.10em;
            text-transform: uppercase;
        }

        .dashboard-status-row .sglgif-status-label {
            font-size: 11px;
        }

        .sglgif-status-tile strong {
            display: block;
            margin-top: 0;
            color: #0f172a;
            font-size: 34px;
            line-height: 0.98;
        }

        .dashboard-status-row .sglgif-status-tile strong {
            font-size: 28px;
        }

        .sglgif-status-tile small {
            display: block;
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .dashboard-status-row .sglgif-status-tile small {
            font-size: 11px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sglgif-status-meter {
            height: 10px;
            margin-top: 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.48);
            border: 1px solid rgba(255, 255, 255, 0.64);
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .dashboard-status-row .sglgif-status-meter {
            height: 8px;
            margin-top: 10px;
        }

        .sglgif-status-meter div {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--status-color), color-mix(in srgb, var(--status-color) 58%, white));
            box-shadow: 0 6px 18px color-mix(in srgb, var(--status-color) 24%, transparent);
        }

        .sglgif-status-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
            color: #475569;
            font-size: 12px;
        }

        .dashboard-status-row .sglgif-status-footer {
            gap: 10px;
            margin-top: 8px;
            font-size: 11px;
            min-width: 0;
        }

        .sglgif-status-footer span {
            color: #475569;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .dashboard-status-row .sglgif-status-footer span {
            font-size: 10px;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* Center and reduce font size for financial tiles to fit one line */
        .sglgif-financial-summary {
            text-align: center;
        }

        .sglgif-financial-summary .sglgif-financial-tile {
            text-align: center;
            display: inline-block;
        }

        .sglgif-financial-summary .sglgif-financial-tile strong {
            font-size: 16px !important;
            line-height: 1.2;
            display: block;
            margin-top: 8px;
            word-break: break-word;
            hyphens: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .sglgif-financial-summary .sglgif-financial-tile strong {
                font-size: 14px !important;
            }
        }

        .sglgif-status-footer i {
            font-size: 13px;
            color: var(--status-color);
        }

        .sglgif-table-wrap {
            overflow-x: auto;
        }

        .dashboard-status-row .sglgif-table-wrap {
            max-height: 244px;
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
        }

        .sglgif-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .dashboard-status-row .sglgif-table {
            font-size: 11px;
        }

        .sglgif-table th,
        .sglgif-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
            color: #334155;
        }

        .dashboard-status-row .sglgif-table th,
        .dashboard-status-row .sglgif-table td {
            padding: 8px 7px;
        }

        .sglgif-project-row {
            cursor: pointer;
            transition: background-color 0.18s ease, transform 0.18s ease;
        }

        .sglgif-project-row:hover {
            background: #f8fbff;
        }

        .sglgif-project-row:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: -2px;
            background: #eff6ff;
        }

        .sglgif-project-row td:last-child {
            position: relative;
            padding-right: 34px;
        }

        .sglgif-project-row td:last-child::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 11px;
            transition: transform 0.18s ease, color 0.18s ease;
        }

        .sglgif-project-row:hover td:last-child::after,
        .sglgif-project-row:focus-visible td:last-child::after {
            color: #2563eb;
            transform: translateY(-50%) translateX(2px);
        }

        .sglgif-table th {
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            background: #f8fafc;
        }

        .dashboard-status-row .sglgif-table th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sglgif-empty,
        .sglgif-empty-cell {
            color: #64748b;
            font-size: 12px;
        }

        .sglgif-empty-cell {
            text-align: center;
            padding: 16px 10px;
        }

        .dashboard-status-row .sglgif-empty-cell {
            padding: 14px 8px;
        }

        .sglgif-modal {
            position: fixed;
            inset: 0;
            z-index: 1300;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .sglgif-modal.is-open {
            display: flex;
        }

        .sglgif-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
        }

        .sglgif-modal-dialog {
            position: relative;
            width: min(1180px, calc(100vw - 40px));
            max-height: calc(100vh - 40px);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sglgif-modal-header {
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sglgif-modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .sglgif-modal-close {
            margin-left: auto;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
        }

        .sglgif-modal-close:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .sglgif-modal-subtitle {
            margin: 0;
            padding: 10px 16px;
            font-size: 12px;
            color: #475569;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .sglgif-modal-body {
            padding: 0;
            overflow: auto;
        }

        .sglgif-modal-table-wrap {
            overflow: auto;
        }

        .sglgif-modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .sglgif-modal-table thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            color: #334155;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #cbd5e1;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
        }

        .sglgif-modal-table tbody td {
            padding: 10px 12px;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .sglgif-modal-table tbody td strong {
            color: #0f172a;
        }

        .sglgif-modal-empty-state {
            padding: 20px 16px;
            color: #64748b;
            font-size: 13px;
        }

        .sglgif-project-modal-dialog {
            width: min(760px, calc(100vw - 40px));
            background: #ffffff;
        }

        .sglgif-project-modal-header {
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .sglgif-project-modal-head-copy {
            flex: 1 1 auto;
            min-width: 0;
        }

        .sglgif-project-status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 22px;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .sglgif-project-modal-header h3 {
            margin: 8px 0 5px;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.35;
            max-width: 100%;
        }

        .sglgif-project-modal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 8px;
            margin: 0;
            color: #64748b;
            font-size: 10px;
            line-height: 1.45;
        }

        .sglgif-project-modal-meta span {
            display: inline-flex;
            align-items: center;
        }

        .sglgif-project-modal-header .sglgif-modal-close {
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            background: #ffffff;
            color: #475569;
            border-color: #cbd5e1;
        }

        .sglgif-project-modal-header .sglgif-modal-close:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .sglgif-project-modal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(240px, 0.92fr);
            gap: 8px;
            padding: 10px 14px 14px;
            align-items: start;
        }

        .sglgif-project-modal-section {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
        }

        .sglgif-project-modal-section--summary,
        .sglgif-project-modal-section--details {
            grid-column: 1;
        }

        .sglgif-project-modal-section--accomplishment {
            grid-column: 2;
            grid-row: 1 / span 2;
        }

        .sglgif-project-modal-section h4 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
        }

        .sglgif-project-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .sglgif-project-summary-card {
            padding: 9px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .sglgif-project-summary-card span {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sglgif-project-summary-card strong {
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .sglgif-project-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .sglgif-project-detail-card {
            padding: 9px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .sglgif-project-detail-card span {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .sglgif-project-detail-card strong {
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .sglgif-project-progress-list {
            display: grid;
            gap: 8px;
        }

        .sglgif-project-progress-card {
            padding: 8px 9px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .sglgif-project-progress-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }

        .sglgif-project-progress-head span {
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        .sglgif-project-progress-head strong {
            color: #0f172a;
            font-size: 10px;
            font-weight: 800;
        }

        .sglgif-project-progress-track {
            height: 5px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .sglgif-project-progress-fill {
            height: 100%;
            border-radius: inherit;
            min-width: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .sglgif-mix-donut-segment {
                animation: none;
                stroke-dasharray: var(--segment-length, 0) 100;
                transition: none;
            }
        }

        @media (max-width: 1360px) {
            .dashboard-main-layout {
                grid-template-columns: minmax(0, 1fr) minmax(320px, 0.92fr);
                padding: 20px;
            }
        }

        @media (max-width: 1280px) {
            .dashboard-main-layout {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .dashboard-top-cards,
            .dashboard-status-row {
                gap: 18px;
            }

            .sglgif-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .sglgif-filter-field--search {
                grid-column: 1 / -1;
            }

            .sglgif-mix-chart-grid,
            .sglgif-dual-panel {
                grid-template-columns: 1fr;
            }

            .sglgif-gauge-grid,
            .sglgif-financial-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .dashboard-main-layout {
                padding: 18px;
                gap: 18px;
            }

            .sglgif-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px 14px;
            }

            .sglgif-filter-actions {
                grid-column: 1 / -1;
                justify-content: stretch;
            }

            .sglgif-action-btn {
                flex: 1 1 calc(50% - 8px);
                min-width: 0;
            }

            .sglgif-panel-head,
            .sglgif-mix-chart-head {
                flex-wrap: wrap;
            }

            .sglgif-bar-head {
                gap: 8px;
            }

            .sglgif-gauge {
                width: 116px;
                height: 116px;
            }

            .sglgif-project-modal-header {
                flex-wrap: wrap;
            }

            .sglgif-project-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sglgif-project-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 820px) {
            .dashboard-main-layout {
                padding: 16px;
                gap: 16px;
                border-radius: 16px;
            }

            .sglgif-card,
            .dashboard-status-row .sglgif-card {
                padding: 15px 14px;
            }

            .sglgif-card-head,
            .sglgif-status-card-head,
            .sglgif-mix-chart-head,
            .sglgif-panel-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .sglgif-status-card-copy {
                flex: 0 0 auto;
                width: 100%;
                min-width: 0;
            }

            .dashboard-status-row .sglgif-status-grid,
            .dashboard-status-row .sglgif-alert-grid,
            .sglgif-mix-chart-grid,
            .sglgif-gauge-grid,
            .sglgif-financial-summary,
            .sglgif-dual-panel {
                grid-template-columns: 1fr;
            }

            .sglgif-switch {
                width: 100%;
                justify-content: space-between;
            }

            .sglgif-switch button {
                flex: 1 1 0;
                text-align: center;
            }

            .sglgif-mix-donut-layout {
                min-height: 0;
                padding: 14px 12px;
            }

            .sglgif-mix-chart-legend {
                grid-template-columns: 1fr;
            }

            .sglgif-modal {
                padding: 14px;
            }

            .sglgif-modal-dialog {
                width: calc(100vw - 28px);
                max-height: calc(100vh - 28px);
            }

            .sglgif-project-modal-header {
                padding: 10px 12px;
            }

            .sglgif-project-modal-grid {
                padding: 8px 12px 12px;
            }

            .sglgif-project-modal-header h3 {
                font-size: 12px;
            }
        }

        @media (max-width: 640px) {
            .dashboard-main-layout {
                padding: 12px;
                gap: 12px;
                border-radius: 14px;
            }

            .dashboard-top-cards,
            .dashboard-status-row {
                gap: 12px;
            }

            .sglgif-filter-grid,
            .sglgif-mix-chart-grid,
            .sglgif-alert-grid,
            .sglgif-status-grid,
            .dashboard-status-row .sglgif-status-grid,
            .dashboard-status-row .sglgif-alert-grid,
            .sglgif-gauge-grid,
            .sglgif-financial-summary,
            .sglgif-dual-panel {
                grid-template-columns: 1fr;
            }

            .sglgif-filter-grid {
                gap: 10px;
            }

            .sglgif-filter-field--search {
                grid-column: span 1;
            }

            .sglgif-filter-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .sglgif-action-btn {
                width: 100%;
                flex: 1 1 auto;
            }

            .sglgif-card,
            .dashboard-status-row .sglgif-card {
                padding: 14px 12px;
                border-radius: 14px;
            }

            .sglgif-card-head h2,
            .sglgif-panel-head h3 {
                font-size: 14px;
            }

            .sglgif-card-head p,
            .dashboard-status-row .sglgif-card-head p {
                font-size: 11px;
                line-height: 1.4;
            }

            .dashboard-status-row .sglgif-status-card-head {
                margin-bottom: 6px;
            }

            .dashboard-status-row .sglgif-status-card-copy p {
                margin-top: 3px;
            }

            .dashboard-status-row .sglgif-status-tile {
                padding: 12px;
            }

            .dashboard-status-row .sglgif-status-tile strong {
                font-size: 24px;
            }

            .dashboard-status-row .sglgif-status-footer span {
                font-size: 9px;
            }

            .dashboard-status-row .sglgif-table-wrap {
                max-height: 220px;
            }

            .sglgif-bar-head {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 6px;
            }

            .sglgif-bar-head strong {
                align-self: flex-start;
            }

            .sglgif-gauge {
                width: 100px;
                height: 100px;
            }

            .sglgif-gauge span {
                font-size: 16px;
            }

            .sglgif-mix-donut-wrap {
                width: min(168px, 76%);
                max-width: 168px;
            }

            .sglgif-mix-donut-center {
                width: 70px;
                height: 70px;
            }

            .sglgif-mix-donut-center strong {
                font-size: 20px;
            }

            .sglgif-mix-donut-center span {
                font-size: 9px;
            }

            .sglgif-modal {
                padding: 12px;
            }

            .sglgif-modal-dialog {
                width: calc(100vw - 24px);
                max-height: calc(100vh - 24px);
                border-radius: 12px;
            }

            .sglgif-modal-header,
            .sglgif-modal-subtitle {
                padding-left: 12px;
                padding-right: 12px;
            }

            .sglgif-modal-header h3 {
                font-size: 14px;
            }

            .sglgif-modal-subtitle,
            .sglgif-modal-table,
            .sglgif-modal-empty-state,
            .sglgif-project-detail-card strong {
                font-size: 11px;
            }

            .sglgif-modal-table thead th,
            .sglgif-modal-table tbody td {
                padding: 8px 10px;
            }

            .sglgif-project-modal-header {
                padding: 9px 10px;
                gap: 8px;
            }

            .sglgif-project-modal-header h3 {
                font-size: 11px;
            }

            .sglgif-project-modal-meta {
                font-size: 9px;
                gap: 3px 6px;
            }

            .sglgif-project-status-badge {
                min-height: 20px;
                padding: 4px 8px;
                font-size: 9px;
            }

            .sglgif-project-summary-card strong {
                font-size: 10px;
            }

            .sglgif-project-summary-grid {
                grid-template-columns: 1fr;
            }

            .sglgif-project-modal-grid {
                grid-template-columns: 1fr;
            }

            .sglgif-project-modal-section--summary,
            .sglgif-project-modal-section--details,
            .sglgif-project-modal-section--accomplishment {
                grid-column: auto;
                grid-row: auto;
            }

            .sglgif-project-modal-grid {
                padding: 8px 10px 10px;
            }

            .sglgif-project-modal-section {
                padding: 8px;
            }

            .sglgif-project-progress-head {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>

    <script>
        function openSglgifModal(modalElement) {
            if (!modalElement) {
                return;
            }

            modalElement.classList.add('is-open');
            modalElement.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeSglgifModal(modalElement) {
            if (!modalElement) {
                return;
            }

            modalElement.classList.remove('is-open');
            modalElement.setAttribute('aria-hidden', 'true');

            if (!document.querySelector('.sglgif-modal.is-open')) {
                document.body.style.overflow = '';
            }
        }

        function toggleProjectFilter(button) {
            const form = button.closest('.project-filter-form');
            if (!form) {
                return;
            }

            const body = form.querySelector('.project-filter-body');
            if (!body) {
                return;
            }

            const isCollapsed = form.classList.contains('collapsed');
            if (isCollapsed) {
                form.classList.remove('collapsed');
                requestAnimationFrame(() => {
                    body.style.maxHeight = `${body.scrollHeight}px`;
                });
            } else {
                body.style.maxHeight = `${body.scrollHeight}px`;
                requestAnimationFrame(() => {
                    form.classList.add('collapsed');
                    body.style.maxHeight = '0px';
                });
            }

            button.setAttribute('aria-expanded', isCollapsed ? 'true' : 'false');
        }

        function initializeSglgifLocationDependencies() {
            const provinceSelect = document.getElementById('sglgif-province');
            const citySelect = document.getElementById('sglgif-city');
            const provinceMunicipalities = @json($provinceMunicipalities ?? []);

            if (!provinceSelect || !citySelect) {
                return;
            }

            const rebuildCityOptions = () => {
                const selectedProvince = String(provinceSelect.value || '').trim();
                const selectedCity = String(citySelect.value || '').trim();
                const nextCities = selectedProvince
                    ? (provinceMunicipalities[selectedProvince] || [])
                        .map((city) => String(city || '').trim())
                        .filter((city) => city !== '')
                    : [];

                citySelect.innerHTML = '';

                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'All';
                citySelect.appendChild(allOption);

                nextCities.forEach((city) => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    option.selected = selectedCity !== '' && selectedCity === city;
                    citySelect.appendChild(option);
                });

                if (!nextCities.includes(selectedCity)) {
                    citySelect.value = '';
                }

                citySelect.disabled = selectedProvince === '';
            };

            provinceSelect.addEventListener('change', rebuildCityOptions);
            rebuildCityOptions();
        }

        document.addEventListener('DOMContentLoaded', () => {
            initializeSglgifLocationDependencies();

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const integerFormatter = new Intl.NumberFormat('en-US', {
                maximumFractionDigits: 0,
            });
            const currencyFormatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            const formatAnimatedBarValue = (value, format) => {
                if (format === 'currency') {
                    return `₱ ${currencyFormatter.format(value)}`;
                }

                return integerFormatter.format(Math.round(value));
            };
            const animateGauge = (gaugeElement, index) => {
                const targetValue = Number.parseFloat(gaugeElement.getAttribute('data-sg-gauge-value') || '0');
                const labelElement = gaugeElement.querySelector('[data-sg-gauge-label]');

                if (!Number.isFinite(targetValue)) {
                    return;
                }

                if (prefersReducedMotion) {
                    gaugeElement.style.setProperty('--gauge-progress', targetValue.toFixed(2));
                    if (labelElement) {
                        labelElement.textContent = `${targetValue.toFixed(2)}%`;
                    }
                    return;
                }

                const durationMs = 1200;
                const delayMs = index * 130;
                const startAnimation = () => {
                    const startTime = performance.now();
                    const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);

                    const updateFrame = (now) => {
                        const rawProgress = Math.min((now - startTime) / durationMs, 1);
                        const easedProgress = easeOutCubic(rawProgress);
                        const currentValue = targetValue * easedProgress;

                        gaugeElement.style.setProperty('--gauge-progress', currentValue.toFixed(2));
                        if (labelElement) {
                            labelElement.textContent = `${currentValue.toFixed(2)}%`;
                        }

                        if (rawProgress < 1) {
                            window.requestAnimationFrame(updateFrame);
                        }
                    };

                    gaugeElement.style.setProperty('--gauge-progress', '0');
                    if (labelElement) {
                        labelElement.textContent = '0.00%';
                    }
                    window.requestAnimationFrame(updateFrame);
                };

                window.setTimeout(startAnimation, delayMs);
            };

            document.querySelectorAll('[data-sg-gauge]').forEach((gaugeElement, index) => {
                animateGauge(gaugeElement, index);
            });

            const animateProvinceBars = (panelElement) => {
                if (!panelElement) {
                    return;
                }

                const animatedRows = Array.from(panelElement.querySelectorAll('[data-sg-bar-animate]'));
                if (!animatedRows.length) {
                    return;
                }

                animatedRows.forEach((rowElement, index) => {
                    const fillElement = rowElement.querySelector('[data-sg-bar-fill]');
                    const numberElements = Array.from(rowElement.querySelectorAll('[data-sg-bar-number]'));

                    if (prefersReducedMotion) {
                        if (fillElement) {
                            fillElement.style.width = `${Number.parseFloat(fillElement.getAttribute('data-target-width') || '0')}%`;
                        }

                        numberElements.forEach((numberElement) => {
                            const targetValue = Number.parseFloat(numberElement.getAttribute('data-value') || '0');
                            const format = numberElement.getAttribute('data-format') || 'integer';
                            numberElement.textContent = formatAnimatedBarValue(targetValue, format);
                        });

                        return;
                    }

                    const durationMs = 1000;
                    const delayMs = index * 90;
                    window.setTimeout(() => {
                        const startTime = performance.now();
                        const easeOutCubic = (progress) => 1 - Math.pow(1 - progress, 3);
                        const targetWidth = fillElement ? Number.parseFloat(fillElement.getAttribute('data-target-width') || '0') : 0;

                        if (fillElement) {
                            fillElement.style.width = '0%';
                        }

                        numberElements.forEach((numberElement) => {
                            const format = numberElement.getAttribute('data-format') || 'integer';
                            numberElement.textContent = formatAnimatedBarValue(0, format);
                        });

                        const updateFrame = (now) => {
                            const rawProgress = Math.min((now - startTime) / durationMs, 1);
                            const easedProgress = easeOutCubic(rawProgress);

                            if (fillElement) {
                                fillElement.style.width = `${(targetWidth * easedProgress).toFixed(2)}%`;
                            }

                            numberElements.forEach((numberElement) => {
                                const targetValue = Number.parseFloat(numberElement.getAttribute('data-value') || '0');
                                const format = numberElement.getAttribute('data-format') || 'integer';
                                numberElement.textContent = formatAnimatedBarValue(targetValue * easedProgress, format);
                            });

                            if (rawProgress < 1) {
                                window.requestAnimationFrame(updateFrame);
                            }
                        };

                        window.requestAnimationFrame(updateFrame);
                    }, delayMs);
                });
            };

            document.querySelectorAll('[data-sg-switch-group]').forEach((switchGroup) => {
                const groupName = switchGroup.getAttribute('data-sg-switch-group');
                const buttons = Array.from(switchGroup.querySelectorAll('[data-sg-switch-target]'));
                const panels = Array.from(document.querySelectorAll(`[data-sg-switch-panel^="${groupName}:"]`));

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const target = button.getAttribute('data-sg-switch-target');

                        buttons.forEach((item) => item.classList.toggle('is-active', item === button));
                        panels.forEach((panel) => {
                            panel.classList.toggle('is-active', panel.getAttribute('data-sg-switch-panel') === `${groupName}:${target}`);
                        });

                        const activePanel = panels.find((panel) => panel.getAttribute('data-sg-switch-panel') === `${groupName}:${target}`);
                        animateProvinceBars(activePanel);
                    });
                });

                const defaultPanel = panels.find((panel) => panel.classList.contains('is-active'));
                animateProvinceBars(defaultPanel);
            });

            document.querySelectorAll('[data-sg-bar-container]').forEach((barContainer) => {
                animateProvinceBars(barContainer);
            });

            document.querySelectorAll('[data-sglgif-modal-target]').forEach((trigger) => {
                const modalTargetId = trigger.getAttribute('data-sglgif-modal-target');
                const modalElement = modalTargetId ? document.getElementById(modalTargetId) : null;
                if (!modalElement) {
                    return;
                }

                trigger.addEventListener('click', () => {
                    openSglgifModal(modalElement);
                });

                trigger.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    openSglgifModal(modalElement);
                });
            });

            document.querySelectorAll('.sglgif-modal').forEach((modalElement) => {
                modalElement.querySelectorAll('[data-sglgif-close-modal]').forEach((closeControl) => {
                    closeControl.addEventListener('click', () => {
                        closeSglgifModal(modalElement);
                    });
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                const openModal = document.querySelector('.sglgif-modal.is-open');
                if (openModal) {
                    closeSglgifModal(openModal);
                }
            });
        });
    </script>
@endsection
