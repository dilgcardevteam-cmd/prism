@extends('layouts.dashboard')

@section('title', 'Fund Utilization Report - ' . $report->project_code)
@section('page-title', 'Fund Utilization Report Details')

@section('content')
    <div class="ops-detail-page">
    <style>
        .ops-detail-page .ops-upload-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 12px !important;
            border: 1.5px dashed #9fb2d4 !important;
            border-radius: 10px !important;
            font-size: 12px !important;
            line-height: 1.4;
            color: #1f2937;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%) !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .ops-detail-page .ops-upload-input:focus {
            outline: none;
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .ops-detail-page .ops-upload-input.drag-active {
            border-color: #1d4ed8 !important;
            background: #e8f0ff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .ops-detail-page .ops-upload-input.is-disabled {
            cursor: not-allowed;
            opacity: 0.65;
            background: #f3f4f6 !important;
            border-style: solid !important;
        }

        .ops-detail-page .ops-upload-input::-webkit-file-upload-button {
            margin-right: 10px;
            border: none;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.02em;
            color: #1e3a8a;
            background: #dbeafe;
            cursor: pointer;
        }

        .ops-detail-page .ops-upload-submit {
            background: linear-gradient(135deg, #059669, #047857) !important;
            box-shadow: 0 8px 14px rgba(5, 150, 105, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .ops-detail-page .ops-upload-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 11px 18px rgba(5, 150, 105, 0.28);
            filter: brightness(1.03);
        }

        .ops-detail-page .ops-upload-filename {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
        }

        .ops-detail-page .ops-upload-filename.has-file {
            border-color: #86efac;
            background: #ecfdf3;
            color: #166534;
        }

        @media (max-width: 640px) {
            .ops-detail-page .ops-upload-input {
                min-width: 100%;
            }
        }
    </style>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 0;">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #002C76, #003d9e); padding: 5px 14px; border-radius: 999px; margin-bottom: 10px;">
                <i class="fas fa-file-invoice-dollar" style="color: rgba(255,255,255,0.85); font-size: 11px;"></i>
                <span style="color: white; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;">{{ $report->project_code }}</span>
            </div>
            <h1 style="color: #0f172a; font-size: 20px; font-weight: 700; margin: 0; line-height: 1.35;">{{ $report->project_title }}</h1>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-shrink: 0;">
            <a href="{{ route('fund-utilization.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Project Information Card -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(15,23,42,0.09); margin-bottom: 28px; overflow: hidden;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 16px 24px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
            <div style="width: 34px; height: 34px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-info-circle" style="color: white; font-size: 14px;"></i>
            </div>
            <h2 style="color: white; font-size: 15px; font-weight: 700; margin: 0; letter-spacing: 0.01em;">Project Information</h2>
        </div>
        <div style="padding: 24px 28px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Code</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->project_code }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->province }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Implementing Unit</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->implementing_unit }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Barangay</label>
                @php
                    $barangayList = collect(preg_split('/[\\r\\n,]+/', $report->barangay ?? ''))
                        ->map(fn($item) => trim($item))
                        ->filter();
                @endphp
                @if($barangayList->isEmpty())
                    <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">Not specified</p>
                @else
                    <ul style="color: #111827; font-size: 16px; font-weight: 500; margin: 0; padding-left: 18px;">
                        @foreach($barangayList as $barangay)
                            <li>{{ $barangay }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Fund Source</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->fund_source }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Funding Year</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->funding_year }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Allocation</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->allocation ? '₱' . number_format($report->allocation, 2) : 'Not specified' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Contract Amount</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->contract_amount ? '₱' . number_format($report->contract_amount, 2) : 'Not specified' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Status</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">
                    @if($report->project_status)
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;
                            @if($report->project_status === 'Ongoing')
                                background-color: #dbeafe; color: #1d4ed8;
                            @elseif($report->project_status === 'Completed')
                                background-color: #d1fae5; color: #065f46;
                            @elseif($report->project_status === 'Cancelled')
                                background-color: #fee2e2; color: #991b1b;
                            @elseif($report->project_status === 'On Hold')
                                background-color: #fef3c7; color: #92400e;
                            @else
                                background-color: #f3f4f6; color: #374151;
                            @endif">
                            {{ $report->project_status }}
                        </span>
                    @else
                        Not specified
                    @endif
                </p>
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Title</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $report->project_title }}</p>
            </div>
        </div>
        </div>
    </div>

    @php
        $isProvincialDilgViewer = Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office';
        $resolveUploaderMeta = function ($record, ?string $uploadedAtField = null, ?string $encoderField = null) use ($isProvincialDilgViewer) {
            if (!$record) {
                return ['time' => null, 'name' => 'Unknown'];
            }

            $uploadedAt = $uploadedAtField ? ($record->{$uploadedAtField} ?? null) : null;
            if (!$uploadedAt) {
                $uploadedAt = $record->created_at ?? $record->updated_at ?? null;
            }

            $uploadedTime = null;
            if ($uploadedAt) {
                $uploadedTime = is_string($uploadedAt)
                    ? \Carbon\Carbon::parse($uploadedAt)->setTimezone(config('app.timezone'))
                    : $uploadedAt->setTimezone(config('app.timezone'));
            }

            $encoderId = $encoderField ? ($record->{$encoderField} ?? null) : null;
            if (!$encoderId) {
                $encoderId = $record->encoder_id ?? null;
            }
            if (!$encoderId && $isProvincialDilgViewer) {
                $encoderId = $record->approved_by_dilg_po ?? $record->approved_by ?? null;
            }

            $encoderUser = $encoderId ? \App\Models\User::where('idno', $encoderId)->first() : null;
            $encoderName = $encoderUser ? trim($encoderUser->fname . ' ' . $encoderUser->lname) : 'Unknown';

            return ['time' => $uploadedTime, 'name' => $encoderName];
        };
        $resolveSubmissionTimelinessTag = function ($uploadedAt, $configuredDeadline) {
            if (!$uploadedAt || !is_array($configuredDeadline)) {
                return null;
            }

            $deadlineAt = $configuredDeadline['deadline_at'] ?? null;
            if (!$deadlineAt) {
                return null;
            }

            $timezone = config('app.timezone');
            $submittedAt = $uploadedAt instanceof \Carbon\CarbonInterface
                ? $uploadedAt->copy()->setTimezone($timezone)
                : \Carbon\Carbon::parse($uploadedAt)->setTimezone($timezone);
            $deadlineTime = $deadlineAt instanceof \Carbon\CarbonInterface
                ? $deadlineAt->copy()->setTimezone($timezone)
                : \Carbon\Carbon::parse($deadlineAt)->setTimezone($timezone);
            $isLate = $submittedAt->greaterThan($deadlineTime);

            return [
                'label' => $isLate ? 'Late' : 'On Time',
                'background' => $isLate ? '#fef2f2' : '#ecfdf5',
                'color' => $isLate ? '#b91c1c' : '#047857',
                'border' => $isLate ? '#fecaca' : '#a7f3d0',
                'title' => $isLate
                    ? 'Submitted after the configured deadline of ' . $deadlineTime->format('M d, Y h:i A')
                    : 'Submitted on or before the configured deadline of ' . $deadlineTime->format('M d, Y h:i A'),
            ];
        };
    @endphp

    <!-- Quarterly Sections -->
    @foreach ($quarters as $quarter)
        @php
            $quarterLabels = ['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'];
            $quarterWindows = [
                'Q1' => 'January - March',
                'Q2' => 'April - June',
                'Q3' => 'July - September',
                'Q4' => 'October - December',
            ];
            $quarterLabel = $quarterLabels[$quarter] ?? $quarter;
            $quarterWindow = $quarterWindows[$quarter] ?? '';
            $configuredQuarterDeadline = $configuredQuarterDeadlines[$quarter] ?? null;
            $quarterDeadlineDisplay = trim((string) ($configuredQuarterDeadline['display'] ?? ''));
            $isExpandedByDefault = $loop->first;
            $displayStyle = $isExpandedByDefault ? 'block' : 'none';
            $iconRotation = $isExpandedByDefault ? 'rotate(180deg)' : 'rotate(0deg)';

            // Define FDP variables early to avoid undefined variable errors
            $isFdpReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_status === 'returned';
        @endphp
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(15,23,42,0.09); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden;">
            <!-- Quarter Accordion Header -->
            <button type="button" onclick="toggleAccordion('quarter-{{ $quarter }}')" style="width: 100%; padding: 18px 24px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); color: white; border: none; text-align: left; cursor: pointer; font-weight: 700; font-size: 15px; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter='brightness(1)'">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <span style="width: 34px; height: 34px; background: rgba(255,255,255,0.15); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-calendar-alt" style="font-size: 14px;"></i>
                    </span>
                    <span>{{ $quarterLabel }}</span>
                    <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: {{ $quarterDeadlineDisplay !== '' ? 'rgba(15,118,110,0.32)' : 'rgba(107,114,128,0.35)' }}; color: #fff;">
                        {{ $quarterDeadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}
                    </span>
                    <span style="font-size: 11px; opacity: 0.95;">{{ $quarterWindow }}</span>
                    <span style="font-size: 11px; opacity: 0.95;">Deadline: {{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}</span>
                    <span style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.15); padding: 4px 12px; border-radius: 999px; font-size: 12px;">
                        <span style="width: 60px; height: 5px; background: rgba(255,255,255,0.25); border-radius: 999px; overflow: hidden; display: inline-block;">
                            <span style="width: {{ $accomplishmentPercentages[$quarter] }}%; height: 100%; background: #34d399; display: block;"></span>
                        </span>
                        <span style="font-weight: 700;">{{ $accomplishmentPercentages[$quarter] }}%</span>
                    </span>
                </span>
                <i class="fas fa-chevron-down" id="icon-quarter-{{ $quarter }}" style="transition: transform 0.3s; transform: {{ $iconRotation }}; opacity: 0.9;"></i>
            </button>

            <!-- Quarter Content -->
            <div id="quarter-{{ $quarter }}" style="display: {{ $displayStyle }}; padding: 22px 24px;">
            <!-- Fund Utilization Report (MOV) Section -->
            @php
                $hasMovFile = $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path;
                $movStatusColor = $hasMovFile ? '#10b981' : '#f59e0b';
                $movBackgroundColor = $hasMovFile ? '#fffbeb' : 'transparent';
                
                // Initialize variables
                $isPendingDilgRoValidation = false;
                $isApprovedByDilgRo = false;
                
                // Check if document was returned
                $isMovReturned = $movUploads[$quarter] && $movUploads[$quarter]->status === 'returned';
                
                if ($isMovReturned) {
                    $movStatusColor = '#ef4444';
                    $movStatusLabel = 'Returned';
                    $movBackgroundColor = '#fee2e2';
                } else {
                    // Check if DILG PO has approved (waiting for RO validation)
                    $isPendingDilgRoValidation = $movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_po && !$movUploads[$quarter]->approved_at_dilg_ro;
                    $isApprovedByDilgRo = $movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_ro;
                    
                    if ($isApprovedByDilgRo) {
                        $movStatusColor = '#059669';
                        $movStatusLabel = 'Approved';
                    } elseif ($isPendingDilgRoValidation) {
                        $movStatusColor = '#3b82f6';
                        $movStatusLabel = 'For DILG Regional Office Validation';
                    } else {
                        $movStatusLabel = $hasMovFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                    }
                }

                $isMovForPoValidation = $hasMovFile && !$isMovReturned && !$isPendingDilgRoValidation && !$isApprovedByDilgRo;
                $isMovUnderValidation = $isPendingDilgRoValidation || $isMovForPoValidation;
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $movStatusColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 0 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(220,38,38,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Fund Utilization Report</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">MOV on PDF Format</span>
                        </span>
                    </span>
                    @php
                    @endphp
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $movStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $movStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($movUploads[$quarter], 'mov_uploaded_at', 'mov_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $encoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_po;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($movUploads[$quarter]->approved_at_dilg_po) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at_dilg_po)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at_dilg_po->setTimezone(config('app.timezone'));
                                        $poApproverId = $movUploads[$quarter]->approved_by_dilg_po ?? $movUploads[$quarter]->approved_by;
                                        $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                        $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}
                                </span>
                            @endif
                            @if($movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_ro)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($movUploads[$quarter]->approved_at_dilg_ro) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                        $roApproverId = $movUploads[$quarter]->approved_by_dilg_ro ?? $movUploads[$quarter]->approved_by;
                                        $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                        $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}
                                </span>
                            @endif
                            @if($isMovReturned && $movUploads[$quarter] && $movUploads[$quarter]->approved_at)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                    @php
                                        $returnedAt = is_string($movUploads[$quarter]->approved_at) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at->setTimezone(config('app.timezone'));
                                        $returnedByUser = $movUploads[$quarter]->approver ? trim($movUploads[$quarter]->approver->fname . ' ' . $movUploads[$quarter]->approver->lname) : 'Unknown';
                                    @endphp
                                    Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $returnedByUser }}
                                </span>
                            @endif
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.upload-mov', $report->project_code) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="file" name="mov_file" accept="application/pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'mov-save-btn-{{ $quarter }}', 'mov-filename-{{ $quarter }}')" {{ $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path && $isMovReturned ? 'disabled' : '' }} title="{{ $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path && $isMovReturned ? 'Document was returned. Delete the current file to upload a new one.' : '' }}">
                        <button type="submit" id="mov-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-upload"></i> Submit
                        </button>
                    </form>
                    <div id="mov-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($movUploads[$quarter]->mov_file_path) }}
                        @endif
                    </div>

                    @if(Auth::user()->agency === 'LGU')
                        @if($movUploads[$quarter] && ($movUploads[$quarter]->mov_file_path || $isMovReturned))
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                                    <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'mov', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endif
                                @if((!$movUploads[$quarter]->encoder_id || $movUploads[$quarter]->encoder_id !== Auth::user()->idno || $isMovReturned) && !$isMovUnderValidation && $movUploads[$quarter]->status !== 'approved')
                                    <button type="button" onclick="deleteDocument('mov', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
            @endif

        </div>

    @endif

@if(Auth::user()->agency === 'LGU' && $movUploads[$quarter])

    <button type="button" onclick="toggleAccordion('mov-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">

        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>

        <i class="fas fa-chevron-down" id="icon-mov-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>

    </button>

    <div id="mov-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">

        <textarea id="textarea-mov-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $isMovReturned ? ($movUploads[$quarter]->approval_remarks ?? '') : ($movUploads[$quarter]->user_remarks ?? '') }}</textarea>

        <button type="button" onclick="saveRemarksAjax('mov', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>

    </div>

@endif

@elseif(Auth::user()->agency === 'DILG')
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPOMov = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                $hasMovFile = $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path;
                                $shouldHideDeleteForDilgMov = $isDilgPOMov || $hasMovFile;
                            @endphp
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'mov', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if(
                                    (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isMovForPoValidation)
                                    || (!$isMovForPoValidation && (!$isPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
                                )
                                    @if(!$shouldHideDeleteForDilgMov && (Auth::user()->province === 'Regional Office' || $movUploads[$quarter]->status !== 'approved'))
                                        <button type="button" onclick="deleteDocument('mov', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    @endif
                                    @if($movUploads[$quarter]->status !== 'approved')
                                        <button type="button" onclick="openRemarksModal('mov', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    @endif
                                    @if(
                                        Auth::user()->province === 'Regional Office'
                                        || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isMovForPoValidation)
                                    )
                                        <button type="button" onclick="openRemarksModal('mov', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($movUploads[$quarter] && ($movUploads[$quarter]->mov_file_path || $movUploads[$quarter]->user_remarks || $isMovReturned))
                            <button type="button" onclick="toggleAccordion('mov-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                <i class="fas fa-chevron-down" id="icon-mov-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                            </button>
                            <div id="mov-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <textarea id="textarea-mov-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;" {{ $isMovReturned ? 'readonly' : '' }}>{{ $isMovReturned ? ($movUploads[$quarter]->approval_remarks ?? '') : ($movUploads[$quarter]->user_remarks ?? '') }}</textarea>
                                @if(!$isMovReturned)
                                    <button type="button" onclick="saveRemarksAjax('mov', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
                @if ($movUploads[$quarter])
                    <!-- DILG Approval Buttons -->
                    @if(Auth::user()->agency === 'DILG')
                        <!-- Remarks Section -->
                        @if($movUploads[$quarter]->approval_remarks)
                            <div style="margin-top: 12px; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Approval Remarks:</p>
                                <p style="color: #374151; font-size: 13px; margin: 0;">{{ $movUploads[$quarter]->approval_remarks }}</p>
                            </div>
                        @endif
                    @elseif(Auth::user()->agency === 'LGU' && $movUploads[$quarter]->approval_remarks)
                        <!-- View Remarks for LGU -->
                        <div style="margin-top: 12px; padding: 10px; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                            <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">DILG Remarks:</p>
                            <p style="color: #374151; font-size: 13px; margin: 0;">{{ $movUploads[$quarter]->approval_remarks }}</p>
                        </div>
                    @endif
                @endif
            </div>
            </div>

            <!-- Written Notice Section -->
            @php
                $hasAnyWrittenFile = $writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $writtenNotices[$quarter]->secretary_dilg_path || $writtenNotices[$quarter]->speaker_house_path || $writtenNotices[$quarter]->president_senate_path || $writtenNotices[$quarter]->house_committee_path || $writtenNotices[$quarter]->senate_committee_path);
                $writtenBackgroundColor = $hasAnyWrittenFile ? '#fffbeb' : 'transparent';
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(37,99,235,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-envelope-open-text" style="color: #2563eb; font-size: 13px;"></i>
                        </span>
                        <div>
                            <p style="margin: 0; color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Written Notice</p>
                            <p style="margin: 0; color: #64748b; font-size: 11px;">MOV Screenshot of Emailed Notice &amp; Written Notice PDF</p>
                        </div>
                    </div>
                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->updated_at)
                        <span style="font-size: 11px; color: #6b7280; flex-shrink: 0; white-space: nowrap;">
                            @php
                                $createdAt = $writtenNotices[$quarter]->updated_at;
                                $uploadedTime = is_string($createdAt) ? \Carbon\Carbon::parse($createdAt)->setTimezone(config('app.timezone')) : $createdAt->setTimezone(config('app.timezone'));
                            @endphp
                            Updated: {{ $uploadedTime->format('M d, Y h:i A') }}
                        </span>
                    @endif
                </div>
                <div style="padding: 16px;">
                <form id="written-notice-form-{{ $quarter }}" action="{{ route('fund-utilization.upload-written-notice', $report->project_code) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="quarter" value="{{ $quarter }}">

                    <div style="border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 15px;">
                        <p style="color: #374151; font-weight: 600; font-size: 13px; margin-bottom: 15px;">Distribution Recipients:</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <!-- Secretary of DBM -->
                            @php
                                $hasDbmFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path;
                                $dbmFieldBg = $hasDbmFile ? '#fffbeb' : '#f9fafb';
                                
                                // Initialize variables
                                $isDbmPendingDilgRoValidation = false;
                                $isDbmApprovedByDilgRo = false;
                                
                                // Check if document was returned - using individual dbm_status field
                                $isDbmReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_status === 'returned';
                                
                                if ($isDbmReturned) {
                                    $dbmStatusColor = '#ef4444';
                                    $dbmStatusLabel = 'Returned';
                                    $dbmFieldBg = '#fee2e2';
                                } else {
                                    // Use individual DBM approval fields so approval is per-document.
                                    $hasDbmApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path && $writtenNotices[$quarter]->dbm_approved_at;
                                    $isDbmPendingDilgRoValidation = $hasDbmApproval && $writtenNotices[$quarter]->dbm_status === 'pending';
                                    $isDbmApprovedByDilgRo = $hasDbmApproval && $writtenNotices[$quarter]->dbm_status === 'approved';
                                    
                                    if ($isDbmApprovedByDilgRo) {
                                        $dbmStatusColor = '#059669';
                                        $dbmStatusLabel = 'Approved';
                                    } elseif ($isDbmPendingDilgRoValidation) {
                                        $dbmStatusColor = '#3b82f6';
                                        $dbmStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $dbmStatusColor = $hasDbmFile ? '#10b981' : '#f59e0b';
                                    $dbmStatusLabel = $hasDbmFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }
                            }

                                $isDbmForPoValidation = $hasDbmFile && !$isDbmReturned && !$isDbmPendingDilgRoValidation && !$isDbmApprovedByDilgRo;
                                $isDbmUnderValidation = $isDbmPendingDilgRoValidation || $isDbmForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $dbmFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Secretary of DBM</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $dbmStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $dbmStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if(
                                    ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                    || ($isDbmReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at)
                                )
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                                @php
                                                    $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'dbm_uploaded_at', 'dbm_encoder_id');
                                                    $uploadedTime = $uploadedInfo['time'];
                                                    $encoderName = $uploadedInfo['name'];
                                                @endphp
                                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                                @php
                                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                                @endphp
                                                @if($submissionTimeliness)
                                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                        {{ $submissionTimeliness['label'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            @php
                                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                                $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                                $hasPoApproval = $writtenNotices[$quarter]
                                                    && $writtenNotices[$quarter]->dbm_approved_at_dilg_po;
                                            @endphp
                                            @if($hasPoApproval)
                                                @php
                                                    $poTimestamp = $writtenNotices[$quarter]->dbm_approved_at_dilg_po;
                                                    $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                    $poApproverId = $writtenNotices[$quarter]->dbm_approved_by_dilg_po ?? $writtenNotices[$quarter]->dbm_approved_by;
                                                    $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                    $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                            @endif
                                            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at_dilg_ro && $writtenNotices[$quarter]->dbm_status === 'approved')
                                                @php
                                                    $roTimestamp = $writtenNotices[$quarter]->dbm_approved_at_dilg_ro;
                                                    $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                    $roApproverId = $writtenNotices[$quarter]->dbm_approved_by_dilg_ro ?? $writtenNotices[$quarter]->dbm_approved_by;
                                                    $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                    $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                            @endif
                                        @endif
                                        @if($isDbmReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at)
                                            @php
                                                $dbmReturnedAt = is_string($writtenNotices[$quarter]->dbm_approved_at) ? \Carbon\Carbon::parse($writtenNotices[$quarter]->dbm_approved_at)->setTimezone(config('app.timezone')) : $writtenNotices[$quarter]->dbm_approved_at->setTimezone(config('app.timezone'));
                                                $dbmApproverUser = $writtenNotices[$quarter]->dbm_approved_by ? \App\Models\User::where('idno', $writtenNotices[$quarter]->dbm_approved_by)->first() : null;
                                                $dbmApproverName = $dbmApproverUser ? trim($dbmApproverUser->fname . ' ' . $dbmApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">Returned at: {{ $dbmReturnedAt->format('M d, Y h:i A') }} by {{ $dbmApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="secretary_dbm" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'dbm-save-btn-{{ $quarter }}', 'dbm-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path && $isDbmReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path && $isDbmReturned ? 'Document was returned. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="dbm-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="dbm-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->secretary_dbm_path) }}
                                    @endif
                                </div>


@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dbm', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$isDbmUnderValidation && $writtenNotices[$quarter]->dbm_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-dbm', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $isDbmReturned))
        <button type="button" onclick="toggleAccordion('dbm-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-dbm-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="dbm-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dbm-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dbm_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dbm-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPODbm = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasDbmFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path;
                                        $shouldHideDeleteForDilgDbm = $isDilgPODbm || $hasDbmFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dbm', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$shouldHideDeleteForDilgDbm && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->dbm_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-dbm', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isDbmForPoValidation)
                || (!$isDbmForPoValidation && (!$isDbmPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->dbm_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-dbm', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isDbmForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-dbm', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $writtenNotices[$quarter]->user_remarks || $isDbmReturned))
                                    <button type="button" onclick="toggleAccordion('dbm-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-dbm-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
        <div id="dbm-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dbm-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dbm_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dbm-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
                                    @endif
                                @endif
                            </div>
                            <!-- Secretary of DILG -->
                            @php
                                $hasDilgFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path;
                                $dilgFieldBg = $hasDilgFile ? '#fffbeb' : '#f9fafb';
                                
                                // Initialize variables
                                $isDilgPendingDilgRoValidation = false;
                                $isDilgApprovedByDilgRo = false;
                                
                                // Check if document was returned - using individual dilg_status field
                                $isDilgReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_status === 'returned';
                                
                                if ($isDilgReturned) {
                                    $dilgStatusColor = '#ef4444';
                                    $dilgStatusLabel = 'Returned';
                                    $dilgFieldBg = '#fee2e2';
                                } else {
                                    // Use individual DILG approval fields so approval is per-document.
                                    $hasDilgApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path && $writtenNotices[$quarter]->dilg_approved_at;
                                    $isDilgPendingDilgRoValidation = $hasDilgApproval && $writtenNotices[$quarter]->dilg_status === 'pending';
                                    $isDilgApprovedByDilgRo = $hasDilgApproval && $writtenNotices[$quarter]->dilg_status === 'approved';
                                    
                                    if ($isDilgApprovedByDilgRo) {
                                        $dilgStatusColor = '#059669';
                                        $dilgStatusLabel = 'Approved';
                                    } elseif ($isDilgPendingDilgRoValidation) {
                                        $dilgStatusColor = '#3b82f6';
                                        $dilgStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $dilgStatusColor = $hasDilgFile ? '#10b981' : '#f59e0b';
                                    $dilgStatusLabel = $hasDilgFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }
                            }

                                $isDilgForPoValidation = $hasDilgFile && !$isDilgReturned && !$isDilgPendingDilgRoValidation && !$isDilgApprovedByDilgRo;
                                $isDilgUnderValidation = $isDilgPendingDilgRoValidation || $isDilgForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $dilgFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Secretary of DILG</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $dilgStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $dilgStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if(
                                    ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                    || ($isDilgReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at)
                                )
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                                @php
                                                    $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'dilg_uploaded_at', 'dilg_encoder_id');
                                                    $uploadedTime = $uploadedInfo['time'];
                                                    $encoderName = $uploadedInfo['name'];
                                                @endphp
                                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                                @php
                                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                                @endphp
                                                @if($submissionTimeliness)
                                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                        {{ $submissionTimeliness['label'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            @php
                                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                                $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                                $hasPoApproval = $writtenNotices[$quarter]
                                                    && $writtenNotices[$quarter]->dilg_approved_at_dilg_po;
                                            @endphp
                                            @if($hasPoApproval)
                                                @php
                                                    $poTimestamp = $writtenNotices[$quarter]->dilg_approved_at_dilg_po;
                                                    $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                    $poApproverId = $writtenNotices[$quarter]->dilg_approved_by_dilg_po ?? $writtenNotices[$quarter]->dilg_approved_by;
                                                    $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                    $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                            @endif
                                            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at_dilg_ro && $writtenNotices[$quarter]->dilg_status === 'approved')
                                                @php
                                                    $roTimestamp = $writtenNotices[$quarter]->dilg_approved_at_dilg_ro;
                                                    $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                    $roApproverId = $writtenNotices[$quarter]->dilg_approved_by_dilg_ro ?? $writtenNotices[$quarter]->dilg_approved_by;
                                                    $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                    $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                            @endif
                                        @endif
                                        @if($isDilgReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at)
                                            @php
                                                $returnedAt = is_string($writtenNotices[$quarter]->dilg_approved_at) ? \Carbon\Carbon::parse($writtenNotices[$quarter]->dilg_approved_at)->setTimezone(config('app.timezone')) : $writtenNotices[$quarter]->dilg_approved_at->setTimezone(config('app.timezone'));
                                                $dilgApproverUser = $writtenNotices[$quarter]->dilg_approved_by ? \App\Models\User::where('idno', $writtenNotices[$quarter]->dilg_approved_by)->first() : null;
                                                $approverName = $dilgApproverUser ? trim($dilgApproverUser->fname . ' ' . $dilgApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $approverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="secretary_dilg" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'dilg-save-btn-{{ $quarter }}', 'dilg-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path && !$isDilgReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path && !$isDilgReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="dilg-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="dilg-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->secretary_dilg_path) }}
                                    @endif
                                </div>


@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $isDilgReturned))
            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dilg', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-eye"></i> View
                </a>
            @endif
            @if(!$isDilgUnderValidation && $writtenNotices[$quarter]->dilg_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-dilg', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $isDilgReturned))
        <button type="button" onclick="toggleAccordion('dilg-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-dilg-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="dilg-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dilg-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dilg_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dilg-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPODilg = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasDilgFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path;
                                        $shouldHideDeleteForDilgDilg = $isDilgPODilg || $hasDilgFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dilg', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$shouldHideDeleteForDilgDilg && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->dilg_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-dilg', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isDilgForPoValidation)
                || (!$isDilgForPoValidation && (!$isDilgPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->dilg_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-dilg', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isDilgForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-dilg', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $writtenNotices[$quarter]->user_remarks || $isDilgReturned))
                                    <button type="button" onclick="toggleAccordion('dilg-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-dilg-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="dilg-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-dilg-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;" {{ $isDilgReturned ? 'readonly' : '' }}>{{ $isDilgReturned ? ($writtenNotices[$quarter]->dilg_remarks ?? '') : ($writtenNotices[$quarter]->user_remarks ?? '') }}</textarea>
                                        @if(!$isDilgReturned)
                                            <button type="button" onclick="saveRemarksAjax('dilg-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                        @endif
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Speaker of the House -->
                            @php
                                $hasSpeakerFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path;
                                $speakerFieldBg = $hasSpeakerFile ? '#fffbeb' : '#f9fafb';
                                
                                // Initialize variables
                                $isSpeakerPendingDilgRoValidation = false;
                                $isSpeakerApprovedByDilgRo = false;
                                
                                // Check if document was returned - using individual speaker_status field
                                $isSpeakerReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_status === 'returned';
                                
                                if ($isSpeakerReturned) {
                                    $speakerStatusColor = '#ef4444';
                                    $speakerStatusLabel = 'Returned';
                                    $speakerFieldBg = '#fee2e2';
                                } else {
                                    // Use individual speaker approval fields so approval is per-document.
                                    $hasSpeakerApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path && $writtenNotices[$quarter]->speaker_approved_at;
                                    $isSpeakerPendingDilgRoValidation = $hasSpeakerApproval && $writtenNotices[$quarter]->speaker_status === 'pending';
                                    $isSpeakerApprovedByDilgRo = $hasSpeakerApproval && $writtenNotices[$quarter]->speaker_status === 'approved';
                                    
                                    if ($isSpeakerApprovedByDilgRo) {
                                        $speakerStatusColor = '#059669';
                                        $speakerStatusLabel = 'Approved';
                                    } elseif ($isSpeakerPendingDilgRoValidation) {
                                        $speakerStatusColor = '#3b82f6';
                                        $speakerStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $speakerStatusColor = $hasSpeakerFile ? '#10b981' : '#f59e0b';
                                        $speakerStatusLabel = $hasSpeakerFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isSpeakerForPoValidation = $hasSpeakerFile && !$isSpeakerReturned && !$isSpeakerPendingDilgRoValidation && !$isSpeakerApprovedByDilgRo;
                                $isSpeakerUnderValidation = $isSpeakerPendingDilgRoValidation || $isSpeakerForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $speakerFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Speaker of the House</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $speakerStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $speakerStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'speaker_uploaded_at', 'speaker_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->speaker_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->speaker_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->speaker_approved_by_dilg_po ?? $writtenNotices[$quarter]->speaker_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_approved_at_dilg_ro && $writtenNotices[$quarter]->speaker_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->speaker_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->speaker_approved_by_dilg_ro ?? $writtenNotices[$quarter]->speaker_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="speaker_house" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'speaker-save-btn-{{ $quarter }}', 'speaker-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path && !$isSpeakerReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path && !$isSpeakerReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="speaker-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="speaker-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->speaker_house_path) }}
                                    @endif
                                </div>

@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-speaker', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$isSpeakerUnderValidation && $writtenNotices[$quarter]->speaker_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-speaker', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->speaker_house_path || $isSpeakerReturned))
        <button type="button" onclick="toggleAccordion('speaker-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-speaker-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="speaker-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-speaker-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->speaker_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('speaker-house', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOSpeaker = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasSpeakerFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path;
                                        $shouldHideDeleteForDilgSpeaker = $isDilgPOSpeaker || $hasSpeakerFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-speaker', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$shouldHideDeleteForDilgSpeaker && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->speaker_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-speaker', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isSpeakerForPoValidation)
                || (!$isSpeakerForPoValidation && (!$isSpeakerPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->speaker_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-speaker', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isSpeakerForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-speaker', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->speaker_house_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('speaker-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-speaker-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="speaker-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-speaker-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->speaker_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('speaker-house', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- President of the Senate -->
                            @php
                                $hasPresidentFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path;
                                $presidentFieldBg = $hasPresidentFile ? '#fffbeb' : '#f9fafb';
                                $isPresidentReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_status === 'returned';
                                if ($isPresidentReturned) {
                                    $presidentFieldBg = '#fee2e2';
                                }
                                
                                // Use individual president approval fields so approval is per-document.
                                $hasPresidentApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path && $writtenNotices[$quarter]->president_approved_at;
                                $isPresidentPendingDilgRoValidation = $hasPresidentApproval && $writtenNotices[$quarter]->president_status === 'pending';
                                $isPresidentApprovedByDilgRo = $hasPresidentApproval && $writtenNotices[$quarter]->president_status === 'approved';
                                
                                if ($isPresidentReturned) {
                                    $presidentStatusColor = '#ef4444';
                                    $presidentStatusLabel = 'Returned';
                                } elseif ($isPresidentApprovedByDilgRo) {
                                    $presidentStatusColor = '#059669';
                                    $presidentStatusLabel = 'Approved';
                                } elseif ($isPresidentPendingDilgRoValidation) {
                                    $presidentStatusColor = '#3b82f6';
                                    $presidentStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $presidentStatusColor = $hasPresidentFile ? '#10b981' : '#f59e0b';
                                    $presidentStatusLabel = $hasPresidentFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }

                                $isPresidentForPoValidation = $hasPresidentFile && !$isPresidentReturned && !$isPresidentPendingDilgRoValidation && !$isPresidentApprovedByDilgRo;
                                $isPresidentUnderValidation = $isPresidentPendingDilgRoValidation || $isPresidentForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $presidentFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>President of the Senate</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $presidentStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $presidentStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'president_uploaded_at', 'president_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->president_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->president_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->president_approved_by_dilg_po ?? $writtenNotices[$quarter]->president_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_approved_at_dilg_ro && $writtenNotices[$quarter]->president_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->president_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->president_approved_by_dilg_ro ?? $writtenNotices[$quarter]->president_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="president_senate" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'president-save-btn-{{ $quarter }}', 'president-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path && !$isPresidentReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path && !$isPresidentReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="president-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="president-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->president_senate_path) }}
                                    @endif
                                </div>

@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-president', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$isPresidentUnderValidation && $writtenNotices[$quarter]->president_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-president', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
@elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOPresident = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasPresidentFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path;
                                        $shouldHideDeleteForDilgPresident = $isDilgPOPresident || $hasPresidentFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-president', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$shouldHideDeleteForDilgPresident && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->president_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-president', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isPresidentForPoValidation)
                || (!$isPresidentForPoValidation && (!$isPresidentPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->president_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-president', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isPresidentForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-president', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->president_senate_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('president-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-president-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="president-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-president-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->president_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('president-senate', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- House Committee on Appropriation -->
                            @php
                                $hasHouseFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path;
                                $houseFieldBg = $hasHouseFile ? '#fffbeb' : '#f9fafb';
                                
                                // Initialize variables
                                $isHousePendingDilgRoValidation = false;
                                $isHouseApprovedByDilgRo = false;
                                
                                // Check if document was returned - using individual house_status field
                                $isHouseReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_status === 'returned';
                                
                                if ($isHouseReturned) {
                                    $houseStatusColor = '#ef4444';
                                    $houseStatusLabel = 'Returned';
                                    $houseFieldBg = '#fee2e2';
                                } else {
                                    // Use individual house approval fields so approval is per-document.
                                    $hasHouseApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path && $writtenNotices[$quarter]->house_approved_at;
                                    $isHousePendingDilgRoValidation = $hasHouseApproval && $writtenNotices[$quarter]->house_status === 'pending';
                                    $isHouseApprovedByDilgRo = $hasHouseApproval && $writtenNotices[$quarter]->house_status === 'approved';
                                    
                                    if ($isHouseApprovedByDilgRo) {
                                        $houseStatusColor = '#059669';
                                        $houseStatusLabel = 'Approved';
                                    } elseif ($isHousePendingDilgRoValidation) {
                                        $houseStatusColor = '#3b82f6';
                                        $houseStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $houseStatusColor = $hasHouseFile ? '#10b981' : '#f59e0b';
                                        $houseStatusLabel = $hasHouseFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isHouseForPoValidation = $hasHouseFile && !$isHouseReturned && !$isHousePendingDilgRoValidation && !$isHouseApprovedByDilgRo;
                                $isHouseUnderValidation = $isHousePendingDilgRoValidation || $isHouseForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $houseFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>House Committee on Appropriation</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $houseStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $houseStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'house_uploaded_at', 'house_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->house_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->house_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->house_approved_by_dilg_po ?? $writtenNotices[$quarter]->house_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_approved_at_dilg_ro && $writtenNotices[$quarter]->house_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->house_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->house_approved_by_dilg_ro ?? $writtenNotices[$quarter]->house_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="house_committee" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'house-save-btn-{{ $quarter }}', 'house-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path && !$isHouseReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path && !$isHouseReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="house-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="house-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->house_committee_path) }}
                                    @endif
                                </div>

@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-house', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$isHouseUnderValidation && $writtenNotices[$quarter]->house_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-house', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
@elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOHouse = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasHouseFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path;
                                        $shouldHideDeleteForDilgHouse = $isDilgPOHouse || $hasHouseFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-house', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$shouldHideDeleteForDilgHouse && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->house_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-house', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isHouseForPoValidation)
                || (!$isHouseForPoValidation && (!$isHousePendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->house_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-house', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isHouseForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-house', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->house_committee_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('house-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-house-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="house-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-house-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->house_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('house-committee', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Senate Committee on Finance -->
                            @php
                                $hasSenateFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path;
                                $senateFieldBg = $hasSenateFile ? '#fffbeb' : '#f9fafb';
                                
                                // Check if Senate document was returned
                                $isSenateReturned = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_status === 'returned';
                                
                                // Apply returned styling if applicable
                                if ($isSenateReturned) {
                                    $senateFieldBg = '#fee2e2';
                                }
                                
                                // Use individual senate approval fields so approval is per-document.
                                $hasSenateApproval = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path && $writtenNotices[$quarter]->senate_approved_at;
                                $isSenatePendingDilgRoValidation = $hasSenateApproval && $writtenNotices[$quarter]->senate_status === 'pending';
                                $isSenateApprovedByDilgRo = $hasSenateApproval && $writtenNotices[$quarter]->senate_status === 'approved';
                                
                                if ($isSenateReturned) {
                                    $senateStatusColor = '#ef4444';
                                    $senateStatusLabel = 'Returned';
                                } elseif ($isSenateApprovedByDilgRo) {
                                    $senateStatusColor = '#059669';
                                    $senateStatusLabel = 'Approved';
                                } elseif ($isSenatePendingDilgRoValidation) {
                                    $senateStatusColor = '#3b82f6';
                                    $senateStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $senateStatusColor = $hasSenateFile ? '#10b981' : '#f59e0b';
                                    $senateStatusLabel = $hasSenateFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }

                                $isSenateForPoValidation = $hasSenateFile && !$isSenateReturned && !$isSenatePendingDilgRoValidation && !$isSenateApprovedByDilgRo;
                                $isSenateUnderValidation = $isSenatePendingDilgRoValidation || $isSenateForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $senateFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Senate Committee on Finance</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $senateStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $senateStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'senate_uploaded_at', 'senate_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->senate_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->senate_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->senate_approved_by_dilg_po ?? $writtenNotices[$quarter]->senate_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_approved_at_dilg_ro && $writtenNotices[$quarter]->senate_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->senate_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->senate_approved_by_dilg_ro ?? $writtenNotices[$quarter]->senate_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="senate_committee" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'senate-save-btn-{{ $quarter }}', 'senate-filename-{{ $quarter }}')" {{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path && !$isSenateReturned ? 'disabled' : '' }} title="{{ $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path && !$isSenateReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                                    <button type="submit" id="senate-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="senate-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->senate_committee_path) }}
                                    @endif
                                </div>

@if(Auth::user()->agency === 'LGU')
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-senate', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if(!$isSenateUnderValidation && $writtenNotices[$quarter]->senate_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-senate', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
                                @elseif(Auth::user()->agency === 'DILG')
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOSenate = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasSenateFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path;
                                        $shouldHideDeleteForDilgSenate = $isDilgPOSenate || $hasSenateFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-senate', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if((!$shouldHideDeleteForDilgSenate || $isSenateReturned) && (Auth::user()->province === 'Regional Office' || $writtenNotices[$quarter]->senate_status !== 'approved'))
                <button type="button" onclick="deleteDocument('written-notice-senate', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if(
                (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isSenateForPoValidation)
                || (!$isSenateForPoValidation && (!$isSenatePendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
            )
                @if($writtenNotices[$quarter]->senate_status !== 'approved')
                    <button type="button" onclick="openRemarksModal('written-notice-senate', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                @endif
                @if(
                    Auth::user()->province === 'Regional Office'
                    || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isSenateForPoValidation)
                )
                    <button type="button" onclick="openRemarksModal('written-notice-senate', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->senate_committee_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('senate-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-senate-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="senate-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-senate-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->senate_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('senate-committee', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                        @endif
                    @endif
                </div>


            </div>
                    </div>
                </form>

                @if($writtenNotices[$quarter])
                    <!-- DILG Approval Buttons -->
                    @if(Auth::user()->agency === 'DILG')
                        <!-- Remarks Section -->
                        @if($writtenNotices[$quarter]->approval_remarks)
                            <div style="margin-top: 12px; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Approval Remarks:</p>
                                <p style="color: #374151; font-size: 13px; margin: 0;">{{ $writtenNotices[$quarter]->approval_remarks }}</p>
                            </div>
                        @endif
                    @elseif(Auth::user()->agency === 'LGU' && $writtenNotices[$quarter]->approval_remarks)
                        <!-- View Remarks for LGU -->
                        <div style="margin-top: 12px; padding: 10px; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                            <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">DILG Remarks:</p>
                            <p style="color: #374151; font-size: 13px; margin: 0;">{{ $writtenNotices[$quarter]->approval_remarks }}</p>
                        </div>
                    @endif
                @endif


            </div>
            </div>

            <!-- Full Disclosure Policy Section -->
            @php
                $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                $isFdpReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_status === 'returned';
                $fdpBackgroundColor = $isFdpReturned ? '#fee2e2' : ($hasFdpFile ? '#fffbeb' : 'transparent');
                $fdpBorderColor = $isFdpReturned ? '#ef4444' : ($hasFdpFile ? '#059669' : '#f59e0b');
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $fdpBorderColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="margin: 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(220,38,38,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Full Disclosure Policy (FDP)</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">On PDF Format</span>
                        </span>
                    </span>
                    @php
                        $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                        $isFdpPendingDilgRoValidation = false;
                        $isFdpApprovedByDilgRo = false;
                        
                        // Check if document was returned
                        $isFdpReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_status === 'returned';

                        if ($isFdpReturned) {
                            $fdpStatusColor = '#ef4444';
                            $fdpStatusLabel = 'Returned';
                            $fdpBackgroundColor = '#fee2e2';
                        } else {
                            // Check if DILG PO has approved (waiting for RO validation)
                            $isFdpPendingDilgRoValidation = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path && $fdpDocuments[$quarter]->approved_at_dilg_po && !$fdpDocuments[$quarter]->approved_at_dilg_ro;
                            $isFdpApprovedByDilgRo = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->approved_at_dilg_ro;
                            
                            if ($isFdpApprovedByDilgRo) {
                                $fdpStatusColor = '#059669';
                                $fdpStatusLabel = 'Approved';
                            } elseif ($isFdpPendingDilgRoValidation) {
                                $fdpStatusColor = '#3b82f6';
                                $fdpStatusLabel = 'For DILG Regional Office Validation';
                            } else {
                                $fdpStatusColor = $hasFdpFile ? '#10b981' : '#f59e0b';
                                $fdpStatusLabel = $hasFdpFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                            }
                        }

                        $isFdpForPoValidation = $hasFdpFile && !$isFdpReturned && !$isFdpPendingDilgRoValidation && !$isFdpApprovedByDilgRo;
                        $isFdpUnderValidation = $isFdpPendingDilgRoValidation || $isFdpForPoValidation;
                    @endphp
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $fdpStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $fdpStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($fdpDocuments[$quarter], 'fdp_uploaded_at', 'fdp_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $encoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->approved_at_dilg_po;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($fdpDocuments[$quarter]->approved_at_dilg_po) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->approved_at_dilg_po)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->approved_at_dilg_po->setTimezone(config('app.timezone'));
                                        $poApproverId = $fdpDocuments[$quarter]->approved_by_dilg_po ?? $fdpDocuments[$quarter]->approved_by;
                                        $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                        $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}
                                </span>
                            @endif
                            @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->approved_at_dilg_ro)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($fdpDocuments[$quarter]->approved_at_dilg_ro) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                        $roApproverId = $fdpDocuments[$quarter]->approved_by_dilg_ro ?? $fdpDocuments[$quarter]->approved_by;
                                        $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                        $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}
                                </span>
                            @endif
                            @if($isFdpReturned && $fdpDocuments[$quarter]->fdp_approved_at)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                    @php
                                        $returnedAt = is_string($fdpDocuments[$quarter]->fdp_approved_at) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->fdp_approved_at)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->fdp_approved_at->setTimezone(config('app.timezone'));
                                        $fdpApproverUser = $fdpDocuments[$quarter]->fdp_approved_by ? \App\Models\User::where('idno', $fdpDocuments[$quarter]->fdp_approved_by)->first() : null;
                                        $fdpApproverName = $fdpApproverUser ? trim($fdpApproverUser->fname . ' ' . $fdpApproverUser->lname) : 'Unknown';
                                    @endphp
                                    Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $fdpApproverName }}
                                </span>
                            @endif
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.upload-fdp', $report->project_code) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="file" name="fdp_file" accept="image/*,.pdf" style="flex: 1; min-width: 200px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" onchange="showSaveButton(this, 'fdp-save-btn-{{ $quarter }}', 'fdp-filename-{{ $quarter }}')" {{ $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path && !$isFdpReturned ? 'disabled' : '' }} title="{{ $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path && !$isFdpReturned ? 'File already uploaded. Delete the current file to upload a new one.' : '' }}">
                        <button type="submit" id="fdp-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-upload"></i> Submit
                        </button>
                    </form>
                    <div id="fdp-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($fdpDocuments[$quarter]->fdp_file_path) }}
                        @endif
                    </div>

                    @if(Auth::user()->agency === 'LGU')
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'fdp', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if(!$isFdpUnderValidation && $fdpDocuments[$quarter]->fdp_status !== 'approved')
                                    <button type="button" onclick="deleteDocument('fdp', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                @endif
                            </div>
                        @endif
                    @elseif(Auth::user()->agency === 'DILG')
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPOFdp = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                                $shouldHideDeleteForDilgFdp = $isDilgPOFdp || $hasFdpFile;
                            @endphp
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'fdp', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if(
                                    (Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province']) && $isFdpForPoValidation)
                                    || (!$isFdpForPoValidation && (!$isFdpPendingDilgRoValidation || (Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office')))
                                )
                                    @if(!$shouldHideDeleteForDilgFdp && (Auth::user()->province === 'Regional Office' || $fdpDocuments[$quarter]->fdp_status !== 'approved'))
                                        <button type="button" onclick="deleteDocument('fdp', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    @endif
                                    @if($fdpDocuments[$quarter]->fdp_status !== 'approved')
                                        <button type="button" onclick="openRemarksModal('fdp', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    @endif
                                    @if(
                                        Auth::user()->province === 'Regional Office'
                                        || (Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office' && $isFdpForPoValidation)
                                    )
                                        <button type="button" onclick="openRemarksModal('fdp', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($fdpDocuments[$quarter] && ($fdpDocuments[$quarter]->fdp_file_path || $fdpDocuments[$quarter]->user_remarks))
                        <button type="button" onclick="toggleAccordion('fdp-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                            <i class="fas fa-chevron-down" id="icon-fdp-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                        </button>
                        <div id="fdp-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                            <textarea id="textarea-fdp-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $fdpDocuments[$quarter]->user_remarks ?? '' }}</textarea>
                            <button type="button" onclick="saveRemarksAjax('fdp', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                        </div>
                        @endif
                    @endif

                    @if(Auth::user()->agency === 'LGU' && $fdpDocuments[$quarter])
                        <button type="button" onclick="toggleAccordion('fdp-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                            <i class="fas fa-chevron-down" id="icon-fdp-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                        </button>
                        <div id="fdp-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                            <textarea id="textarea-fdp-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $fdpDocuments[$quarter]->user_remarks ?? '' }}</textarea>
                            <button type="button" onclick="saveRemarksAjax('fdp', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                        </div>
                    @endif
                </div>
            </div>
            </div>

            <!-- LGU Posting Link Section -->
            @php
                $postingLinkValue = $fdpDocuments[$quarter]->posting_link ?? '';
                $safePostingLink = \App\Support\InputSanitizer::sanitizeHttpUrl($postingLinkValue);
                $hasPostingLink = $fdpDocuments[$quarter] && $postingLinkValue !== '';
                $hasSafePostingLink = !empty($safePostingLink);
                $isPostingReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->posting_status === 'returned';
                $postingBackgroundColor = $hasPostingLink ? '#fffbeb' : 'transparent';

                if ($isPostingReturned) {
                    $postingStatusColor = '#ef4444';
                    $postingStatusLabel = 'Returned';
                    $postingBackgroundColor = '#fee2e2';
                } elseif ($hasPostingLink && $fdpDocuments[$quarter]->posting_status === 'approved') {
                    $postingStatusColor = '#059669';
                    $postingStatusLabel = 'Approved';
                } else {
                    $postingStatusColor = $hasPostingLink ? '#10b981' : '#f59e0b';
                    $postingStatusLabel = $hasPostingLink ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                }
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $postingStatusColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="margin: 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(5,150,105,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-link" style="color: #059669; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">LGU Website / Social Media</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">Posting Link</span>
                        </span>
                    </span>
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $postingStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $postingStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($hasPostingLink)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($fdpDocuments[$quarter], 'posting_uploaded_at', 'posting_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $postingEncoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $postingEncoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = Auth::user()->agency === 'DILG' && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->posting_approved_at_dilg_po;
                                $hasRoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->posting_approved_at_dilg_ro;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($fdpDocuments[$quarter]->posting_approved_at_dilg_po) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at_dilg_po)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at_dilg_po->setTimezone(config('app.timezone'));
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }}
                                </span>
                            @endif
                            @if($hasRoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($fdpDocuments[$quarter]->posting_approved_at_dilg_ro) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }}
                                </span>
                            @endif
                        @endif
                        @if($isPostingReturned && $fdpDocuments[$quarter]->posting_approved_at)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                @php
                                    $returnedAt = is_string($fdpDocuments[$quarter]->posting_approved_at) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at->setTimezone(config('app.timezone'));
                                    $postingApproverUser = $fdpDocuments[$quarter]->posting_approved_by ? \App\Models\User::where('idno', $fdpDocuments[$quarter]->posting_approved_by)->first() : null;
                                    $postingApproverName = $postingApproverUser ? trim($postingApproverUser->fname . ' ' . $postingApproverUser->lname) : 'Unknown';
                                @endphp
                                Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $postingApproverName }}
                            </span>
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.save-posting-link', $report->project_code) }}" method="POST" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="text" name="posting_link" value="{{ $postingLinkValue }}" placeholder="https://example.com/post" style="flex: 1; min-width: 240px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" oninput="showSaveButtonForText(this, 'posting-save-btn-{{ $quarter }}')">
                        <button type="submit" id="posting-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </form>
                    <div style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($hasPostingLink)
                            <i class="fas fa-link" style="margin-right: 4px;"></i>Current link:
                            @if($hasSafePostingLink)
                                <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                                    {{ $postingLinkValue }}
                                </a>
                            @else
                                <span style="color: #374151; word-break: break-all;">{{ $postingLinkValue }}</span>
                            @endif
                        @endif
                    </div>

                    @if(Auth::user()->agency === 'LGU')
                        @if($hasPostingLink || $isPostingReturned)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($hasSafePostingLink)
                                    <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> Open Link
                                    </a>
                                @endif
                                @if(!$fdpDocuments[$quarter] || $fdpDocuments[$quarter]->posting_status !== 'approved')
                                    <button type="button" onclick="deleteDocument('posting-link', '{{ $quarter }}')" title="Delete link" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                @endif
                            </div>
                        @endif
                    @elseif(Auth::user()->agency === 'DILG')
                        @if($hasPostingLink)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($hasSafePostingLink)
                                    <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> Open Link
                                    </a>
                                @endif
                                @if(!$fdpDocuments[$quarter] || $fdpDocuments[$quarter]->posting_status !== 'approved')
                                    <button type="button" onclick="openRemarksModal('posting-link', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                @endif
                                @if(Auth::user()->province === 'Regional Office')
                                    <button type="button" onclick="openRemarksModal('posting-link', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            </div>

            </div>
        </div>
    @endforeach

    <style>
        button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        input[type="file"], textarea {
            transition: all 0.3s ease;
        }

        input[type="file"]:focus, textarea:focus {
            border-color: #002C76 !important;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
            outline: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1300;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: #ffffff;
            margin: 6% auto;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22);
            position: relative;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #002C76;
            font-size: 18px;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .close-modal:hover {
            color: #000;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .logs-table th,
        .logs-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        .log-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .log-pill.upload { background-color: #d1fae5; color: #065f46; }
        .log-pill.delete { background-color: #fee2e2; color: #991b1b; }
        .log-pill.approve { background-color: #dbeafe; color: #1d4ed8; }
        .log-pill.return { background-color: #fde68a; color: #92400e; }
        .log-pill.remarks { background-color: #e0e7ff; color: #4338ca; }
        .log-pill.update { background-color: #e5e7eb; color: #374151; }

        /* Section card hover accent */
        div[style*="border-left: 4px solid"] {
            transition: box-shadow 0.18s ease;
        }
        div[style*="border-left: 4px solid"]:hover {
            box-shadow: 0 4px 14px rgba(0, 44, 118, 0.1);
        }

        /* Prevent full-width accordion buttons from jumping on hover */
        button[style*="width: 100%"]:hover {
            transform: none !important;
        }

        /* Notes toggle button polish */
        button[onclick*="toggleAccordion"] {
            transition: background-color 0.15s, color 0.15s;
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 20px;
            }

            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Logs Modal -->
    <div id="logsModal" class="modal">
        <div class="modal-content" style="max-width: 900px; padding: 0; overflow: hidden;">
            <div class="modal-header" style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-history" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Activity Logs</h2>
                </div>
                <button class="close-modal" onclick="closeLogsModal()" style="color: rgba(255,255,255,0.8); font-size: 22px; line-height: 1;">&times;</button>
            </div>
            <div style="padding: 20px; max-height: 60vh; overflow-y: auto;">
                @if(empty($activityLogs))
                    <div style="padding: 16px; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                        No activity logs found for this project.
                    </div>
                @else
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Document</th>
                                <th>Quarter</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLogs as $log)
                                @php
                                    $actionLabel = strtoupper($log['action'] ?? 'update');
                                    $actionClass = $log['action'] ?? 'update';
                                    $docType = $log['document_type'] ?? 'n/a';
                                    $docLabelMap = [
                                        'mov' => 'MOV',
                                        'fdp' => 'FDP',
                                        'posting-link' => 'LGU Posting Link',
                                        'written-notice' => 'Written Notice',
                                        'written-notice-dbm' => 'Written Notice (DBM)',
                                        'written-notice-dilg' => 'Written Notice (DILG)',
                                        'written-notice-speaker' => 'Written Notice (Speaker)',
                                        'written-notice-president' => 'Written Notice (President)',
                                        'written-notice-house' => 'Written Notice (House)',
                                        'written-notice-senate' => 'Written Notice (Senate)',
                                    ];
                                    $docLabel = $docLabelMap[$docType] ?? $docType;
                                    $userDisplay = $log['user_name'] ?? 'Unknown';
                                    if (!empty($log['user_agency'])) {
                                        $userDisplay .= ' (' . $log['user_agency'] . ')';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $log['timestamp']->format('M d, Y h:i A') }}</td>
                                    <td>{{ $userDisplay }}</td>
                                    <td><span class="log-pill {{ $actionClass }}">{{ $actionLabel }}</span></td>
                                    <td>{{ $docLabel }}</td>
                                    <td>{{ $log['quarter'] ?? '—' }}</td>
                                    <td>{{ $log['remarks'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div id="remarksModal" class="modal">
        <div class="modal-content" style="padding: 0; overflow: hidden;">
            <div class="modal-header" style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-comment-alt" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 id="modalTitle" style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Add Remarks</h2>
                </div>
                <button class="close-modal" onclick="closeRemarksModal()" style="color: rgba(255,255,255,0.8); font-size: 22px; line-height: 1;">&times;</button>
            </div>
            <form id="remarksForm" method="POST" style="display: none; padding: 20px;">
                @csrf
                <textarea id="remarksText" name="remarks" placeholder="Enter remarks..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div class="modal-buttons">
                    <button type="button" onclick="closeRemarksModal()" style="background-color: #6b7280; color: white;"><i class="fas fa-times" style="margin-right: 8px;"></i>Cancel</button>
                    <button type="submit" id="submitBtn" style="background-color: #002C76; color: white;">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentUploadType = '';
        let currentQuarter = '';
        let currentAction = '';
        const projectCode = '{{ $report->project_code }}';
        const baseUrl = '{{ url("/fund-utilization") }}';

        function openLogsModal() {
            const modal = document.getElementById('logsModal');
            modal.style.display = 'block';
        }

        function closeLogsModal() {
            const modal = document.getElementById('logsModal');
            modal.style.display = 'none';
        }

        function openRemarksModal(uploadType, quarter, action) {
            currentUploadType = uploadType;
            currentQuarter = quarter;
            currentAction = action;

            const modal = document.getElementById('remarksModal');
            const form = document.getElementById('remarksForm');
            const titleElement = document.getElementById('modalTitle');
            const remarksField = document.getElementById('remarksText');
            const submitBtn = document.getElementById('submitBtn');

            let title = '';
            let actionLabel = '';

            if (action === 'approve') {
                title = 'Approve ' + uploadType.replace('-', ' ');
                actionLabel = 'Approve (Optional remarks)';
                submitBtn.style.backgroundColor = '#10b981';
                remarksField.placeholder = 'Enter optional remarks for approval...';
                remarksField.required = false;
            } else if (action === 'return') {
                title = 'Return ' + uploadType.replace('-', ' ');
                actionLabel = 'Return (Required remarks)';
                submitBtn.style.backgroundColor = '#dc2626';
                remarksField.placeholder = 'Enter reason for return...';
                remarksField.required = true;
            } else if (action === 'remark') {
                title = 'Add Remarks for ' + uploadType.replace('-', ' ');
                actionLabel = 'Add Remarks';
                submitBtn.style.backgroundColor = '#6366f1';
                remarksField.placeholder = 'Enter remarks...';
                remarksField.required = true;
            }

            titleElement.textContent = title;
            remarksField.value = '';

            // Construct the form action URL directly
            form.action = `${baseUrl}/${projectCode}/approve/${uploadType}/${quarter}`;
            form.style.display = 'block';

            // Create hidden input for action
            let actionInput = document.getElementById('actionInput');
            if (!actionInput) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.id = 'actionInput';
                actionInput.name = 'action';
                form.appendChild(actionInput);
            }
            actionInput.value = action;

            modal.style.display = 'block';
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('remarksModal');
            const logsModal = document.getElementById('logsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            if (event.target === logsModal) {
                logsModal.style.display = 'none';
            }
        }

        document.getElementById('remarksForm').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });

        // Toggle Accordion Function
        function toggleAccordion(elementId) {
            const element = document.getElementById(elementId);
            const icon = document.getElementById('icon-' + elementId);
            if (!element) return;

            const isOpen = !(element.style.display === 'none' || element.style.display === '');

            // Collapse other quarter panels when opening one
            if (!isOpen && elementId.startsWith('quarter-')) {
                document.querySelectorAll('[id^="quarter-"]').forEach(function (otherPanel) {
                    if (otherPanel === element) return;
                    if (otherPanel.style.display === 'block') {
                        otherPanel.style.display = 'none';
                        const otherId = otherPanel.getAttribute('id');
                        const otherIcon = document.getElementById('icon-' + otherId);
                        if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                    }
                });
            }

            if (!isOpen) {
                element.style.display = 'block';
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                element.style.display = 'none';
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        // Save Quarter Function
        function saveQuarter(quarter) {
            // Collect all unsaved changes from the quarter
            const movForm = document.querySelector(`form[action*="upload-mov"]`);
            const writtenNoticeForm = document.querySelector(`form[action*="upload-written-notice"]`);
            const fdpForm = document.querySelector(`form[action*="upload-fdp"]`);
            const remarksTextareas = document.querySelectorAll(`textarea[id$="-remarks-${quarter}"]`);

            let hasChanges = false;
            const formsToSubmit = [];

            // Check for file inputs with selected files
            if (movForm && movForm.querySelector('input[name="mov_file"]').files.length > 0) {
                formsToSubmit.push(movForm);
                hasChanges = true;
            }
            if (writtenNoticeForm && writtenNoticeForm.querySelector('input[name="written_notice_file"]').files.length > 0) {
                formsToSubmit.push(writtenNoticeForm);
                hasChanges = true;
            }
            if (fdpForm && fdpForm.querySelector('input[name="fdp_file"]').files.length > 0) {
                formsToSubmit.push(fdpForm);
                hasChanges = true;
            }

            // Check for remarks in accordions
            remarksTextareas.forEach(textarea => {
                if (textarea.value.trim()) {
                    hasChanges = true;
                }
            });

            if (!hasChanges) {
                alert('No changes to save for this quarter.');
                return;
            }

            // Submit all forms with changes
            formsToSubmit.forEach(form => {
                form.submit();
            });

            // Show success message
            alert(`Quarter ${quarter} saved successfully!`);
        }

        // Save Remarks via AJAX
        function saveRemarksAjax(uploadType, quarter) {
            const textareaId = `textarea-${uploadType}-remarks-${quarter}`;
            const textarea = document.getElementById(textareaId);
            
            if (!textarea) {
                alert('Error: Could not find remarks field.');
                return;
            }

            const remarks = textarea.value;
            const url = `${baseUrl}/${projectCode}/save-remarks/${uploadType}/${quarter}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    remarks: remarks
                })
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Failed to save remarks');
            })
            .then(data => {
                // Show success message
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check" style="margin-right: 4px;"></i> Saved!';
                button.style.backgroundColor = '#059669';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving remarks. Please try again.');
            });
        }

        // Delete Document
        function deleteDocument(docType, quarter) {
            if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                return;
            }

            const url = `${baseUrl}/${projectCode}/delete-document/${docType}/${quarter}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);
                    if (data.message.includes('deleted successfully')) {
                        location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting document. Please try again.');
            });
        }

        function deleteProjectConfirm(projectCode) {
            const message = `Are you sure you want to delete this project and ALL its associated data and logs?\n\nProject Code: ${projectCode}\n\nThis action CANNOT be undone.`;
            if (!confirm(message)) {
                return;
            }

            const url = `${baseUrl}/${projectCode}`;

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);
                    if (data.message.includes('deleted successfully')) {
                        // Redirect to fund-utilization index after successful deletion
                        window.location.href = '/fund-utilization';
                    }
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting project. Please try again.');
            });
        }

        function initializeUploadStyling() {
            const fileInputs = document.querySelectorAll('.ops-detail-page input[type="file"]');

            fileInputs.forEach(input => {
                if (input.dataset.uploadStyled === '1') {
                    return;
                }

                input.dataset.uploadStyled = '1';
                input.classList.add('ops-upload-input');

                if (input.disabled) {
                    input.classList.add('is-disabled');
                }

                ['dragenter', 'dragover'].forEach(evt => {
                    input.addEventListener(evt, function(e) {
                        e.preventDefault();
                        if (!input.disabled) {
                            input.classList.add('drag-active');
                        }
                    });
                });

                ['dragleave', 'drop', 'dragend'].forEach(evt => {
                    input.addEventListener(evt, function() {
                        input.classList.remove('drag-active');
                    });
                });

                input.addEventListener('change', function() {
                    input.classList.remove('drag-active');
                    if (input.files && input.files.length > 0) {
                        input.classList.add('has-selection');
                    } else {
                        input.classList.remove('has-selection');
                    }
                });

                const submitBtn = input.parentElement ? input.parentElement.querySelector('button[type="submit"]') : null;
                if (submitBtn) {
                    submitBtn.classList.add('ops-upload-submit');
                }

                const onchangeValue = input.getAttribute('onchange') || '';
                const filenameMatch = onchangeValue.match(/'([^']*filename-[^']*)'/);
                if (filenameMatch && filenameMatch[1]) {
                    const filenameDiv = document.getElementById(filenameMatch[1]);
                    if (filenameDiv) {
                        filenameDiv.classList.add('ops-upload-filename');
                        if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                            filenameDiv.classList.add('has-file');
                        }
                    }
                }
            });

            document.querySelectorAll('.ops-detail-page button[id$="-save-btn"]').forEach(btn => {
                btn.classList.add('ops-upload-submit');
            });
        }

        document.addEventListener('DOMContentLoaded', initializeUploadStyling);
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initializeUploadStyling();
        }

        // Show save button and filename when file is selected
        function renderSelectedFileName(filenameDiv, fileName) {
            const icon = document.createElement('i');
            icon.className = 'fas fa-file';
            icon.style.marginRight = '4px';
            filenameDiv.replaceChildren(icon, document.createTextNode(`Selected: ${fileName}`));
        }

        function showSaveButton(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameDiv = document.getElementById(filenameId);

            if (!saveBtn || !filenameDiv) {
                return;
            }

            saveBtn.classList.add('ops-upload-submit');
            filenameDiv.classList.add('ops-upload-filename');
            
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                // Show the save button
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                // Display filename
                renderSelectedFileName(filenameDiv, fileName);
                filenameDiv.style.display = 'block';
                filenameDiv.classList.add('has-file');
                
                // Add click handler to submit the form
                saveBtn.onclick = function(e) {
                    e.preventDefault();
                    const formId = saveBtn.getAttribute('form');
                    if (formId) {
                        const form = document.getElementById(formId);
                        if (form) {
                            form.submit();
                        } else {
                            console.error(`Form with ID ${formId} not found`);
                        }
                    } else {
                        // Fallback: find parent form
                        const form = saveBtn.closest('form');
                        if (form) {
                            form.submit();
                        } else {
                            console.error('No form found for button');
                        }
                    }
                };
            } else {
                // Hide the save button if no new file selected
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                // Keep filename div visible if there's already uploaded content (from Blade)
                // Only hide if it's empty
                if (!filenameDiv.textContent.trim()) {
                    filenameDiv.style.display = 'none';
                    filenameDiv.classList.remove('has-file');
                }
            }
        }

        function showSaveButtonForText(input, buttonId) {
            const saveBtn = document.getElementById(buttonId);
            if (!saveBtn) {
                return;
            }

            const hasValue = input && input.value && input.value.trim().length > 0;
            saveBtn.style.opacity = hasValue ? '1' : '0';
            saveBtn.style.pointerEvents = hasValue ? 'auto' : 'none';
        }
    </script>

    <!-- Floating Activity Logs Button -->
    <button onclick="openLogsModal()" id="activityLogsFab" style="position: fixed; bottom: 24px; right: 24px; display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background-color: #002C76; color: white; border: none; border-radius: 999px; cursor: pointer; font-size: 13px; font-weight: 600; box-shadow: 0 8px 20px rgba(0, 44, 118, 0.35); z-index: 1200; transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease; white-space: nowrap;" onmouseover="this.style.backgroundColor='#003d9e'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 24px rgba(0, 44, 118, 0.4)';" onmouseout="this.style.backgroundColor='#002C76'; this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0, 44, 118, 0.35)';">
        <i class="fas fa-clipboard-list" style="font-size: 14px;"></i>
        <span>Activity Logs</span>
    </button>

    <style>
        @media (max-width: 640px) {
            #activityLogsFab span { display: none; }
            #activityLogsFab { padding: 14px; border-radius: 50%; }
        }
    </style>


    </div>
@endsection
