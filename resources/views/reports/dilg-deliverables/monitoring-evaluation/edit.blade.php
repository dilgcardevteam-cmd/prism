@extends('layouts.dashboard')

@section('title', $pageTitle . ' - Update')
@section('page-title', 'Update ' . $pageTitle)

@section('content')
    <div class="ops-detail-page">
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
        <div>
            <h1>Update - {{ $officeName }}</h1>
            <p>Upload or update monthly submissions for this report.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route($indexRoute, ['year' => $reportingYear]) }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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
                    <label for="me-monthly-year-filter" style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">Reporting Year</label>
                    <select id="me-monthly-year-filter" name="year" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #fff;">
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
            {{ $pageTitle }} Uploads (CY {{ $reportingYear }})
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
                    $docKey = $reportDocType . '|' . $reportingYear . '|' . $monthCode;
                    $doc = $documentsByKey[$docKey] ?? null;
                    $inputId = 'me-monthly-input-' . $monthCode;
                    $buttonId = 'me-monthly-btn-' . $monthCode;
                    $filenameId = 'me-monthly-file-' . $monthCode;
                    $isRegionalOfficeUserForUpload = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                    $hasFile = $doc && $doc->file_path;
                    $isReturned = $doc && $doc->status === 'returned';
                    $configuredMonthDeadline = $configuredMonthlyDeadlines[$monthCode] ?? null;
                    $monthDeadlineDisplay = is_array($configuredMonthDeadline) ? (string) ($configuredMonthDeadline['display'] ?? '') : '';
                    $disableUploadInput = ($hasFile && !$isReturned) || $isRegionalOfficeUserForUpload;
                    $isApprovedRo = $doc && $doc->status === 'approved';
                    $isPendingRo = $doc && $doc->status === 'pending_ro';
                    $isPendingDeletion = $doc && $doc->status === 'pending_deletion';
                    $isExpandedByDefault = $loop->first;
                    $statusLabel = 'Pending Upload';
                    $statusColor = '#f59e0b';
                    if ($hasFile) {
                        $statusLabel = 'For DILG Regional Office Validation';
                        $statusColor = '#3b82f6';
                    }
                    if ($isApprovedRo) {
                        $statusLabel = 'Approved';
                        $statusColor = '#059669';
                    }
                    if ($isPendingDeletion) {
                        $statusLabel = 'For Deletion Approval';
                        $statusColor = '#ea580c'; // dark orange
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
                    $isProvincialDilgUploader = $uploaderUser && method_exists($uploaderUser, 'isProvincialDilgAssignment')
                        ? $uploaderUser->isProvincialDilgAssignment()
                        : false;
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
                        class="me-monthly-accordion-toggle"
                        data-target="me-monthly-{{ $monthCode }}"
                        aria-expanded="{{ $isExpandedByDefault ? 'true' : 'false' }}"
                        style="width: 100%; padding: 14px 16px; background-color: #002C76; color: white; border: none; text-align: left; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;"
                    >
                        <span style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                            <span>{{ $label }} - {{ $pageTitle }}</span>
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
                    <div id="me-monthly-{{ $monthCode }}" style="display: {{ $isExpandedByDefault ? 'block' : 'none' }}; padding: 16px; background-color: #ffffff;">
                        <form method="POST" action="{{ route($uploadRoute, $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 16px; border-radius: 8px; background-color: #f9fafb;">
                            @csrf
                            <input type="hidden" name="year" value="{{ $reportingYear }}">
                            <input type="hidden" name="month" value="{{ $monthCode }}">
                            <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0 0 8px 0;">
                                {{ $label }} Upload
                            </label>
                            <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
                                @php
                                    $timelineEvents = [];

                                    if ($uploadedTime) {
                                        $timelineEvents[] = [
                                            'timestamp' => $uploadedTime,
                                            'priority' => 10,
                                            'message' => 'Uploaded by: ' . $uploaderName . ' at ' . $uploadedTime->format('M d, Y h:i A'),
                                        ];
                                    }

                                    if ($doc && $doc->approved_at_dilg_ro) {
                                        $roValidatedAt = is_string($doc->approved_at_dilg_ro)
                                            ? \Carbon\Carbon::parse($doc->approved_at_dilg_ro)->setTimezone(config('app.timezone'))
                                            : $doc->approved_at_dilg_ro->copy()->setTimezone(config('app.timezone'));
                                        $roApproverName = $roApprover ? trim($roApprover->fname . ' ' . $roApprover->lname) : 'DILG Regional Office';
                                        $timelineEvents[] = [
                                            'timestamp' => $roValidatedAt,
                                            'priority' => 30,
                                            'message' => 'Approved (DILG RO) at: ' . $roValidatedAt->format('M d, Y h:i A') . ' by ' . $roApproverName,
                                        ];
                                    }

                                    if ($doc && $doc->status === 'pending_deletion') {
                                        $deletionTime = is_string($doc->updated_at)
                                            ? \Carbon\Carbon::parse($doc->updated_at)->setTimezone(config('app.timezone'))
                                            : $doc->updated_at->copy()->setTimezone(config('app.timezone'));
                                        $timelineEvents[] = [
                                            'timestamp' => $deletionTime,
                                            'priority' => 40,
                                            'message' => 'Deletion Requested at: ' . $deletionTime->format('M d, Y h:i A') . '. Reason: "' . ($doc->approval_remarks ?: 'No reason specified') . '"',
                                        ];
                                    }

                                    usort($timelineEvents, function ($leftEvent, $rightEvent) {
                                        $leftPriority = (int) ($leftEvent['priority'] ?? 0);
                                        $rightPriority = (int) ($rightEvent['priority'] ?? 0);
                                        return $leftPriority <=> $rightPriority;
                                    });
                                @endphp
                                @foreach($timelineEvents as $event)
                                    <div style="margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-info-circle" style="color: #6b7280;"></i>
                                        <span>{{ $event['message'] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                                <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px;">Document File (PDF)</label>
                                    <input
                                        type="file"
                                        id="{{ $inputId }}"
                                        name="document"
                                        accept="application/pdf"
                                        @disabled($disableUploadInput)
                                        class="ops-upload-input dashboard-file-input"
                                        onchange="showMonitoringEvaluationSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                                        style="width: 100%;"
                                    >
                                </div>
                            </div>

                            @if ($submissionTimeliness)
                                <div style="margin-top: 10px; margin-bottom: 10px;">
                                    <span
                                        title="{{ $submissionTimeliness['title'] }}"
                                        style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }};"
                                    >
                                        <i class="fas {{ $submissionTimeliness['label'] === 'Late' ? 'fa-circle-exclamation' : 'fa-circle-check' }}"></i>
                                        Submission status: {{ $submissionTimeliness['label'] }}
                                    </span>
                                </div>
                            @endif

                            @if ($returnedAt)
                                <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-left: 4px solid #b91c1c; border-radius: 6px; padding: 12px; margin: 12px 0; font-size: 12px; color: #991b1b;">
                                    <div style="font-weight: 700; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.02em;">Returned Details</div>
                                    <div style="margin-bottom: 2px;">Returned on: <strong>{{ $returnedAt->format('M d, Y h:i A') }}</strong> by <strong>{{ $returnedByName }}</strong> ({{ $returnedByLevel }})</div>
                                    @if ($returnedRemarks)
                                        <div style="margin-top: 6px; font-style: italic; background: white; padding: 8px; border-radius: 4px; border: 1px solid #fee2e2;">
                                            &ldquo;{{ $returnedRemarks }}&rdquo;
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 10px;">
                                <div id="{{ $filenameId }}" style="display: none; font-size: 11px; color: #6b7280; font-weight: 600;"></div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @if ($hasFile)
                                        <button
                                            type="button"
                                            onclick="openMonitoringEvaluationDocumentViewerModal('{{ route($viewDocumentRoute, ['office' => $officeName, 'docId' => $doc->id]) }}', '{{ $label }} {{ $pageTitle }}')"
                                            style="padding: 8px 14px; background-color: #1e293b; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                        >
                                            <i class="fas fa-eye"></i> View PDF
                                        </button>

                                        <button
                                            type="button"
                                            onclick="openMonitoringEvaluationHistoryModal('{{ $monthCode }}', '{{ $label }}')"
                                            style="padding: 8px 14px; background-color: #4b5563; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                        >
                                            <i class="fas fa-history"></i> History
                                        </button>

                                        @if (Auth::user()->isSuperAdmin())
                                            <form method="POST" action="{{ route($deleteRoute, ['office' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    style="padding: 8px 14px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                                >
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        @endif

                                        @if ($doc->status === 'approved' && ((string) Auth::id() === (string) $doc->uploaded_by || Auth::user()->isSuperAdmin()))
                                            <button
                                                type="button"
                                                onclick="openMonitoringEvaluationDeletionRequestModal({{ $doc->id }})"
                                                style="padding: 8px 14px; background-color: #ea580c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                            >
                                                <i class="fas fa-trash-alt"></i> Request Deletion
                                            </button>
                                        @endif

                                        @if ($doc->status === 'pending_deletion' && (Auth::user()->isRegionalUser() || Auth::user()->isSuperAdmin() || Auth::user()->isRegionalOfficeAssignment()))
                                            <button
                                                type="button"
                                                onclick="openMonitoringEvaluationDeletionDecisionModal({{ $doc->id }}, 'approve')"
                                                style="padding: 8px 14px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                            >
                                                <i class="fas fa-check"></i> Approve Deletion
                                            </button>
                                            <button
                                                type="button"
                                                onclick="openMonitoringEvaluationDeletionDecisionModal({{ $doc->id }}, 'reject')"
                                                style="padding: 8px 14px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                            >
                                                <i class="fas fa-times"></i> Reject Deletion
                                            </button>
                                        @endif

                                        @if ($doc->status === 'pending_deletion' && !(Auth::user()->isRegionalUser() || Auth::user()->isSuperAdmin() || Auth::user()->isRegionalOfficeAssignment()))
                                            <span style="font-size: 12px; color: #ea580c; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-info-circle"></i> Awaiting deletion validation from Regional Office
                                            </span>
                                        @endif

                                        @php
                                            $isRegionalOfficeUser = Auth::user()->isRegionalUser() || Auth::user()->isSuperAdmin() || Auth::user()->isRegionalOfficeAssignment();
                                            $isProvincialOfficeUser = !$isRegionalOfficeUser && Auth::user()->agency === 'DILG';

                                            $canPoApprove = false;
                                            $canRoApprove = $isRegionalOfficeUser && $doc->status === 'pending_ro';
                                            $canPoReturn = false;
                                            $canRoReturn = $isRegionalOfficeUser && $doc->status === 'pending_ro';
                                        @endphp

                                        @if ($canPoApprove || $canRoApprove)
                                            <button
                                                type="button"
                                                onclick="openMonitoringEvaluationApprovalModal({{ $doc->id }}, 'approve')"
                                                style="padding: 8px 14px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                            >
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        @endif

                                        @if ($canPoReturn || $canRoReturn)
                                            <button
                                                type="button"
                                                onclick="openMonitoringEvaluationApprovalModal({{ $doc->id }}, 'return')"
                                                style="padding: 8px 14px; background-color: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                            >
                                                <i class="fas fa-undo"></i> Return
                                            </button>
                                        @endif
                                    @endif

                                    <button
                                        type="submit"
                                        id="{{ $buttonId }}"
                                        style="opacity: 0; pointer-events: none; padding: 8px 14px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                    >
                                        Upload
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="monitoringEvaluationActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="monitoringEvaluationActivityLogTitle" aria-hidden="true">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px 16px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); border-radius: 12px 12px 0 0; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clipboard-list" style="color: white; font-size: 14px;"></i>
                    </div>
                    <h3 id="monitoringEvaluationActivityLogTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                </div>
                <button type="button" id="monitoringEvaluationActivityLogClose" aria-label="Close activity logs" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
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
                    <div style="overflow-x: auto;" data-freeze-columns data-freeze-columns-max="2" data-freeze-columns-key="me-monthly-edit-activity-log">
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
                                        $actionRaw = strtolower($log['action'] ?? '');
                                        if (str_contains($actionRaw, 'validate_po') || str_contains($actionRaw, 'validated (dilg po)')) {
                                            continue;
                                        }

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

    <div id="monitoringEvaluationActivityLogBackdrop" aria-hidden="true"></div>

    <button id="monitoringEvaluationActivityLogFab" type="button" aria-controls="monitoringEvaluationActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
        <span>Activity Logs</span>
    </button>

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

        #monitoringEvaluationActivityLogBackdrop {
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

        #monitoringEvaluationActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #monitoringEvaluationActivityLogModal {
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

        #monitoringEvaluationActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-monitoring-evaluation-logs {
            overflow: hidden;
        }

        #monitoringEvaluationActivityLogFab {
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

        #monitoringEvaluationActivityLogFab:hover {
            background-color: #003d9e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.4);
        }

        #monitoringEvaluationActivityLogFab:active {
            transform: translateY(0);
        }

        #monitoringEvaluationActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        @media (max-width: 640px) {
            #monitoringEvaluationActivityLogFab span { display: none; }
            #monitoringEvaluationActivityLogFab { padding: 14px; border-radius: 50%; }
        }

        @media (max-width: 768px) {
            #monitoringEvaluationActivityLogModal {
                width: 94vw;
            }
        }
    </style>

    <script>
        const monitoringEvaluationActivityLogModal = document.getElementById('monitoringEvaluationActivityLogModal');
        const monitoringEvaluationActivityLogBackdrop = document.getElementById('monitoringEvaluationActivityLogBackdrop');
        const monitoringEvaluationActivityLogFab = document.getElementById('monitoringEvaluationActivityLogFab');
        const monitoringEvaluationActivityLogClose = document.getElementById('monitoringEvaluationActivityLogClose');

        function setMonitoringEvaluationActivityLogVisibility(isVisible) {
            if (!monitoringEvaluationActivityLogModal || !monitoringEvaluationActivityLogBackdrop || !monitoringEvaluationActivityLogFab) {
                return;
            }

            monitoringEvaluationActivityLogModal.classList.toggle('is-visible', isVisible);
            monitoringEvaluationActivityLogBackdrop.classList.toggle('is-visible', isVisible);
            document.body.classList.toggle('modal-open-monitoring-evaluation-logs', isVisible);
            monitoringEvaluationActivityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            monitoringEvaluationActivityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            monitoringEvaluationActivityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            monitoringEvaluationActivityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

            const labelSpan = monitoringEvaluationActivityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Hide Activity Logs' : 'Activity Logs';
            }

            if (isVisible && monitoringEvaluationActivityLogClose) {
                monitoringEvaluationActivityLogClose.focus();
            }
        }

        if (monitoringEvaluationActivityLogFab && monitoringEvaluationActivityLogModal && monitoringEvaluationActivityLogBackdrop) {
            monitoringEvaluationActivityLogFab.addEventListener('click', () => {
                const isOpen = monitoringEvaluationActivityLogModal.classList.contains('is-visible');
                setMonitoringEvaluationActivityLogVisibility(!isOpen);
            });

            monitoringEvaluationActivityLogBackdrop.addEventListener('click', () => {
                setMonitoringEvaluationActivityLogVisibility(false);
            });

            if (monitoringEvaluationActivityLogClose) {
                monitoringEvaluationActivityLogClose.addEventListener('click', () => {
                    setMonitoringEvaluationActivityLogVisibility(false);
                });
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && monitoringEvaluationActivityLogModal && monitoringEvaluationActivityLogModal.classList.contains('is-visible')) {
                setMonitoringEvaluationActivityLogVisibility(false);
            }
        });
    </script>

    <div id="monitoringEvaluationApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
            <h3 id="monitoringEvaluationApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
            <form id="monitoringEvaluationApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="monitoringEvaluationApprovalAction">
                <textarea id="monitoringEvaluationApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button type="button" onclick="closeMonitoringEvaluationApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                    <button type="submit" id="monitoringEvaluationApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function initializeMonitoringEvaluationUploadStyling() {
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

            document.querySelectorAll('.ops-detail-page button[id^="me-monthly-btn-"]').forEach(function (btn) {
                btn.classList.add('ops-upload-submit');
            });

            document.querySelectorAll('.ops-detail-page div[id^="me-monthly-file-"]').forEach(function (filenameDiv) {
                filenameDiv.classList.add('ops-upload-filename');
                if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                    filenameDiv.classList.add('has-file');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initializeMonitoringEvaluationUploadStyling);

        document.querySelectorAll('.me-monthly-accordion-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                if (!panel) return;

                const isOpen = panel.style.display === 'block';

                if (!isOpen) {
                    document.querySelectorAll('.me-monthly-accordion-toggle').forEach(function (otherBtn) {
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

        function showMonitoringEvaluationSaveButton(fileInput, buttonId, filenameId) {
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

        function openMonitoringEvaluationApprovalModal(docId, action) {
            const modal = document.getElementById('monitoringEvaluationApprovalModal');
            const form = document.getElementById('monitoringEvaluationApprovalForm');
            const title = document.getElementById('monitoringEvaluationApprovalTitle');
            const actionInput = document.getElementById('monitoringEvaluationApprovalAction');
            const remarks = document.getElementById('monitoringEvaluationApprovalRemarks');
            const submitBtn = document.getElementById('monitoringEvaluationApprovalSubmit');

            form.action = '{{ $baseRouteUrl }}/' + encodeURIComponent('{{ $officeName }}') + '/approve/' + docId;
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

        function closeMonitoringEvaluationApprovalModal() {
            document.getElementById('monitoringEvaluationApprovalModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const approvalModal = document.getElementById('monitoringEvaluationApprovalModal');
            if (event.target === approvalModal) {
                closeMonitoringEvaluationApprovalModal();
            }
            const historyModal = document.getElementById('meHistoryModal');
            if (event.target === historyModal) {
                closeMonitoringEvaluationHistoryModal();
            }
            const delRequestModal = document.getElementById('meDeletionRequestModal');
            if (event.target === delRequestModal) {
                closeMonitoringEvaluationDeletionRequestModal();
            }
            const delDecisionModal = document.getElementById('meDeletionDecisionModal');
            if (event.target === delDecisionModal) {
                closeMonitoringEvaluationDeletionDecisionModal();
            }
            const docViewerModal = document.getElementById('meDocumentViewerModal');
            if (event.target === docViewerModal) {
                closeMonitoringEvaluationDocumentViewerModal();
            }
        });

        function openMonitoringEvaluationDocumentViewerModal(docUrl, docTitle) {
            const modal = document.getElementById('meDocumentViewerModal');
            const frame = document.getElementById('meDocumentViewerFrame');
            const title = document.getElementById('meDocumentViewerTitle');
            if (!modal || !frame || !title) return;

            title.textContent = docTitle || 'View Document';
            frame.src = docUrl;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeMonitoringEvaluationDocumentViewerModal() {
            const modal = document.getElementById('meDocumentViewerModal');
            const frame = document.getElementById('meDocumentViewerFrame');
            if (frame) frame.src = 'about:blank';
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function openMonitoringEvaluationDeletionRequestModal(docId) {
            const modal = document.getElementById('meDeletionRequestModal');
            const form = document.getElementById('meDeletionRequestForm');
            if (!modal || !form) return;
            form.action = '{{ $baseRouteUrl }}/' + encodeURIComponent('{{ $officeName }}') + '/request-deletion/' + docId;
            document.getElementById('meDeletionRequestRemarks').value = '';
            modal.style.display = 'block';
        }

        function closeMonitoringEvaluationDeletionRequestModal() {
            const modal = document.getElementById('meDeletionRequestModal');
            if (modal) modal.style.display = 'none';
        }

        function openMonitoringEvaluationDeletionDecisionModal(docId, decision) {
            const modal = document.getElementById('meDeletionDecisionModal');
            const form = document.getElementById('meDeletionDecisionForm');
            const title = document.getElementById('meDeletionDecisionTitle');
            const decisionInput = document.getElementById('meDeletionDecisionInput');
            const submitBtn = document.getElementById('meDeletionDecisionSubmit');
            if (!modal || !form) return;

            form.action = '{{ $baseRouteUrl }}/' + encodeURIComponent('{{ $officeName }}') + '/decide-deletion/' + docId;
            decisionInput.value = decision;
            document.getElementById('meDeletionDecisionRemarks').value = '';

            if (decision === 'approve') {
                title.textContent = 'Approve Deletion Request';
                submitBtn.style.backgroundColor = '#10b981';
                submitBtn.textContent = 'Confirm Approval';
            } else {
                title.textContent = 'Reject Deletion Request';
                submitBtn.style.backgroundColor = '#dc2626';
                submitBtn.textContent = 'Confirm Rejection';
            }

            modal.style.display = 'block';
        }

        function closeMonitoringEvaluationDeletionDecisionModal() {
            const modal = document.getElementById('meDeletionDecisionModal');
            if (modal) modal.style.display = 'none';
        }
    </script>

    <!-- Deletion Request Modal -->
    <div id="meDeletionRequestModal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1100; backdrop-filter: blur(4px);">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); max-width: 420px; width: 90%;">
            <h3 style="margin-top: 0; color: #0f172a; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-trash-alt" style="color: #ea580c;"></i> Request Document Deletion
            </h3>
            <form id="meDeletionRequestForm" method="POST">
                @csrf
                <div style="margin: 16px 0;">
                    <label for="meDeletionRequestRemarks" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Reason for Deletion</label>
                    <textarea id="meDeletionRequestRemarks" name="remarks" required placeholder="Please explain why you need this document deleted..." style="width: 100%; min-height: 100px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeMonitoringEvaluationDeletionRequestModal()" style="padding: 8px 14px; background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Cancel</button>
                    <button type="submit" style="padding: 8px 14px; background-color: #ea580c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deletion Decision Modal -->
    <div id="meDeletionDecisionModal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1100; backdrop-filter: blur(4px);">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); max-width: 420px; width: 90%;">
            <h3 id="meDeletionDecisionTitle" style="margin-top: 0; color: #0f172a; font-size: 16px; font-weight: 700;">Decide Deletion Request</h3>
            <form id="meDeletionDecisionForm" method="POST">
                @csrf
                <input type="hidden" name="decision" id="meDeletionDecisionInput">
                <div style="margin: 16px 0;">
                    <label for="meDeletionDecisionRemarks" style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Remarks (Optional)</label>
                    <textarea id="meDeletionDecisionRemarks" name="remarks" placeholder="Add any comments or notes here..." style="width: 100%; min-height: 100px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeMonitoringEvaluationDeletionDecisionModal()" style="padding: 8px 14px; background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Cancel</button>
                    <button type="submit" id="meDeletionDecisionSubmit" style="padding: 8px 14px; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="meHistoryModal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1100; backdrop-filter: blur(4px);">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); max-width: 800px; width: 90%; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-history" style="color: white; font-size: 14px;"></i>
                    </div>
                    <h3 id="meHistoryModalTitle" style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Submission History</h3>
                </div>
                <button type="button" onclick="closeMonitoringEvaluationHistoryModal()" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">&times;</button>
            </div>
            <div id="meHistoryModalBody" style="padding: 24px; overflow-y: auto; background-color: #f9fafb; flex-grow: 1;">
                <!-- Timeline content populated by JS -->
            </div>
        </div>
    </div>

    <style>
        /* Timeline modal styles for per-file history */
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            background: #e6e6e6;
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            width: 50%;
            padding: 12px 20px;
            box-sizing: border-box;
        }

        .timeline-item.left {
            left: 0;
            text-align: right;
        }

        .timeline-item.right {
            left: 50%;
            text-align: left;
        }

        .timeline-item .timeline-bullet {
            position: absolute;
            top: 18px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(2,6,23,0.06);
        }

        .timeline-bullet.upload { border-color: #10b981; background: #10b981; }
        .timeline-bullet.uploaded { border-color: #10b981; background: #10b981; }
        .timeline-bullet.return { border-color: #ef4444; background: #ef4444; }
        .timeline-bullet.returned { border-color: #ef4444; background: #ef4444; }
        .timeline-bullet.update { border-color: #6b7280; background: #6b7280; }
        .timeline-bullet.validate_po { border-color: #3b82f6; background: #3b82f6; }
        .timeline-bullet.validate_ro { border-color: #059669; background: #059669; }
        .timeline-bullet.delete_request { border-color: #ea580c; background: #ea580c; }
        .timeline-bullet.delete { border-color: #dc2626; background: #dc2626; }
        .timeline-bullet.delete_reject { border-color: #6b7280; background: #6b7280; }

        .timeline-item.left .timeline-bullet {
            right: -6px;
        }

        .timeline-item.right .timeline-bullet {
            left: -6px;
        }

        .timeline-card {
            display: inline-block;
            max-width: 460px;
            padding: 12px 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(2,6,23,0.06);
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .timeline-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(2,6,23,0.08);
        }

        .timeline-meta { display:flex; gap:8px; align-items:center; }
        .avatar {
            width:28px; height:28px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:white;
        }

        .doc-chip {
            display:inline-block; padding:4px 8px; font-size:11px; font-weight:700; border-radius:999px; background:#f1f5f9; color:#0f172a; margin-left:6px;
        }

        .action-pill { display:inline-block; padding:6px 8px; font-size:11px; font-weight:700; border-radius:999px; color:white; }
        .action-upload { background: #10b981; }
        .action-uploaded { background: #10b981; }
        .action-submitted { background: #3b82f6; }
        .action-resubmitted { background: #3b82f6; }
        .action-forwarded { background: #3b82f6; }
        .action-approved { background: #10b981; }
        .action-return { background: #dc2626; }
        .action-returned { background: #dc2626; }
        .action-deleted { background: #dc2626; }
        .action-update { background: #6b7280; }
        .action-delete { background: #dc2626; }
        .action-delete_request { background: #ea580c; }
        .action-delete_reject { background: #6b7280; }
        .action-validate_po { background: #3b82f6; }
        .action-validate_ro { background: #059669; }

        .timeline-title { display:flex; gap:8px; align-items:center; }

        .timeline-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .timeline-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .timeline-remarks {
            white-space: pre-wrap;
            color: #374151;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 20px;
            }
            .timeline-item {
                width: 100%;
                padding-left: 40px;
                padding-right: 0;
            }
            .timeline-item.left {
                left: 0;
                text-align: left;
            }
            .timeline-item.right {
                left: 0;
                text-align: left;
            }
            .timeline-item.left .timeline-bullet {
                left: 14px;
                right: auto;
            }
            .timeline-item.right .timeline-bullet {
                left: 14px;
            }
        }
    </style>

    <script>
        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        const reportingYear = {{ $reportingYear }};
        const meActivityLogs = @json($activityLogs ?? []);
        const systemUsers = @json($usersById ?? []);

        function openMonitoringEvaluationHistoryModal(monthCode, monthLabel) {
            const modal = document.getElementById('meHistoryModal');
            const titleEl = document.getElementById('meHistoryModalTitle');
            const body = document.getElementById('meHistoryModalBody');
            if (!modal || !titleEl || !body) return;

            titleEl.textContent = `${monthLabel} Monthly M&E Report - Submission History`;

            // Filter activity logs by month matching monthCode and year matching reportingYear (with fallback parsing for legacy logs)
            const monthOptionsMapping = {
                'JAN': 'January',
                'FEB': 'February',
                'MAR': 'March',
                'APR': 'April',
                'MAY': 'May',
                'JUN': 'June',
                'JUL': 'July',
                'AUG': 'August',
                'SEP': 'September',
                'OCT': 'October',
                'NOV': 'November',
                'DEC': 'December'
            };

            const filteredLogs = meActivityLogs.filter(log => {
                const actionRaw = (log.action || '').toString().toLowerCase();
                if (actionRaw.includes('validate_po') || actionRaw.includes('validated (dilg po)')) {
                    return false;
                }

                let m = log.month;
                let y = log.year;
                
                if (!m || !y) {
                    const docLabel = log.document || '';
                    const yearMatch = docLabel.match(/CY\s+(\d{4})/i);
                    if (yearMatch) {
                        y = Number(yearMatch[1]);
                    }
                    for (const [code, label] of Object.entries(monthOptionsMapping)) {
                        if (docLabel.toLowerCase().includes(label.toLowerCase())) {
                            m = code;
                            break;
                        }
                    }
                }
                
                return m === monthCode && Number(y) === Number(reportingYear);
            });

            // Sort logs by timestamp descending (newest first)
            const sortedLogs = [...filteredLogs].sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

            if (!sortedLogs.length) {
                body.innerHTML = '<div style="padding: 16px; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">No activity logs found for this month.</div>';
            } else {
                let html = '<div class="timeline">';
                sortedLogs.forEach((log, idx) => {
                    const side = (idx % 2 === 0) ? 'left' : 'right';
                    const ts = log.timestamp ? (new Date(log.timestamp)).toLocaleString() : '';
                    
                    let actorName = 'Unknown';
                    let actorAgency = '';
                    const user = systemUsers[log.user_id];
                    if (user) {
                        actorName = (user.fname + ' ' + user.lname).trim();
                        actorAgency = user.province || '';
                    }
                    const userDisplay = actorName + (actorAgency ? (' (' + actorAgency + ')') : '');

                    const actionRaw = (log.action || 'update').toString().toLowerCase();
                    let actionKey = 'update';
                    let actionLabel = actionRaw.toUpperCase();

                    if (actionRaw.includes('upload')) {
                        actionKey = 'upload';
                        actionLabel = 'UPLOADED';
                    } else if (actionRaw.includes('validate_po') || actionRaw.includes('validated (dilg po)')) {
                        actionKey = 'validate_po';
                        actionLabel = 'VALIDATED (DILG PO)';
                    } else if (actionRaw.includes('validate_ro') || actionRaw.includes('validated (dilg ro)') || actionRaw === 'approved') {
                        actionKey = 'validate_ro';
                        actionLabel = 'APPROVED (DILG RO)';
                    } else if (actionRaw.includes('return')) {
                        actionKey = 'return';
                        actionLabel = 'RETURNED';
                    } else if (actionRaw.includes('delete_request') || actionRaw.includes('deletion requested')) {
                        actionKey = 'delete_request';
                        actionLabel = 'DELETION REQUESTED';
                    } else if (actionRaw.includes('delete_reject') || actionRaw.includes('deletion rejected')) {
                        actionKey = 'delete_reject';
                        actionLabel = 'DELETION REJECTED';
                    } else if (actionRaw === 'delete' || actionRaw.includes('deletion approved')) {
                        actionKey = 'delete';
                        actionLabel = 'DELETION APPROVED';
                    }

                    // initials for avatar
                    const initials = (actorName || 'U').split(' ').map(s => s[0] || '').join('').substring(0,2).toUpperCase();
                    
                    let avatarBg = '#6b7280';
                    if (actorAgency && String(actorAgency).toLowerCase().includes('dilg')) avatarBg = '#0ea5a9';
                    if (actorAgency && String(actorAgency).toLowerCase().includes('lgu')) avatarBg = '#f59e0b';

                    html += '<div class="timeline-item ' + side + '">';
                    html += '<div class="timeline-bullet ' + escapeHtml(actionKey) + '" aria-hidden="true"></div>';
                    html += '<div class="timeline-card">';
                    html += '<div class="timeline-meta">';
                    html += '<span class="avatar" style="background:' + avatarBg + '">' + escapeHtml(initials) + '</span>';
                    html += '<div style="margin-left:8px; display:inline-block; vertical-align:middle; text-align: left;">';
                    html += '<div style="font-size:11px;color:#6b7280">' + escapeHtml(ts) + '</div>';
                    html += '<div style="font-weight:700;color:#0f172a">' + escapeHtml(userDisplay) + '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '<div class="timeline-title" style="margin-top: 8px; text-align: left;">';
                    html += '<span class="action-pill action-' + escapeHtml(actionKey) + '">' + escapeHtml(actionLabel) + '</span>';
                    html += '</div>';
                    html += '<div class="timeline-remarks" style="margin-top: 8px; text-align: left;"><strong>Remarks :</strong> ' + escapeHtml(log.remarks || '—') + '</div>';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                body.innerHTML = html;
            }
            modal.style.display = 'block';
        }

        function closeMonitoringEvaluationHistoryModal() {
            document.getElementById('meHistoryModal').style.display = 'none';
        }
    </script>

    <!-- Document Viewer Modal -->
    <div id="meDocumentViewerModal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1200; backdrop-filter: blur(4px); justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); width: 95%; max-width: 1200px; height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-pdf" style="color: white; font-size: 14px;"></i>
                    </div>
                    <div>
                        <h3 id="meDocumentViewerTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">View Document</h3>
                        <div style="color: rgba(255,255,255,0.78); font-size: 11px; margin-top: 2px;">Previewing document inside the page</div>
                    </div>
                </div>
                <button type="button" onclick="closeMonitoringEvaluationDocumentViewerModal()" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="flex: 1; background: #f8fafc; padding: 0; position: relative;">
                <iframe id="meDocumentViewerFrame" title="Document Viewer" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
    </div>
@endsection

