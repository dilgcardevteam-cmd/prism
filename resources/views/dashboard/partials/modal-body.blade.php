@php
    $modalId = $modalId ?? '';
    $statusSubaybayanProjectsModalMap = $statusSubaybayanProjectsMap ?? [];
    $fundSourceProjectsModalMap = $fundSourceProjectsMap ?? [];
    $financialStatusProjectsModal = collect($financialStatusProjects ?? []);
    $balanceProjectsModal = collect($projectsWithBalance ?? []);
    $projectAtRiskAgingProjectsModal = $projectAtRiskAgingProjects ?? [];
    $projectAtRiskSlippageProjectsModal = $projectAtRiskSlippageProjects ?? [];
    $projectUpdateRiskProjectsModal = $projectUpdateRiskProjects ?? [];
    $projectUpdateStatusStyles = [
        'High Risk' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'badgeBg' => '#fee2e2', 'badgeColor' => '#991b1b', 'accent' => '#dc2626'],
        'Low Risk' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'badgeBg' => '#fef3c7', 'badgeColor' => '#92400e', 'accent' => '#f59e0b'],
        'No Risk' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'badgeBg' => '#dcfce7', 'badgeColor' => '#166534', 'accent' => '#16a34a'],
    ];
@endphp

{{-- 1. Status Subaybayan Modal --}}
@if (str_starts_with($modalId, 'status-subaybayan-'))
    @php
        $matchedStatus = null;
        foreach (($statusSubaybayanCounts ?? []) as $statusName => $count) {
            $key = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $statusName), '-');
            $testId = 'status-subaybayan-' . ($key !== '' ? $key : 'unspecified') . '-modal';
            if (strcasecmp($testId, $modalId) === 0) {
                $matchedStatus = $statusName;
                break;
            }
        }
        $statusModalProjects = $matchedStatus !== null ? collect($statusSubaybayanProjectsModalMap[$matchedStatus] ?? []) : collect();
        $statusModalProvinceOptions = $statusModalProjects->pluck('province')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $statusModalCityOptions = $statusModalProjects->pluck('city_municipality')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $statusModalFundingYearOptions = $statusModalProjects->pluck('funding_year')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $statusModalStatusOptions = $statusModalProjects->pluck('status')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
    @endphp

    @if ($statusModalProjects->isNotEmpty())
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $statusModalProjects->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Province</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($statusModalProvinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}">{{ $provinceOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>City/Municipality</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="3" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($statusModalCityOptions as $cityOption)
                        <option value="{{ $cityOption }}">{{ $cityOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Funding Year</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="4" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($statusModalFundingYearOptions as $fundingYearOption)
                        <option value="{{ $fundingYearOption }}">{{ $fundingYearOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Status</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="5" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($statusModalStatusOptions as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Funding Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statusModalProjects as $projectRow)
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->province ?: '-' }}</td>
                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                            <td>{{ $projectRow->funding_year ?: '-' }}</td>
                            <td>{{ $projectRow->status ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No {{ strtolower((string) $matchedStatus) }} projects found for the current dashboard filters.
        </div>
    @endif

{{-- 2. Fund Source Modal --}}
@elseif (str_starts_with($modalId, 'fund-source-'))
    @php
        $matchedFundSource = null;
        foreach (($fundSourceCounts ?? []) as $fsName => $count) {
            $key = trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $fsName), '-');
            $testId = 'fund-source-' . ($key !== '' ? $key : 'unspecified') . '-modal';
            if (strcasecmp($testId, $modalId) === 0) {
                $matchedFundSource = $fsName;
                break;
            }
        }
        $fundSourceModalProjects = $matchedFundSource !== null ? collect(
            $fundSourceProjectsModalMap[$matchedFundSource]
                ?? $fundSourceProjectsModalMap[strtoupper(trim((string) $matchedFundSource))]
                ?? []
        ) : collect();
        $fundSourceProvinceOptions = $fundSourceModalProjects->pluck('province')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $fundSourceCityOptions = $fundSourceModalProjects->pluck('city_municipality')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $fundSourceStatusOptions = $fundSourceModalProjects->pluck('status')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
    @endphp

    @if ($fundSourceModalProjects->isNotEmpty())
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $fundSourceModalProjects->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Province</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($fundSourceProvinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}">{{ $provinceOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>City/Municipality</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="3" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($fundSourceCityOptions as $cityOption)
                        <option value="{{ $cityOption }}">{{ $cityOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Status</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="4" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($fundSourceStatusOptions as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fundSourceModalProjects as $projectRow)
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->province ?: '-' }}</td>
                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                            <td>{{ $projectRow->status ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No projects found for {{ $matchedFundSource ?? 'fund source' }} under the current dashboard filters.
        </div>
    @endif

{{-- 3. Financial Status Modals (Allocation, Percentage, Obligation, Disbursement) --}}
@elseif (in_array($modalId, ['financial-allocation-projects-modal', 'financial-percentage-projects-modal', 'financial-obligation-projects-modal', 'financial-disbursement-projects-modal']))
    @php
        $financialStatusFilterOptionsStatus = $financialStatusProjectsModal
            ->pluck('status')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->sort()
            ->values();
    @endphp

    @if ($financialStatusProjectsModal->isNotEmpty())
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $financialStatusProjectsModal->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Status</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($financialStatusFilterOptionsStatus as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Status</th>
                        <th>Original Allocation</th>
                        <th>LGU Counterpart</th>
                        <th>Obligation</th>
                        <th>Disbursement</th>
                        <th>Reverted Allocation</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($financialStatusProjectsModal as $projectRow)
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->status ?: '-' }}</td>
                            <td>{{ $projectRow->original_allocation !== null ? number_format((float) $projectRow->original_allocation, 2) : '-' }}</td>
                            <td>{{ $projectRow->lgu_counterpart !== null ? number_format((float) $projectRow->lgu_counterpart, 2) : '-' }}</td>
                            <td>{{ $projectRow->obligation !== null ? number_format((float) $projectRow->obligation, 2) : '-' }}</td>
                            <td>{{ $projectRow->disbursement !== null ? number_format((float) $projectRow->disbursement, 2) : '-' }}</td>
                            <td>{{ $projectRow->reverted_allocation !== null ? number_format((float) $projectRow->reverted_allocation, 2) : '-' }}</td>
                            <td>{{ $projectRow->balance !== null ? number_format((float) $projectRow->balance, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No financial status projects found for the current dashboard filters.
        </div>
    @endif

{{-- 4. Balance Projects Modal --}}
@elseif ($modalId === 'financial-balance-projects-modal')
    @if ($balanceProjectsModal->isNotEmpty())
        @php
            $balanceStatusOptions = $balanceProjectsModal->pluck('status')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
            $balanceTotals = [
                'original_allocation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->original_allocation ?? 0)),
                'lgu_counterpart' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->lgu_counterpart ?? 0)),
                'reverted_allocation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->reverted_allocation ?? 0)),
                'obligation' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->obligation ?? 0)),
                'disbursement' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->disbursement ?? 0)),
                'balance' => (float) $balanceProjectsModal->sum(fn ($row) => (float) ($row->balance ?? 0)),
            ];
        @endphp
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $balanceProjectsModal->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Status</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($balanceStatusOptions as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Status</th>
                        <th>Original Allocation</th>
                        <th>LGU Counterpart</th>
                        <th>Reverted Allocation</th>
                        <th>Obligation</th>
                        <th>Disbursement</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balanceProjectsModal as $projectRow)
                        @php
                            $originalAllocation = $projectRow->original_allocation ?? null;
                            $lguCounterpart = $projectRow->lgu_counterpart ?? null;
                            $revertedAllocation = $projectRow->reverted_allocation ?? null;
                            $obligationAmount = $projectRow->obligation ?? null;
                            $disbursementAmount = $projectRow->disbursement ?? null;
                            $balanceAmount = $projectRow->balance ?? null;
                        @endphp
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->status ?: '-' }}</td>
                            <td>{{ $originalAllocation !== null ? number_format((float) $originalAllocation, 2) : '-' }}</td>
                            <td>{{ $lguCounterpart !== null ? number_format((float) $lguCounterpart, 2) : '-' }}</td>
                            <td>{{ $revertedAllocation !== null ? number_format((float) $revertedAllocation, 2) : '-' }}</td>
                            <td>{{ $obligationAmount !== null ? number_format((float) $obligationAmount, 2) : '-' }}</td>
                            <td>{{ $disbursementAmount !== null ? number_format((float) $disbursementAmount, 2) : '-' }}</td>
                            <td>{{ $balanceAmount !== null ? number_format((float) $balanceAmount, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                    <tr class="dashboard-modal-total-row">
                        <td colspan="3">Total</td>
                        <td>{{ number_format($balanceTotals['original_allocation'], 2) }}</td>
                        <td>{{ number_format($balanceTotals['lgu_counterpart'], 2) }}</td>
                        <td>{{ number_format($balanceTotals['reverted_allocation'], 2) }}</td>
                        <td>{{ number_format($balanceTotals['obligation'], 2) }}</td>
                        <td>{{ number_format($balanceTotals['disbursement'], 2) }}</td>
                        <td>{{ number_format($balanceTotals['balance'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No projects with balance found for the current dashboard filters.
        </div>
    @endif

{{-- 5. Project Risk Aging Modal --}}
@elseif (str_starts_with($modalId, 'project-risk-aging-'))
    @php
        $matchedAgingLabel = null;
        foreach (($projectAtRiskAgingSummaryOrder ?? ['High Risk', 'Low Risk', 'No Risk']) as $riskLabel) {
            $key = strtolower(str_replace(' ', '-', $riskLabel));
            if ('project-risk-aging-' . $key . '-modal' === $modalId) {
                $matchedAgingLabel = $riskLabel;
                break;
            }
        }
        $modalProjects = $matchedAgingLabel !== null ? collect($projectAtRiskAgingProjectsModal[$matchedAgingLabel] ?? []) : collect();
        $modalProvinceOptions = $modalProjects->pluck('province')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $modalCityOptions = $modalProjects->pluck('city_municipality')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
    @endphp

    @if ($modalProjects->isNotEmpty())
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $modalProjects->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Province</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($modalProvinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}">{{ $provinceOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>City/Municipality</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="3" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($modalCityOptions as $cityOption)
                        <option value="{{ $cityOption }}">{{ $cityOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Min Aging</span>
                <input type="number" step="0.01" data-modal-filter-control data-filter-mode="number-min" data-column-index="5" placeholder="0" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Max Aging</span>
                <input type="number" step="0.01" data-modal-filter-control data-filter-mode="number-max" data-column-index="5" placeholder="999" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Extraction Date</th>
                        <th>Aging (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modalProjects as $projectRow)
                        @php
                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                : '-';
                        @endphp
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->province ?: '-' }}</td>
                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                            <td>{{ $latestUpdateDate }}</td>
                            <td>{{ $projectRow->aging_days ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No {{ strtolower((string) $matchedAgingLabel) }} aging projects found for the current dashboard filters.
        </div>
    @endif

{{-- 6. Project Risk Slippage Modal --}}
@elseif (str_starts_with($modalId, 'project-risk-slippage-'))
    @php
        $matchedSlippageLabel = null;
        foreach (($projectAtRiskSummaryOrder ?? ['Ahead', 'No Risk', 'On Schedule', 'High Risk', 'Moderate Risk', 'Low Risk']) as $riskLabel) {
            $key = strtolower(str_replace(' ', '-', $riskLabel));
            if ('project-risk-slippage-' . $key . '-modal' === $modalId) {
                $matchedSlippageLabel = $riskLabel;
                break;
            }
        }
        $modalProjects = $matchedSlippageLabel !== null ? collect($projectAtRiskSlippageProjectsModal[$matchedSlippageLabel] ?? []) : collect();
        $modalProvinceOptions = $modalProjects->pluck('province')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $modalCityOptions = $modalProjects->pluck('city_municipality')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
        $modalRiskOptions = $modalProjects->pluck('risk_level')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->unique()->sort()->values();
    @endphp

    @if ($modalProjects->isNotEmpty())
        <div class="dashboard-modal-filter-panel" data-modal-filter-panel data-total-count="{{ $modalProjects->count() }}">
            <label class="dashboard-modal-filter-field">
                <span>Project Code</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="0" placeholder="Contains code" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Project Title</span>
                <input type="search" data-modal-filter-control data-filter-mode="contains" data-column-index="1" placeholder="Contains title" oninput="return window.runDashboardModalFilters(this)">
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Province</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="2" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($modalProvinceOptions as $provinceOption)
                        <option value="{{ $provinceOption }}">{{ $provinceOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>City/Municipality</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="3" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($modalCityOptions as $cityOption)
                        <option value="{{ $cityOption }}">{{ $cityOption }}</option>
                    @endforeach
                </select>
            </label>
            <label class="dashboard-modal-filter-field">
                <span>Risk Level</span>
                <select data-modal-filter-control data-filter-mode="exact" data-column-index="6" onchange="return window.runDashboardModalFilters(this)">
                    <option value="">All</option>
                    @foreach ($modalRiskOptions as $riskOption)
                        <option value="{{ $riskOption }}">{{ $riskOption }}</option>
                    @endforeach
                </select>
            </label>
            <div class="dashboard-modal-filter-actions">
                <button type="button" class="dashboard-modal-filter-clear" data-modal-filter-clear onclick="return window.clearDashboardModalFilters(this)">Clear</button>
                <span class="dashboard-modal-filter-count" data-modal-filter-count></span>
            </div>
        </div>
        <div class="dashboard-modal-empty-state dashboard-modal-filter-empty" data-modal-filter-empty hidden>
            No projects match the selected modal filters.
        </div>
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Extraction Date</th>
                        <th>Slippage</th>
                        <th>Risk Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modalProjects as $projectRow)
                        @php
                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                : '-';
                            $slippageDisplay = trim((string) ($projectRow->slippage_display ?? ''));
                            if ($slippageDisplay === '' && $projectRow->slippage_value !== null) {
                                $slippageDisplay = rtrim(rtrim(number_format((float) $projectRow->slippage_value, 2, '.', ''), '0'), '.');
                            }
                            if ($slippageDisplay !== '' && !str_contains($slippageDisplay, '%')) {
                                $slippageDisplay .= '%';
                            }
                        @endphp
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->province ?: '-' }}</td>
                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                            <td>{{ $latestUpdateDate }}</td>
                            <td>{{ $slippageDisplay !== '' ? $slippageDisplay : '-' }}</td>
                            <td>{{ $projectRow->risk_level ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No {{ strtolower((string) $matchedSlippageLabel) }} slippage projects found for the current dashboard filters.
        </div>
    @endif

{{-- 7. Project Update Status - All Risk Levels --}}
@elseif ($modalId === 'project-update-all-status-modal')
    @php
        $projectUpdateStatusOrder = $projectUpdateStatusOrder ?? ['High Risk', 'Low Risk', 'No Risk'];
        $projectUpdateAllStatusHasProjects = false;
        foreach ($projectUpdateStatusOrder as $riskLabel) {
            if (collect($projectUpdateRiskProjectsModal[$riskLabel] ?? [])->isNotEmpty()) {
                $projectUpdateAllStatusHasProjects = true;
                break;
            }
        }
    @endphp

    @if ($projectUpdateAllStatusHasProjects)
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Latest Date</th>
                        <th>Aging (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projectUpdateStatusOrder as $riskLabel)
                        @php
                            $riskStyle = $projectUpdateStatusStyles[$riskLabel] ?? ['badgeBg' => '#f3f4f6', 'badgeColor' => '#374151'];
                            $riskProjects = collect($projectUpdateRiskProjectsModal[$riskLabel] ?? []);
                        @endphp
                        @foreach ($riskProjects as $projectRow)
                            @php
                                $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                                $latestUpdateDate = $latestUpdateDateRaw !== ''
                                    ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                    : '-';
                            @endphp
                            <tr>
                                <td>
                                    <span class="project-update-status-badge" style="background-color: {{ $riskStyle['badgeBg'] }}; color: {{ $riskStyle['badgeColor'] }};">
                                        {{ $riskLabel }}
                                    </span>
                                </td>
                                <td>{{ $projectRow->project_code ?? '-' }}</td>
                                <td>{{ $projectRow->project_title ?: '-' }}</td>
                                <td>{{ $projectRow->province ?: '-' }}</td>
                                <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                                <td>{{ $latestUpdateDate }}</td>
                                <td>{{ $projectRow->aging_days ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No project update status projects found for the current dashboard filters.
        </div>
    @endif

{{-- 8. Project Update Individual Risk Modal --}}
@elseif (str_starts_with($modalId, 'project-update-'))
    @php
        $matchedUpdateLabel = null;
        foreach (($projectUpdateStatusOrder ?? ['High Risk', 'Low Risk', 'No Risk']) as $riskLabel) {
            $key = strtolower(str_replace(' ', '-', $riskLabel));
            if ('project-update-' . $key . '-modal' === $modalId) {
                $matchedUpdateLabel = $riskLabel;
                break;
            }
        }
        $modalProjects = $matchedUpdateLabel !== null ? collect($projectUpdateRiskProjectsModal[$matchedUpdateLabel] ?? []) : collect();
    @endphp

    @if ($modalProjects->isNotEmpty())
        <div class="dashboard-modal-table-wrap">
            <table class="dashboard-modal-table">
                <thead>
                    <tr>
                        <th>Project Code</th>
                        <th>Project Title</th>
                        <th>Province</th>
                        <th>City/Municipality</th>
                        <th>Latest Date</th>
                        <th>Aging (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modalProjects as $projectRow)
                        @php
                            $latestUpdateDateRaw = trim((string) ($projectRow->latest_update_date ?? ''));
                            $latestUpdateDate = $latestUpdateDateRaw !== ''
                                ? \Illuminate\Support\Carbon::parse($latestUpdateDateRaw)->format('M d, Y')
                                : '-';
                        @endphp
                        <tr>
                            <td>{{ $projectRow->project_code ?? '-' }}</td>
                            <td>{{ $projectRow->project_title ?: '-' }}</td>
                            <td>{{ $projectRow->province ?: '-' }}</td>
                            <td>{{ $projectRow->city_municipality ?: '-' }}</td>
                            <td>{{ $latestUpdateDate }}</td>
                            <td>{{ $projectRow->aging_days ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="dashboard-modal-empty-state">
            No {{ strtolower((string) $matchedUpdateLabel) }} projects found for the current dashboard filters.
        </div>
    @endif
@endif

