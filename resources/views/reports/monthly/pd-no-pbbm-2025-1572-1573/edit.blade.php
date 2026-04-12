@extends('layouts.dashboard')

@section('title', 'Report on PD No. PBBM-2025-1572-1573 - Update')
@section('page-title', 'Update Report on PD No. PBBM-2025-1572-1573')

@section('content')
    <div class="ops-detail-page">
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <div>
            <h1>Update - {{ $officeName }}</h1>
            <p>Upload or update monthly submissions for this report.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573', ['year' => $reportingYear]) }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <strong style="display: block; margin-bottom: 8px;">Please review the following:</strong>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $province ?? '—' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">City/Municipality</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $officeName }}</p>
            </div>
            <div>
                <form method="GET" style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                    <label for="road-maintenance-year" style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">Reporting Year</label>
                    <select id="road-maintenance-year" name="year" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #fff;">
                        @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                            <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 20px; font-weight: 600;">
            Monthly Report on PD No. PBBM-2025-1572-1573 Uploads (CY {{ $reportingYear }})
        </h2>

        <div style="display: grid; gap: 12px;">
            @php
                $isProvincialDilgViewer = Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office';
                $resolveUploaderMeta = function ($record) use ($isProvincialDilgViewer, $usersById) {
                    if (!$record) {
                        return ['time' => null, 'name' => 'Unknown'];
                    }

                    $uploadedAt = $record->uploaded_at ?? $record->created_at ?? $record->updated_at ?? null;
                    $uploadedTime = null;
                    if ($uploadedAt) {
                        $uploadedTime = is_string($uploadedAt)
                            ? \Carbon\Carbon::parse($uploadedAt)->setTimezone(config('app.timezone'))
                            : $uploadedAt->copy()->setTimezone(config('app.timezone'));
                    }

                    $encoderId = $record->uploaded_by ?? null;
                    if (!$encoderId && $isProvincialDilgViewer) {
                        $encoderId = $record->approved_by_dilg_po ?? null;
                    }

                    $encoderUser = $encoderId && isset($usersById[$encoderId]) ? $usersById[$encoderId] : null;
                    $encoderName = $encoderUser ? trim($encoderUser->fname . ' ' . $encoderUser->lname) : 'Unknown';

                    return ['time' => $uploadedTime, 'name' => $encoderName !== '' ? $encoderName : 'Unknown'];
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
            @foreach ($months as $monthCode => $label)
                @php
                    $docKey = 'pd_no_pbbm_2025_1572_1573|' . $reportingYear . '|' . $monthCode;
                    $doc = $documentsByKey[$docKey] ?? null;
                    $inputId = 'road-maintenance-input-' . $monthCode;
                    $buttonId = 'road-maintenance-btn-' . $monthCode;
                    $filenameId = 'road-maintenance-file-' . $monthCode;
                    $isRegionalOfficeUserForUpload = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                    $hasFile = $doc && $doc->file_path;
                    $isReturned = $doc && $doc->status === 'returned';
                    $configuredMonthDeadline = $configuredMonthlyDeadlines[$monthCode] ?? null;
                    $monthDeadlineDisplay = is_array($configuredMonthDeadline) ? (string) ($configuredMonthDeadline['display'] ?? '') : '';
                    $disableUploadInput = ($hasFile && !$isReturned) || $isRegionalOfficeUserForUpload;
                    $isApprovedRo = $doc && $doc->approved_at_dilg_ro;
                    $isPendingRo = $doc && $doc->approved_at_dilg_po && !$doc->approved_at_dilg_ro;
                    $isExpandedByDefault = $loop->first;
                    $statusLabel = 'Pending Upload';
                    $statusColor = '#f59e0b';
                    if ($hasFile) {
                        $statusLabel = 'For DILG Provincial Office Validation';
                        $statusColor = '#3b82f6';
                    }
                    if ($isPendingRo) {
                        $statusLabel = 'For DILG Regional Office Validation';
                        $statusColor = '#3b82f6';
                    }
                    if ($isApprovedRo) {
                        $statusLabel = 'Approved';
                        $statusColor = '#059669';
                    }
                    if ($isReturned) {
                        $statusLabel = 'Returned';
                        $statusColor = '#dc2626';
                    }
                    $uploadedInfo = $resolveUploaderMeta($doc);
                    $uploadedTime = $uploadedInfo['time'];
                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredMonthDeadline);
                    $uploaderName = $uploadedInfo['name'];
                    $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                    $isDilgMountainUploader = $uploaderUser
                        && strtoupper(trim((string) ($uploaderUser->agency ?? ''))) === 'DILG'
                        && strtolower(trim((string) ($uploaderUser->province ?? ''))) === 'mountain province';
                    $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                    $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                    $returnedAt = null;
                    $returnedByName = 'Unknown';
                    $returnedByLevel = null;
                    $returnedRemarks = null;
                    if ($isReturned && $doc && $doc->approved_at) {
                        $returnedAt = is_string($doc->approved_at)
                            ? \Carbon\Carbon::parse($doc->approved_at)->setTimezone(config('app.timezone'))
                            : $doc->approved_at->copy()->setTimezone(config('app.timezone'));
                        $returnedById = $doc->approved_by_dilg_ro ?? $doc->approved_by_dilg_po;
                        $returnedByUser = $returnedById && isset($usersById[$returnedById]) ? $usersById[$returnedById] : null;
                        if ($returnedByUser) {
                            $returnedByName = trim($returnedByUser->fname . ' ' . $returnedByUser->lname) ?: 'Unknown';
                        }

                        if (!empty($doc->approved_by_dilg_ro)) {
                            $returnedByLevel = 'DILG Regional Office';
                        } elseif (!empty($doc->approved_by_dilg_po)) {
                            $returnedByLevel = 'DILG Provincial Office';
                        }

                        $returnedRemarks = trim((string) ($doc->approval_remarks ?? ''));
                        if ($returnedRemarks === '') {
                            $returnedRemarks = null;
                        }
                    }
                @endphp
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <button
                        type="button"
                        class="road-maintenance-accordion-toggle"
                        data-target="road-maintenance-{{ $monthCode }}"
                        aria-expanded="{{ $isExpandedByDefault ? 'true' : 'false' }}"
                        style="width: 100%; padding: 14px 16px; background-color: #002C76; color: white; border: none; text-align: left; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;"
                    >
                        <span style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                            <span>{{ $label }} - Report on PD No. PBBM-2025-1572-1573</span>
                            <span style="font-size: 11px; opacity: 0.95;">Deadline: {{ $monthDeadlineDisplay !== '' ? $monthDeadlineDisplay : 'No superadmin deadline set' }}</span>
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 10px;">
                            <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 20px; font-size: 10px; font-weight: 600;">
                                {{ $statusLabel }}
                            </span>
                            <span style="display: inline-block; padding: 4px 10px; background-color: {{ $monthDeadlineDisplay !== '' ? '#0f766e' : '#6b7280' }}; color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 20px; font-size: 10px; font-weight: 600;">
                                {{ $monthDeadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}
                            </span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s; transform: {{ $isExpandedByDefault ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                        </span>
                    </button>
                    <div id="road-maintenance-{{ $monthCode }}" style="display: {{ $isExpandedByDefault ? 'block' : 'none' }}; padding: 16px; background-color: #ffffff;">
                        <form method="POST" action="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 16px; border-radius: 8px; background-color: #f9fafb;">
                            @csrf
                            <input type="hidden" name="year" value="{{ $reportingYear }}">
                            <input type="hidden" name="month" value="{{ $monthCode }}">
                            <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0 0 8px 0;">
                                {{ $label }} Upload
                            </label>
                            <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
                                @php
                                    $timelineEvents = [];
                                    $poValidatedAt = null;
                                    $poApproverName = 'Unknown';

                                    if ($doc && $doc->approved_at_dilg_po) {
                                        $poValidatedAt = is_string($doc->approved_at_dilg_po)
                                            ? \Carbon\Carbon::parse($doc->approved_at_dilg_po)->setTimezone(config('app.timezone'))
                                            : $doc->approved_at_dilg_po->copy()->setTimezone(config('app.timezone'));
                                        $poApproverName = $poApprover ? trim($poApprover->fname . ' ' . $poApprover->lname) : 'Unknown';
                                    }

                                    $isUploadedAndPoValidatedBySameUser = $doc
                                        && $uploadedTime
                                        && $poValidatedAt
                                        && $isDilgMountainUploader
                                        && !empty($doc->uploaded_by)
                                        && !empty($doc->approved_by_dilg_po)
                                        && (string) $doc->uploaded_by === (string) $doc->approved_by_dilg_po
                                        && $uploadedTime->getTimestamp() === $poValidatedAt->getTimestamp();

                                    if ($uploadedTime) {
                                        $timelineEvents[] = [
                                            'timestamp' => $uploadedTime,
                                            'priority' => 10,
                                            'message' => $isUploadedAndPoValidatedBySameUser
                                                ? 'Uploaded and Validated at: ' . $uploadedTime->format('M d, Y h:i A') . ' by ' . $uploaderName . ' (DILG Provincial Office)'
                                                : 'Uploaded at: ' . $uploadedTime->format('M d, Y h:i A') . ' by ' . $uploaderName,
                                            'color' => '#6b7280',
                                            'font_size' => '11px',
                                            'font_weight' => 'normal',
                                        ];
                                    }

                                    if ($poValidatedAt && !$isUploadedAndPoValidatedBySameUser) {
                                        $timelineEvents[] = [
                                            'timestamp' => $poValidatedAt,
                                            'priority' => 20,
                                            'message' => 'DILG Provincial Validated at: ' . $poValidatedAt->format('M d, Y h:i A') . ' by ' . $poApproverName,
                                            'color' => '#059669',
                                            'font_size' => '10px',
                                            'font_weight' => 'normal',
                                        ];
                                    }

                                    if ($doc && $doc->approved_at_dilg_ro) {
                                        $roValidatedAt = is_string($doc->approved_at_dilg_ro)
                                            ? \Carbon\Carbon::parse($doc->approved_at_dilg_ro)->setTimezone(config('app.timezone'))
                                            : $doc->approved_at_dilg_ro->copy()->setTimezone(config('app.timezone'));
                                        $roApproverName = $roApprover ? trim($roApprover->fname . ' ' . $roApprover->lname) : 'Unknown';

                                        $timelineEvents[] = [
                                            'timestamp' => $roValidatedAt,
                                            'priority' => 30,
                                            'message' => 'DILG Regional Validated at: ' . $roValidatedAt->format('M d, Y h:i A') . ' by ' . $roApproverName,
                                            'color' => '#0891b2',
                                            'font_size' => '10px',
                                            'font_weight' => 'normal',
                                        ];
                                    }

                                    if ($isReturned) {
                                        $returnSuffix = '';
                                        if ($returnedByLevel) {
                                            $returnSuffix .= ' (' . $returnedByLevel . ')';
                                        }
                                        if ($returnedRemarks) {
                                            $returnSuffix .= ' - Remarks: ' . $returnedRemarks;
                                        }

                                        $timelineEvents[] = [
                                            'timestamp' => $returnedAt,
                                            'priority' => 40,
                                            'message' => 'Returned at: ' . ($returnedAt ? $returnedAt->format('M d, Y h:i A') : '-') . ' by ' . $returnedByName . $returnSuffix,
                                            'color' => '#dc2626',
                                            'font_size' => '10px',
                                            'font_weight' => 'normal',
                                        ];
                                    }

                                    usort($timelineEvents, function ($a, $b) {
                                        $aTime = $a['timestamp'] instanceof \DateTimeInterface ? $a['timestamp']->getTimestamp() : PHP_INT_MAX;
                                        $bTime = $b['timestamp'] instanceof \DateTimeInterface ? $b['timestamp']->getTimestamp() : PHP_INT_MAX;

                                        if ($aTime === $bTime) {
                                            return ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0);
                                        }

                                        return $aTime <=> $bTime;
                                    });
                                @endphp

                                @foreach ($timelineEvents as $timelineEvent)
                                    <div style="display: block; font-size: {{ $timelineEvent['font_size'] }}; font-weight: {{ $timelineEvent['font_weight'] }}; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                        {{ $timelineEvent['message'] }}
                                    </div>
                                @endforeach
                            </div>
                            @if ($doc && $doc->file_path)
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                    <a href="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; color: #002C76; font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-file"></i>&nbsp;View current file
                                    </a>
                                    @if ($submissionTimeliness)
                                        <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; padding: 4px 10px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                            {{ $submissionTimeliness['label'] }}
                                        </span>
                                    @endif
                                    @if (Auth::user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573.delete-document', ['office' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Delete this uploaded document? This action cannot be undone.');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                                <i class="fas fa-trash-alt"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                            @php
                                $isRegionalOfficeUser = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                                $isProvincialDilgUser = Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office';
                                $isForRegionalValidation = $doc && $doc->approved_at_dilg_po && !$doc->approved_at_dilg_ro;
                                $isApproved = $doc && $doc->status === 'approved';
                                $hideReturnButton = $isProvincialDilgUser && $isReturned;
                                $showApprovalButtons = $doc
                                    && Auth::user()->agency === 'DILG'
                                    && !($isProvincialDilgUser && $isForRegionalValidation)
                                    && !($isRegionalOfficeUser && $isReturned)
                                    && !($isRegionalOfficeUser && $isApproved)
                                    && !($isProvincialDilgUser && $isApproved);
                            @endphp
                            <input
                                id="{{ $inputId }}"
                                type="file"
                                name="document"
                                accept=".pdf,application/pdf"
                                required
                                @disabled($disableUploadInput)
                                class="ops-upload-input"
                                style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
                                onchange="showRoadMaintenanceSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                            >
                                <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                    Remarks: Only PDF files are allowed (maximum 15MB).
                                </div>
                                @if ($disableUploadInput)
                                    <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                        @if ($isRegionalOfficeUserForUpload)
                                            Regional Office cannot upload files. Choose file is disabled.
                                        @else
                                            File already uploaded for this month. Choose file is disabled.
                                        @endif
                                    </div>
                            @endif
                            @if ($showApprovalButtons)
                                <div style="display: flex; gap: 6px; margin-top: 8px; margin-bottom: 8px; justify-content: flex-start; align-items: center;">
                                    <button type="button" onclick="openRoadMaintenanceApprovalModal({{ $doc->id }}, 'approve')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                        <i class="fas fa-check"></i>
                                        <span>Approve</span>
                                    </button>
                                    @if (!$hideReturnButton)
                                        <button type="button" onclick="openRoadMaintenanceApprovalModal({{ $doc->id }}, 'return')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                            <i class="fas fa-undo"></i>
                                            <span>Return</span>
                                        </button>
                                    @endif
                                </div>
                            @endif
                            <div id="{{ $filenameId }}" class="ops-upload-filename" style="display: none; margin-bottom: 8px; font-size: 12px; color: #6b7280;"></div>
                                <button
                                    type="submit"
                                    id="{{ $buttonId }}"
                                    class="ops-upload-submit"
                                    style="width: 25%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; opacity: 0; pointer-events: none; transition: all 0.3s ease; display: block; margin-left: 0; margin-right: auto;"
                            >
                                Upload
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="roadMaintenanceActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="roadMaintenanceActivityLogTitle" aria-hidden="true">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px 16px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); border-radius: 12px 12px 0 0; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clipboard-list" style="color: white; font-size: 14px;"></i>
                    </div>
                    <h3 id="roadMaintenanceActivityLogTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                </div>
                <button type="button" id="roadMaintenanceActivityLogClose" aria-label="Close activity logs" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="padding: 20px 24px; overflow-y: auto; max-height: 65vh;">
                @if (empty($activityLogs))
                    <div style="padding: 40px 20px; text-align: center; color: #9ca3af;">
                        <i class="fas fa-clipboard" style="font-size: 36px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                        <div style="font-size: 14px; font-weight: 600; color: #6b7280;">No activity recorded yet.</div>
                    </div>
                @else
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                                    <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Date/Time</th>
                                    <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                                    <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Document</th>
                                    <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">User</th>
                                    <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activityLogs as $index => $log)
                                    @php
                                        $logUser = $log['user_id'] && isset($usersById[$log['user_id']])
                                            ? $usersById[$log['user_id']]
                                            : null;
                                        $action = strtolower($log['action'] ?? '');
                                        if (str_contains($action, 'upload') || str_contains($action, 'save')) {
                                            $pillBg = '#d1fae5'; $pillColor = '#065f46';
                                        } elseif (str_contains($action, 'delete') || str_contains($action, 'remove')) {
                                            $pillBg = '#fee2e2'; $pillColor = '#991b1b';
                                        } elseif (str_contains($action, 'approve')) {
                                            $pillBg = '#dbeafe'; $pillColor = '#1d4ed8';
                                        } elseif (str_contains($action, 'return') || str_contains($action, 'reject')) {
                                            $pillBg = '#fef3c7'; $pillColor = '#92400e';
                                        } else {
                                            $pillBg = '#e5e7eb'; $pillColor = '#374151';
                                        }
                                        $rowBg = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
                                    @endphp
                                    <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 10px 12px; color: #374151; font-size: 12px; white-space: nowrap;">
                                            {{ $log['timestamp'] ? $log['timestamp']->format('M d, Y H:i') : '—' }}
                                        </td>
                                        <td style="padding: 10px 12px; font-size: 12px;">
                                            <span style="display: inline-block; padding: 2px 8px; background-color: {{ $pillBg }}; color: {{ $pillColor }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ $log['action'] }}</span>
                                        </td>
                                        <td style="padding: 10px 12px; color: #374151; font-size: 12px;">{{ $log['document'] }}</td>
                                        <td style="padding: 10px 12px; color: #374151; font-size: 12px; white-space: nowrap;">
                                            {{ $logUser ? trim($logUser->fname . ' ' . $logUser->lname) : 'Unknown' }}
                                        </td>
                                        <td style="padding: 10px 12px; color: #6b7280; font-size: 12px;">{{ $log['remarks'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="roadMaintenanceActivityLogBackdrop" aria-hidden="true"></div>

    <button id="roadMaintenanceActivityLogFab" type="button" aria-controls="roadMaintenanceActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
        <span>Activity Logs</span>
    </button>

    <script>
        function initializeRoadMaintenanceUploadStyling() {
            const fileInputs = document.querySelectorAll('.ops-detail-page input[type="file"]');

            fileInputs.forEach(function (input) {
                input.classList.add('ops-upload-input');

                if (input.disabled) {
                    input.classList.add('is-disabled');
                }

                ['dragenter', 'dragover'].forEach(function (evt) {
                    input.addEventListener(evt, function (e) {
                        e.preventDefault();
                        if (!input.disabled) {
                            input.classList.add('drag-active');
                        }
                    });
                });

                ['dragleave', 'drop', 'dragend'].forEach(function (evt) {
                    input.addEventListener(evt, function () {
                        input.classList.remove('drag-active');
                    });
                });
            });

            document.querySelectorAll('.ops-detail-page button[id^="road-maintenance-btn-"]').forEach(function (btn) {
                btn.classList.add('ops-upload-submit');
            });

            document.querySelectorAll('.ops-detail-page div[id^="road-maintenance-file-"]').forEach(function (filenameDiv) {
                filenameDiv.classList.add('ops-upload-filename');
                if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                    filenameDiv.classList.add('has-file');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initializeRoadMaintenanceUploadStyling);

        document.querySelectorAll('.road-maintenance-accordion-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                if (!panel) return;

                const isOpen = panel.style.display === 'block';

                if (!isOpen) {
                    document.querySelectorAll('.road-maintenance-accordion-toggle').forEach(function (otherBtn) {
                        if (otherBtn === button) return;
                        const otherId = otherBtn.getAttribute('data-target');
                        const otherPanel = document.getElementById(otherId);
                        if (otherPanel && otherPanel.style.display === 'block') {
                            otherPanel.style.display = 'none';
                            otherBtn.setAttribute('aria-expanded', 'false');
                            const otherIcon = otherBtn.querySelector('.fa-chevron-down');
                            if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                        }
                    });
                }

                panel.style.display = isOpen ? 'none' : 'block';
                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

                const icon = button.querySelector('.fa-chevron-down');
                if (icon) {
                    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        });

        function showRoadMaintenanceSaveButton(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameDiv = document.getElementById(filenameId);
            if (!saveBtn || !filenameDiv) return;

            saveBtn.classList.add('ops-upload-submit');
            filenameDiv.classList.add('ops-upload-filename');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const selectedFile = fileInput.files[0];
                const fileName = selectedFile.name;
                const hasPdfExtension = /\.pdf$/i.test(fileName);
                const maxSizeBytes = 15 * 1024 * 1024;

                if (!hasPdfExtension) {
                    fileInput.value = '';
                    saveBtn.style.opacity = '0';
                    saveBtn.style.pointerEvents = 'none';
                    filenameDiv.textContent = 'Only PDF files are allowed.';
                    filenameDiv.style.color = '#dc2626';
                    filenameDiv.style.display = 'block';
                    filenameDiv.classList.remove('has-file');
                    return;
                }

                if (selectedFile.size > maxSizeBytes) {
                    fileInput.value = '';
                    saveBtn.style.opacity = '0';
                    saveBtn.style.pointerEvents = 'none';
                    filenameDiv.textContent = 'File size must not exceed 15MB.';
                    filenameDiv.style.color = '#dc2626';
                    filenameDiv.style.display = 'block';
                    filenameDiv.classList.remove('has-file');
                    return;
                }

                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                const icon = document.createElement('i');
                icon.className = 'fas fa-file';
                icon.style.marginRight = '4px';
                filenameDiv.replaceChildren(icon, document.createTextNode(`Selected: ${fileName}`));
                filenameDiv.style.color = '#6b7280';
                filenameDiv.style.display = 'block';
                filenameDiv.classList.add('has-file');
            } else {
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                if (!filenameDiv.textContent.trim()) {
                    filenameDiv.style.display = 'none';
                    filenameDiv.classList.remove('has-file');
                }
            }
        }
    </script>

    <style>
        .ops-detail-page .ops-upload-input {
            width: 100%;
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

        #roadMaintenanceActivityLogBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1190;
        }

        #roadMaintenanceActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #roadMaintenanceActivityLogModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(920px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1200;
        }

        #roadMaintenanceActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-road-maintenance-logs {
            overflow: hidden;
        }

        #roadMaintenanceActivityLogFab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background-color: #002C76;
            color: white;
            border: none;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 8px 20px rgba(0, 44, 118, 0.35);
            z-index: 1180;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        #roadMaintenanceActivityLogFab:hover {
            background-color: #003d9e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.4);
        }

        #roadMaintenanceActivityLogFab:active {
            transform: translateY(0);
        }

        #roadMaintenanceActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        @media (max-width: 640px) {
            #roadMaintenanceActivityLogFab span { display: none; }
            #roadMaintenanceActivityLogFab { padding: 14px; border-radius: 50%; }
        }

        @media (max-width: 768px) {
            #roadMaintenanceActivityLogModal {
                width: 94vw;
            }
        }
    </style>

    <script>
        const roadMaintenanceActivityLogModal = document.getElementById('roadMaintenanceActivityLogModal');
        const roadMaintenanceActivityLogBackdrop = document.getElementById('roadMaintenanceActivityLogBackdrop');
        const roadMaintenanceActivityLogFab = document.getElementById('roadMaintenanceActivityLogFab');
        const roadMaintenanceActivityLogClose = document.getElementById('roadMaintenanceActivityLogClose');

        function setRoadMaintenanceActivityLogVisibility(isVisible) {
            if (!roadMaintenanceActivityLogModal || !roadMaintenanceActivityLogBackdrop || !roadMaintenanceActivityLogFab) {
                return;
            }

            roadMaintenanceActivityLogModal.classList.toggle('is-visible', isVisible);
            roadMaintenanceActivityLogBackdrop.classList.toggle('is-visible', isVisible);
            document.body.classList.toggle('modal-open-road-maintenance-logs', isVisible);
            roadMaintenanceActivityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            roadMaintenanceActivityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            roadMaintenanceActivityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            roadMaintenanceActivityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

            const labelSpan = roadMaintenanceActivityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Hide Activity Logs' : 'Activity Logs';
            }

            if (isVisible && roadMaintenanceActivityLogClose) {
                roadMaintenanceActivityLogClose.focus();
            }
        }

        if (roadMaintenanceActivityLogFab && roadMaintenanceActivityLogModal && roadMaintenanceActivityLogBackdrop) {
            roadMaintenanceActivityLogFab.addEventListener('click', () => {
                const isOpen = roadMaintenanceActivityLogModal.classList.contains('is-visible');
                setRoadMaintenanceActivityLogVisibility(!isOpen);
            });

            roadMaintenanceActivityLogBackdrop.addEventListener('click', () => {
                setRoadMaintenanceActivityLogVisibility(false);
            });

            if (roadMaintenanceActivityLogClose) {
                roadMaintenanceActivityLogClose.addEventListener('click', () => {
                    setRoadMaintenanceActivityLogVisibility(false);
                });
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && roadMaintenanceActivityLogModal && roadMaintenanceActivityLogModal.classList.contains('is-visible')) {
                setRoadMaintenanceActivityLogVisibility(false);
            }
        });
    </script>

    <div id="roadMaintenanceApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
            <h3 id="roadMaintenanceApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
            <form id="roadMaintenanceApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="roadMaintenanceApprovalAction">
                <textarea id="roadMaintenanceApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button type="button" onclick="closeRoadMaintenanceApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                    <button type="submit" id="roadMaintenanceApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRoadMaintenanceApprovalModal(docId, action) {
            const modal = document.getElementById('roadMaintenanceApprovalModal');
            const form = document.getElementById('roadMaintenanceApprovalForm');
            const title = document.getElementById('roadMaintenanceApprovalTitle');
            const actionInput = document.getElementById('roadMaintenanceApprovalAction');
            const remarks = document.getElementById('roadMaintenanceApprovalRemarks');
            const submitBtn = document.getElementById('roadMaintenanceApprovalSubmit');

            form.action = '{{ url('/reports/monthly/pd-no-pbbm-2025-1572-1573') }}/' + encodeURIComponent('{{ $officeName }}') + '/approve/' + docId;
            actionInput.value = action;
            remarks.value = '';

            if (action === 'return') {
                title.textContent = 'Return Document';
                submitBtn.style.backgroundColor = '#dc2626';
                remarks.required = true;
            } else {
                title.textContent = 'Approve Document';
                submitBtn.style.backgroundColor = '#10b981';
                remarks.required = false;
            }

            modal.style.display = 'block';
        }

        function closeRoadMaintenanceApprovalModal() {
            document.getElementById('roadMaintenanceApprovalModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const modal = document.getElementById('roadMaintenanceApprovalModal');
            if (event.target === modal) {
                closeRoadMaintenanceApprovalModal();
            }
        });
    </script>
    </div>
@endsection
