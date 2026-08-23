@extends('layouts.dashboard')

@section('title', 'Edit PISAT Assessment')
@section('page-title', 'Edit PISAT Assessment')

@section('content')
<div class="content-header" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <h1>Edit PISAT Assessment</h1>
            <p>Status: <span style="font-weight:700;text-transform:uppercase;color:#1e40af;">{{ $assessment->status }}</span></p>
        </div>
        <div>
            <a href="{{ route('reports.one-time.pisat') }}" 
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:#6b7280;color:#ffffff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to List</span>
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;padding:14px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
        <strong style="display:block;margin-bottom:6px;">Please fix the following validation errors:</strong>
        <ul style="margin:0;padding-left:20px;font-size:13px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($assessment->status === 'returned' && $assessment->remarks)
    <div style="background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;padding:16px;border-radius:10px;margin-bottom:24px;">
        <h4 style="margin:0 0 6px 0;font-size:14px;font-weight:700;"><i class="fas fa-undo"></i> Returned Remarks:</h4>
        <p style="margin:0;font-size:13px;line-height:1.5;">{{ $assessment->remarks }}</p>
    </div>
@endif

@php
    $isValidator = !Auth::user()->isLguScopedUser();
    $lguDisabled = $isValidator ? 'disabled' : '';
@endphp

<!-- Form for LGU update (only enabled for LGU when status is draft or returned) -->
<form method="POST" action="{{ route('reports.one-time.pisat.update', $assessment) }}" id="pisatForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="project_code" id="project_code" value="{{ old('project_code', $assessment->project_code) }}">

    <!-- SECTION I: PROJECT PROFILE -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:18px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin:0 0 20px 0;">
            I. Project Profile
        </h2>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div style="grid-column: span 2;">
                <label for="project_title" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Project Title <span style="color:#dc2626;">*</span></label>
                <input type="text" id="project_title" name="project_title" value="{{ old('project_title', $assessment->project_title) }}" required {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label for="location" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Location</label>
                <input type="text" id="location" name="location" value="{{ old('location', $assessment->location) }}" {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label for="implementing_lgu" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Implementing LGU</label>
                <input type="text" id="implementing_lgu" name="implementing_lgu" value="{{ old('implementing_lgu', $assessment->implementing_lgu) }}" {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label for="date_of_turnover" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Date of Turnover</label>
                <input type="date" id="date_of_turnover" name="date_of_turnover" value="{{ old('date_of_turnover', $assessment->date_of_turnover ? $assessment->date_of_turnover->format('Y-m-d') : '') }}" {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
            <div>
                <label for="date_of_assessment" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Date of Assessment</label>
                <input type="date" id="date_of_assessment" name="date_of_assessment" value="{{ old('date_of_assessment', $assessment->date_of_assessment ? $assessment->date_of_assessment->format('Y-m-d') : '') }}" {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
            <div style="grid-column: span 2;">
                <label for="respondent_group" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Respondent Group</label>
                <input type="text" id="respondent_group" name="respondent_group" value="{{ old('respondent_group', $assessment->respondent_group) }}" {{ $lguDisabled }}
                       style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
            </div>
        </div>
    </div>

    <!-- SECTION II: MSC STORY -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:18px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin:0 0 10px 0;">
            II. The "Most Significant Change" (MSC) Story
        </h2>
        <div style="color:#1e40af;background:#eff6ff;padding:12px;border-radius:8px;font-size:13px;margin-bottom:20px;font-style:italic;line-height:1.5;">
            <i class="fas fa-info-circle" style="margin-right:4px;"></i>
            <strong>Guide:</strong> This section captures the human experience behind the project by identifying the most meaningful change experienced by the community after project completion.
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;margin-bottom:8px;font-size:13px;font-weight:700;color:#1e40af;line-height:1.5;">
                Question to Beneficiaries: “Looking back since the project was completed, what do you think has been the most significant change experienced by the community because of this project?” [Topic: Health/Income/Safety]?
            </label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px;">
                <div style="grid-column: span 2;">
                    <label for="msc_title" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">1. Title of the Story</label>
                    <span style="display:block;font-size:12px;color:#1e40af;margin-bottom:4px;font-style:italic;">(Give the story a short but meaningful title.)</span>
                    <input type="text" id="msc_title" name="msc_title" value="{{ old('msc_title', $assessment->msc_title) }}" {{ $lguDisabled }}
                           style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                </div>
                <div style="grid-column: span 2;">
                    <label for="msc_situation_before" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">2. The Situation BEFORE (The Beginning)</label>
                    <span style="display:block;font-size:12px;color:#1e40af;margin-bottom:4px;font-style:italic;">(What conditions, challenges, or difficulties existed before the project was implemented?)</span>
                    <textarea id="msc_situation_before" name="msc_situation_before" rows="4" {{ $lguDisabled }}
                              style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('msc_situation_before', $assessment->msc_situation_before) }}</textarea>
                </div>
                <div style="grid-column: span 2;">
                    <label for="msc_situation_now" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">3. The Situation NOW (The Result)</label>
                    <span style="display:block;font-size:12px;color:#1e40af;margin-bottom:4px;font-style:italic;">(What improvements or changes are now being experienced? Who benefited and how?)</span>
                    <textarea id="msc_situation_now" name="msc_situation_now" rows="4" {{ $lguDisabled }}
                              style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('msc_situation_now', $assessment->msc_situation_now) }}</textarea>
                </div>
                <div style="grid-column: span 2;">
                    <label for="msc_why_significant" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">4. Why is this Significant?</label>
                    <span style="display:block;font-size:12px;color:#1e40af;margin-bottom:4px;font-style:italic;">(Why is this change important to the community? What makes it meaningful compared to other changes?)</span>
                    <textarea id="msc_why_significant" name="msc_why_significant" rows="4" {{ $lguDisabled }}
                              style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('msc_why_significant', $assessment->msc_why_significant) }}</textarea>
                </div>
                <div>
                    <label for="msc_category" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Categorization of Change</label>
                    <span style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">Based on the story above, which area of change is most evident?</span>
                    <select id="msc_category" name="msc_category" {{ $lguDisabled }} style="width:100%;height:40px;padding:0 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                        <option value="">-- Select Category --</option>
                        <option value="economic" @selected(old('msc_category', $assessment->msc_category) === 'economic')>Economic Empowerment (e.g. increased income, reduced transport cost)</option>
                        <option value="social_health" @selected(old('msc_category', $assessment->msc_category) === 'social_health')>Social / Health Well-being (e.g. reduced sickness, improved safety)</option>
                        <option value="institutional" @selected(old('msc_category', $assessment->msc_category) === 'institutional')>Institutional Strengthening (e.g. coordination, participation)</option>
                        <option value="behavioral" @selected(old('msc_category', $assessment->msc_category) === 'behavioral')>Behavioral Change (e.g. sanitation practices, cooperation)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION III: PROJECT IMPACT ASSESSMENT -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:18px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin:0 0 10px 0;">
            III. Project Impact Assessment
        </h2>
        <div style="color:#1e40af;background:#eff6ff;padding:12px;border-radius:8px;font-size:13px;margin-bottom:20px;font-style:italic;line-height:1.5;">
            <i class="fas fa-info-circle" style="margin-right:4px;"></i>
            <strong>Guide:</strong> This section validates community experiences through observable and verifiable indicators of project impact.
        </div>

        <div style="padding:14px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:24px;font-size:13px;">
            <strong style="color:#374151;">Rating Guide:</strong> All indicators shall be rated using a scale of <strong>1 to 5</strong>, where:<br>
            <span style="display:inline-block;margin:6px 14px 0 0;"><strong>1</strong> – Very Poor</span>
            <span style="display:inline-block;margin:6px 14px 0 0;"><strong>2</strong> – Poor</span>
            <span style="display:inline-block;margin:6px 14px 0 0;"><strong>3</strong> – Fair / Moderate</span>
            <span style="display:inline-block;margin:6px 14px 0 0;"><strong>4</strong> – Good</span>
            <span style="display:inline-block;margin:6px 14px 0 0;"><strong>5</strong> – Excellent</span>
        </div>

        <!-- A. ECONOMIC DIMENSION -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;background:#f3f4f6;padding:8px 12px;border-radius:6px;margin:0 0 16px 0;">
            A. ECONOMIC DIMENSION (Livelihood & Access)
        </h3>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:6px;">Travel Time / Cost Savings</strong>
                <div style="display:flex;gap:12px;align-items:center;">
                    <div style="flex:1;">
                        <span style="font-size:12px;color:#64748b;">Current Fare/Travel Time:</span>
                        <input type="text" name="travel_time_savings_current" value="{{ old('travel_time_savings_current', $assessment->travel_time_savings_current) }}" {{ $lguDisabled }} style="width:100%;height:34px;padding:0 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;margin-top:4px;">
                    </div>
                    <div style="flex:1;">
                        <span style="font-size:12px;color:#64748b;">Previous:</span>
                        <input type="text" name="travel_time_savings_previous" value="{{ old('travel_time_savings_previous', $assessment->travel_time_savings_previous) }}" {{ $lguDisabled }} style="width:100%;height:34px;padding:0 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;margin-top:4px;">
                    </div>
                </div>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="travel_time_savings_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('travel_time_savings_rating', $assessment->travel_time_savings_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Asset Value</strong>
                <span style="font-size:12px;color:#64748b;display:block;margin-bottom:6px;">Did nearby land values, rentals, or property activity increase? (Findings/Verification)</span>
                <textarea name="asset_value_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('asset_value_findings', $assessment->asset_value_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="asset_value_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('asset_value_rating', $assessment->asset_value_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">New Economic Activities</strong>
                <span style="font-size:12px;color:#64748b;display:block;margin-bottom:6px;">Are there new stores, transport routes, market activities, or livelihood opportunities? (Findings/Verification)</span>
                <textarea name="new_economic_activities_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('new_economic_activities_findings', $assessment->new_economic_activities_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="new_economic_activities_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('new_economic_activities_rating', $assessment->new_economic_activities_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:24px;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Agricultural Output</strong>
                <span style="font-size:12px;color:#1e40af;display:block;margin-bottom:6px;font-style:italic;">(For irrigation/FMR projects) Has spoilage decreased or productivity improved?</span>
                <textarea name="agricultural_output_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('agricultural_output_findings', $assessment->agricultural_output_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="agricultural_output_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('agricultural_output_rating', $assessment->agricultural_output_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- B. SOCIAL DIMENSION -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;background:#f3f4f6;padding:8px 12px;border-radius:6px;margin:24px 0 16px 0;">
            B. SOCIAL DIMENSION (Quality of Life)
        </h3>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Access to Services</strong>
                <span style="font-size:12px;color:#64748b;display:block;margin-bottom:6px;">Can emergency vehicles, delivery trucks, or residents access the area more easily?</span>
                <textarea name="access_to_services_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('access_to_services_findings', $assessment->access_to_services_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="access_to_services_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('access_to_services_rating', $assessment->access_to_services_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Health Outcomes</strong>
                <span style="font-size:12px;color:#1e40af;display:block;margin-bottom:6px;font-style:italic;">(For water/sanitation projects) Was there a decrease in water-borne diseases or health complaints?</span>
                <textarea name="health_outcomes_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('health_outcomes_findings', $assessment->health_outcomes_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="health_outcomes_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('health_outcomes_rating', $assessment->health_outcomes_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #e5e7eb;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Safety and Security</strong>
                <span style="font-size:12px;color:#64748b;display:block;margin-bottom:6px;">Can residents safely use the area during nighttime or emergencies?</span>
                <textarea name="safety_security_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('safety_security_findings', $assessment->safety_security_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="safety_security_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('safety_security_rating', $assessment->safety_security_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:20px;margin-bottom:24px;">
            <div>
                <strong style="font-size:14px;color:#374151;display:block;margin-bottom:4px;">Community Pride and Ownership</strong>
                <span style="font-size:12px;color:#64748b;display:block;margin-bottom:6px;">Do residents actively care for, protect, and value the facility/project?</span>
                <textarea name="community_pride_findings" rows="2" {{ $lguDisabled }} style="width:100%;padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;font-family:inherit;">{{ old('community_pride_findings', $assessment->community_pride_findings) }}</textarea>
            </div>
            <div>
                <label style="font-size:12px;color:#64748b;display:block;margin-bottom:4px;">Rating (1-5)</label>
                <select name="community_pride_rating" {{ $lguDisabled }} style="width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:14px;">
                    <option value="">- Rating -</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(old('community_pride_rating', $assessment->community_pride_rating) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- C. UNEXPECTED OUTCOMES -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;background:#f3f4f6;padding:8px 12px;border-radius:6px;margin:24px 0 16px 0;">
            C. UNEXPECTED OUTCOMES (Positive or Negative)
        </h3>
        <span style="display:block;font-size:12px;color:#64748b;margin-bottom:14px;">This section captures outcomes or effects that were not originally anticipated during project implementation.</span>

        <div style="margin-bottom:16px;">
            <label for="unexpected_positive" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Positive Outcome / Benefit</label>
            <span style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;font-style:italic;">(e.g., “The road is now used as a jogging route and community activity area.”)</span>
            <textarea id="unexpected_positive" name="unexpected_positive" rows="3" {{ $lguDisabled }} style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('unexpected_positive', $assessment->unexpected_positive) }}</textarea>
        </div>

        <div style="margin-bottom:10px;">
            <label for="unexpected_negative" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Negative Outcome / Concern</label>
            <span style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;font-style:italic;">(e.g., “Faster vehicles increased accident risks,” or “Drainage canals are frequently clogged with waste.”)</span>
            <textarea id="unexpected_negative" name="unexpected_negative" rows="3" {{ $lguDisabled }} style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('unexpected_negative', $assessment->unexpected_negative) }}</textarea>
        </div>
    </div>

    <!-- SECTION IV: SUSTAINABILITY AND OPERATIONAL ASSESSMENT -->
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:18px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin:0 0 10px 0;">
            IV. Sustainability and Operational Assessment
        </h2>
        <div style="color:#1e40af;background:#eff6ff;padding:12px;border-radius:8px;font-size:13px;margin-bottom:20px;font-style:italic;line-height:1.5;">
            <i class="fas fa-info-circle" style="margin-right:4px;"></i>
            <strong>Guide:</strong> This section assesses whether the project remains functional, maintained, utilized, and sustainable after completion.
        </div>

        <!-- A. ORGANIZATIONAL VIABILITY -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:0 0 16px 0;">
            A. Organizational Viability
        </h3>

        <div style="margin-bottom:18px;">
            <label for="manager_maintainer" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Who currently manages or maintains the facility/project?</label>
            <input type="text" id="manager_maintainer" name="manager_maintainer" value="{{ old('manager_maintainer', $assessment->manager_maintainer) }}" {{ $lguDisabled }}
                   style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <div>
                <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Is there an organized user group or association?</span>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="organized_user_group" value="yes_active" @checked(old('organized_user_group', $assessment->organized_user_group) === 'yes_active') {{ $lguDisabled }}> Yes (Active)
                    </label>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="organized_user_group" value="yes_inactive" @checked(old('organized_user_group', $assessment->organized_user_group) === 'yes_inactive') {{ $lguDisabled }}> Yes (Inactive)
                    </label>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="organized_user_group" value="no" @checked(old('organized_user_group', $assessment->organized_user_group) === 'no') {{ $lguDisabled }}> No
                    </label>
                </div>
            </div>
            <div>
                <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Is the organization recognized by the LGU?</span>
                <span style="display:block;font-size:11px;color:#64748b;margin-bottom:8px;">(e.g., Executive Order, Accreditation, Resolution)</span>
                <div style="display:flex;gap:16px;">
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="organization_recognized" value="yes" @checked(old('organization_recognized', $assessment->organization_recognized) === 'yes') {{ $lguDisabled }}> Yes
                    </label>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="organization_recognized" value="no" @checked(old('organization_recognized', $assessment->organization_recognized) === 'no') {{ $lguDisabled }}> No
                    </label>
                </div>
            </div>
        </div>

        <!-- B. FINANCIAL VIABILITY -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:24px 0 16px 0;">
            B. Financial Viability
        </h3>

        <div style="margin-bottom:18px;">
            <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Is there an Operations and Maintenance (O&M) Fund?</span>
            <div style="display:flex;gap:16px;">
                <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="radio" name="has_om_fund" value="yes" @checked(old('has_om_fund', $assessment->has_om_fund) === 'yes') {{ $lguDisabled }}> Yes
                </label>
                <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="radio" name="has_om_fund" value="no" @checked(old('has_om_fund', $assessment->has_om_fund) === 'no') {{ $lguDisabled }}> No
                </label>
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Source of Funds</span>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="source_of_funds_user_fees" value="1" @checked(old('source_of_funds_user_fees', $assessment->source_of_funds_user_fees)) {{ $lguDisabled }}> User Fees
                    </label>
                    <div style="display:inline-flex;align-items:center;gap:4px;">
                        <span style="font-size:12px;color:#64748b;">(Collection Rate:</span>
                        <input type="text" name="source_of_funds_user_fees_rate" value="{{ old('source_of_funds_user_fees_rate', $assessment->source_of_funds_user_fees_rate) }}" {{ $lguDisabled }} placeholder="0" style="width:60px;height:26px;padding:0 6px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;">
                        <span style="font-size:12px;color:#64748b;">%)</span>
                    </div>
                </div>
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="source_of_funds_barangay" value="1" @checked(old('source_of_funds_barangay', $assessment->source_of_funds_barangay)) {{ $lguDisabled }}> Barangay Allocation
                    </label>
                </div>
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="source_of_funds_municipal" value="1" @checked(old('source_of_funds_municipal', $assessment->source_of_funds_municipal)) {{ $lguDisabled }}> Municipal Subsidy
                    </label>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="source_of_funds_other" value="1" @checked(old('source_of_funds_other', $assessment->source_of_funds_other)) {{ $lguDisabled }}> Other:
                    </label>
                    <input type="text" name="source_of_funds_other_desc" value="{{ old('source_of_funds_other_desc', $assessment->source_of_funds_other_desc) }}" {{ $lguDisabled }} placeholder="Specify" style="flex:1;height:28px;padding:0 8px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;">
                </div>
            </div>
        </div>

        <div style="margin-bottom:24px;">
            <label for="available_funds" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Available Funds / Cash on Hand (PHP)</label>
            <span style="display:block;font-size:11px;color:#64748b;margin-bottom:6px;">(Based on Treasurer’s Record, Passbook, or Financial Record)</span>
            <input type="number" step="0.01" id="available_funds" name="available_funds" value="{{ old('available_funds', $assessment->available_funds) }}" {{ $lguDisabled }} placeholder="0.00"
                   style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>

        <!-- C. PHYSICAL CONDITION -->
        <h3 style="font-size:15px;font-weight:700;color:#111827;border-bottom:1px solid #f3f4f6;padding-bottom:6px;margin:24px 0 16px 0;">
            C. Physical Condition (Technical Check)
        </h3>

        <div style="margin-bottom:18px;">
            <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Functional Status</span>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="radio" name="functional_status" value="operational_100" @checked(old('functional_status', $assessment->functional_status) === 'operational_100') {{ $lguDisabled }}> 100% Operational <span style="font-size:11px;color:#64748b;">(Used regularly and functioning as intended)</span>
                </label>
                <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="radio" name="functional_status" value="operational_partial" @checked(old('functional_status', $assessment->functional_status) === 'operational_partial') {{ $lguDisabled }}> Partially Operational <span style="font-size:11px;color:#64748b;">(With defects, interruptions, or limited use)</span>
                </label>
                <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="radio" name="functional_status" value="non_operational" @checked(old('functional_status', $assessment->functional_status) === 'non_operational') {{ $lguDisabled }}> Non-Operational <span style="font-size:11px;color:#64748b;">(Unused, abandoned, unsafe, or severely deteriorated)</span>
                </label>
            </div>
        </div>

        <div style="margin-bottom:10px;">
            <span style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Defect Matrix</span>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="defect_structural_cracks" value="1" @checked(old('defect_structural_cracks', $assessment->defect_structural_cracks)) {{ $lguDisabled }}> Structural / Surface Cracks
                    </label>
                </div>
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="defect_drainage_leakage" value="1" @checked(old('defect_drainage_leakage', $assessment->defect_drainage_leakage)) {{ $lguDisabled }}> Drainage / Leakage Issues
                    </label>
                </div>
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="defect_electrical_plumbing" value="1" @checked(old('defect_electrical_plumbing', $assessment->defect_electrical_plumbing)) {{ $lguDisabled }}> Electrical / Plumbing Failures
                    </label>
                </div>
                <div>
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="defect_vandalism_wear" value="1" @checked(old('defect_vandalism_wear', $assessment->defect_vandalism_wear)) {{ $lguDisabled }}> Vandalism / Wear and Tear
                    </label>
                </div>
                <div style="grid-column: span 2; display:flex; align-items:center; gap:8px;">
                    <label style="font-size:13px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="defect_other" value="1" @checked(old('defect_other', $assessment->defect_other)) {{ $lguDisabled }}> Other Observed Defects:
                    </label>
                    <input type="text" name="defect_other_desc" value="{{ old('defect_other_desc', $assessment->defect_other_desc) }}" {{ $lguDisabled }} placeholder="Specify defects" style="flex:1;height:28px;padding:0 8px;border:1px solid #cbd5e1;border-radius:4px;font-size:12px;">
                </div>
            </div>
        </div>
    </div>

    @if (! $isValidator)
        <!-- LGU UPDATE ACTIONS -->
        <div style="display:flex;justify-content:flex-end;gap:12px;margin-bottom:40px;">
            <button type="submit" name="submit_btn" value="draft" 
                    style="padding:12px 24px;background:#ffffff;border:1px solid #cbd5e1;color:#374151;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                <i class="fas fa-save" style="margin-right:6px;"></i> Save as Draft
            </button>
            <button type="submit" name="submit_btn" value="submit" 
                    style="padding:12px 24px;background:#002c76;color:#ffffff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 4px rgba(0,0,0,0.15);">
                <i class="fas fa-paper-plane" style="margin-right:6px;"></i> Submit Assessment
            </button>
        </div>
    @endif
</form>

<!-- SECTION V: EVALUATION FINDINGS AND PROPOSED ACTIONS (For DILG Validator Users) -->
@if ($isValidator)
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:40px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <h2 style="font-size:18px;font-weight:700;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin:0 0 10px 0;">
            V. Evaluation Findings and Proposed Actions
        </h2>
        <div style="color:#1e40af;background:#eff6ff;padding:12px;border-radius:8px;font-size:13px;margin-bottom:20px;font-style:italic;line-height:1.5;">
            <i class="fas fa-info-circle" style="margin-right:4px;"></i>
            <strong>Guide:</strong> This section shall also be accomplished by the Engineer or PEO who conducted the PISAT to summarize assessment findings and provide corresponding recommendations based on field validation results.
        </div>

        <form method="POST" action="{{ route('reports.one-time.pisat.validate', $assessment) }}">
            @csrf

            <div style="margin-bottom:18px;">
                <label style="display:block;margin-bottom:8px;font-size:13px;font-weight:600;color:#374151;">Impact Classification <span style="color:#dc2626;">*</span></label>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:flex-start;gap:8px;cursor:pointer;">
                        <input type="radio" name="impact_classification" value="transformational" @checked(old('impact_classification', $assessment->impact_classification) === 'transformational') style="margin-top:3px;">
                        <div>
                            <strong>Transformational</strong>
                            <span style="display:block;font-size:11px;color:#64748b;">(The project created significant positive changes in the community.)</span>
                        </div>
                    </label>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:flex-start;gap:8px;cursor:pointer;">
                        <input type="radio" name="impact_classification" value="functional" @checked(old('impact_classification', $assessment->impact_classification) === 'functional') style="margin-top:3px;">
                        <div>
                            <strong>Functional</strong>
                            <span style="display:block;font-size:11px;color:#64748b;">(The project remains operational and continues to deliver intended benefits.)</span>
                        </div>
                    </label>
                    <label style="font-size:13px;font-weight:500;color:#374151;display:inline-flex;align-items:flex-start;gap:8px;cursor:pointer;">
                        <input type="radio" name="impact_classification" value="at_risk" @checked(old('impact_classification', $assessment->impact_classification) === 'at_risk') style="margin-top:3px;">
                        <div>
                            <strong>At-Risk</strong>
                            <span style="display:block;font-size:11px;color:#64748b;">(The project shows signs of deterioration, underutilization, or sustainability concerns.)</span>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:18px;">
                <label for="key_findings" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Key Findings / Observations</label>
                <textarea id="key_findings" name="key_findings" rows="4" 
                          style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('key_findings', $assessment->key_findings) }}</textarea>
            </div>

            <div style="margin-bottom:18px;">
                <label for="proposed_actions" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Proposed Actions / Recommendations</label>
                <textarea id="proposed_actions" name="proposed_actions" rows="4" 
                          style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;">{{ old('proposed_actions', $assessment->proposed_actions) }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">
                <div>
                    <label for="prepared_by" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Prepared By</label>
                    <input type="text" id="prepared_by" name="prepared_by" value="{{ old('prepared_by', $assessment->prepared_by ?? Auth::user()->fname . ' ' . Auth::user()->lname) }}"
                           style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                </div>
                <div>
                    <label for="position" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Position</label>
                    <input type="text" id="position" name="position" value="{{ old('position', $assessment->position ?? Auth::user()->position) }}"
                           style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                </div>
                <div>
                    <label for="date_prepared" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Date</label>
                    <input type="date" id="date_prepared" name="date_prepared" value="{{ old('date_prepared', $assessment->date_prepared ? $assessment->date_prepared->format('Y-m-d') : date('Y-m-d')) }}"
                           style="width:100%;height:40px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                </div>
            </div>

            <div style="margin-bottom:24px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <label for="remarks" style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:#374151;">Validator Remarks (Required for Returning)</label>
                <textarea id="remarks" name="remarks" rows="2" placeholder="Provide feedback or reasons if returning this to draft status."
                          style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;">{{ old('remarks', $assessment->remarks) }}</textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;">
                <button type="submit" name="validation_action" value="return" 
                        style="padding:12px 24px;background:#ef4444;color:#ffffff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 4px rgba(239,68,68,0.2);">
                    <i class="fas fa-undo" style="margin-right:6px;"></i> Return to LGU
                </button>
                <button type="submit" name="validation_action" value="approve" 
                        style="padding:12px 24px;background:#059669;color:#ffffff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 4px rgba(5,150,105,0.2);">
                    <i class="fas fa-check-circle" style="margin-right:6px;"></i> Approve Assessment
                </button>
            </div>
        </form>
    </div>
@endif
@endsection

