@extends('layouts.dashboard')

@section('title', 'RLIP/LIME Project Details')
@section('page-title', 'RLIP/LIME Project Details')

@section('styles')
    <style>
        .rlip-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 16px;
            -webkit-overflow-scrolling: touch;
        }

        .rlip-tabs::-webkit-scrollbar {
            height: 6px;
        }

        .rlip-tabs::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .rlip-tab {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .rlip-tab:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1e3a8a;
        }

        .rlip-tab.is-active {
            background: #002C76;
            border-color: #002C76;
            color: #ffffff;
        }

        .rlip-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            padding: 0 6px;
            font-size: 10px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.2);
        }

        .rlip-tab:not(.is-active) .rlip-tab-count {
            background: #e2e8f0;
            color: #475569;
        }

        .rlip-tab-panel {
            display: none;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }

        .rlip-tab-panel.is-active {
            display: block;
        }

        .rlip-tab-panel-head {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            font-size: 13px;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .rlip-field-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .rlip-field-table th {
            background: #f8fafc;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .rlip-field-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
        }

        .rlip-field-table tr:last-child td {
            border-bottom: 0;
        }

        .rlip-basic-layout {
            padding: 12px;
            background: #f8fafc;
        }

        .rlip-basic-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(280px, 1fr);
            gap: 12px;
            align-items: start;
        }

        .rlip-basic-stack {
            display: grid;
            gap: 12px;
        }

        .rlip-basic-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .rlip-basic-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            color: #1e3a8a;
            font-size: 18px;
            font-weight: 700;
        }

        .rlip-basic-title i {
            font-size: 14px;
        }

        .rlip-basic-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .rlip-basic-info-item {
            padding: 10px;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            background: #f8fafc;
            min-height: 82px;
        }

        .rlip-basic-info-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .rlip-basic-info-value {
            font-size: 15px;
            color: #0f172a;
            line-height: 1.35;
            font-weight: 700;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .rlip-basic-description {
            margin: 0;
            font-size: 14px;
            line-height: 1.65;
            color: #334155;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .rlip-basic-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .rlip-basic-list li {
            display: grid;
            grid-template-columns: minmax(120px, 1fr) minmax(120px, 1fr);
            gap: 8px;
            padding: 8px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 14px;
            color: #334155;
        }

        .rlip-basic-list li:last-child {
            border-bottom: 0;
        }

        .rlip-basic-list .label {
            color: #64748b;
            font-weight: 600;
        }

        .rlip-basic-list .value {
            color: #1e293b;
            font-weight: 700;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .rlip-generic-text-block {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
        }

        .rlip-generic-text-block:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .rlip-generic-empty {
            margin: 0;
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .rlip-basic-grid {
                grid-template-columns: 1fr;
            }

            .rlip-basic-info-grid {
                grid-template-columns: 1fr;
            }

            .rlip-field-table th:first-child,
            .rlip-field-table td:first-child {
                min-width: 180px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="content-header">
        <h1>RLIP/LIME Project Details</h1>
        <p>Full categorized content from the RLIP/LIME master list.</p>
    </div>

    <div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
            <div>
                <h2 style="margin: 0; color: #002C76; font-size: 18px;">{{ $project['project_title'] ?: 'Untitled Project' }}</h2>
                <div style="margin-top: 6px; font-size: 12px; color: #6b7280;">
                    Project Code: <strong style="color: #374151;">{{ $project['project_code'] ?: '-' }}</strong>
                    &middot; Row #{{ $project['row_number'] ?? '-' }}
                    @if(!empty($sourceMeta['source_file']))
                        &middot; Source: {{ $sourceMeta['source_file'] }}
                    @endif
                </div>
            </div>
            <a href="{{ $backUrl }}" style="padding: 8px 12px; background-color: #002C76; color: #fff; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 20px;">
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f8fafc;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Location</div>
                <div style="margin-top: 4px; font-size: 13px; color: #0f172a;">{{ $project['location'] ?: '-' }}</div>
            </div>
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f8fafc;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Funding Year</div>
                <div style="margin-top: 4px; font-size: 13px; color: #0f172a;">{{ $project['funding_year'] ?: '-' }}</div>
            </div>
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f8fafc;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Fund Source</div>
                <div style="margin-top: 4px; font-size: 13px; color: #0f172a;">{{ $project['fund_source'] ?: '-' }}</div>
            </div>
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f8fafc;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">Project Status</div>
                <div style="margin-top: 4px; font-size: 13px; color: #0f172a;">{{ $project['project_status'] ?: '-' }}</div>
            </div>
        </div>

        @php
            $preferredSections = [
                'BASIC PROFILE',
                'IMAGE',
                'INITIAL PROJECT REPORT',
                'MONTHLY TARGETS',
                'LOGICAL FRAMEWORK',
                'MONTHLY ACCOMPLISHMENT (FORM 2)',
                'PROJECT EXCEPTION REPORT (FORM 3)',
                'PROJECT RESULTS (FORM 4)',
                'CERTIFICATE OF COMPLETION',
                'ADMIN DETAILS',
                'CONTRACT DETAILS',
            ];

            $availableSections = collect(array_keys($categoryValues ?? []));
            $orderedSections = collect($preferredSections)
                ->filter(fn (string $section) => $availableSections->contains($section))
                ->merge($availableSections->reject(fn (string $section) => in_array($section, $preferredSections, true)))
                ->values();
        @endphp

        @if($orderedSections->isEmpty())
            <p style="margin: 0; color: #6b7280; text-align: center; padding: 20px 0;">No categorized project details found for this record.</p>
        @else
            <div class="project-tabs rlip-tabs" role="tablist" aria-label="RLIP/LIME detail categories">
                @foreach($orderedSections as $section)
                    @php
                        $slug = \Illuminate\Support\Str::slug((string) $section);
                        $tabId = 'rlip-tab-' . $slug;
                        $panelId = 'rlip-panel-' . $slug;
                        $isActive = $loop->first;
                        $fieldCount = count($categoryValues[$section] ?? []);
                    @endphp
                    <button
                        type="button"
                        id="{{ $tabId }}"
                        class="project-tab rlip-tab{{ $isActive ? ' is-active' : '' }}"
                        data-rlip-tab-target="{{ $panelId }}"
                        role="tab"
                        aria-controls="{{ $panelId }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    >
                        <span>{{ $section }}</span>
                        <span class="rlip-tab-count">{{ $fieldCount }}</span>
                    </button>
                @endforeach
            </div>

            @foreach($orderedSections as $section)
                @php
                    $slug = \Illuminate\Support\Str::slug((string) $section);
                    $tabId = 'rlip-tab-' . $slug;
                    $panelId = 'rlip-panel-' . $slug;
                    $isActive = $loop->first;
                    $fields = $categoryValues[$section] ?? [];
                @endphp
                <div
                    id="{{ $panelId }}"
                    class="project-tab-panel rlip-tab-panel{{ $isActive ? ' is-active' : '' }}"
                    role="tabpanel"
                    aria-labelledby="{{ $tabId }}"
                    aria-hidden="{{ $isActive ? 'false' : 'true' }}"
                >
                    @if($section === 'BASIC PROFILE')
                        @php
                            $normalizedLookup = [];
                            foreach ($fields as $fieldItem) {
                                $normalizedLabel = strtoupper(trim((string) ($fieldItem['label'] ?? '')));
                                $normalizedLabel = preg_replace('/\s+/', ' ', $normalizedLabel) ?? $normalizedLabel;
                                $normalizedLookup[$normalizedLabel] = $fieldItem;
                            }

                            $pickField = function (array $aliases) use ($normalizedLookup): array {
                                foreach ($aliases as $alias) {
                                    $normalizedAlias = strtoupper(trim((string) $alias));
                                    $normalizedAlias = preg_replace('/\s+/', ' ', $normalizedAlias) ?? $normalizedAlias;
                                    if (isset($normalizedLookup[$normalizedAlias])) {
                                        return $normalizedLookup[$normalizedAlias];
                                    }
                                }

                                return ['value' => '', 'is_empty' => true];
                            };

                            $displayValue = function (array $fieldValue): string {
                                $value = trim((string) ($fieldValue['value'] ?? ''));
                                return (($fieldValue['is_empty'] ?? true) || $value === '') ? '-' : $value;
                            };

                            $basicProjectCode = $pickField(['PROJECT CODE']);
                            $basicProjectTitle = $pickField(['PROJECT TITLE']);
                            $basicFundingYear = $pickField(['FUNDING YEAR']);
                            $basicFundSource = $pickField(['FUND SOURCE']);
                            $basicMode = $pickField(['MODE OF IMPLEMENTATION']);
                            $basicDescription = $pickField(['PROJECT DESCRIPTION']);
                            $basicPsgc = $pickField(['PSGC']);
                            $basicRegion = $pickField(['REGION']);
                            $basicProvince = $pickField(['PROVINCE']);
                            $basicCity = $pickField(['CITY / MUNICIPALITY', 'CITY/MUNICIPALITY', 'CITY / MUN']);
                            $basicBarangay = $pickField(['BARANGAY']);
                            $sourceGeneratedAt = !empty($sourceMeta['generated_at'])
                                ? \Illuminate\Support\Carbon::parse($sourceMeta['generated_at'])->format('M d, Y')
                                : '-';
                        @endphp
                        <div class="rlip-basic-layout">
                            <div class="rlip-basic-grid">
                                <div class="rlip-basic-stack">
                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-bars"></i> Basic Information</h3>
                                        <div class="rlip-basic-info-grid">
                                            <div class="rlip-basic-info-item">
                                                <div class="rlip-basic-info-label">Project Code</div>
                                                <div class="rlip-basic-info-value">{{ $displayValue($basicProjectCode) }}</div>
                                            </div>
                                            <div class="rlip-basic-info-item">
                                                <div class="rlip-basic-info-label">Project Title</div>
                                                <div class="rlip-basic-info-value">{{ $displayValue($basicProjectTitle) }}</div>
                                            </div>
                                            <div class="rlip-basic-info-item">
                                                <div class="rlip-basic-info-label">Funding Year</div>
                                                <div class="rlip-basic-info-value">{{ $displayValue($basicFundingYear) }}</div>
                                            </div>
                                            <div class="rlip-basic-info-item">
                                                <div class="rlip-basic-info-label">Fund Source</div>
                                                <div class="rlip-basic-info-value">{{ $displayValue($basicFundSource) }}</div>
                                            </div>
                                            <div class="rlip-basic-info-item">
                                                <div class="rlip-basic-info-label">Mode of Implementation</div>
                                                <div class="rlip-basic-info-value">{{ $displayValue($basicMode) }}</div>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="far fa-file-alt"></i> Description</h3>
                                        <p class="rlip-basic-description">{{ $displayValue($basicDescription) }}</p>
                                    </section>
                                </div>

                                <div class="rlip-basic-stack">
                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-map-marker-alt"></i> Location Details</h3>
                                        <ul class="rlip-basic-list">
                                            <li><span class="label">PSGC</span><span class="value">{{ $displayValue($basicPsgc) }}</span></li>
                                            <li><span class="label">Region</span><span class="value">{{ $displayValue($basicRegion) }}</span></li>
                                            <li><span class="label">Province</span><span class="value">{{ $displayValue($basicProvince) }}</span></li>
                                            <li><span class="label">City / Municipality</span><span class="value">{{ $displayValue($basicCity) }}</span></li>
                                            <li><span class="label">Barangay</span><span class="value">{{ $displayValue($basicBarangay) }}</span></li>
                                        </ul>
                                    </section>

                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-info-circle"></i> Additional Info</h3>
                                        <ul class="rlip-basic-list">
                                            <li><span class="label">Status</span><span class="value">{{ $project['project_status'] ?: '-' }}</span></li>
                                            <li><span class="label">Last Updated</span><span class="value">{{ $sourceGeneratedAt }}</span></li>
                                            <li><span class="label">Source File</span><span class="value">{{ $sourceMeta['source_file'] ?? '-' }}</span></li>
                                            <li><span class="label">Row Number</span><span class="value">#{{ $project['row_number'] ?? '-' }}</span></li>
                                        </ul>
                                    </section>
                                </div>
                            </div>
                        </div>
                    @else
                        @php
                            $displayValue = function (array $fieldValue): string {
                                $value = trim((string) ($fieldValue['value'] ?? ''));
                                return (($fieldValue['is_empty'] ?? true) || $value === '') ? '-' : $value;
                            };

                            $shortFields = [];
                            $longFields = [];
                            foreach ($fields as $fieldItem) {
                                $labelUpper = strtoupper(trim((string) ($fieldItem['label'] ?? '')));
                                $valueLength = mb_strlen(trim((string) ($fieldItem['value'] ?? '')));
                                $isLong = str_contains($labelUpper, 'DESCRIPTION')
                                    || str_contains($labelUpper, 'REMARK')
                                    || str_contains($labelUpper, 'NARRATIVE')
                                    || $valueLength > 120;

                                if ($isLong) {
                                    $longFields[] = $fieldItem;
                                } else {
                                    $shortFields[] = $fieldItem;
                                }
                            }

                            $splitIndex = (int) ceil(count($shortFields) / 2);
                            $leftFields = array_slice($shortFields, 0, $splitIndex);
                            $rightFields = array_slice($shortFields, $splitIndex);
                            $sourceGeneratedAt = !empty($sourceMeta['generated_at'])
                                ? \Illuminate\Support\Carbon::parse($sourceMeta['generated_at'])->format('M d, Y')
                                : '-';
                        @endphp
                        <div class="rlip-basic-layout">
                            <div class="rlip-basic-grid">
                                <div class="rlip-basic-stack">
                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-bars"></i> {{ $section }} Information</h3>
                                        @if(empty($leftFields))
                                            <p class="rlip-generic-empty">No fields found for this section.</p>
                                        @else
                                            <div class="rlip-basic-info-grid">
                                                @foreach($leftFields as $fieldItem)
                                                    <div class="rlip-basic-info-item">
                                                        <div class="rlip-basic-info-label">{{ $fieldItem['label'] }}</div>
                                                        <div class="rlip-basic-info-value">{{ $displayValue($fieldItem) }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </section>

                                    @if(!empty($longFields))
                                        <section class="rlip-basic-card">
                                            <h3 class="rlip-basic-title"><i class="far fa-file-alt"></i> Details</h3>
                                            @foreach($longFields as $fieldItem)
                                                <div class="rlip-generic-text-block">
                                                    <div class="rlip-basic-info-label">{{ $fieldItem['label'] }}</div>
                                                    <p class="rlip-basic-description">{{ $displayValue($fieldItem) }}</p>
                                                </div>
                                            @endforeach
                                        </section>
                                    @endif
                                </div>

                                <div class="rlip-basic-stack">
                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-info-circle"></i> Additional Info</h3>
                                        <ul class="rlip-basic-list">
                                            @forelse($rightFields as $fieldItem)
                                                <li>
                                                    <span class="label">{{ $fieldItem['label'] }}</span>
                                                    <span class="value">{{ $displayValue($fieldItem) }}</span>
                                                </li>
                                            @empty
                                                <li>
                                                    <span class="label">Info</span>
                                                    <span class="value">-</span>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </section>

                                    <section class="rlip-basic-card">
                                        <h3 class="rlip-basic-title"><i class="fas fa-database"></i> Metadata</h3>
                                        <ul class="rlip-basic-list">
                                            <li><span class="label">Status</span><span class="value">{{ $project['project_status'] ?: '-' }}</span></li>
                                            <li><span class="label">Last Updated</span><span class="value">{{ $sourceGeneratedAt }}</span></li>
                                            <li><span class="label">Source File</span><span class="value">{{ $sourceMeta['source_file'] ?? '-' }}</span></li>
                                            <li><span class="label">Row Number</span><span class="value">#{{ $project['row_number'] ?? '-' }}</span></li>
                                        </ul>
                                    </section>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = Array.from(document.querySelectorAll('[data-rlip-tab-target]'));
            const panels = Array.from(document.querySelectorAll('.rlip-tab-panel'));

            if (tabs.length === 0 || panels.length === 0) {
                return;
            }

            function setActivePanel(panelId) {
                panels.forEach(function (panel) {
                    const isActive = panel.id === panelId;
                    panel.classList.toggle('is-active', isActive);
                    panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });

                tabs.forEach(function (tab) {
                    const isActive = tab.getAttribute('data-rlip-tab-target') === panelId;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const panelId = tab.getAttribute('data-rlip-tab-target');
                    if (panelId) {
                        setActivePanel(panelId);
                    }
                });
            });

            const hashPanelId = window.location.hash ? window.location.hash.replace('#', '') : '';
            const hasHashPanel = panels.some(function (panel) {
                return panel.id === hashPanelId;
            });
            const initialPanelId = hasHashPanel ? hashPanelId : panels[0].id;
            setActivePanel(initialPanelId);
        });
    </script>
@endsection
