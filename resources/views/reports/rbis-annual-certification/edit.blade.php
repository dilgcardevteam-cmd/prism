@extends('layouts.dashboard')

@section('title', 'RBIS Annual Certification - Update')
@section('page-title', 'Update RBIS Annual Certification')

@section('content')
    <div class="ops-detail-page">
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <div>
            <h1>Update - {{ $officeName }}</h1>
            <p>Upload or update RBIS Annual Certification documents.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('rbis-annual-certification.index', ['year' => $reportingYear]) }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <strong style="display: block; margin-bottom: 8px;">Please fix the following:</strong>
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
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">City/Municipality or PLGU</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $officeName }}</p>
            </div>
            <div>
                <form method="GET" style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                    <label for="rbis-reporting-year" style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">Reporting Year</label>
                    <select id="rbis-reporting-year" name="year" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #fff;">
                        @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                            <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                        @endfor
                    </select>
                </form>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Configured Deadline</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">
                    {{ is_array($configuredDeadline ?? null) ? ($configuredDeadline['display'] ?? '—') : 'No deadline set by admin for this year' }}
                </p>
            </div>
            @if (is_array($configuredDeadline ?? null) && !empty($configuredDeadline['deadline_iso']))
                <div>
                    <label id="rbis-deadline-countdown-label" style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; transition: color 0.2s ease;">Countdown</label>
                    <p id="rbis-deadline-countdown" data-deadline-iso="{{ $configuredDeadline['deadline_iso'] }}" style="color: #1e3a8a; font-size: 15px; font-weight: 700; margin: 0; transition: color 0.2s ease;">
                        Syncing...
                    </p>
                    <div data-pagasa-time data-rbis-deadline-source style="display: none;" aria-hidden="true"></div>
                </div>
            @elseif (is_array($configuredDeadline ?? null))
                <div>
                    <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Countdown</label>
                    <p style="color: #92400e; font-size: 15px; font-weight: 500; margin: 0;">
                        Unavailable
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 20px; font-weight: 600;">
            RBIS Annual Certification Upload (CY {{ $reportingYear }})
        </h2>

        <div style="display: grid; gap: 12px;">
            @php
                $doc = $documents->first();
                $inputId = 'rbis-input-annual';
                $buttonId = 'rbis-btn-annual';
                $filenameId = 'rbis-file-annual';
                $deadlineDisplay = is_array($configuredDeadline ?? null) ? (string) ($configuredDeadline['display'] ?? '') : '';
                $isProvincialDilgViewer = Auth::user()->agency === 'DILG' && Auth::user()->province !== 'Regional Office';
                $isRegionalOfficeUserForUpload = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                $hasFile = $doc && $doc->file_path;
                $isReturned = $doc && $doc->status === 'returned';
                $disableUploadInput = ($hasFile && !$isReturned) || $isRegionalOfficeUserForUpload;
                $isApprovedRo = $doc && $doc->approved_at_dilg_ro;
                $isPendingRo = $doc && $doc->approved_at_dilg_po && !$doc->approved_at_dilg_ro;
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

                $uploadedInfo = $resolveUploaderMeta($doc);
                $uploadedTime = $uploadedInfo['time'];
                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredDeadline);
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

            <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                <button
                    type="button"
                    class="rbis-accordion-toggle"
                    data-target="rbis-annual-slot"
                    aria-expanded="true"
                    style="width: 100%; padding: 14px 16px; background-color: #002C76; color: white; border: none; text-align: left; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;"
                >
                    <span>Annual Certification Document</span>
                    <span style="display: inline-flex; align-items: center; gap: 10px;">
                        <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 20px; font-size: 10px; font-weight: 600;">
                            {{ $statusLabel }}
                        </span>
                        <span style="display: inline-block; padding: 4px 10px; background-color: {{ $deadlineDisplay !== '' ? '#0f766e' : '#6b7280' }}; color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 20px; font-size: 10px; font-weight: 600;">
                            {{ $deadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}
                        </span>
                        <i class="fas fa-chevron-down" style="transition: transform 0.3s; transform: rotate(180deg);"></i>
                    </span>
                </button>
                <div id="rbis-annual-slot" style="display: block; padding: 16px; background-color: #ffffff;">
                    <form method="POST" action="{{ route('rbis-annual-certification.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 16px; border-radius: 8px; background-color: #f9fafb;">
                        @csrf
                        <input type="hidden" name="year" value="{{ $reportingYear }}">
                        <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0 0 8px 0;">
                            Annual Certification Upload (CY {{ $reportingYear }})
                        </label>
                        <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
                            Allowed format: PDF (max 15MB).
                        </div>
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

                            @if (empty($timelineEvents))
                                <div style="display: block; font-size: 11px; color: #6b7280;">
                                    No upload activity for CY {{ $reportingYear }}.
                                </div>
                            @endif
                            @foreach ($timelineEvents as $timelineEvent)
                                <div style="display: block; font-size: {{ $timelineEvent['font_size'] }}; font-weight: {{ $timelineEvent['font_weight'] }}; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                    {{ $timelineEvent['message'] }}
                                </div>
                            @endforeach
                        </div>
                            @if ($doc && $doc->file_path)
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                    <a href="{{ route('rbis-annual-certification.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; color: #002C76; font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-file"></i>&nbsp;View current file
                                    </a>
                                    @if ($submissionTimeliness)
                                        <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; padding: 4px 10px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                            {{ $submissionTimeliness['label'] }}
                                        </span>
                                    @endif
                                    @if (Auth::user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('rbis-annual-certification.delete-document', ['office' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Delete this uploaded document? This action cannot be undone.');" style="display: inline;">
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
                        <input
                            id="{{ $inputId }}"
                            type="file"
                            name="document"
                            required
                            accept=".pdf,application/pdf"
                            @disabled($disableUploadInput)
                            style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
                            onchange="showRbisSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                        >
                        @if ($disableUploadInput)
                            <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                @if ($isRegionalOfficeUserForUpload)
                                    Regional Office cannot upload files. Choose file is disabled.
                                @else
                                    File already uploaded for this year. Choose file is disabled.
                                @endif
                            </div>
                        @endif
                        @if ($showApprovalButtons)
                            <div style="display: flex; gap: 6px; margin-top: 8px; margin-bottom: 8px; justify-content: flex-start; align-items: center;">
                                <button type="button" onclick="openRbisApprovalModal({{ $doc->id }}, 'approve')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                    <i class="fas fa-check"></i>
                                    <span>Approve</span>
                                </button>
                                @if (!$hideReturnButton)
                                    <button type="button" onclick="openRbisApprovalModal({{ $doc->id }}, 'return')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                        <i class="fas fa-undo"></i>
                                        <span>Return</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                        <div id="{{ $filenameId }}" style="display: none; margin-bottom: 8px; font-size: 12px; color: #6b7280;"></div>
                        <button
                            type="submit"
                            id="{{ $buttonId }}"
                            style="width: 25%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; opacity: 0; pointer-events: none; transition: all 0.3s ease; display: block; margin-left: 0; margin-right: auto;"
                        >
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="rbisActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="rbisActivityLogTitle" aria-hidden="true">
        <div style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 id="rbisActivityLogTitle" style="color: #002C76; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                <button type="button" id="rbisActivityLogClose" aria-label="Close activity logs" style="border: none; background: #e2e8f0; color: #0f172a; width: 28px; height: 28px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="max-height: 60vh; overflow-y: auto;">
                @if (empty($activityLogs))
                    <div style="padding: 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; color: #6b7280; font-size: 13px;">
                        No activity recorded yet.
                    </div>
                @else
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 10px; text-align: left; color: #374151; font-weight: 600; font-size: 12px;">Date/Time</th>
                                    <th style="padding: 10px; text-align: left; color: #374151; font-weight: 600; font-size: 12px;">Action</th>
                                    <th style="padding: 10px; text-align: left; color: #374151; font-weight: 600; font-size: 12px;">Document</th>
                                    <th style="padding: 10px; text-align: left; color: #374151; font-weight: 600; font-size: 12px;">User</th>
                                    <th style="padding: 10px; text-align: left; color: #374151; font-weight: 600; font-size: 12px;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activityLogs as $log)
                                    @php
                                        $logUser = $log['user_id'] && isset($usersById[$log['user_id']])
                                            ? $usersById[$log['user_id']]
                                            : null;
                                    @endphp
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 10px; color: #111827; font-size: 12px;">
                                            {{ $log['timestamp'] ? $log['timestamp']->format('M d, Y H:i') : '—' }}
                                        </td>
                                        <td style="padding: 10px; color: #111827; font-size: 12px;">
                                            {{ $log['action'] }}
                                        </td>
                                        <td style="padding: 10px; color: #111827; font-size: 12px;">
                                            {{ $log['document'] }}
                                        </td>
                                        <td style="padding: 10px; color: #111827; font-size: 12px;">
                                            {{ $logUser ? trim($logUser->fname . ' ' . $logUser->lname) : 'Unknown' }}
                                        </td>
                                        <td style="padding: 10px; color: #6b7280; font-size: 12px;">
                                            {{ $log['remarks'] ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="rbisActivityLogBackdrop" aria-hidden="true"></div>

    <button id="rbisActivityLogFab" type="button" aria-controls="rbisActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true" style="font-size: 14px;"></i>
        <span>Activity Logs</span>
    </button>

    <script>
        document.querySelectorAll('.rbis-accordion-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                if (!panel) return;

                const isOpen = panel.style.display === 'block';

                if (!isOpen) {
                    document.querySelectorAll('.rbis-accordion-toggle').forEach(function (otherBtn) {
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

        function showRbisSaveButton(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameDiv = document.getElementById(filenameId);
            if (!saveBtn || !filenameDiv) return;

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                filenameDiv.textContent = `Selected: ${fileName}`;
                filenameDiv.style.display = 'block';
            } else {
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                if (!filenameDiv.textContent.trim()) {
                    filenameDiv.style.display = 'none';
                }
            }
        }
    </script>

    <style>
        #rbisActivityLogBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            z-index: 1190;
        }

        #rbisActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #rbisActivityLogModal {
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

        #rbisActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-rbis-logs {
            overflow: hidden;
        }

        #rbisActivityLogFab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background-color: #002C76;
            color: white;
            border: none;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
            z-index: 1200;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        #rbisActivityLogFab:hover {
            background-color: #0b3b84;
            transform: translateY(-2px);
            box-shadow: 0 14px 22px rgba(15, 23, 42, 0.22);
        }

        #rbisActivityLogFab:active {
            transform: translateY(0);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.2);
        }

        #rbisActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        #rbisActivityLogFab span {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            #rbisActivityLogModal {
                width: 94vw;
            }

            #rbisActivityLogFab {
                right: 16px;
                bottom: 16px;
                padding: 10px 12px;
            }

            #rbisActivityLogFab span {
                display: none;
            }
        }
    </style>

    <script>
        const rbisActivityLogModal = document.getElementById('rbisActivityLogModal');
        const rbisActivityLogBackdrop = document.getElementById('rbisActivityLogBackdrop');
        const rbisActivityLogFab = document.getElementById('rbisActivityLogFab');
        const rbisActivityLogClose = document.getElementById('rbisActivityLogClose');

        function setRbisActivityLogVisibility(isVisible) {
            if (!rbisActivityLogModal || !rbisActivityLogBackdrop || !rbisActivityLogFab) {
                return;
            }

            rbisActivityLogModal.classList.toggle('is-visible', isVisible);
            rbisActivityLogBackdrop.classList.toggle('is-visible', isVisible);
            document.body.classList.toggle('modal-open-rbis-logs', isVisible);
            rbisActivityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            rbisActivityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            rbisActivityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            rbisActivityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

            const labelSpan = rbisActivityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Hide Activity Logs' : 'Activity Logs';
            }

            if (isVisible && rbisActivityLogClose) {
                rbisActivityLogClose.focus();
            }
        }

        if (rbisActivityLogFab && rbisActivityLogModal && rbisActivityLogBackdrop) {
            rbisActivityLogFab.addEventListener('click', () => {
                const isOpen = rbisActivityLogModal.classList.contains('is-visible');
                setRbisActivityLogVisibility(!isOpen);
            });

            rbisActivityLogBackdrop.addEventListener('click', () => {
                setRbisActivityLogVisibility(false);
            });

            if (rbisActivityLogClose) {
                rbisActivityLogClose.addEventListener('click', () => {
                    setRbisActivityLogVisibility(false);
                });
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && rbisActivityLogModal && rbisActivityLogModal.classList.contains('is-visible')) {
                setRbisActivityLogVisibility(false);
            }
        });
    </script>

    <div id="rbisApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
            <h3 id="rbisApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
            <form id="rbisApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="rbisApprovalAction">
                <textarea id="rbisApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button type="button" onclick="closeRbisApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                    <button type="submit" id="rbisApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRbisApprovalModal(docId, action) {
            const modal = document.getElementById('rbisApprovalModal');
            const form = document.getElementById('rbisApprovalForm');
            const title = document.getElementById('rbisApprovalTitle');
            const actionInput = document.getElementById('rbisApprovalAction');
            const remarks = document.getElementById('rbisApprovalRemarks');
            const submitBtn = document.getElementById('rbisApprovalSubmit');

            form.action = '{{ route('rbis-annual-certification.approve', [$officeName, '__DOC_ID__']) }}'.replace('__DOC_ID__', docId);
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

        function closeRbisApprovalModal() {
            document.getElementById('rbisApprovalModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const modal = document.getElementById('rbisApprovalModal');
            if (event.target === modal) {
                closeRbisApprovalModal();
            }
        });

        (function () {
            const countdownEl = document.getElementById('rbis-deadline-countdown');
            const countdownLabelEl = document.getElementById('rbis-deadline-countdown-label');
            const pagasaTimeEl = document.querySelector('[data-rbis-deadline-source]');

            if (!countdownEl || !pagasaTimeEl) {
                return;
            }

            const deadlineIso = countdownEl.dataset.deadlineIso || '';
            const deadlineMs = Date.parse(deadlineIso);

            const getCountdownTheme = (remainingMs) => {
                if (remainingMs <= 0) {
                    return {
                        labelColor: '#b91c1c',
                        countdownColor: '#b91c1c',
                    };
                }

                if (remainingMs <= 24 * 60 * 60 * 1000) {
                    return {
                        labelColor: '#b91c1c',
                        countdownColor: '#b91c1c',
                    };
                }

                if (remainingMs <= 3 * 24 * 60 * 60 * 1000) {
                    return {
                        labelColor: '#c2410c',
                        countdownColor: '#c2410c',
                    };
                }

                if (remainingMs <= 7 * 24 * 60 * 60 * 1000) {
                    return {
                        labelColor: '#b45309',
                        countdownColor: '#b45309',
                    };
                }

                return {
                    labelColor: '#6b7280',
                    countdownColor: '#1e3a8a',
                };
            };

            const applyCountdownTheme = (theme) => {
                if (countdownLabelEl) {
                    countdownLabelEl.style.color = theme.labelColor;
                }

                countdownEl.style.color = theme.countdownColor;
            };

            const unavailableTheme = {
                labelColor: '#92400e',
                countdownColor: '#92400e',
            };

            const setCountdownState = (label, color) => {
                countdownEl.textContent = label;
                countdownEl.style.color = color;
            };

            const pad = (value) => String(value).padStart(2, '0');

            const formatRemaining = (remainingMs) => {
                const totalSeconds = Math.max(0, Math.floor(remainingMs / 1000));
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                return `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
            };

            if (Number.isNaN(deadlineMs)) {
                applyCountdownTheme(unavailableTheme);
                setCountdownState('Unavailable', '#92400e');
                return;
            }

            const renderCountdown = () => {
                const serverIso = pagasaTimeEl.dataset.pagasaIso || '';
                const serverMs = Date.parse(serverIso);

                if (!serverIso || Number.isNaN(serverMs)) {
                    applyCountdownTheme(getCountdownTheme(8 * 24 * 60 * 60 * 1000));
                    setCountdownState('Syncing...', '#1e3a8a');
                    return;
                }

                const remainingMs = deadlineMs - serverMs;
                applyCountdownTheme(getCountdownTheme(remainingMs));

                if (remainingMs <= 0) {
                    setCountdownState('Deadline reached', '#b91c1c');
                    return;
                }

                setCountdownState(formatRemaining(remainingMs), '#1e3a8a');
            };

            renderCountdown();
            const intervalId = window.setInterval(renderCountdown, 1000);
            window.addEventListener('beforeunload', function () {
                window.clearInterval(intervalId);
            }, { once: true });
        })();
    </script>
    </div>
@endsection
