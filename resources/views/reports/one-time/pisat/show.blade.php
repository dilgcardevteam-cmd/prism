@extends('layouts.dashboard')

@section('title', 'PISAT Assessment Details')
@section('page-title', 'PISAT Assessment Details')

@section('content')
<div class="content-header" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <h1>PISAT Assessment: {{ $assessment->project_title }}</h1>
            <p>
                Status: 
                @php
                    $statusColors = [
                        'draft' => ['bg' => '#f3f4f6', 'text' => '#4b5563', 'border' => '#d1d5db'],
                        'submitted' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe'],
                        'approved' => ['bg' => '#ecfdf5', 'text' => '#047857', 'border' => '#a7f3d0'],
                        'returned' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
                    ];
                    $colors = $statusColors[$assessment->status] ?? ['bg' => '#f3f4f6', 'text' => '#4b5563', 'border' => '#d1d5db'];
                @endphp
                <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid {{ $colors['border'] }};background:{{ $colors['bg'] }};color:{{ $colors['text'] }};text-transform:uppercase;vertical-align:middle;margin-left:6px;">
                    {{ $assessment->status }}
                </span>
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if (
                (Auth::user()->isLguScopedUser() && in_array($assessment->status, ['draft', 'returned'])) ||
                (!Auth::user()->isLguScopedUser() && $assessment->status === 'submitted')
            )
                <a href="{{ route('reports.one-time.pisat.edit', $assessment) }}" 
                   style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#eab308;color:#ffffff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                    <i class="fas fa-edit"></i>
                    <span>Edit / Validate</span>
                </a>
            @endif
            <a href="{{ route('reports.one-time.pisat') }}" 
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#6b7280;color:#ffffff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to List</span>
            </a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:24px;max-width:960px;margin:0 auto 40px auto;">

    <!-- SECTION I: PROJECT PROFILE -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:16px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:10px;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.02em;">
            I. Project Profile
        </h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="grid-column: span 2;">
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Project Title:</span>
                <span style="font-size:15px;color:#111827;font-weight:600;">{{ $assessment->project_title }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Location:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->location ?: '—' }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Implementing LGU:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->implementing_lgu ?: '—' }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Date of Turnover:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->date_of_turnover ? $assessment->date_of_turnover->format('M d, Y') : '—' }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Date of Assessment:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->date_of_assessment ? $assessment->date_of_assessment->format('M d, Y') : '—' }}</span>
            </div>
            <div style="grid-column: span 2;">
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Respondent Group:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->respondent_group ?: '—' }}</span>
            </div>
        </div>
    </div>

    <!-- SECTION II: MSC STORY -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:16px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:10px;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.02em;">
            II. The "Most Significant Change" (MSC) Story
        </h2>
        <div style="margin-bottom:16px;">
            <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Story Title:</span>
            <span style="font-size:15px;color:#111827;font-weight:700;">{{ $assessment->msc_title ?: '—' }}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr;gap:16px;">
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">The Situation BEFORE (The Beginning):</span>
                <div style="font-size:14px;color:#374151;line-height:1.6;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->msc_situation_before ?: '—' }}</div>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">The Situation NOW (The Result):</span>
                <div style="font-size:14px;color:#374151;line-height:1.6;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->msc_situation_now ?: '—' }}</div>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">Why is this Significant?:</span>
                <div style="font-size:14px;color:#374151;line-height:1.6;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->msc_why_significant ?: '—' }}</div>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Categorization of Change:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">
                    @if($assessment->msc_category === 'economic')
                        Economic Empowerment (e.g. increased income, reduced transport cost)
                    @elseif($assessment->msc_category === 'social_health')
                        Social / Health Well-being (e.g. reduced sickness, improved safety)
                    @elseif($assessment->msc_category === 'institutional')
                        Institutional Strengthening (e.g. coordination, participation)
                    @elseif($assessment->msc_category === 'behavioral')
                        Behavioral Change (e.g. sanitation practices, cooperation)
                    @else
                        —
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- SECTION III: PROJECT IMPACT ASSESSMENT -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:16px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:10px;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.02em;">
            III. Project Impact Assessment
        </h2>

        <!-- A. ECONOMIC DIMENSION -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;background:#f3f4f6;padding:6px 12px;border-radius:6px;margin:0 0 12px 0;">
            A. ECONOMIC DIMENSION (Livelihood & Access)
        </h3>
        <div style="display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Travel Time / Cost Savings</strong>
                    <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                        Current: {{ $assessment->travel_time_savings_current ?: '—' }} | Previous: {{ $assessment->travel_time_savings_previous ?: '—' }}
                    </div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->travel_time_savings_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Asset Value</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->asset_value_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->asset_value_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">New Economic Activities</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->new_economic_activities_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->new_economic_activities_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Agricultural Output</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->agricultural_output_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->agricultural_output_rating ?: '—' }} / 5</strong>
                </div>
            </div>
        </div>

        <!-- B. SOCIAL DIMENSION -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;background:#f3f4f6;padding:6px 12px;border-radius:6px;margin:20px 0 12px 0;">
            B. SOCIAL DIMENSION (Quality of Life)
        </h3>
        <div style="display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Access to Services</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->access_to_services_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->access_to_services_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Health Outcomes</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->health_outcomes_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->health_outcomes_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed #f3f4f6;padding-bottom:8px;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Safety and Security</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->safety_security_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->safety_security_rating ?: '—' }} / 5</strong>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="flex:1;">
                    <strong style="font-size:13px;color:#374151;">Community Pride and Ownership</strong>
                    <div style="font-size:12px;color:#374151;margin-top:4px;white-space:pre-wrap;">{{ $assessment->community_pride_findings ?: '—' }}</div>
                </div>
                <div style="text-align:right;min-width:60px;">
                    <span style="font-size:11px;color:#6b7280;display:block;">Rating</span>
                    <strong style="font-size:15px;color:#111827;">{{ $assessment->community_pride_rating ?: '—' }} / 5</strong>
                </div>
            </div>
        </div>

        <!-- C. UNEXPECTED OUTCOMES -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;background:#f3f4f6;padding:6px 12px;border-radius:6px;margin:20px 0 12px 0;">
            C. UNEXPECTED OUTCOMES
        </h3>
        <div style="display:grid;grid-template-columns:1fr;gap:14px;">
            <div>
                <strong style="font-size:13px;color:#374151;display:block;margin-bottom:4px;">Positive Outcome / Benefit:</strong>
                <div style="font-size:13px;color:#374151;background:#f9fafb;padding:10px 12px;border-radius:6px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->unexpected_positive ?: '—' }}</div>
            </div>
            <div>
                <strong style="font-size:13px;color:#374151;display:block;margin-bottom:4px;">Negative Outcome / Concern:</strong>
                <div style="font-size:13px;color:#374151;background:#f9fafb;padding:10px 12px;border-radius:6px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->unexpected_negative ?: '—' }}</div>
            </div>
        </div>
    </div>

    <!-- SECTION IV: SUSTAINABILITY AND OPERATIONAL ASSESSMENT -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:16px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:10px;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.02em;">
            IV. Sustainability and Operational Assessment
        </h2>

        <!-- A. ORGANIZATIONAL VIABILITY -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:0 0 12px 0;">
            A. Organizational Viability
        </h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div style="grid-column: span 2;">
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Manager / Maintainer:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->manager_maintainer ?: '—' }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Organized User Group/Association?:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">
                    @if($assessment->organized_user_group === 'yes_active') Yes (Active)
                    @elseif($assessment->organized_user_group === 'yes_inactive') Yes (Inactive)
                    @elseif($assessment->organized_user_group === 'no') No
                    @else — @endif
                </span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">LGU Recognized?:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->organization_recognized ? ucfirst($assessment->organization_recognized) : '—' }}</span>
            </div>
        </div>

        <!-- B. FINANCIAL VIABILITY -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:20px 0 12px 0;">
            B. Financial Viability
        </h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Has O&M Fund?:</span>
                <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->has_om_fund ? ucfirst($assessment->has_om_fund) : '—' }}</span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Available Funds / Cash on Hand:</span>
                <strong style="font-size:14px;color:#111827;">
                    {{ $assessment->available_funds !== null ? 'PHP ' . number_format($assessment->available_funds, 2) : '—' }}
                </strong>
            </div>
            <div style="grid-column: span 2;">
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">Sources of Funds:</span>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @if ($assessment->source_of_funds_user_fees)
                        <span style="padding:4px 10px;background:#f3f4f6;border-radius:6px;font-size:12px;color:#374151;">
                            User Fees (Collection Rate: {{ $assessment->source_of_funds_user_fees_rate ?: '0' }}%)
                        </span>
                    @endif
                    @if ($assessment->source_of_funds_barangay)
                        <span style="padding:4px 10px;background:#f3f4f6;border-radius:6px;font-size:12px;color:#374151;">Barangay Allocation</span>
                    @endif
                    @if ($assessment->source_of_funds_municipal)
                        <span style="padding:4px 10px;background:#f3f4f6;border-radius:6px;font-size:12px;color:#374151;">Municipal Subsidy</span>
                    @endif
                    @if ($assessment->source_of_funds_other)
                        <span style="padding:4px 10px;background:#f3f4f6;border-radius:6px;font-size:12px;color:#374151;">
                            Other: {{ $assessment->source_of_funds_other_desc ?: '—' }}
                        </span>
                    @endif
                    @if (!($assessment->source_of_funds_user_fees || $assessment->source_of_funds_barangay || $assessment->source_of_funds_municipal || $assessment->source_of_funds_other))
                        <span style="color:#6b7280;font-size:13px;font-style:italic;">No fund sources specified.</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- C. PHYSICAL CONDITION -->
        <h3 style="font-size:14px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:20px 0 12px 0;">
            C. Physical Condition (Technical Check)
        </h3>
        <div style="display:grid;grid-template-columns:1fr;gap:14px;">
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Functional Status:</span>
                <span style="font-size:14px;color:#111827;font-weight:600;">
                    @if($assessment->functional_status === 'operational_100') 100% Operational (Used regularly and functioning as intended)
                    @elseif($assessment->functional_status === 'operational_partial') Partially Operational (With defects, interruptions, or limited use)
                    @elseif($assessment->functional_status === 'non_operational') Non-Operational (Unused, abandoned, unsafe, or severely deteriorated)
                    @else — @endif
                </span>
            </div>
            <div>
                <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:6px;">Defects Observed:</span>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @if ($assessment->defect_structural_cracks)
                        <span style="padding:4px 10px;background:#fee2e2;border-radius:6px;font-size:12px;color:#991b1b;border:1px solid #fecaca;">Structural / Surface Cracks</span>
                    @endif
                    @if ($assessment->defect_drainage_leakage)
                        <span style="padding:4px 10px;background:#fee2e2;border-radius:6px;font-size:12px;color:#991b1b;border:1px solid #fecaca;">Drainage / Leakage Issues</span>
                    @endif
                    @if ($assessment->defect_electrical_plumbing)
                        <span style="padding:4px 10px;background:#fee2e2;border-radius:6px;font-size:12px;color:#991b1b;border:1px solid #fecaca;">Electrical / Plumbing Failures</span>
                    @endif
                    @if ($assessment->defect_vandalism_wear)
                        <span style="padding:4px 10px;background:#fee2e2;border-radius:6px;font-size:12px;color:#991b1b;border:1px solid #fecaca;">Vandalism / Wear and Tear</span>
                    @endif
                    @if ($assessment->defect_other)
                        <span style="padding:4px 10px;background:#fee2e2;border-radius:6px;font-size:12px;color:#991b1b;border:1px solid #fecaca;">
                            Other: {{ $assessment->defect_other_desc ?: '—' }}
                        </span>
                    @endif
                    @if (!($assessment->defect_structural_cracks || $assessment->defect_drainage_leakage || $assessment->defect_electrical_plumbing || $assessment->defect_vandalism_wear || $assessment->defect_other))
                        <span style="padding:4px 10px;background:#ecfdf5;border-radius:6px;font-size:12px;color:#047857;border:1px solid #a7f3d0;font-weight:600;">
                            No Defects Observed
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION V: EVALUATION FINDINGS AND PROPOSED ACTIONS (Validator Review) -->
    @if ($assessment->status === 'approved' || $assessment->impact_classification)
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <h2 style="font-size:16px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:10px;margin:0 0 16px 0;text-transform:uppercase;letter-spacing:0.02em;">
                V. Evaluation Findings and Proposed Actions
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div style="grid-column: span 2;">
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Impact Classification:</span>
                    <strong style="font-size:15px;color:#1e40af;text-transform:capitalize;">{{ $assessment->impact_classification }}</strong>
                </div>
                <div style="grid-column: span 2;">
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">Key Findings / Observations:</span>
                    <div style="font-size:14px;color:#374151;line-height:1.6;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->key_findings ?: '—' }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;margin-bottom:4px;">Proposed Actions / Recommendations:</span>
                    <div style="font-size:14px;color:#374151;line-height:1.6;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;white-space:pre-wrap;">{{ $assessment->proposed_actions ?: '—' }}</div>
                </div>
                <div>
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Prepared By:</span>
                    <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->prepared_by ?: '—' }}</span>
                </div>
                <div>
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Position:</span>
                    <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->position ?: '—' }}</span>
                </div>
                <div>
                    <span style="display:block;font-size:12px;color:#6b7280;font-weight:600;">Date Prepared:</span>
                    <span style="font-size:14px;color:#111827;font-weight:500;">{{ $assessment->date_prepared ? $assessment->date_prepared->format('M d, Y') : '—' }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

