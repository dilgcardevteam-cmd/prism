@extends('layouts.dashboard')

@section('title', 'RSSA LFP Dashboard')
@section('page-title', 'RSSA LFP Dashboard')

@section('content')
    @php
        $peso = function ($value) {
            return 'PHP ' . number_format((float) $value, 2);
        };

        $countPercent = function ($count, $total) {
            if ((int) $total <= 0) {
                return '0.00%';
            }

            return number_format((((int) $count) / ((int) $total)) * 100, 2) . '%';
        };

        $statusPalette = [
            '#16a34a',
            '#2563eb',
            '#e11d48',
            '#7c3aed',
            '#ea580c',
            '#0f766e',
            '#b45309',
            '#475569',
        ];

        $fundPalette = [
            ['border' => '#bfdbfe', 'background' => 'linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%)', 'icon' => 'fa-layer-group', 'color' => '#1e3a8a'],
            ['border' => '#bbf7d0', 'background' => 'linear-gradient(180deg, #f8fef9 0%, #f0fdf4 100%)', 'icon' => 'fa-hand-holding-dollar', 'color' => '#14532d'],
            ['border' => '#fed7aa', 'background' => 'linear-gradient(180deg, #fffaf4 0%, #fff7ed 100%)', 'icon' => 'fa-road', 'color' => '#7c2d12'],
            ['border' => '#a5f3fc', 'background' => 'linear-gradient(180deg, #f4feff 0%, #ecfeff 100%)', 'icon' => 'fa-leaf', 'color' => '#164e63'],
            ['border' => '#c4b5fd', 'background' => 'linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%)', 'icon' => 'fa-award', 'color' => '#5b21b6'],
            ['border' => '#fecaca', 'background' => 'linear-gradient(180deg, #fff8f8 0%, #fef2f2 100%)', 'icon' => 'fa-building', 'color' => '#7f1d1d'],
        ];

        $fundMax = max(array_values($fundSourceCounts ?: ['-' => 0]));

        $functionalPercentValue = (int) $totalProjects > 0
            ? round(((int) $functionalProjects / (int) $totalProjects) * 100, 2)
            : 0;
        $operationalPercentValue = (int) $totalProjects > 0
            ? round(((int) $operationalProjects / (int) $totalProjects) * 100, 2)
            : 0;
        $maintenancePercentValue = (int) $totalProjects > 0
            ? round(((int) $maintainedProjects / (int) $totalProjects) * 100, 2)
            : 0;

        $fundChartSegments = [];
        $fundChartLimit = 6;
        $fundChartSlice = array_slice($fundSourceCounts, 0, $fundChartLimit, true);
        $fundChartRemainder = array_slice($fundSourceCounts, $fundChartLimit, null, true);
        if (!empty($fundChartRemainder)) {
            $fundChartSlice['Others'] = array_sum($fundChartRemainder);
        }

        foreach ($fundChartSlice as $label => $count) {
            $style = $fundPalette[count($fundChartSegments) % count($fundPalette)];
            $fundChartSegments[] = [
                'label' => $label,
                'count' => (int) $count,
                'color' => $style['color'],
                'border' => $style['border'],
            ];
        }

        $fundChartTotal = array_sum(array_column($fundChartSegments, 'count'));
        $fundChartGradientParts = [];
        $fundChartOffset = 0;
        foreach ($fundChartSegments as $segment) {
            $portion = $fundChartTotal > 0 ? ($segment['count'] / $fundChartTotal) * 100 : 0;
            $nextOffset = $fundChartOffset + $portion;
            $fundChartGradientParts[] = "{$segment['color']} {$fundChartOffset}% {$nextOffset}%";
            $fundChartOffset = $nextOffset;
        }
        $fundChartGradient = !empty($fundChartGradientParts)
            ? 'conic-gradient(' . implode(', ', $fundChartGradientParts) . ')'
            : 'conic-gradient(#dbeafe 0% 100%)';

        $statusChartBars = [];
        $statusChartMax = max(array_values($projectStatusCounts ?: ['-' => 0]));
        foreach (array_slice($projectStatusCounts, 0, 8, true) as $label => $count) {
            $statusChartBars[] = [
                'label' => $label,
                'shortLabel' => mb_strimwidth($label, 0, 16, '...'),
                'count' => (int) $count,
                'height' => $statusChartMax > 0 ? max((($count / $statusChartMax) * 100), 8) : 0,
                'color' => $statusPalette[count($statusChartBars) % count($statusPalette)],
            ];
        }

        $sustainabilityChartBars = [
            [
                'label' => 'Functional',
                'value' => $functionalPercentValue,
                'count' => (int) $functionalProjects,
                'color' => '#22c55e',
            ],
            [
                'label' => 'Operational',
                'value' => $operationalPercentValue,
                'count' => (int) $operationalProjects,
                'color' => '#3b82f6',
            ],
            [
                'label' => 'Maintained',
                'value' => $maintenancePercentValue,
                'count' => (int) $maintainedProjects,
                'color' => '#7c3aed',
            ],
        ];
    @endphp

    <div class="content-header rssa-hero">
        <div class="rssa-hero-copy">
            <p class="rssa-hero-kicker">Rapid Subproject Sustainability Assessment</p>
            <h1>RSSA LFP Dashboard</h1>
            <p class="rssa-hero-subtitle">Infographic view of locally funded project sustainability, funding, and completion movement.</p>
        </div>
        <div class="rssa-hero-badges">
            <div class="rssa-hero-badge">
                <span class="rssa-hero-badge-label">Province</span>
                <strong>{{ $filters['province'] ?? 'All' }}</strong>
            </div>
            <div class="rssa-hero-badge">
                <span class="rssa-hero-badge-label">City/Municipality</span>
                <strong>{{ $filters['city_municipality'] ?? 'All' }}</strong>
            </div>
            <div class="rssa-hero-badge">
                <span class="rssa-hero-badge-label">Funding Year</span>
                <strong>{{ $filters['funding_year'] ?? 'All' }}</strong>
            </div>
            <div class="rssa-hero-badge">
                <span class="rssa-hero-badge-label">Program</span>
                <strong>{{ $filters['program'] ?? 'All' }}</strong>
            </div>
        </div>
    </div>

    @include('projects.partials.project-section-tabs', ['activeTab' => $activeProjectTab ?? 'rssa'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=Spline+Sans:wght@400;500;600&display=swap');

        .rssa-dashboard-shell {
            --ink: #0f172a;
            --muted: #475569;
            --accent: #f97316;
            --accent-2: #0ea5e9;
            --deep: #0b2a4a;
            --surface: #f4f6fb;
            --card: #ffffff;
            --border: rgba(15, 23, 42, 0.08);
            font-family: 'Spline Sans', 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top left, rgba(14, 165, 233, 0.12), transparent 40%),
                radial-gradient(circle at 20% 10%, rgba(249, 115, 22, 0.1), transparent 45%),
                #f4f6fb;
            border-radius: 20px;
            padding: 20px;
        }

        .rssa-dashboard-shell h1,
        .rssa-dashboard-shell h2,
        .rssa-dashboard-shell h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
            letter-spacing: -0.01em;
        }

        .rssa-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 1fr);
            gap: 18px;
            align-items: stretch;
            margin-bottom: 20px;
            padding: 22px 24px;
            border: 1px solid rgba(14, 165, 233, 0.18);
            border-radius: 22px;
            background:
                radial-gradient(circle at top right, rgba(249, 115, 22, 0.16), transparent 32%),
                linear-gradient(135deg, rgba(11, 42, 74, 0.96) 0%, rgba(29, 78, 216, 0.92) 100%);
            color: #ffffff;
            box-shadow: 0 18px 34px rgba(11, 42, 74, 0.18);
        }

        .rssa-hero-copy {
            display: grid;
            gap: 8px;
            align-content: center;
        }

        .rssa-hero-kicker {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-size: 11px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.72);
        }

        .rssa-hero h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.7rem);
            line-height: 1.05;
            color: #ffffff;
        }

        .rssa-hero-subtitle {
            margin: 0;
            max-width: 720px;
            font-size: 14px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.82);
        }

        .rssa-hero-badges {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .rssa-hero-badge {
            display: grid;
            gap: 6px;
            align-content: center;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
        }

        .rssa-hero-badge-label {
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.68);
        }

        .rssa-hero-badge strong {
            font-size: 15px;
            line-height: 1.35;
            color: #ffffff;
            word-break: break-word;
        }

        .rssa-layout {
            display: grid;
            gap: 18px;
        }

        .rssa-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        .rssa-filter-panel {
            display: grid;
            gap: 14px;
        }

        .rssa-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px 0;
        }

        .rssa-filter-title {
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-size: 11px;
            color: var(--deep);
            font-weight: 700;
        }

        .rssa-filter-form {
            padding: 12px 18px 18px;
            display: grid;
            gap: 12px;
        }

        .rssa-filter-body {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.25s ease;
        }

        .rssa-filter-form.collapsed .rssa-filter-body {
            max-height: 0;
            opacity: 0;
            transform: translateY(-6px);
            pointer-events: none;
        }

        .rssa-filter-form.collapsed .rssa-filter-chevron {
            transform: rotate(180deg);
        }

        .rssa-filter-toggle {
            border: 0;
            background: transparent;
            color: #0b2a4a;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .rssa-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .rssa-filter-field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .rssa-filter-field select {
            width: 100%;
            height: 40px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            border-radius: 10px;
            background-color: #ffffff;
            color: #111827;
            padding: 0 12px;
            font-size: 13px;
        }

        .rssa-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rssa-button {
            height: 40px;
            min-width: 140px;
            border-radius: 12px;
            border: 0;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            padding: 0 18px;
        }

        .rssa-button--primary {
            background: linear-gradient(135deg, #0b2a4a 0%, #1d4ed8 100%);
            color: #ffffff;
        }

        .rssa-button--ghost {
            background: #e0f2fe;
            color: #0b2a4a;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .rssa-main {
            display: grid;
            gap: 18px;
            min-width: 0;
        }

        .rssa-metric-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .rssa-metric-card {
            padding: 16px;
            display: grid;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .rssa-metric-kicker {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: #64748b;
            font-weight: 700;
        }

        .rssa-metric-value {
            font-size: clamp(1.55rem, 2.1vw, 2rem);
            font-weight: 700;
            color: var(--deep);
            line-height: 1.12;
            word-break: break-word;
        }

        .rssa-metric-sub {
            font-size: 12px;
            color: #64748b;
        }

        .rssa-metric-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(14, 165, 233, 0.12);
            color: #0b2a4a;
        }

        .rssa-ring {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: conic-gradient(var(--ring-color) calc(var(--value) * 1%), rgba(148, 163, 184, 0.28) 0);
            display: grid;
            place-items: center;
            position: relative;
        }

        .rssa-ring::before {
            content: '';
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
        }

        .rssa-ring-content {
            position: absolute;
            display: grid;
            place-items: center;
            text-align: center;
            gap: 2px;
        }

        .rssa-ring-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
        }

        .rssa-ring-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #94a3b8;
            font-weight: 700;
        }

        .rssa-duo {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr);
            gap: 14px;
        }

        .rssa-chart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .rssa-chart-card {
            padding: 18px;
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .rssa-chart-body {
            min-width: 0;
        }

        .rssa-donut-layout {
            display: grid;
            grid-template-columns: minmax(150px, 190px) minmax(0, 1fr);
            gap: 16px;
            align-items: center;
        }

        .rssa-donut-chart {
            width: 170px;
            height: 170px;
            margin: 0 auto;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--donut-gradient);
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
        }

        .rssa-donut-chart::before {
            content: '';
            width: 108px;
            height: 108px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.22);
        }

        .rssa-donut-center {
            position: absolute;
            display: grid;
            gap: 4px;
            text-align: center;
        }

        .rssa-donut-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--deep);
        }

        .rssa-donut-copy {
            font-size: 10px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }

        .rssa-chart-legend {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        .rssa-chart-legend-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-width: 0;
        }

        .rssa-chart-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
        }

        .rssa-chart-label {
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.35;
            word-break: break-word;
        }

        .rssa-chart-number {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }

        .rssa-column-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(64px, 1fr));
            gap: 10px;
            align-items: end;
            min-height: 220px;
            padding-top: 16px;
        }

        .rssa-column {
            display: grid;
            gap: 8px;
            align-items: end;
        }

        .rssa-column-bar-wrap {
            height: 170px;
            display: flex;
            align-items: end;
            justify-content: center;
        }

        .rssa-column-bar {
            width: min(100%, 44px);
            min-height: 12px;
            border-radius: 14px 14px 8px 8px;
            background: linear-gradient(180deg, var(--bar-color) 0%, var(--bar-color) 100%);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        }

        .rssa-column-value {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
        }

        .rssa-column-label {
            font-size: 10px;
            line-height: 1.35;
            color: #64748b;
            text-align: center;
            word-break: break-word;
        }

        .rssa-sustainability-bars {
            display: grid;
            gap: 14px;
        }

        .rssa-sustainability-row {
            display: grid;
            gap: 8px;
        }

        .rssa-sustainability-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
        }

        .rssa-sustainability-track {
            height: 16px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .rssa-sustainability-fill {
            height: 100%;
            border-radius: inherit;
        }

        .rssa-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .rssa-section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--deep);
        }

        .rssa-bar-row {
            display: grid;
            gap: 6px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .rssa-bar-meta {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
        }

        .rssa-bar-meta span:first-child {
            flex: 1 1 auto;
            min-width: 0;
            line-height: 1.4;
            padding-right: 8px;
            word-break: break-word;
        }

        .rssa-bar-meta span:last-child {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .rssa-bar-track {
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .rssa-bar-fill {
            height: 100%;
            border-radius: inherit;
        }

        .rssa-list {
            display: grid;
            gap: 10px;
            max-height: 360px;
            overflow: auto;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.65) transparent;
        }

        .rssa-list::-webkit-scrollbar {
            width: 8px;
        }

        .rssa-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.55);
        }

        .rssa-completion-item {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.18);
            background: linear-gradient(135deg, #f8fbff 0%, #eef2ff 100%);
            display: grid;
            gap: 6px;
        }

        .rssa-completion-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            font-weight: 700;
            color: #1d4ed8;
        }

        .rssa-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .rssa-status-card {
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .rssa-status-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--deep);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rssa-status-pills {
            display: grid;
            gap: 10px;
        }

        .rssa-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: #f8fafc;
        }

        .rssa-pill-label {
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.4;
            word-break: break-word;
        }

        .rssa-pill-value {
            font-size: 16px;
            font-weight: 700;
        }

        .rssa-animate {
            animation: rssaFloatIn 0.7s ease both;
        }

        @keyframes rssaFloatIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1200px) {
            .rssa-hero {
                grid-template-columns: minmax(0, 1fr);
            }

            .rssa-chart-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .rssa-duo {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 720px) {
            .rssa-dashboard-shell {
                padding: 14px;
            }

            .rssa-hero {
                padding: 18px;
                border-radius: 18px;
            }

            .rssa-hero-badges {
                grid-template-columns: minmax(0, 1fr);
            }

            .rssa-filter-actions {
                justify-content: flex-start;
            }

            .rssa-donut-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .rssa-donut-chart {
                width: 150px;
                height: 150px;
            }

            .rssa-donut-chart::before {
                width: 96px;
                height: 96px;
            }
        }
    </style>

    <div class="rssa-dashboard-shell">
        <div class="rssa-layout">
            <form method="GET" action="{{ route('dashboard') }}" class="rssa-card rssa-filter-form rssa-animate">
                <input type="hidden" name="tab" value="rssa">
                <div class="rssa-filter-head">
                    <div class="rssa-filter-title">Project Filter</div>
                    <button type="button" class="rssa-filter-toggle" onclick="window.toggleRssaFilter(this)" aria-expanded="true" aria-controls="rssa-filter-body">
                        <span>Toggle</span>
                        <span class="rssa-filter-chevron" style="transition: transform 0.2s ease;">
                            <i class="fas fa-chevron-up"></i>
                        </span>
                    </button>
                </div>

                <div id="rssa-filter-body" class="rssa-filter-body">
                    <div class="rssa-filter-grid">
                        <div class="rssa-filter-field">
                            <label for="province">Province</label>
                            <select id="province" name="province">
                                <option value="">All</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province }}" {{ ($filters['province'] ?? '') === $province ? 'selected' : '' }}>{{ $province }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="city_municipality">City/Municipality</label>
                            <select id="city_municipality" name="city_municipality" data-selected-city="{{ $filters['city_municipality'] ?? '' }}">
                                <option value="">All</option>
                                @foreach($cityOptions as $city)
                                    <option value="{{ $city }}" {{ ($filters['city_municipality'] ?? '') === $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="program">Program</label>
                            <select id="program" name="program">
                                <option value="">All</option>
                                @foreach($programOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['program'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="funding_year">Funding Year</label>
                            <select id="funding_year" name="funding_year">
                                <option value="">All</option>
                                @foreach($fundingYearOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['funding_year'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="project_type">Project Type</label>
                            <select id="project_type" name="project_type">
                                <option value="">All</option>
                                @foreach($projectTypeOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['project_type'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="project_status">Project Status</label>
                            <select id="project_status" name="project_status">
                                <option value="">All</option>
                                @foreach($projectStatusOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['project_status'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="functional">Functional</label>
                            <select id="functional" name="functional">
                                <option value="">All</option>
                                @foreach($functionalOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['functional'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rssa-filter-field">
                            <label for="operational">Operational</label>
                            <select id="operational" name="operational">
                                <option value="">All</option>
                                @foreach($operationalOptions as $option)
                                    <option value="{{ $option }}" {{ ($filters['operational'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rssa-filter-actions">
                        <button type="submit" class="rssa-button rssa-button--primary">Apply Filter</button>
                        <a href="{{ route('dashboard', ['tab' => 'rssa']) }}" class="rssa-button rssa-button--ghost">Reset Filter</a>
                    </div>
                </div>
            </form>

            <main class="rssa-main">
                @if($tableMissing ?? false)
                    <div class="rssa-card" style="padding: 20px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;">
                        RSSA data table is not available yet. Import RSSA data first in `/system-management/upload-rssa`.
                    </div>
                @else
                    <section class="rssa-metric-row">
                        <div class="rssa-card rssa-metric-card rssa-animate">
                            <div class="rssa-metric-icon"><i class="fas fa-compass"></i></div>
                            <div class="rssa-metric-kicker">Total Projects</div>
                            <div class="rssa-metric-value">{{ number_format($totalProjects) }}</div>
                            <div class="rssa-metric-sub">Assessed {{ number_format($assessedProjects) }} | Functional {{ number_format($functionalProjects) }}</div>
                        </div>

                        <div class="rssa-card rssa-metric-card rssa-animate">
                            <div class="rssa-metric-icon" style="background: rgba(34, 197, 94, 0.15); color: #166534;"><i class="fas fa-circle-check"></i></div>
                            <div class="rssa-metric-kicker">Functional Rate</div>
                            <div class="rssa-metric-value">{{ $countPercent($functionalProjects, $totalProjects) }}</div>
                            <div class="rssa-ring" style="--value: {{ $functionalPercentValue }}; --ring-color: #22c55e;">
                                <div class="rssa-ring-content">
                                    <div class="rssa-ring-value">{{ $countPercent($functionalProjects, $totalProjects) }}</div>
                                    <div class="rssa-ring-label">Functional</div>
                                </div>
                            </div>
                        </div>

                        <div class="rssa-card rssa-metric-card rssa-animate">
                            <div class="rssa-metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8;"><i class="fas fa-gears"></i></div>
                            <div class="rssa-metric-kicker">Operational Rate</div>
                            <div class="rssa-metric-value">{{ $countPercent($operationalProjects, $totalProjects) }}</div>
                            <div class="rssa-ring" style="--value: {{ $operationalPercentValue }}; --ring-color: #3b82f6;">
                                <div class="rssa-ring-content">
                                    <div class="rssa-ring-value">{{ $countPercent($operationalProjects, $totalProjects) }}</div>
                                    <div class="rssa-ring-label">Operational</div>
                                </div>
                            </div>
                        </div>

                        <div class="rssa-card rssa-metric-card rssa-animate">
                            <div class="rssa-metric-icon" style="background: rgba(249, 115, 22, 0.15); color: #c2410c;"><i class="fas fa-wallet"></i></div>
                            <div class="rssa-metric-kicker">Total Project Cost</div>
                            <div class="rssa-metric-value">{{ $peso($totalProjectCost) }}</div>
                            <div class="rssa-metric-sub">Original Subsidy {{ $peso($totalOriginalSubsidy) }}</div>
                        </div>
                    </section>

                    <section class="rssa-chart-grid">
                        <div class="rssa-card rssa-chart-card rssa-animate">
                            <div class="rssa-section-head">
                                <div>
                                    <div class="rssa-section-title">Fund Source Mix</div>
                                    <div class="rssa-metric-sub">Top funding sources in the current RSSA scope.</div>
                                </div>
                            </div>
                            <div class="rssa-chart-body rssa-donut-layout">
                                <div style="position: relative; display: grid; place-items: center;">
                                    <div class="rssa-donut-chart" style="--donut-gradient: {{ $fundChartGradient }};">
                                    </div>
                                    <div class="rssa-donut-center">
                                        <div class="rssa-donut-value">{{ number_format($fundChartTotal) }}</div>
                                        <div class="rssa-donut-copy">Projects</div>
                                    </div>
                                </div>
                                <div class="rssa-chart-legend">
                                    @forelse($fundChartSegments as $segment)
                                        <div class="rssa-chart-legend-item">
                                            <span class="rssa-chart-dot" style="background: {{ $segment['color'] }};"></span>
                                            <span class="rssa-chart-label">{{ $segment['label'] }}</span>
                                            <span class="rssa-chart-number">{{ number_format($segment['count']) }}</span>
                                        </div>
                                    @empty
                                        <div class="rssa-chart-legend-item">
                                            <span class="rssa-chart-dot" style="background: #cbd5e1;"></span>
                                            <span class="rssa-chart-label">No fund source data</span>
                                            <span class="rssa-chart-number">0</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="rssa-card rssa-chart-card rssa-animate">
                            <div class="rssa-section-head">
                                <div>
                                    <div class="rssa-section-title">RSSA Status Chart</div>
                                    <div class="rssa-metric-sub">Top status counts across the filtered dataset.</div>
                                </div>
                            </div>
                            <div class="rssa-chart-body">
                                <div class="rssa-column-chart">
                                    @forelse($statusChartBars as $bar)
                                        <div class="rssa-column" title="{{ $bar['label'] }}: {{ number_format($bar['count']) }}">
                                            <div class="rssa-column-value">{{ number_format($bar['count']) }}</div>
                                            <div class="rssa-column-bar-wrap">
                                                <div class="rssa-column-bar" style="height: {{ number_format($bar['height'], 2, '.', '') }}%; --bar-color: {{ $bar['color'] }};"></div>
                                            </div>
                                            <div class="rssa-column-label">{{ $bar['shortLabel'] }}</div>
                                        </div>
                                    @empty
                                        <div class="rssa-column">
                                            <div class="rssa-column-value">0</div>
                                            <div class="rssa-column-bar-wrap">
                                                <div class="rssa-column-bar" style="height: 12%; --bar-color: #cbd5e1;"></div>
                                            </div>
                                            <div class="rssa-column-label">No data</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="rssa-card rssa-chart-card rssa-animate">
                            <div class="rssa-section-head">
                                <div>
                                    <div class="rssa-section-title">Sustainability Snapshot</div>
                                    <div class="rssa-metric-sub">Functional, operational, and maintenance coverage.</div>
                                </div>
                            </div>
                            <div class="rssa-chart-body rssa-sustainability-bars">
                                @foreach($sustainabilityChartBars as $bar)
                                    <div class="rssa-sustainability-row">
                                        <div class="rssa-sustainability-head">
                                            <span>{{ $bar['label'] }}</span>
                                            <span>{{ number_format($bar['count']) }} | {{ number_format($bar['value'], 2) }}%</span>
                                        </div>
                                        <div class="rssa-sustainability-track">
                                            <div class="rssa-sustainability-fill" style="width: {{ number_format($bar['value'], 2, '.', '') }}%; background: linear-gradient(90deg, {{ $bar['color'] }}, {{ $bar['color'] }}cc);"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="rssa-duo">
                        <div class="rssa-card rssa-animate" style="padding: 18px;">
                            <div class="rssa-section-head">
                                <div>
                                    <div class="rssa-section-title">Fund Source Footprint</div>
                                    <div class="rssa-metric-sub">Distribution of project counts by funding source.</div>
                                </div>
                            </div>
                            <div class="rssa-list">
                                @forelse($fundSourceCounts as $source => $count)
                                    @php
                                        $style = $fundPalette[$loop->index % count($fundPalette)];
                                        $fundWidth = $fundMax > 0 ? ($count / $fundMax) * 100 : 0;
                                    @endphp
                                    <div class="rssa-bar-row">
                                        <div class="rssa-bar-meta">
                                            <span>{{ $source }}</span>
                                            <span>{{ number_format($count) }}</span>
                                        </div>
                                        <div class="rssa-bar-track">
                                            <div class="rssa-bar-fill" style="width: {{ number_format($fundWidth, 2, '.', '') }}%; background: linear-gradient(90deg, {{ $style['color'] }}, {{ $style['color'] }}cc);"></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rssa-bar-row">
                                        <div class="rssa-bar-meta">
                                            <span>No fund source data found.</span>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="rssa-card rssa-animate" style="padding: 18px;">
                        <div class="rssa-section-head">
                            <div>
                                <div class="rssa-section-title">Status of Project (RSSA Status)</div>
                                <div class="rssa-metric-sub">Distribution of project statuses in the filtered RSSA dataset.</div>
                            </div>
                        </div>
                        <div class="rssa-list">
                            @php $statusMax = max(array_values($projectStatusCounts ?: ['-' => 0])); @endphp
                            @forelse($projectStatusCounts as $statusLabel => $count)
                                @php
                                    $width = $statusMax > 0 ? ($count / $statusMax) * 100 : 0;
                                    $color = $statusPalette[$loop->index % count($statusPalette)];
                                @endphp
                                <div class="rssa-bar-row" style="border-color: {{ $color }}22; background: {{ $color }}10;">
                                    <div class="rssa-bar-meta">
                                        <span>{{ $statusLabel }}</span>
                                        <span>{{ number_format($count) }} ({{ $countPercent($count, $totalProjects) }})</span>
                                    </div>
                                    <div class="rssa-bar-track">
                                        <div class="rssa-bar-fill" style="width: {{ number_format($width, 2, '.', '') }}%; background: linear-gradient(90deg, {{ $color }}, {{ $color }}cc);"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="rssa-bar-row">
                                    <div class="rssa-bar-meta">
                                        <span>No status data for the current filter set.</span>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rssa-status-grid">
                        <div class="rssa-card rssa-status-card rssa-animate">
                            <div class="rssa-status-title"><i class="fas fa-circle-check"></i> Functional Status</div>
                            <div class="rssa-status-pills">
                                @foreach($functionalCounts as $label => $count)
                                    @php
                                        $good = $label === 'Functional';
                                        $pillColor = $good ? '#166534' : '#b91c1c';
                                    @endphp
                                    <div class="rssa-pill">
                                        <span class="rssa-pill-label">{{ $label }}</span>
                                        <span class="rssa-pill-value" style="color: {{ $pillColor }};">{{ number_format($count) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rssa-card rssa-status-card rssa-animate">
                            <div class="rssa-status-title"><i class="fas fa-gears"></i> Operational Status</div>
                            <div class="rssa-status-pills">
                                @foreach($operationalCounts as $label => $count)
                                    @php
                                        $good = $label === 'Operational';
                                        $pillColor = $good ? '#1d4ed8' : '#c2410c';
                                    @endphp
                                    <div class="rssa-pill">
                                        <span class="rssa-pill-label">{{ $label }}</span>
                                        <span class="rssa-pill-value" style="color: {{ $pillColor }};">{{ number_format($count) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rssa-card rssa-status-card rssa-animate">
                            <div class="rssa-status-title"><i class="fas fa-screwdriver-wrench"></i> Maintenance Status</div>
                            <div class="rssa-status-pills">
                                @foreach($maintenanceCounts as $label => $count)
                                    @php
                                        $good = $label === 'Maintained';
                                        $pillColor = $good ? '#5b21b6' : '#475569';
                                    @endphp
                                    <div class="rssa-pill">
                                        <span class="rssa-pill-label">{{ $label }}</span>
                                        <span class="rssa-pill-value" style="color: {{ $pillColor }};">{{ number_format($count) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="rssa-card rssa-status-card rssa-animate">
                            <div class="rssa-status-title"><i class="fas fa-triangle-exclamation"></i> Non-Functional Categories</div>
                            <div class="rssa-status-pills">
                                @forelse($nonFunctionalCategoryCounts as $label => $count)
                                    <div class="rssa-pill">
                                        <span class="rssa-pill-label">{{ $label }}</span>
                                        <span class="rssa-pill-value" style="color: #b45309;">{{ number_format($count) }}</span>
                                    </div>
                                @empty
                                    <div class="rssa-pill">
                                        <span class="rssa-pill-label">No category data</span>
                                        <span class="rssa-pill-value" style="color: #475569;">0</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </div>

    <script>
        window.toggleRssaFilter = window.toggleRssaFilter || function (button) {
            const form = button.closest('.rssa-filter-form');
            if (!form) {
                return;
            }

            form.classList.toggle('collapsed');
            const isCollapsed = form.classList.contains('collapsed');
            button.setAttribute('aria-expanded', String(!isCollapsed));
        };

        (() => {
            const provinceMunicipalities = @json($provinceMunicipalities ?? []);
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city_municipality');
            if (!provinceSelect || !citySelect) {
                return;
            }

            const renderCities = () => {
                const province = provinceSelect.value;
                const selectedCity = citySelect.dataset.selectedCity || '';
                const cities = province && Array.isArray(provinceMunicipalities[province]) ? provinceMunicipalities[province] : [];

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
            };

            provinceSelect.addEventListener('change', () => {
                citySelect.dataset.selectedCity = '';
                renderCities();
            });

            renderCities();
        })();
    </script>
@endsection
