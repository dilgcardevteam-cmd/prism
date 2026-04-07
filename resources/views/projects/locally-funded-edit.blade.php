@extends('layouts.dashboard')

@section('title', 'Edit Locally Funded Project')
@section('page-title', 'Edit Locally Funded Project')

@section('content')
    <div class="content-header">
        <h1>Edit Locally Funded Project</h1>
        <p>Update the details for this locally funded project</p>
    </div>

    @if ($errors->any())
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <form action="{{ route('locally-funded-project.update', $project) }}" method="POST">
            @csrf
            @method('PUT')

            @php
                $showProfile = !$section || $section === 'profile';
                $showContract = !$section || $section === 'contract';
                $months = [
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December',
                ];
            @endphp

            <!-- PROJECT PROFILE SECTION -->
            <div style="margin-bottom: 24px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background-color: #f3f7ff; display: {{ $showProfile ? 'block' : 'none' }};">
                <h3 style="color: #002C76; font-size: 16px; font-weight: 700; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #002C76;">Project Profile</h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <!-- Project Description (Full Width) -->
                    <div style="grid-column: 1 / -1;">
                        <label for="project_description" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Description *</label>
                        <textarea id="project_description" name="project_description" required rows="3"
                                  style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; resize: vertical;">{{ old('project_description') }}</textarea>
                    </div>

                    <!-- Province -->
                    <div>
                        <label for="province" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Province *</label>
                        <select id="province" name="province" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Province --</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" {{ old('province') === $province ? 'selected' : '' }}>{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City/Municipality -->
                    <div>
                        <label for="city_municipality" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">City/Municipality *</label>
                        <select id="city_municipality" name="city_municipality" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Province First --</option>
                        </select>
                        <small style="color: #9ca3af; font-size: 12px; margin-top: 4px; display: block;">Select a province above to see available cities/municipalities</small>
                    </div>

                    <!-- Barangay -->
                    <div>
                        <label for="barangay" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Barangay *</label>
                        <div style="position: relative;">
                            <div id="barangay_badges" style="display: flex; flex-wrap: wrap; gap: 6px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; min-height: 44px; background-color: white; margin-bottom: 8px; align-content: flex-start;">
                                <span style="color: #9ca3af; font-size: 14px; align-self: center;">Click dropdown to add barangays</span>
                            </div>
                            <select id="barangay" name="barangay[]" multiple
                                    style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white; min-height: 120px;">
                            </select>
                        </div>
                        <small style="color: #9ca3af; font-size: 12px; margin-top: 4px; display: block;">Select city/municipality first, then click items to add as badges. Click badge X to remove.</small>
                        <!-- Hidden input to store selected values as JSON array for form submission -->
                        <input type="hidden" id="barangay_hidden" name="barangay_json" value="">
                    </div>

                    <!-- Funding Year -->
                    <div>
                        <label for="funding_year" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Funding Year *</label>
                        <select id="funding_year" name="funding_year" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Funding Year --</option>
                            @foreach($fundingYears as $year)
                                <option value="{{ $year }}" {{ old('funding_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fund Source -->
                    <div>
                        <label for="fund_source" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Fund Source *</label>
                        <select id="fund_source" name="fund_source" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Fund Source --</option>
                            @foreach($fundSources as $source)
                                <option value="{{ $source }}" {{ old('fund_source') === $source ? 'selected' : '' }}>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- SubayBayan Project Code -->
                    <div>
                        <label for="subaybayan_project_code" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">SubayBayan Project Code *</label>
                        <input type="text" id="subaybayan_project_code" name="subaybayan_project_code" value="{{ old('subaybayan_project_code') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Project Name -->
                    <div>
                        <label for="project_name" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Name *</label>
                        <input type="text" id="project_name" name="project_name" value="{{ old('project_name') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Project Type -->
                    <div>
                        <label for="project_type" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Type *</label>
                        <select id="project_type" name="project_type" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Project Type --</option>
                            <option value="Evacuation Center / Multi-Purpose Hall" {{ old('project_type') === 'Evacuation Center / Multi-Purpose Hall' ? 'selected' : '' }}>Evacuation Center / Multi-Purpose Hall</option>
                            <option value="Water Supply and Sanitation" {{ old('project_type') === 'Water Supply and Sanitation' ? 'selected' : '' }}>Water Supply and Sanitation</option>
                            <option value="Local Roads and Bridges" {{ old('project_type') === 'Local Roads and Bridges' ? 'selected' : '' }}>Local Roads and Bridges</option>
                            <option value="Others" {{ old('project_type') === 'Others' ? 'selected' : '' }}>Others</option>
                        </select>
                    </div>

                    <!-- Date of NADAI -->
                    <div>
                        <label for="date_nadai" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NADAI *</label>
                        <input type="date" id="date_nadai" name="date_nadai" value="{{ old('date_nadai', $project->date_nadai ? $project->date_nadai->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- LGSF Allocation -->
                    <div>
                        <label for="lgsf_allocation" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">LGSF Allocation (based on NADAI) *</label>
                        <input type="text" id="lgsf_allocation" name="lgsf_allocation" value="{{ old('lgsf_allocation') ? number_format((float)preg_replace('/[^0-9.]/', '', old('lgsf_allocation')), 2, '.', ',') : '' }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- LGU Counterpart -->
                    <div>
                        <label for="lgu_counterpart" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">LGU Counterpart *</label>
                        <input type="text" id="lgu_counterpart" name="lgu_counterpart" value="{{ old('lgu_counterpart') ? number_format((float)preg_replace('/[^0-9.]/', '', old('lgu_counterpart')), 2, '.', ',') : '' }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- No. of Beneficiaries -->
                    <div>
                        <label for="no_of_beneficiaries" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">No. of Beneficiaries *</label>
                        <input type="number" id="no_of_beneficiaries" name="no_of_beneficiaries" value="{{ old('no_of_beneficiaries') }}" min="0" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- With Rainwater Collection System -->
                    <div>
                        <label for="rainwater_collection_system" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 13px;">With Rainwater Collection System (for Govt buildings)</label>
                        <select id="rainwater_collection_system" name="rainwater_collection_system"
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select --</option>
                            <option value="Yes" {{ old('rainwater_collection_system') === 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('rainwater_collection_system') === 'No' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <!-- Date of Confirmation Fund Receipt -->
                    <div>
                        <label for="date_confirmation_fund_receipt" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Confirmation Fund Receipt *</label>
                        <input type="date" id="date_confirmation_fund_receipt" name="date_confirmation_fund_receipt" value="{{ old('date_confirmation_fund_receipt', $project->date_confirmation_fund_receipt ? $project->date_confirmation_fund_receipt->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                </div>
            </div>

            <!-- CONTRACT INFORMATION SECTION -->
            <div style="margin-bottom: 24px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background-color: #f7fdf4; display: {{ $showContract ? 'block' : 'none' }};">
                <h3 style="color: #002C76; font-size: 16px; font-weight: 700; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #002C76;">Contract Information</h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <!-- Mode of Procurement -->
                    <div>
                        <label for="mode_of_procurement" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Mode of Procurement *</label>
                        <select id="mode_of_procurement" name="mode_of_procurement" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Mode of Procurement --</option>
                            <option value="admin" {{ old('mode_of_procurement') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="contract" {{ old('mode_of_procurement') === 'contract' ? 'selected' : '' }}>Contract</option>
                        </select>
                    </div>

                    <!-- Implementing Unit -->
                    <div>
                        <label for="implementing_unit" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Implementing Unit *</label>
                        <select id="implementing_unit" name="implementing_unit" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Implementing Unit --</option>
                            <option value="Provincial LGU" {{ old('implementing_unit') === 'Provincial LGU' ? 'selected' : '' }}>Provincial LGU</option>
                            <option value="Municipal LGU" {{ old('implementing_unit') === 'Municipal LGU' ? 'selected' : '' }}>Municipal LGU</option>
                            <option value="Barangay LGU" {{ old('implementing_unit') === 'Barangay LGU' ? 'selected' : '' }}>Barangay LGU</option>
                        </select>
                    </div>

                    <!-- Date of Posting (ITB) -->
                    <div>
                        <label for="date_posting_itb" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Posting (ITB) *</label>
                        <input type="date" id="date_posting_itb" name="date_posting_itb" value="{{ old('date_posting_itb', $project->date_posting_itb ? $project->date_posting_itb->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Date of Bid Opening -->
                    <div>
                        <label for="date_bid_opening" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Bid Opening *</label>
                        <input type="date" id="date_bid_opening" name="date_bid_opening" value="{{ old('date_bid_opening', $project->date_bid_opening ? $project->date_bid_opening->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Date of NOA -->
                    <div>
                        <label for="date_noa" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NOA *</label>
                        <input type="date" id="date_noa" name="date_noa" value="{{ old('date_noa', $project->date_noa ? $project->date_noa->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Date of NTP -->
                    <div>
                        <label for="date_ntp" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NTP *</label>
                        <input type="date" id="date_ntp" name="date_ntp" value="{{ old('date_ntp', $project->date_ntp ? $project->date_ntp->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Contractor -->
                    <div>
                        <label for="contractor" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contractor *</label>
                        <input type="text" id="contractor" name="contractor" value="{{ old('contractor') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Contract Amount -->
                    <div>
                        <label for="contract_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contract Amount *</label>
                        <input type="text" id="contract_amount" name="contract_amount" value="{{ old('contract_amount') ? number_format((float)preg_replace('/[^0-9.]/', '', old('contract_amount')), 2, '.', ',') : '' }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Project Duration -->
                    <div>
                        <label for="project_duration" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 13px;">Project Duration (based on Contract Agreement) *</label>
                        <input type="text" id="project_duration" name="project_duration" value="{{ old('project_duration') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Actual Start Date -->
                    <div>
                        <label for="actual_start_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Actual Start Date *</label>
                        <input type="date" id="actual_start_date" name="actual_start_date" value="{{ old('actual_start_date', $project->actual_start_date ? $project->actual_start_date->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Target Date of Completion (Based on original Project Duration) -->
                    <div>
                        <label for="target_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Target Date of Completion (Based on original Project Duration) *</label>
                        <input type="date" id="target_date_completion" name="target_date_completion" value="{{ old('target_date_completion', $project->target_date_completion ? $project->target_date_completion->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Revised Target Date of Completion -->
                    <div>
                        <label for="revised_target_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Revised Target Date of Completion (for FOU updating)</label>
                        <input type="date" id="revised_target_date_completion" name="revised_target_date_completion" value="{{ old('revised_target_date_completion', $project->revised_target_date_completion ? $project->revised_target_date_completion->format('Y-m-d') : '') }}"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Actual Date of Completion -->
                    <div>
                        <label for="actual_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Actual Date of Completion</label>
                        <input type="date" id="actual_date_completion" name="actual_date_completion" value="{{ old('actual_date_completion', $project->actual_date_completion ? $project->actual_date_completion->format('Y-m-d') : '') }}"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <!-- FINANCIAL ACCOMPLISHMENT SECTION -->
            <div id="financialAccomplishmentSection" style="margin-bottom: 24px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background-color: #f1f8ff;">
                <h3 style="color: #002C76; font-size: 16px; font-weight: 700; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #002C76;">Financial Accomplishment (based on Subaybayan)</h3>

                <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                    <!-- Obligated Amount -->
                    <div>
                        <label for="obligation" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Obligated Amount</label>
                        <input type="text" id="obligation" name="obligation" value="{{ old('obligation') ? number_format((float)preg_replace('/[^0-9.]/', '', old('obligation')), 2, '.', ',') : '' }}" placeholder="0.00"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                        <details class="monthly-details financial-monthly-details" style="margin-top: 8px;">
                            <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                            <div style="margin-top: 10px;">
                                <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                    <div>Month</div>
                                    <div>Value</div>
                                    <div>Date & Time</div>
                                    <div>Updated By</div>
                                </div>
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" value="" placeholder="-" data-financial-field="obligation" data-financial-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Disbursed Amount -->
                    <div>
                        <label for="disbursed_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Disbursed Amount</label>
                        <input type="text" id="disbursed_amount" name="disbursed_amount" value="{{ old('disbursed_amount') ? number_format((float)preg_replace('/[^0-9.]/', '', old('disbursed_amount')), 2, '.', ',') : '' }}" placeholder="0.00"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                        <details class="monthly-details financial-monthly-details" style="margin-top: 8px;">
                            <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                            <div style="margin-top: 10px;">
                                <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                    <div>Month</div>
                                    <div>Value</div>
                                    <div>Date & Time</div>
                                    <div>Updated By</div>
                                </div>
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" value="" placeholder="-" data-financial-field="disbursed_amount" data-financial-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Reverted Amount -->
                    <div>
                        <label for="reverted_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Reverted Amount</label>
                        <input type="text" id="reverted_amount" name="reverted_amount" value="{{ old('reverted_amount') ? number_format((float)preg_replace('/[^0-9.]/', '', old('reverted_amount')), 2, '.', ',') : '' }}" placeholder="0.00"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                        <details class="monthly-details financial-monthly-details" style="margin-top: 8px;">
                            <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                            <div style="margin-top: 10px;">
                                <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                    <div>Month</div>
                                    <div>Value</div>
                                    <div>Date & Time</div>
                                    <div>Updated By</div>
                                </div>
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" value="" placeholder="-" data-financial-field="reverted_amount" data-financial-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Balance -->
                    <div>
                        <label for="balance" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Balance</label>
                        <input type="text" id="balance" name="balance" value="{{ old('balance') ? number_format((float)preg_replace('/[^0-9.]/', '', old('balance')), 2, '.', ',') : '' }}" placeholder="0.00"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <!-- Utilization Rate -->
                    <div>
                        <label for="utilization_rate" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Utilization Rate</label>
                        <input type="number" id="utilization_rate" name="utilization_rate" value="{{ old('utilization_rate') }}" step="0.01" min="0" max="100" placeholder="0.00"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                        <small style="color: #9ca3af; font-size: 12px; margin-top: 4px; display: block;">Percentage (0 - 100)</small>
                        <details class="monthly-details financial-monthly-details" style="margin-top: 8px;">
                            <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                            <div style="margin-top: 10px;">
                                <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                    <div>Month</div>
                                    <div>Value</div>
                                    <div>Date & Time</div>
                                    <div>Updated By</div>
                                </div>
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" value="" placeholder="-" data-financial-field="utilization_rate" data-financial-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label for="financial_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Remarks</label>
                        <textarea id="financial_remarks" name="financial_remarks" rows="3"
                                  style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; resize: vertical;">{{ old('financial_remarks') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- PHYSICAL ACCOMPLISHMENT SECTION -->
            <div id="physicalAccomplishmentSection" style="margin-bottom: 24px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background-color: #fef3c7;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 2px solid #92400e; padding-bottom: 10px;">
                    <h3 style="color: #92400e; font-size: 16px; font-weight: 700; margin: 0;">Physical Accomplishment</h3>
                    <button type="button" id="physical_update_button" style="display: inline-flex; align-items: center; padding: 8px 16px; background-color: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2);">
                        <i class="fas fa-pen" style="margin-right: 6px;"></i> Update
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                    <!-- FOU Physical Accomplishment -->
                    <div style="padding: 16px; border: 1px solid #fbbf24; border-radius: 10px; background-color: #fffbeb;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #92400e;">FOU Physical Accomplishment</h4>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="status_project_fou" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Status of Project (for FOU updating)</label>
                                <select id="status_project_fou" name="status_project_fou"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: white;">
                                    <option value="">-- Select Status --</option>
                                    <option value="COMPLETED" {{ old('status_project_fou') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                    <option value="ONGOING" {{ old('status_project_fou') === 'ONGOING' ? 'selected' : '' }}>On-going</option>
                                    <option value="BID EVALUATION/OPENING" {{ old('status_project_fou') === 'BID EVALUATION/OPENING' ? 'selected' : '' }}>Bid Evaluation/Opening</option>
                                    <option value="NOA ISSUANCE" {{ old('status_project_fou') === 'NOA ISSUANCE' ? 'selected' : '' }}>NOA Issuance</option>
                                    <option value="DED PREPARATION" {{ old('status_project_fou') === 'DED PREPARATION' ? 'selected' : '' }}>DED Preparation</option>
                                    <option value="NOT YET STARTED" {{ old('status_project_fou') === 'NOT YET STARTED' ? 'selected' : '' }}>Not Yet Started</option>
                                    <option value="ITB/AD POSTED" {{ old('status_project_fou') === 'ITB/AD POSTED' ? 'selected' : '' }}>ITB/AD Posted</option>
                                    <option value="TERMINATED" {{ old('status_project_fou') === 'TERMINATED' ? 'selected' : '' }}>Terminated</option>
                                    <option value="CANCELLED" {{ old('status_project_fou') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                <button type="submit" class="physical-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="accomplishment_pct" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">% of Accomplishment (for FOU updating)</label>
                                <input type="number" id="accomplishment_pct" name="accomplishment_pct" value="{{ old('accomplishment_pct') }}" step="0.01" min="0" max="100" placeholder="0.00"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <details class="monthly-details physical-monthly-details" style="margin-top: 8px;">
                                    <summary class="monthly-summary" style="cursor: pointer; color: #92400e; background-color: #fed7aa; border: 1px solid #fdba74; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                                    <div style="margin-top: 10px;">
                                        <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                            <div>Month</div>
                                            <div>Value</div>
                                            <div>Date & Time</div>
                                            <div>Updated By</div>
                                        </div>
                                        <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                            @foreach($months as $monthNumber => $monthName)
                                                <div>{{ $monthName }}</div>
                                                <div>
                                                    <input type="number" step="0.01" min="0" max="100" value="" placeholder="-" data-physical-field="accomplishment_pct" data-physical-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                                </div>
                                                <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                                <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                                <button type="submit" class="physical-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="slippage" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Slippage (for FOU updating)</label>
                                <input type="number" id="slippage" name="slippage" value="{{ old('slippage') }}" step="0.01" min="0" placeholder="0.00"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <details class="monthly-details physical-monthly-details" style="margin-top: 8px;">
                                    <summary class="monthly-summary" style="cursor: pointer; color: #92400e; background-color: #fed7aa; border: 1px solid #fdba74; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                                    <div style="margin-top: 10px;">
                                        <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                            <div>Month</div>
                                            <div>Value</div>
                                            <div>Date & Time</div>
                                            <div>Updated By</div>
                                        </div>
                                        <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                            @foreach($months as $monthNumber => $monthName)
                                                <div>{{ $monthName }}</div>
                                                <div>
                                                    <input type="number" step="0.01" min="0" value="" placeholder="-" data-physical-field="slippage" data-physical-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                                </div>
                                                <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">-</span></div>
                                                <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">-</span></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                                <button type="submit" class="physical-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="actual_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Actual Date of Completion</label>
                                <input type="date" id="actual_date_completion" name="actual_date_completion" value="{{ old('actual_date_completion', $project->actual_date_completion ? $project->actual_date_completion->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="physical-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="nc_letters" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Issued with Non-Compliance (NC) Letters</label>
                                <select id="nc_letters" name="nc_letters"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: white;">
                                    <option value="">-- Select --</option>
                                    <option value="Yes" {{ old('nc_letters') === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('nc_letters') === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <button type="submit" class="physical-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RO Physical Accomplishment -->
                    <div style="padding: 16px; border: 1px solid #60a5fa; border-radius: 10px; background-color: #eff6ff;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #1d4ed8;">RO Physical Accomplishment</h4>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="status_project_ro" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Status of Project per Subaybayan (for RO updating)</label>
                                <select id="status_project_ro" name="status_project_ro"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: white;">
                                    <option value="">-- Select Status --</option>
                                    <option value="COMPLETED" {{ old('status_project_ro') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                    <option value="ONGOING" {{ old('status_project_ro') === 'ONGOING' ? 'selected' : '' }}>On-going</option>
                                    <option value="BID EVALUATION/OPENING" {{ old('status_project_ro') === 'BID EVALUATION/OPENING' ? 'selected' : '' }}>Bid Evaluation/Opening</option>
                                    <option value="NOA ISSUANCE" {{ old('status_project_ro') === 'NOA ISSUANCE' ? 'selected' : '' }}>NOA Issuance</option>
                                    <option value="DED PREPARATION" {{ old('status_project_ro') === 'DED PREPARATION' ? 'selected' : '' }}>DED Preparation</option>
                                    <option value="NOT YET STARTED" {{ old('status_project_ro') === 'NOT YET STARTED' ? 'selected' : '' }}>Not Yet Started</option>
                                    <option value="ITB/AD POSTED" {{ old('status_project_ro') === 'ITB/AD POSTED' ? 'selected' : '' }}>ITB/AD Posted</option>
                                    <option value="TERMINATED" {{ old('status_project_ro') === 'TERMINATED' ? 'selected' : '' }}>Terminated</option>
                                    <option value="CANCELLED" {{ old('status_project_ro') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                                </select>

            <!-- MONITORING / INSPECTION ACTIVITIES SECTION -->
            <div style="margin-bottom: 24px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background-color: #fff7ed;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 2px solid #9a3412; padding-bottom: 10px;">
                    <h3 style="color: #9a3412; font-size: 16px; font-weight: 700; margin: 0;">Monitoring/Inspection Activities</h3>
                    <button type="button" id="monitoring_update_button" style="display: inline-flex; align-items: center; padding: 8px 16px; background-color: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2);">
                        <i class="fas fa-pen" style="margin-right: 6px;"></i> Update
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                    <!-- DILG Provincial Office Activity -->
                    <div style="padding: 16px; border: 1px solid #fed7aa; border-radius: 10px; background-color: #fffaf4;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #9a3412;">DILG Provincial Office Activity</h4>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="po_monitoring_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date of Monitoring by PO</label>
                                <input type="date" id="po_monitoring_date" name="po_monitoring_date" value="{{ old('po_monitoring_date', $project->po_monitoring_date ? $project->po_monitoring_date->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="po_final_inspection" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">PO Conducted Final Inspection?</label>
                                <select id="po_final_inspection" name="po_final_inspection"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: white;">
                                    <option value="">-- Select --</option>
                                    <option value="Yes" {{ old('po_final_inspection', $project->po_final_inspection) === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('po_final_inspection', $project->po_final_inspection) === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="po_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                                <textarea id="po_remarks" name="po_remarks" rows="3"
                                          style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical;">{{ old('po_remarks', $project->po_remarks) }}</textarea>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DILG Regional Office Activity -->
                    <div style="padding: 16px; border: 1px solid #bfdbfe; border-radius: 10px; background-color: #eff6ff;">
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #1d4ed8;">DILG Regional Office Activity</h4>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="ro_monitoring_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date of Monitoring by RO</label>
                                <input type="date" id="ro_monitoring_date" name="ro_monitoring_date" value="{{ old('ro_monitoring_date', $project->ro_monitoring_date ? $project->ro_monitoring_date->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="ro_final_inspection" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">RO Conducted Final Inspection?</label>
                                <select id="ro_final_inspection" name="ro_final_inspection"
                                        style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: white;">
                                    <option value="">-- Select --</option>
                                    <option value="Yes" {{ old('ro_final_inspection', $project->ro_final_inspection) === 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ old('ro_final_inspection', $project->ro_final_inspection) === 'No' ? 'selected' : '' }}>No</option>
                                </select>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="ro_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                                <textarea id="ro_remarks" name="ro_remarks" rows="3"
                                          style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical;">{{ old('ro_remarks', $project->ro_remarks) }}</textarea>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- POST IMPLEMENTATION REQUIREMENTS SECTION -->
            <div style="margin-bottom: 24px; padding: 20px; border: 1px solid #fde68a; border-radius: 10px; background-color: #fffbeb;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 2px solid #92400e; padding-bottom: 10px;">
                    <h3 style="color: #92400e; font-size: 16px; font-weight: 700; margin: 0;">Post Implementation Requirements</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                    <div style="padding: 14px; border: 1px solid #bbf7d0; border-radius: 10px; background-color: #f0fdf4;">
                        <h5 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 700; color: #166534;">PCR Submission</h5>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="pcr_submission_deadline" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Deadline of PCR Submission</label>
                                <input type="date" id="pcr_submission_deadline" name="pcr_submission_deadline" value="{{ old('pcr_submission_deadline', $effectivePcrSubmissionDeadline ? $effectivePcrSubmissionDeadline->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="pcr_date_submitted_to_po" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to PO</label>
                                <input type="date" id="pcr_date_submitted_to_po" name="pcr_date_submitted_to_po" value="{{ old('pcr_date_submitted_to_po', $project->pcr_date_submitted_to_po ? $project->pcr_date_submitted_to_po->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="pcr_date_received_by_ro" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Received by RO</label>
                                <input type="date" id="pcr_date_received_by_ro" name="pcr_date_received_by_ro" value="{{ old('pcr_date_received_by_ro', $project->pcr_date_received_by_ro ? $project->pcr_date_received_by_ro->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="pcr_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                                <textarea id="pcr_remarks" name="pcr_remarks" rows="3"
                                          style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical;">{{ old('pcr_remarks', $project->pcr_remarks) }}</textarea>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <div style="padding: 14px; border: 1px solid #bfdbfe; border-radius: 10px; background-color: #eff6ff;">
                        <h5 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 700; color: #1d4ed8;">RSSA Report</h5>
                        <div style="display: grid; gap: 12px;">
                            <div>
                                <label for="rssa_report_deadline" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Deadline of RSSA Report</label>
                                <input type="date" id="rssa_report_deadline" name="rssa_report_deadline" value="{{ old('rssa_report_deadline', $effectiveRssaReportDeadline ? $effectiveRssaReportDeadline->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="rssa_submission_status" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Status of Submission</label>
                                <input type="text" id="rssa_submission_status" name="rssa_submission_status" value="{{ old('rssa_submission_status', $project->rssa_submission_status) }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="rssa_date_submitted_to_po" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to PO</label>
                                <input type="date" id="rssa_date_submitted_to_po" name="rssa_date_submitted_to_po" value="{{ old('rssa_date_submitted_to_po', $project->rssa_date_submitted_to_po ? $project->rssa_date_submitted_to_po->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="rssa_date_received_by_ro" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Received by RO</label>
                                <input type="date" id="rssa_date_received_by_ro" name="rssa_date_received_by_ro" value="{{ old('rssa_date_received_by_ro', $project->rssa_date_received_by_ro ? $project->rssa_date_received_by_ro->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="rssa_date_submitted_to_co" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to CO</label>
                                <input type="date" id="rssa_date_submitted_to_co" name="rssa_date_submitted_to_co" value="{{ old('rssa_date_submitted_to_co', $project->rssa_date_submitted_to_co ? $project->rssa_date_submitted_to_co->format('Y-m-d') : '') }}"
                                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box;">
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                            <div>
                                <label for="rssa_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                                <textarea id="rssa_remarks" name="rssa_remarks" rows="3"
                                          style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical;">{{ old('rssa_remarks', $project->rssa_remarks) }}</textarea>
                                <button type="submit" class="monitoring-save" style="display: none; margin-top: 8px; align-items: center; padding: 8px 16px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                                    <i class="fas fa-save" style="margin-right: 6px;"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions" style="display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; justify-content: flex-end;">
                <button type="button" id="update_button" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2);">
                    <i class="fas fa-pen" style="margin-right: 8px;"></i> Update
                </button>
                <button type="submit" id="save_button" style="display: none; align-items: center; padding: 12px 24px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Save
                </button>
                <a id="cancel_project_link" href="{{ route('projects.locally-funded') }}" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-times" style="margin-right: 8px;"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        button:hover, a:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 44, 118, 0.3);
        }

        .monthly-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .monthly-details[open] {
            border-color: #c7d2fe;
            background-color: #eef2ff;
            box-shadow: 0 6px 14px rgba(30, 64, 175, 0.12);
        }

        .monthly-details > summary {
            list-style: none;
        }

        .monthly-summary::marker {
            content: '';
        }

        .monthly-summary::after {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background-color: #c7d2fe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            transition: transform 0.2s ease;
        }

        details[open] > .monthly-summary::after {
            transform: rotate(45deg);
        }

        #financialAccomplishmentSection .monthly-details {
            width: 50%;
            min-width: 320px;
            box-sizing: border-box;
        }

        .form-actions {
            flex-wrap: wrap;
        }

        @media (max-width: 1024px) {
            div[style*="grid-template-columns: repeat(3"] {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 20px;
            }

            .content-header p {
                font-size: 12px;
            }

            #financialAccomplishmentSection .monthly-details {
                width: 100%;
                min-width: 0;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions > * {
                width: 100%;
                justify-content: center;
            }

            #physical_update_button,
            #monitoring_update_button,
            #update_button,
            #save_button,
            .physical-save,
            .monitoring-save {
                width: 40px !important;
                min-width: 40px;
                height: 40px !important;
                padding: 0 !important;
                border-radius: 999px !important;
                justify-content: center !important;
                font-size: 0 !important;
                line-height: 1;
                overflow: hidden;
            }

            #physical_update_button i,
            #monitoring_update_button i,
            #update_button i,
            #save_button i,
            .physical-save i,
            .monitoring-save i {
                margin-right: 0 !important;
                font-size: 14px !important;
            }
        }

    </style>

    @php
        $initialProvince = old('province', $project->province);
        $initialCity = old('city_municipality', $project->city_municipality);
        $initialBarangays = old('barangay_json')
            ? json_decode(old('barangay_json'), true)
            : array_values(array_filter(array_map('trim', explode(',', $project->barangay))));
    @endphp

    <script>
        // Complete data structure with provinces -> cities/municipalities -> barangays
        const locationData = {
          "Abra": {
            "Bangued": [
              "Agtangao",
              "Angad",
              "Bañacao",
              "Bangbangar",
              "Cabuloan",
              "Calaba",
              "Tablac",
              "Cosili West",
              "Cosili East",
              "Dangdangla",
              "Lingtan",
              "Lipcan",
              "Lubong",
              "Macarcarmay",
              "Maoay",
              "Macray",
              "Malita",
              "Palao",
              "Patucannay",
              "Sagap",
              "San Antonio",
              "Santa Rosa",
              "Sao-atan",
              "Sappaac",
              "Zone 2 Pob.",
              "Zone 3 Pob.",
              "Zone 4 Pob.",
              "Zone 5 Pob.",
              "Zone 6 Pob.",
              "Zone 7 Pob.",
              "Zone 1 Pob."
            ],
            "Boliney": [
              "Amti",
              "Bao-yan",
              "Danac East",
              "Dao-angan",
              "Dumagas",
              "Kilong-Olao",
              "Poblacion",
              "Danac West"
            ],
            "Bucay": [
              "Abang",
              "Bangbangcag",
              "Bangcagan",
              "Banglolao",
              "Bugbog",
              "Calao",
              "Dugong",
              "Labon",
              "Layugan",
              "Madalipay",
              "Pagala",
              "Palaquio",
              "Pakiling",
              "Patoc",
              "North Poblacion",
              "South Poblacion",
              "Quimloong",
              "Salnec",
              "San Miguel",
              "Siblong",
              "Tabiog"
            ],
            "Bucloc": [
              "Ducligan",
              "Labaan",
              "Lingey",
              "Lamao"
            ],
            "Daguioman": [
              "Ableg",
              "Cabaruyan",
              "Pikek",
              "Tui"
            ],
            "Danglas": [
              "Abaquid",
              "Cabaruan",
              "Caupasan",
              "Nagaparan",
              "Padangitan",
              "Pangal"
            ],
            "Dolores": [
              "Bayaan",
              "Cabaroan",
              "Calumbaya",
              "Cardona",
              "Isit",
              "Kimmalaba",
              "Libtec",
              "Lub-lubba",
              "Mudiit",
              "Namit-ingan",
              "Pacac",
              "Poblacion",
              "Salucag",
              "Talogtog",
              "Taping"
            ],
            "La Paz": [
              "Benben",
              "Bulbulala",
              "Buli",
              "Canan",
              "Liguis",
              "Malabbaga",
              "Mudeng",
              "Pidipid",
              "Poblacion",
              "San Gregorio",
              "Toon",
              "Udangan"
            ],
            "Lacub": [
              "Bacag",
              "Buneg",
              "Guinguinabang",
              "Lan-ag",
              "Pacoc",
              "Poblacion"
            ],
            "Lagangilang": [
              "Aguet",
              "Bacooc",
              "Balais",
              "Cayapa",
              "Dalaguisen",
              "Laang",
              "Lagben",
              "Laguiben",
              "Nagtipulan",
              "Nagtupacan",
              "Paganao",
              "Pawa",
              "Poblacion",
              "Presentar"
            ],
            "San Isidro": [
              "Tagodtod",
              "Taping",
              "Cabayogan",
              "Dalimag",
              "Langbaban",
              "Manayday",
              "Pantoc",
              "Poblacion",
              "Sabtan-olo",
              "San Marcial",
              "Tangbao"
            ],
            "Lagayan": [
              "Ba-i",
              "Collago",
              "Pang-ot",
              "Poblacion",
              "Pulot"
            ],
            "Langiden": [
              "Baac",
              "Dalayap",
              "Mabungtot",
              "Malapaao",
              "Poblacion",
              "Quillat"
            ],
            "Licuan-Baay": [
              "Bonglo",
              "Bulbulala",
              "Cawayan",
              "Domenglay",
              "Lenneng",
              "Mapisla",
              "Mogao",
              "Nalbuan",
              "Poblacion",
              "Subagan",
              "Tumalip"
            ],
            "Luba": [
              "Ampalioc",
              "Barit",
              "Gayaman",
              "Lul-luno",
              "Luzong",
              "Nagbukel-Tuquipa",
              "Poblacion",
              "Sabnangan"
            ],
            "Malibcong": [
              "Bayabas",
              "Binasaran",
              "Buanao",
              "Dulao",
              "Duldulao",
              "Gacab",
              "Lat-ey",
              "Mataragan",
              "Pacgued",
              "Taripan",
              "Umnap"
            ],
            "Manabo": [
              "Catacdegan Viejo",
              "Luzong",
              "Ayyeng",
              "San Jose Norte",
              "San Jose Sur",
              "San Juan Norte",
              "San Juan Sur",
              "San Ramon East",
              "San Ramon West",
              "Santo Tomas",
              "Catacdegan Nuevo"
            ],
            "Peñarrubia": [
              "Dumayco",
              "Lusuac",
              "Namarabar",
              "Patiao",
              "Malamsit",
              "Poblacion",
              "Riang",
              "Santa Rosa",
              "Tattawa"
            ],
            "Pidigan": [
              "Alinaya",
              "Arab",
              "Garreta",
              "Immuli",
              "Laskig",
              "Naguirayan",
              "Monggoc",
              "Pamutic",
              "Pangtud",
              "Poblacion East",
              "Poblacion West",
              "San Diego",
              "Sulbec",
              "Suyo",
              "Yuyeng"
            ],
            "Pilar": [
              "Bolbolo",
              "Brookside",
              "Ocup",
              "Dalit",
              "Dintan",
              "Gapang",
              "Kinabiti",
              "Maliplipit",
              "Nagcanasan",
              "Nanangduan",
              "Narnara",
              "Pang-ot",
              "Patad",
              "Poblacion",
              "San Juan East",
              "San Juan West",
              "South Balioag",
              "Tikitik"
            ],
            "Villavieja": [],
            "Sallapadan": [
              "Bazar",
              "Bilabila",
              "Gangal",
              "Maguyepyep",
              "Naguilian",
              "Saccaang",
              "Subusob",
              "Ud-udiao"
            ],
            "San Juan": [
              "Abualan",
              "Ba-ug",
              "Badas",
              "Cabcaborao",
              "Colabaoan",
              "Culiong",
              "Daoidao",
              "Guimba",
              "Lam-ag",
              "Lumobang",
              "Nangobongan",
              "Pattaoig",
              "Poblacion North",
              "Poblacion South",
              "Quidaoen",
              "Sabangan",
              "Silet",
              "Supi-il",
              "Tagaytay"
            ],
            "San Quintin": [
              "Labaan",
              "Palang",
              "Pantoc",
              "Poblacion",
              "Tangadan",
              "Villa Mercedes"
            ],
            "Tayum": [
              "Bagalay",
              "Basbasa",
              "Budac",
              "Bumagcat",
              "Cabaroan",
              "Deet",
              "Gaddani",
              "Patucannay",
              "Pias",
              "Poblacion",
              "Velasco"
            ],
            "Tineg": [
              "Poblacion",
              "Alaoa",
              "Anayan",
              "Apao",
              "Belaat",
              "Caganayan",
              "Cogon",
              "Lanec",
              "Lapat-Balantay",
              "Naglibacan"
            ],
            "Tubo": [
              "Alangtin",
              "Amtuagan",
              "Dilong",
              "Kili",
              "Poblacion",
              "Supo",
              "Tiempo",
              "Tubtuba",
              "Wayangan",
              "Tabacda"
            ],
            "Villaviciosa": [
              "Ap-apaya",
              "Bol-lilising",
              "Cal-lao",
              "Lap-lapog",
              "Lumaba",
              "Poblacion",
              "Tamac",
              "Tuquib"
            ]
          },
          "Benguet": {
            "Atok": [
              "Abiang",
              "Caliking",
              "Cattubo",
              "Naguey",
              "Paoay",
              "Pasdong",
              "Poblacion",
              "Topdac"
            ],
            "Bakun": [
              "Ampusongan",
              "Bagu",
              "Dalipey",
              "Gambang",
              "Kayapa",
              "Poblacion",
              "Sinacbat"
            ],
            "Bokod": [
              "Ambuclao",
              "Bila",
              "Bobok-Bisal",
              "Daclan",
              "Ekip",
              "Karao",
              "Nawal",
              "Pito",
              "Poblacion",
              "Tikey"
            ],
            "Buguias": [
              "Abatan",
              "Amgaleyguey",
              "Amlimay",
              "Baculongan Norte",
              "Bangao",
              "Buyacaoan",
              "Calamagan",
              "Catlubong",
              "Loo",
              "Natubleng",
              "Poblacion",
              "Baculongan Sur",
              "Lengaoan",
              "Sebang"
            ],
            "Itogon": [
              "Ampucao",
              "Dalupirip",
              "Gumatdang",
              "Loacan",
              "Poblacion",
              "Tinongdan",
              "Tuding",
              "Ucab",
              "Virac"
            ],
            "Kabayan": [
              "Adaoay",
              "Anchukey",
              "Ballay",
              "Bashoy",
              "Batan",
              "Duacan",
              "Eddet",
              "Gusaran",
              "Kabayan Barrio",
              "Lusod",
              "Pacso",
              "Poblacion",
              "Tawangan"
            ],
            "Kapangan": [
              "Balakbak",
              "Beleng-Belis",
              "Boklaoan",
              "Cayapes",
              "Cuba",
              "Datakan",
              "Gadang",
              "Gaswiling",
              "Labueg",
              "Paykek",
              "Poblacion Central",
              "Pudong",
              "Pongayan",
              "Sagubo",
              "Taba-ao"
            ],
            "Kibungan": [
              "Badeo",
              "Lubo",
              "Madaymen",
              "Palina",
              "Poblacion",
              "Sagpat",
              "Tacadang"
            ],
            "La Trinidad": [
              "Alapang",
              "Alno",
              "Ambiong",
              "Bahong",
              "Balili",
              "Beckel",
              "Bineng",
              "Betag",
              "Cruz",
              "Lubas",
              "Pico",
              "Poblacion",
              "Puguis",
              "Shilan",
              "Tawang",
              "Wangal"
            ],
            "Mankayan": [
              "Balili",
              "Bedbed",
              "Bulalacao",
              "Cabiten",
              "Colalo",
              "Guinaoang",
              "Paco",
              "Palasaan",
              "Poblacion",
              "Sapid",
              "Tabio",
              "Taneg"
            ],
            "Sablan": [
              "Bagong",
              "Balluay",
              "Banangan",
              "Banengbeng",
              "Bayabas",
              "Kamog",
              "Pappa",
              "Poblacion"
            ],
            "Tuba": [
              "Ansagan",
              "Camp One",
              "Camp 3",
              "Camp 4",
              "Nangalisan",
              "Poblacion",
              "San Pascual",
              "Tabaan Norte",
              "Tabaan Sur",
              "Tadiangan",
              "Taloy Norte",
              "Taloy Sur",
              "Twin Peaks"
            ],
            "Tublay": [
              "Ambassador",
              "Ambongdolan",
              "Ba-ayan",
              "Basil",
              "Daclan",
              "Caponga",
              "Tublay Central",
              "Tuel"
            ]
          },
          "Ifugao": {
            "Banaue": [
              "Amganad",
              "Anaba",
              "Bangaan",
              "Batad",
              "Bocos",
              "Banao",
              "Cambulo",
              "Ducligan",
              "Gohang",
              "Kinakin",
              "Poblacion",
              "Poitan",
              "San Fernando",
              "Balawis",
              "Ohaj",
              "Tam-an",
              "View Point",
              "Pula"
            ],
            "Hungduan": [
              "Abatan",
              "Bangbang",
              "Maggok",
              "Poblacion",
              "Bokiawan",
              "Hapao",
              "Lubo-ong",
              "Nungulunan",
              "Ba-ang"
            ],
            "Kiangan": [
              "Ambabag",
              "Baguinge",
              "Bokiawan",
              "Dalligan",
              "Duit",
              "Hucab",
              "Julongan",
              "Lingay",
              "Mungayang",
              "Nagacadan",
              "Pindongan",
              "Poblacion",
              "Tuplac",
              "Bolog"
            ],
            "Lagawe": [
              "Abinuan",
              "Banga",
              "Boliwong",
              "Burnay",
              "Buyabuyan",
              "Caba",
              "Cudog",
              "Dulao",
              "Jucbong",
              "Luta",
              "Montabiong",
              "Olilicon",
              "Poblacion South",
              "Ponghal",
              "Pullaan",
              "Tungngod",
              "Tupaya",
              "Poblacion East",
              "Poblacion North",
              "Poblacion West"
            ],
            "Lamut": [
              "Ambasa",
              "Hapid",
              "Lawig",
              "Lucban",
              "Mabatobato",
              "Magulon",
              "Nayon",
              "Panopdopan",
              "Payawan",
              "Pieza",
              "Poblacion East",
              "Pugol",
              "Salamague",
              "Bimpal",
              "Holowon",
              "Poblacion West",
              "Sanafe",
              "Umilag"
            ],
            "Mayoyao": [
              "Aduyongan",
              "Alimit",
              "Ayangan",
              "Balangbang",
              "Banao",
              "Banhal",
              "Bongan",
              "Buninan",
              "Chaya",
              "Chumang",
              "Guinihon",
              "Inwaloy",
              "Langayan",
              "Liwo",
              "Maga",
              "Magulon",
              "Mapawoy",
              "Mayoyao Proper",
              "Mongol",
              "Nalbu",
              "Nattum",
              "Palaad",
              "Poblacion",
              "Talboc",
              "Tulaed",
              "Bato-Alatbang",
              "Epeng"
            ],
            "Alfonso Lista": [
              "Bangar",
              "Busilac",
              "Calimag",
              "Calupaan",
              "Caragasan",
              "Dolowog",
              "Kiling",
              "Namnama",
              "Namillangan",
              "Pinto",
              "Poblacion",
              "San Jose",
              "San Juan",
              "San Marcos",
              "San Quintin",
              "Santa Maria",
              "Santo Domingo",
              "Little Tadian",
              "Ngileb",
              "Laya"
            ],
            "Aguinaldo": [
              "Awayan",
              "Bunhian",
              "Butac",
              "Chalalo",
              "Damag",
              "Galonogon",
              "Halag",
              "Itab",
              "Jacmal",
              "Majlong",
              "Mongayang",
              "Posnaan",
              "Ta-ang",
              "Talite",
              "Ubao",
              "Buwag"
            ],
            "Hingyon": [
              "Anao",
              "Bangtinon",
              "Bitu",
              "Cababuyan",
              "Mompolia",
              "Namulditan",
              "O-ong",
              "Piwong",
              "Poblacion",
              "Ubuag",
              "Umalbong",
              "Northern Cababuyan"
            ],
            "Tinoc": [
              "Ahin",
              "Ap-apid",
              "Binablayan",
              "Danggo",
              "Eheb",
              "Gumhang",
              "Impugong",
              "Luhong",
              "Tukucan",
              "Tulludan",
              "Wangwang"
            ],
            "Asipulo": [
              "Amduntog",
              "Antipolo",
              "Camandag",
              "Cawayan",
              "Hallap",
              "Namal",
              "Nungawa",
              "Panubtuban",
              "Pula",
              "Liwon"
            ]
          },
          "Kalinga": {
            "Balbalan": [
              "Ababa-an",
              "Balantoy",
              "Balbalan Proper",
              "Balbalasang",
              "Buaya",
              "Dao-angan",
              "Gawa-an",
              "Mabaca",
              "Maling",
              "Pantikian",
              "Poswoy",
              "Poblacion",
              "Talalang",
              "Tawang"
            ],
            "Lubuagan": [
              "Dangoy",
              "Mabilong",
              "Mabongtot",
              "Poblacion",
              "Tanglag",
              "Lower Uma",
              "Upper Uma",
              "Antonio Canao",
              "Uma del Norte"
            ],
            "Pasil": [
              "Ableg",
              "Balatoc",
              "Balinciagao Norte",
              "Cagaluan",
              "Colayo",
              "Dalupa",
              "Dangtalan",
              "Galdang",
              "Guina-ang",
              "Magsilay",
              "Malucsad",
              "Pugong",
              "Balenciagao Sur",
              "Bagtayan"
            ],
            "Pinukpuk": [
              "Aciga",
              "Allaguia",
              "Ammacian",
              "Apatan",
              "Ba-ay",
              "Ballayangon",
              "Bayao",
              "Wagud",
              "Camalog",
              "Katabbogan",
              "Dugpa",
              "Cawagayan",
              "Asibanglan",
              "Limos",
              "Magaogao",
              "Malagnat",
              "Mapaco",
              "Pakawit",
              "Pinukpuk Junction",
              "Socbot",
              "Taga",
              "Pinococ",
              "Taggay"
            ],
            "Rizal": [
              "Babalag East",
              "Calaocan",
              "Kinama",
              "Liwan East",
              "Liwan West",
              "Macutay",
              "San Pascual",
              "San Quintin",
              "Santor",
              "Babalag West",
              "Bulbol",
              "Romualdez",
              "San Francisco",
              "San Pedro"
            ],
            "City of Tabuk": [
              "Agbannawag",
              "Amlao",
              "Appas",
              "Bagumbayan",
              "Balawag",
              "Balong",
              "Bantay",
              "Bulanao",
              "Cabaritan",
              "Cabaruan",
              "Calaccad",
              "Calanan",
              "Dilag",
              "Dupag",
              "Gobgob",
              "Guilayon",
              "Lanna",
              "Laya East",
              "Laya West",
              "Lucog",
              "Magnao",
              "Magsaysay",
              "Malalao",
              "Masablang",
              "Nambaran",
              "Nambucayan",
              "Naneng",
              "Dagupan Centro",
              "San Juan",
              "Suyang",
              "Tuga",
              "Bado Dangwa",
              "Bulo",
              "Casigayan",
              "Cudal",
              "Dagupan Weste",
              "Lacnog",
              "Malin-awa",
              "New Tanglag",
              "San Julian",
              "Bulanao Norte",
              "Ipil",
              "Lacnog West"
            ],
            "Tanudan": [
              "Anggacan",
              "Babbanoy",
              "Dacalan",
              "Gaang",
              "Lower Mangali",
              "Lower Taloctoc",
              "Lower Lubo",
              "Upper Lubo",
              "Mabaca",
              "Pangol",
              "Poblacion",
              "Upper Taloctoc",
              "Anggacan Sur",
              "Dupligan",
              "Lay-asan",
              "Mangali Centro"
            ],
            "Tinglayan": [
              "Ambato Legleg",
              "Bangad Centro",
              "Basao",
              "Belong Manubal",
              "Butbut",
              "Bugnay",
              "Buscalan",
              "Dananao",
              "Loccong",
              "Luplupa",
              "Mallango",
              "Poblacion",
              "Sumadel 1",
              "Sumadel 2",
              "Tulgao East",
              "Tulgao West",
              "Upper Bangad",
              "Ngibat",
              "Old Tinglayan",
              "Lower Bangad"
            ]
          },
          "Mountain Province": {
            "Barlig": [
              "Chupac",
              "Fiangtin",
              "Kaleo",
              "Latang",
              "Lias Kanluran",
              "Lingoy",
              "Lunas",
              "Macalana",
              "Ogo-og",
              "Gawana",
              "Lias Silangan"
            ],
            "Bauko": [
              "Abatan",
              "Bagnen Oriente",
              "Bagnen Proper",
              "Balintaugan",
              "Banao",
              "Bila",
              "Guinzadan Central",
              "Guinzadan Norte",
              "Guinzadan Sur",
              "Lagawa",
              "Leseb",
              "Mabaay",
              "Mayag",
              "Monamon Norte",
              "Monamon Sur",
              "Mount Data",
              "Otucan Norte",
              "Otucan Sur",
              "Poblacion",
              "Sadsadan",
              "Sinto",
              "Tapapan"
            ],
            "Besao": [
              "Agawa",
              "Ambaguio",
              "Banguitan",
              "Besao East",
              "Besao West",
              "Catengan",
              "Gueday",
              "Lacmaan",
              "Laylaya",
              "Padangan",
              "Payeo",
              "Suquib",
              "Tamboan",
              "Kin-iway"
            ],
            "Bontoc": [
              "Alab Proper",
              "Alab Oriente",
              "Balili",
              "Bayyo",
              "Bontoc Ili",
              "Caneo",
              "Dalican",
              "Gonogon",
              "Guinaang",
              "Mainit",
              "Maligcong",
              "Samoki",
              "Talubin",
              "Tocucan",
              "Poblacion",
              "Caluttit"
            ],
            "Natonin": [
              "Alunogan",
              "Balangao",
              "Banao",
              "Banawal",
              "Butac",
              "Maducayan",
              "Poblacion",
              "Saliok",
              "Sta. Isabel",
              "Tonglayan",
              "Pudo"
            ],
            "Paracelis": [
              "Anonat",
              "Bacarri",
              "Bananao",
              "Bantay",
              "Butigue",
              "Bunot",
              "Buringal",
              "Palitud",
              "Poblacion"
            ],
            "Sabangan": [
              "Bao-angan",
              "Bun-ayan",
              "Busa",
              "Camatagan",
              "Capinitan",
              "Data",
              "Gayang",
              "Lagan",
              "Losad",
              "Namatec",
              "Napua",
              "Pingad",
              "Poblacion",
              "Supang",
              "Tambingan"
            ],
            "Sadanga": [
              "Anabel",
              "Belwang",
              "Betwagan",
              "Bekigan",
              "Poblacion",
              "Sacasacan",
              "Saclit",
              "Demang"
            ],
            "Sagada": [
              "Aguid",
              "Tetepan Sur",
              "Ambasing",
              "Angkeling",
              "Antadao",
              "Balugan",
              "Bangaan",
              "Dagdag",
              "Demang",
              "Fidelisan",
              "Kilong",
              "Madongo",
              "Poblacion",
              "Pide",
              "Nacagang",
              "Suyo",
              "Taccong",
              "Tanulong",
              "Tetepan Norte"
            ],
            "Tadian": [
              "Balaoa",
              "Banaao",
              "Bantey",
              "Batayan",
              "Bunga",
              "Cadad-anan",
              "Cagubatan",
              "Duagan",
              "Dacudac",
              "Kayan East",
              "Lenga",
              "Lubon",
              "Mabalite",
              "Masla",
              "Pandayan",
              "Poblacion",
              "Sumadel",
              "Tue",
              "Kayan West"
            ]
          },
          "Apayao": {
            "Calanasan": [
              "Butao",
              "Cadaclan",
              "Langnao",
              "Lubong",
              "Naguilian",
              "Namaltugan",
              "Poblacion",
              "Sabangan",
              "Santa Filomena",
              "Tubongan",
              "Tanglagan",
              "Tubang",
              "Don Roque Ablan Sr.",
              "Eleazar",
              "Eva Puzon",
              "Kabugawan",
              "Macalino",
              "Santa Elena"
            ],
            "Conner": [
              "Allangigan",
              "Buluan",
              "Caglayan",
              "Calafug",
              "Cupis",
              "Daga",
              "Guinamgaman",
              "Karikitan",
              "Katablangan",
              "Malama",
              "Manag",
              "Nabuangan",
              "Paddaoan",
              "Puguin",
              "Ripang",
              "Sacpil",
              "Talifugo",
              "Banban",
              "Guinaang",
              "Ili",
              "Mawigue"
            ],
            "Flora": [
              "Allig",
              "Anninipan",
              "Atok",
              "Bagutong",
              "Balasi",
              "Balluyan",
              "Malayugan",
              "Malubibit Norte",
              "Poblacion East",
              "Tamalunog",
              "Mallig",
              "Malubibit Sur",
              "Poblacion West",
              "San Jose",
              "Santa Maria",
              "Upper Atok"
            ],
            "Kabugao": [
              "Badduat",
              "Baliwanan",
              "Bulu",
              "Dagara",
              "Dibagat",
              "Cabetayan",
              "Karagawan",
              "Kumao",
              "Laco",
              "Lenneng",
              "Lucab",
              "Luttuacan",
              "Madatag",
              "Madduang",
              "Magabta",
              "Maragat",
              "Musimut",
              "Nagbabalayan",
              "Poblacion",
              "Tuyangan",
              "Waga"
            ],
            "Luna": [
              "Bacsay",
              "Capagaypayan",
              "Dagupan",
              "Lappa",
              "Marag",
              "Poblacion",
              "Quirino",
              "Salvacion",
              "San Francisco",
              "San Isidro Norte",
              "San Sebastian",
              "Santa Lina",
              "Tumog",
              "Zumigui",
              "Cagandungan",
              "Calabigan",
              "Cangisitan",
              "Luyon",
              "San Gregorio",
              "San Isidro Sur",
              "Shalom",
              "Turod"
            ],
            "Pudtol": [
              "Aga",
              "Alem",
              "Cabatacan",
              "Cacalaggan",
              "Capannikian",
              "Lower Maton",
              "Malibang",
              "Mataguisi",
              "Poblacion",
              "San Antonio",
              "Swan",
              "Upper Maton",
              "Amado",
              "Aurora",
              "Doña Loreta",
              "Emilia",
              "Imelda",
              "Lt. Balag",
              "Lydia",
              "San Jose",
              "San Luis",
              "San Mariano"
            ],
            "Santa Marcela": [
              "Barocboc",
              "Consuelo",
              "Imelda",
              "Malekkeg",
              "Marcela",
              "Nueva",
              "Panay",
              "San Antonio",
              "Sipa Proper",
              "Emiliana",
              "San Carlos",
              "San Juan",
              "San Mariano"
            ]
          },
          "City of Baguio": {
            "City of Baguio": [
              "Apugan-Loakan",
              "Asin Road",
              "Atok Trail",
              "Bakakeng Central",
              "Bakakeng North",
              "Happy Hollow",
              "Balsigan",
              "Bayan Park West",
              "Bayan Park East",
              "Brookspoint",
              "Brookside",
              "Cabinet Hill-Teacher's Camp",
              "Camp Allen",
              "Camp 7",
              "Camp 8",
              "Campo Filipino",
              "City Camp Central",
              "City Camp Proper",
              "Country Club Village",
              "Cresencia Village",
              "Dagsian, Upper",
              "DPS Area",
              "Dizon Subdivision",
              "Quirino Hill, East",
              "Engineers' Hill",
              "Fairview Village",
              "Fort del Pilar",
              "General Luna, Upper",
              "General Luna, Lower",
              "Gibraltar",
              "Greenwater Village",
              "Guisad Central",
              "Guisad Sorong",
              "Hillside",
              "Holy Ghost Extension",
              "Holy Ghost Proper",
              "Imelda Village",
              "Irisan",
              "Kayang Extension",
              "Kias",
              "Kagitingan",
              "Loakan Proper",
              "Lopez Jaena",
              "Lourdes Subdivision Extension",
              "Dagsian, Lower",
              "Lourdes Subdivision, Lower",
              "Quirino Hill, Lower",
              "General Emilio F. Aguinaldo",
              "Lualhati",
              "Lucnab",
              "Magsaysay, Lower",
              "Magsaysay Private Road",
              "Aurora Hill Proper",
              "Bal-Marcoville",
              "Quirino Hill, Middle",
              "Military Cut-off",
              "Mines View Park",
              "Modern Site, East",
              "Modern Site, West",
              "New Lucban",
              "Aurora Hill, North Central",
              "Sanitary Camp, North",
              "Outlook Drive",
              "Pacdal",
              "Pinget",
              "Pinsao Pilot Project",
              "Pinsao Proper",
              "Poliwes",
              "Pucsusan",
              "MRR-Queen Of Peace",
              "Rock Quarry, Lower",
              "Salud Mitra",
              "San Antonio Village",
              "San Luis Village",
              "San Roque Village",
              "San Vicente",
              "Santa Escolastica",
              "Santo Rosario",
              "Santo Tomas School Area",
              "Santo Tomas Proper",
              "Scout Barrio",
              "Session Road Area",
              "Slaughter House Area",
              "Sanitary Camp, South",
              "Saint Joseph Village",
              "Teodora Alonzo",
              "Trancoville",
              "Rock Quarry, Upper",
              "Victoria Village",
              "Quirino Hill, West",
              "Andres Bonifacio",
              "Legarda-Burnham-Kisad",
              "Imelda R. Marcos",
              "Lourdes Subdivision, Proper",
              "Quirino-Magsaysay, Upper",
              "A. Bonifacio-Caguioa-Rimando",
              "Ambiong",
              "Aurora Hill, South Central",
              "Abanao-Zandueta-Kayong-Chugum-Otek",
              "Bagong Lipunan",
              "BGH Compound",
              "Bayan Park Village",
              "Camdas Subdivision",
              "Palma-Urbano",
              "Dominican Hill-Mirador",
              "Alfonso Tabora",
              "Dontogan",
              "Ferdinand",
              "Happy Homes",
              "Harrison-Claudio Carantes",
              "Honeymoon",
              "Kabayanihan",
              "Kayang-Hilltop",
              "Gabriela Silang",
              "Liwanag-Loakan",
              "Malcolm Square-Perfecto",
              "Manuel A. Roxas",
              "Padre Burgos",
              "Quezon Hill, Upper",
              "Rock Quarry, Middle",
              "Phil-Am",
              "Quezon Hill Proper",
              "Middle Quezon Hill Subdivision",
              "Rizal Monument Area",
              "SLU-SVP Housing Village",
              "South Drive",
              "Magsaysay, Upper",
              "Market Subdivision, Upper",
              "Padre Zamora"
            ]
          }
        };

        // Initialize selectedBarangays object BEFORE event listeners
        let selectedBarangays = {};
        const initialProvince = @json($initialProvince);
        const initialCity = @json($initialCity);
        const initialBarangays = @json($initialBarangays);

        function createBarangayPlaceholder() {
            const placeholder = document.createElement('span');
            placeholder.style.color = '#9ca3af';
            placeholder.style.fontSize = '14px';
            placeholder.style.alignSelf = 'center';
            placeholder.textContent = 'Click dropdown to add barangays';
            return placeholder;
        }

        function setBarangayPlaceholder(container) {
            container.replaceChildren(createBarangayPlaceholder());
        }

        function createBarangayBadge(barangay) {
            const badge = document.createElement('span');
            badge.style.display = 'inline-flex';
            badge.style.alignItems = 'center';
            badge.style.gap = '6px';
            badge.style.backgroundColor = '#002C76';
            badge.style.color = 'white';
            badge.style.padding = '6px 12px';
            badge.style.borderRadius = '20px';
            badge.style.fontSize = '13px';
            badge.style.fontWeight = '500';
            badge.appendChild(document.createTextNode(barangay));

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.style.background = 'none';
            removeButton.style.border = 'none';
            removeButton.style.color = 'white';
            removeButton.style.cursor = 'pointer';
            removeButton.style.fontSize = '16px';
            removeButton.style.padding = '0';
            removeButton.style.lineHeight = '1';
            removeButton.textContent = '×';
            removeButton.addEventListener('click', function() {
                delete selectedBarangays[barangay];
                updateBadges();
            });

            badge.appendChild(removeButton);

            return badge;
        }

        // Handle Province Change
        document.getElementById('province').addEventListener('change', function() {
            const selectedProvince = this.value;
            const citySelect = document.getElementById('city_municipality');
            const barangaySelect = document.getElementById('barangay');
            const barangayBadges = document.getElementById('barangay_badges');
            const barangayHidden = document.getElementById('barangay_hidden');

            // Clear existing options
            citySelect.innerHTML = '<option value="">-- Select City/Municipality --</option>';
            barangaySelect.innerHTML = '';
            
            // Reset barangay selections
            selectedBarangays = {};
            setBarangayPlaceholder(barangayBadges);
            barangayHidden.value = '';

            // Populate cities/municipalities
            if (selectedProvince && locationData[selectedProvince]) {
                Object.keys(locationData[selectedProvince]).forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });

        // Handle City/Municipality Change
        document.getElementById('city_municipality').addEventListener('change', function() {
            const selectedProvince = document.getElementById('province').value;
            const selectedCity = this.value;
            const barangaySelect = document.getElementById('barangay');
            const barangayBadges = document.getElementById('barangay_badges');
            const barangayHidden = document.getElementById('barangay_hidden');

            // Clear barangays and reset selections
            barangaySelect.innerHTML = '';
            selectedBarangays = {};
            setBarangayPlaceholder(barangayBadges);
            barangayHidden.value = '';

            // Populate barangays
            if (selectedProvince && selectedCity && locationData[selectedProvince] && locationData[selectedProvince][selectedCity]) {
                locationData[selectedProvince][selectedCity].forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay;
                    option.textContent = barangay;
                    barangaySelect.appendChild(option);
                });
            }
        });

        // Handle Barangay Selection (click to add badge)
        document.getElementById('barangay').addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue && !selectedBarangays[selectedValue]) {
                selectedBarangays[selectedValue] = true;
                updateBadges();
            }
            
            // Reset the select
            this.value = '';
        });

        // Update badges display
        function updateBadges() {
            const barangayBadges = document.getElementById('barangay_badges');
            const barangayHidden = document.getElementById('barangay_hidden');
            
            if (Object.keys(selectedBarangays).length === 0) {
                setBarangayPlaceholder(barangayBadges);
                barangayHidden.value = '';
                return;
            }
            
            const selectedList = Object.keys(selectedBarangays);
            const fragment = document.createDocumentFragment();

            selectedList.forEach(barangay => {
                fragment.appendChild(createBarangayBadge(barangay));
            });

            barangayBadges.replaceChildren(fragment);
            // Store as JSON array that Laravel can parse
            barangayHidden.value = JSON.stringify(selectedList);
        }

        // Format currency input fields
        function formatCurrencyValue(value) {
            // Remove all non-numeric characters except decimal point
            let numValue = value.replace(/[^\d.]/g, '');
            
            // Handle multiple decimal points - keep only the first one
            let parts = numValue.split('.');
            if (parts.length > 2) {
                numValue = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Parse as float and ensure 2 decimal places
            if (numValue === '' || numValue === '.' || isNaN(numValue)) {
                return '';
            }
            
            let num = parseFloat(numValue);
            if (isNaN(num)) return '';
            
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Get numeric value from formatted currency string
        function getNumericValue(formattedValue) {
            return formattedValue.replace(/[^\d.]/g, '');
        }

        // Add event listeners for currency fields
        const currencyFields = ['lgsf_allocation', 'lgu_counterpart', 'contract_amount', 'disbursed_amount', 'obligation', 'reverted_amount', 'balance'];
        
        currencyFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            
            // Store original numeric value in a data attribute
            field.dataset.numeric = getNumericValue(field.value);
            
            // Format on blur (when user leaves the field)
            field.addEventListener('blur', function() {
                let formatted = formatCurrencyValue(this.value);
                if (formatted === '') {
                    this.value = '0.00';
                    this.dataset.numeric = '0';
                } else {
                    this.value = formatted;
                    this.dataset.numeric = getNumericValue(formatted);
                }
            });

            // Allow only numbers and decimal point on input
            field.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                // Allow: numbers, decimal point, backspace, delete
                if (!/[0-9.]/.test(char)) {
                    e.preventDefault();
                }
            });

            // Format on focus to show existing value
            field.addEventListener('focus', function() {
                // If field shows the formatted version, it's fine
            });
        });

        // Clean currency values before form submission
        document.addEventListener('submit', function(e) {
            // Only handle form submissions
            if (e.target && e.target.tagName === 'FORM') {
                const currencyFields = ['lgsf_allocation', 'lgu_counterpart', 'contract_amount', 'disbursed_amount', 'obligation', 'reverted_amount', 'balance'];
                
                currencyFields.forEach(fieldId => {
                    const field = e.target.querySelector('#' + fieldId);
                    if (field) {
                        // Extract numeric value - remove all non-numeric except decimal
                        let rawValue = field.value.replace(/[^\d.]/g, '');
                        
                        // Parse as number
                        let numValue = parseFloat(rawValue);
                        
                        // Set clean numeric value
                        if (isNaN(numValue) || rawValue === '') {
                            field.value = '0';
                        } else {
                            field.value = numValue.toString();
                        }
                    }
                });
            }
        }, true);

        // Trigger change events on page load if values are pre-selected
        window.addEventListener('load', function() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city_municipality');

            if (initialProvince) {
                provinceSelect.value = initialProvince;
                provinceSelect.dispatchEvent(new Event('change'));
            }

            if (initialCity) {
                citySelect.value = initialCity;
                citySelect.dispatchEvent(new Event('change'));
            }

            if (Array.isArray(initialBarangays) && initialBarangays.length) {
                initialBarangays.forEach((barangay) => {
                    if (barangay) {
                        selectedBarangays[barangay] = true;
                    }
                });
                updateBadges();
            }
        });

        const updateProjectButton = document.getElementById('update_button');
        const saveProjectButton = document.getElementById('save_button');
        if (updateProjectButton && saveProjectButton) {
            updateProjectButton.addEventListener('click', function() {
                updateProjectButton.style.display = 'none';
                saveProjectButton.style.display = 'inline-flex';
            });
        }

        const monitoringUpdateButton = document.getElementById('monitoring_update_button');
        if (monitoringUpdateButton) {
            monitoringUpdateButton.addEventListener('click', function() {
                monitoringUpdateButton.style.display = 'none';
                document.querySelectorAll('.monitoring-save').forEach((button) => {
                    button.style.display = 'inline-flex';
                });
            });
        }

        // Allow editing only the current month for financial monthly placeholders
        const financialCurrentMonth = {{ now()->month }};
        document.querySelectorAll('[data-financial-edit="true"]').forEach((input) => {
            const inputMonth = parseInt(input.getAttribute('data-month'), 10);
            if (inputMonth === financialCurrentMonth) {
                input.disabled = false;
                input.style.backgroundColor = '#ffffff';
            } else {
                input.disabled = true;
                input.style.backgroundColor = '#f3f4f6';
            }
        });
    </script>
@endsection

