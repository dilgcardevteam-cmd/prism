@extends('layouts.dashboard')

@section('title', 'RLIP/LIME Dashboard')
@section('page-title', 'RLIP/LIME Dashboard')

@section('content')
    <div class="content-header">
        <h1>RLIP/LIME-20% Development Fund Dashboard</h1>
        <p>
            Metrics and distribution based on RLIP/LIME dataset.
            @if(!empty($sourceMeta['generated_at']))
                Last parsed: {{ \Illuminate\Support\Carbon::parse($sourceMeta['generated_at'])->format('Y-m-d h:i A') }}.
            @endif
        </p>
    </div>

    @include('projects.partials.project-section-tabs', ['activeTab' => $activeTab ?? 'rlip-lime'])

    <div style="background: #ffffff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 8px rgba(15,23,42,0.06);">
        @php
            $activeFilters = array_merge([
                'search' => '',
                'funding_year' => '',
                'fund_source' => '',
                'province' => '',
                'city' => '',
                'status' => '',
            ], $filters ?? []);
        @endphp

        <form id="rlip-dashboard-filters" method="GET" action="{{ route('projects.rlip-lime.dashboard') }}" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-bottom: 18px;">
            <div style="min-width: 220px; flex: 1;">
                <label for="dashboard-search" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Search</label>
                <input id="dashboard-search" type="text" name="search" value="{{ $activeFilters['search'] }}" placeholder="Search project code, title, location..." style="width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
            </div>
            <div style="min-width: 140px;">
                <label for="dashboard-year" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Funding Year</label>
                <select id="dashboard-year" name="funding_year" style="width: 100%; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    <option value="">All</option>
                    @foreach($fundingYears as $year)
                        <option value="{{ $year }}" {{ (string) $activeFilters['funding_year'] === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 160px;">
                <label for="dashboard-source" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Fund Source</label>
                <select id="dashboard-source" name="fund_source" style="width: 100%; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    <option value="">All</option>
                    @foreach($fundSources as $source)
                        <option value="{{ $source }}" {{ (string) $activeFilters['fund_source'] === (string) $source ? 'selected' : '' }}>{{ $source }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 160px;">
                <label for="dashboard-province" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Province</label>
                <select id="dashboard-province" name="province" style="width: 100%; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    <option value="">All</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province }}" {{ (string) $activeFilters['province'] === (string) $province ? 'selected' : '' }}>{{ $province }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 160px;">
                <label for="dashboard-city" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">City/Mun</label>
                <select id="dashboard-city" name="city" data-selected-city="{{ $activeFilters['city'] }}" style="width: 100%; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    <option value="">All</option>
                    @foreach($cityOptions as $city)
                        <option value="{{ $city }}" {{ (string) $activeFilters['city'] === (string) $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 160px;">
                <label for="dashboard-status" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Status</label>
                <select id="dashboard-status" name="status" style="width: 100%; padding: 7px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px;">
                    <option value="">All</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ (string) $activeFilters['status'] === (string) $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('projects.rlip-lime.dashboard') }}" style="padding: 8px 12px; background: #64748b; color: #ffffff; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;">
                Clear
            </a>
            <a href="{{ route('projects.rlip-lime', request()->query()) }}" style="padding: 8px 12px; background: #0369a1; color: #ffffff; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none;">
                Open Table
            </a>
        </form>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 18px;">
            <div style="border: 1px solid #dbeafe; background: #eff6ff; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Total Projects</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($totalProjects) }}</div>
            </div>
            <div style="border: 1px solid #dcfce7; background: #f0fdf4; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #166534; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Total Programmed</div>
                <div style="margin-top: 6px; font-size: 24px; color: #0f172a; font-weight: 800;">&#8369; {{ number_format($totalProgrammedAmount, 2) }}</div>
            </div>
            <div style="border: 1px solid #fde68a; background: #fffbeb; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Avg Completion</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($averageCompletion, 2) }}%</div>
            </div>
            <div style="border: 1px solid #e9d5ff; background: #faf5ff; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #6b21a8; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Total Employment</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($totalEmployment) }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px;" class="rlip-dashboard-breakdown-grid">
            <section style="border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; padding: 12px;">
                <h3 style="margin: 0 0 10px; color: #0f172a; font-size: 14px; font-weight: 700;">Projects by Status</h3>
                @forelse($statusBreakdown as $item)
                    @php
                        $barWidth = $topStatusCount > 0 ? round(($item['count'] / $topStatusCount) * 100, 2) : 0;
                    @endphp
                    <div style="margin-bottom: 9px;">
                        <div style="display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin-bottom: 4px;">
                            <span style="color: #334155;">{{ $item['label'] }}</span>
                            <strong style="color: #0f172a;">{{ number_format($item['count']) }}</strong>
                        </div>
                        <div style="height: 6px; border-radius: 999px; background: #f1f5f9;">
                            <div style="width: {{ $barWidth }}%; height: 100%; border-radius: 999px; background: #2563eb;"></div>
                        </div>
                    </div>
                @empty
                    <p style="margin: 0; color: #64748b; font-size: 12px;">No data for selected filters.</p>
                @endforelse
            </section>

            <section style="border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; padding: 12px;">
                <h3 style="margin: 0 0 10px; color: #0f172a; font-size: 14px; font-weight: 700;">Projects by Fund Source</h3>
                @forelse($fundSourceBreakdown as $item)
                    @php
                        $barWidth = $topFundSourceCount > 0 ? round(($item['count'] / $topFundSourceCount) * 100, 2) : 0;
                    @endphp
                    <div style="margin-bottom: 9px;">
                        <div style="display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin-bottom: 4px;">
                            <span style="color: #334155;">{{ $item['label'] }}</span>
                            <strong style="color: #0f172a;">{{ number_format($item['count']) }}</strong>
                        </div>
                        <div style="height: 6px; border-radius: 999px; background: #f1f5f9;">
                            <div style="width: {{ $barWidth }}%; height: 100%; border-radius: 999px; background: #059669;"></div>
                        </div>
                    </div>
                @empty
                    <p style="margin: 0; color: #64748b; font-size: 12px;">No data for selected filters.</p>
                @endforelse
            </section>

            <section style="border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; padding: 12px;">
                <h3 style="margin: 0 0 10px; color: #0f172a; font-size: 14px; font-weight: 700;">Top Provinces</h3>
                @forelse($provinceBreakdown as $item)
                    @php
                        $barWidth = $topProvinceCount > 0 ? round(($item['count'] / $topProvinceCount) * 100, 2) : 0;
                    @endphp
                    <div style="margin-bottom: 9px;">
                        <div style="display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin-bottom: 4px;">
                            <span style="color: #334155;">{{ $item['label'] }}</span>
                            <strong style="color: #0f172a;">{{ number_format($item['count']) }}</strong>
                        </div>
                        <div style="height: 6px; border-radius: 999px; background: #f1f5f9;">
                            <div style="width: {{ $barWidth }}%; height: 100%; border-radius: 999px; background: #7c3aed;"></div>
                        </div>
                    </div>
                @empty
                    <p style="margin: 0; color: #64748b; font-size: 12px;">No data for selected filters.</p>
                @endforelse
            </section>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 18px;">
            <div style="border: 1px solid #dbeafe; background: #f8fbff; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #1e40af; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Completed Projects</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($completedProjects) }}</div>
                <div style="font-size: 12px; color: #475569;">Delivery rate: {{ number_format($completedRatePercent, 2) }}%</div>
            </div>
            <div style="border: 1px solid #e2e8f0; background: #ffffff; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">On-going Projects</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($ongoingProjects) }}</div>
                <div style="font-size: 12px; color: #475569;">Active implementation pipeline</div>
            </div>
            <div style="border: 1px solid #fde68a; background: #fffbeb; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">Not Yet Started</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($notStartedProjects) }}</div>
                <div style="font-size: 12px; color: #475569;">Potential mobilization backlog</div>
            </div>
            <div style="border: 1px solid #fecaca; background: #fff5f5; border-radius: 10px; padding: 14px;">
                <div style="font-size: 11px; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">High Budget, Low Progress</div>
                <div style="margin-top: 6px; font-size: 28px; color: #0f172a; font-weight: 800;">{{ number_format($highBudgetLowProgressCount) }}</div>
                <div style="font-size: 12px; color: #475569;">Threshold: &#8369; {{ number_format($highBudgetThreshold, 2) }}</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px;" class="rlip-dashboard-ring-grid">
            <section class="rlip-infographic-card">
                <h3 style="margin: 0 0 12px; color: #0f172a; font-size: 14px; font-weight: 700;">Portfolio Completion Rate</h3>
                <div class="rlip-ring-wrap">
                    <div class="rlip-ring" style="--p: {{ max(0, min(100, (float) $completedRatePercent)) }}; --ring-color: #2563eb;">
                        <span>{{ number_format($completedRatePercent, 2) }}%</span>
                    </div>
                    <div class="rlip-ring-copy">
                        <p><strong>{{ number_format($completedProjects) }}</strong> projects completed</p>
                        <p>Out of <strong>{{ number_format($totalProjects) }}</strong> total projects in filter scope.</p>
                    </div>
                </div>
            </section>
            <section class="rlip-infographic-card">
                <h3 style="margin: 0 0 12px; color: #0f172a; font-size: 14px; font-weight: 700;">Documentation Coverage</h3>
                <div class="rlip-ring-wrap">
                    <div class="rlip-ring" style="--p: {{ max(0, min(100, (float) $documentationCoveragePercent)) }}; --ring-color: #059669;">
                        <span>{{ number_format($documentationCoveragePercent, 2) }}%</span>
                    </div>
                    <div class="rlip-ring-copy">
                        <p>AIP: <strong>{{ number_format($withAipCount) }}</strong> ({{ number_format($aipCoveragePercent, 2) }}%)</p>
                        <p>Brief attachment: <strong>{{ number_format($withBriefAttachmentCount) }}</strong> ({{ number_format($briefCoveragePercent, 2) }}%)</p>
                        <p>Completion doc: <strong>{{ number_format($withCompletionAttachmentCount) }}</strong> ({{ number_format($completionDocCoveragePercent, 2) }}%)</p>
                    </div>
                </div>
            </section>
            <section class="rlip-infographic-card">
                <h3 style="margin: 0 0 12px; color: #0f172a; font-size: 14px; font-weight: 700;">Schedule Risk Exposure</h3>
                <div class="rlip-ring-wrap">
                    <div class="rlip-ring" style="--p: {{ max(0, min(100, (float) $scheduleRiskPercent)) }}; --ring-color: #dc2626;">
                        <span>{{ number_format($scheduleRiskPercent, 2) }}%</span>
                    </div>
                    <div class="rlip-ring-copy">
                        <p>Overdue projects: <strong>{{ number_format($overdueCount) }}</strong></p>
                        <p>Due in 30 days: <strong>{{ number_format($dueSoonCount) }}</strong></p>
                        <p>Missing schedule fields: <strong>{{ number_format($withoutScheduleCount) }}</strong></p>
                    </div>
                </div>
            </section>
        </div>

        @php
            $topCompletionBucketCount = (int) ($completionBucketBreakdown->first()['count'] ?? 0);
            $topFundingYearCount = (int) ($fundingYearBreakdown->first()['count'] ?? 0);
            $topCityCount = (int) ($cityBreakdown->first()['count'] ?? 0);
            $topProjectTypeCount = (int) ($projectTypeBreakdown->first()['count'] ?? 0);
            $topModeCount = (int) ($modeBreakdown->first()['count'] ?? 0);
            $topProfileApprovalCount = (int) ($profileApprovalBreakdown->first()['count'] ?? 0);
            $topCompletionApprovalCount = (int) ($completionApprovalBreakdown->first()['count'] ?? 0);
        @endphp

        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px;" class="rlip-dashboard-breakdown-grid-4">
            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Completion Stage Distribution</h3>
                @forelse($completionBucketBreakdown as $item)
                    @php $barWidth = $topCompletionBucketCount > 0 ? round(($item['count'] / $topCompletionBucketCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Funding Year Distribution</h3>
                @forelse($fundingYearBreakdown as $item)
                    @php $barWidth = $topFundingYearCount > 0 ? round(($item['count'] / $topFundingYearCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Top Cities / Municipalities</h3>
                @forelse($cityBreakdown as $item)
                    @php $barWidth = $topCityCount > 0 ? round(($item['count'] / $topCityCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Project Type Mix</h3>
                @forelse($projectTypeBreakdown as $item)
                    @php $barWidth = $topProjectTypeCount > 0 ? round(($item['count'] / $topProjectTypeCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px;" class="rlip-dashboard-breakdown-grid">
            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Mode of Implementation</h3>
                @forelse($modeBreakdown as $item)
                    @php $barWidth = $topModeCount > 0 ? round(($item['count'] / $topModeCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Profile Approval Status</h3>
                @forelse($profileApprovalBreakdown as $item)
                    @php $barWidth = $topProfileApprovalCount > 0 ? round(($item['count'] / $topProfileApprovalCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Completion Approval Status</h3>
                @forelse($completionApprovalBreakdown as $item)
                    @php $barWidth = $topCompletionApprovalCount > 0 ? round(($item['count'] / $topCompletionApprovalCount) * 100, 2) : 0; @endphp
                    <div class="rlip-infographic-row">
                        <div class="rlip-infographic-row-head"><span>{{ $item['label'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>
                        <div class="rlip-infographic-bar"><div style="width: {{ $barWidth }}%;"></div></div>
                    </div>
                @empty
                    <p class="rlip-infographic-empty">No data for selected filters.</p>
                @endforelse
            </section>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;" class="rlip-dashboard-monitoring-grid">
            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Documentation & Budget Monitoring</h3>
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px;">
                    <div class="rlip-mini-stat">
                        <div class="rlip-mini-label">Total Contract Amount</div>
                        <div class="rlip-mini-value">&#8369; {{ number_format($totalContractAmount, 2) }}</div>
                    </div>
                    <div class="rlip-mini-stat">
                        <div class="rlip-mini-label">Average Programmed / Project</div>
                        <div class="rlip-mini-value">&#8369; {{ number_format($averageProgrammedAmount, 2) }}</div>
                    </div>
                </div>
                <div class="rlip-infographic-row">
                    <div class="rlip-infographic-row-head"><span>AIP Available</span><strong>{{ number_format($aipCoveragePercent, 2) }}%</strong></div>
                    <div class="rlip-infographic-bar"><div style="width: {{ max(0, min(100, (float) $aipCoveragePercent)) }}%;"></div></div>
                </div>
                <div class="rlip-infographic-row">
                    <div class="rlip-infographic-row-head"><span>Project Brief Attachment</span><strong>{{ number_format($briefCoveragePercent, 2) }}%</strong></div>
                    <div class="rlip-infographic-bar"><div style="width: {{ max(0, min(100, (float) $briefCoveragePercent)) }}%;"></div></div>
                </div>
                <div class="rlip-infographic-row">
                    <div class="rlip-infographic-row-head"><span>Completion Attachment</span><strong>{{ number_format($completionDocCoveragePercent, 2) }}%</strong></div>
                    <div class="rlip-infographic-bar"><div style="width: {{ max(0, min(100, (float) $completionDocCoveragePercent)) }}%;"></div></div>
                </div>
            </section>

            <section class="rlip-infographic-card">
                <h3 class="rlip-infographic-title">Schedule Health & Compliance Alerts</h3>
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;">
                    <div class="rlip-mini-stat">
                        <div class="rlip-mini-label">With Complete Schedule</div>
                        <div class="rlip-mini-value">{{ number_format($withScheduleCount) }}</div>
                    </div>
                    <div class="rlip-mini-stat">
                        <div class="rlip-mini-label">Missing Schedule Fields</div>
                        <div class="rlip-mini-value">{{ number_format($withoutScheduleCount) }}</div>
                    </div>
                    <div class="rlip-mini-stat rlip-mini-stat-risk">
                        <div class="rlip-mini-label">Overdue Projects</div>
                        <div class="rlip-mini-value">{{ number_format($overdueCount) }}</div>
                    </div>
                    <div class="rlip-mini-stat rlip-mini-stat-risk">
                        <div class="rlip-mini-label">Due in Next 30 Days</div>
                        <div class="rlip-mini-value">{{ number_format($dueSoonCount) }}</div>
                    </div>
                </div>
                <div style="margin-top: 12px; font-size: 12px; color: #475569;">
                    Completed projects missing completion date: <strong>{{ number_format($completedWithoutDateCount) }}</strong>
                </div>
            </section>
        </div>

    </div>

    <style>
        .rlip-infographic-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            padding: 12px;
        }

        .rlip-infographic-title {
            margin: 0 0 10px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
        }

        .rlip-infographic-row {
            margin-bottom: 9px;
        }

        .rlip-infographic-row-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            margin-bottom: 4px;
            color: #334155;
        }

        .rlip-infographic-row-head strong {
            color: #0f172a;
        }

        .rlip-infographic-bar {
            height: 7px;
            border-radius: 999px;
            background: #f1f5f9;
            overflow: hidden;
        }

        .rlip-infographic-bar > div {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #0ea5e9);
            transition: width 0.5s ease;
        }

        .rlip-infographic-empty {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .rlip-ring-wrap {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .rlip-ring {
            --p: 0;
            --ring-color: #2563eb;
            width: 96px;
            height: 96px;
            position: relative;
            border-radius: 999px;
            background: conic-gradient(var(--ring-color) calc(var(--p) * 1%), #e2e8f0 0);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .rlip-ring::before {
            content: '';
            width: 72px;
            height: 72px;
            border-radius: 999px;
            background: #ffffff;
            position: absolute;
        }

        .rlip-ring span {
            position: relative;
            z-index: 1;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .rlip-ring-copy {
            display: grid;
            gap: 5px;
            font-size: 12px;
            color: #475569;
        }

        .rlip-ring-copy p {
            margin: 0;
        }

        .rlip-mini-stat {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px;
        }

        .rlip-mini-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .rlip-mini-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            word-break: break-word;
        }

        .rlip-mini-stat-risk {
            border-color: #fecaca;
            background: #fff7f7;
        }

        .rlip-dashboard-breakdown-grid-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        @media (max-width: 1000px) {
            .rlip-dashboard-breakdown-grid {
                grid-template-columns: 1fr !important;
            }

            .rlip-dashboard-breakdown-grid-4,
            .rlip-dashboard-ring-grid,
            .rlip-dashboard-monitoring-grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            #rlip-dashboard-filters > div {
                width: 100%;
                min-width: 0 !important;
                flex: 1 1 100%;
            }

            #rlip-dashboard-filters > a {
                width: 100%;
                text-align: center;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .rlip-ring-wrap {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filtersForm = document.getElementById('rlip-dashboard-filters');
            const searchInput = document.getElementById('dashboard-search');
            const provinceSelect = document.getElementById('dashboard-province');
            const citySelect = document.getElementById('dashboard-city');
            const yearSelect = document.getElementById('dashboard-year');
            const sourceSelect = document.getElementById('dashboard-source');
            const statusSelect = document.getElementById('dashboard-status');
            const locationData = @json($provinceMunicipalities);
            const selectedCity = citySelect ? (citySelect.dataset.selectedCity || '') : '';
            const AUTO_SEARCH_DELAY_MS = 700;
            const AUTO_SEARCH_MIN_CHARS = 2;
            let searchTimer = null;
            let lastSubmittedSearch = searchInput ? searchInput.value.trim() : '';

            if (!filtersForm || !provinceSelect || !citySelect) {
                return;
            }

            const allCities = new Set();
            Object.values(locationData).forEach(function (cities) {
                if (!Array.isArray(cities)) {
                    return;
                }
                cities.forEach(function (city) {
                    allCities.add(city);
                });
            });

            function populateCityOptions(selectedProvince, preferredValue) {
                const currentValue = preferredValue || citySelect.value || '';
                citySelect.innerHTML = '';

                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'All';
                citySelect.appendChild(allOption);

                const cities = selectedProvince && Array.isArray(locationData[selectedProvince])
                    ? locationData[selectedProvince]
                    : Array.from(allCities);

                cities.sort().forEach(function (city) {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });

                if (currentValue && cities.includes(currentValue)) {
                    citySelect.value = currentValue;
                }
            }

            function submitFilters() {
                if (searchInput) {
                    lastSubmittedSearch = searchInput.value.trim();
                }
                filtersForm.requestSubmit();
            }

            function scheduleAutoSearch() {
                if (!searchInput) {
                    return;
                }

                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    const currentSearch = searchInput.value.trim();
                    const hasMinChars = currentSearch.length >= AUTO_SEARCH_MIN_CHARS;
                    const isCleared = currentSearch.length === 0;
                    if (!hasMinChars && !isCleared) {
                        return;
                    }
                    if (currentSearch === lastSubmittedSearch) {
                        return;
                    }
                    submitFilters();
                }, AUTO_SEARCH_DELAY_MS);
            }

            if (searchInput) {
                searchInput.addEventListener('input', scheduleAutoSearch);
            }

            provinceSelect.addEventListener('change', function () {
                populateCityOptions(this.value);
                citySelect.value = '';
                submitFilters();
            });

            [yearSelect, sourceSelect, citySelect, statusSelect]
                .filter(Boolean)
                .forEach(function (select) {
                    select.addEventListener('change', submitFilters);
                });

            populateCityOptions(provinceSelect.value, selectedCity);
        });
    </script>
@endsection
