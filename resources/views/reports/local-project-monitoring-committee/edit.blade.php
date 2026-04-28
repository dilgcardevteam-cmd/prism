@extends('layouts.dashboard')

@section('title', 'Local Project Monitoring Committee - Update')
@section('page-title', 'Update Local Project Monitoring Committee')

@section('content')
    <div class="ops-detail-page">
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div>
            <h1>Update - {{ $officeName }}</h1>
            <p>Upload or update committee documents and activities.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('local-project-monitoring-committee.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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

    @if (session('error'))
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
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
        </div>
    </div>

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h2 style="color: #002C76; font-size: 18px; margin-bottom: 20px; font-weight: 600;">Uploading of Documents</h2>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
            @php
                $docBlocks = [
                    ['label' => 'Executive Order for CY 2025 (MOV)', 'doc_type' => 'eo', 'year' => 2025],
                    ['label' => 'Annual Work and Financial Plan (AWFP) for CY 2025', 'doc_type' => 'awfp', 'year' => 2025],
                    ['label' => 'Monitoring and Evaluation Plan for CY 2025', 'doc_type' => 'mep', 'year' => 2025],
                    ['label' => 'Executive Order for 2026', 'doc_type' => 'eo', 'year' => 2026],
                    ['label' => 'CY 2026 Annual Work and Financial Plan', 'doc_type' => 'awfp', 'year' => 2026],
                    ['label' => 'CY 2026 Monitoring and Evaluation Plan', 'doc_type' => 'mep', 'year' => 2026],
                ];
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
            @foreach ($docBlocks as $docBlock)
                @php
                    $docKey = $docBlock['doc_type'] . '|' . $docBlock['year'] . '|';
                    $doc = $documentsByKey[$docKey] ?? null;
                    $inputId = 'lpmc-doc-input-' . $docBlock['doc_type'] . '-' . $docBlock['year'];
                    $buttonId = 'lpmc-doc-btn-' . $docBlock['doc_type'] . '-' . $docBlock['year'];
                    $filenameId = 'lpmc-doc-file-' . $docBlock['doc_type'] . '-' . $docBlock['year'];
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
                    $uploader = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                    $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                    $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                    $uploadedInfo = $resolveUploaderMeta($doc);
                    $uploadedTime = $uploadedInfo['time'];
                    $uploaderName = $uploadedInfo['name'];
                    $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                    $isDilgMountainUploader = $uploaderUser
                        && strtoupper(trim((string) ($uploaderUser->agency ?? ''))) === 'DILG'
                        && strtolower(trim((string) ($uploaderUser->province ?? ''))) === 'mountain province';
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
                <form method="POST" action="{{ route('local-project-monitoring-committee.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 18px; border-radius: 8px; background-color: #f9fafb;">
                    @csrf
                    <input type="hidden" name="doc_type" value="{{ $docBlock['doc_type'] }}">
                    <input type="hidden" name="year" value="{{ $docBlock['year'] }}">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                        <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0;">{{ $docBlock['label'] }}</label>
                        <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border-radius: 20px; font-size: 10px; font-weight: 600;">
                            {{ $statusLabel }}
                        </span>
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

                        @foreach ($timelineEvents as $timelineEvent)
                            <div style="display: block; font-size: {{ $timelineEvent['font_size'] }}; font-weight: {{ $timelineEvent['font_weight'] }}; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                {{ $timelineEvent['message'] }}
                            </div>
                        @endforeach
                    </div>
                    @if ($doc && $doc->file_path)
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                            <a href="{{ route('local-project-monitoring-committee.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; color: #002C76; font-size: 12px; text-decoration: none;">
                                <i class="fas fa-file"></i>&nbsp;View current file
                            </a>
                            @if (Auth::user()->isSuperAdmin())
                                <form method="POST" action="{{ route('local-project-monitoring-committee.delete-document', ['lpmc' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Delete this uploaded document? This action cannot be undone.');" style="display: inline;">
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
                        @disabled($disableUploadInput)
                        class="ops-upload-input"
                        style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
                        onchange="showLpmcSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                    >
                    @if ($disableUploadInput)
                        <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                            @if ($isRegionalOfficeUserForUpload)
                                Regional Office cannot upload files. Choose file is disabled.
                            @else
                                File already submitted. Choose file is disabled until the current file is returned.
                            @endif
                        </div>
                    @endif
                    <div id="{{ $filenameId }}" class="ops-upload-filename" style="display: none; margin-bottom: 8px; font-size: 12px; color: #6b7280;"></div>
                    <button
                        type="submit"
                        id="{{ $buttonId }}"
                        @disabled($disableUploadInput)
                        class="ops-upload-submit"
                        style="width: 100%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: {{ $disableUploadInput ? 'not-allowed' : 'pointer' }}; font-weight: 600; font-size: 12px; opacity: {{ $disableUploadInput ? '0.55' : '0' }}; pointer-events: none; transition: all 0.3s ease;"
                    >
                        Upload
                    </button>
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
                    @if ($showApprovalButtons)
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <button type="button" onclick="openLpmcApprovalModal({{ $doc->id }}, 'approve')" style="flex: 1; padding: 8px 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                Approve
                            </button>
                            @if (!$hideReturnButton)
                                <button type="button" onclick="openLpmcApprovalModal({{ $doc->id }}, 'return')" style="flex: 1; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                    Return
                                </button>
                            @endif
                        </div>
                    @endif
                </form>
            @endforeach
        </div>

        <div style="display: grid; gap: 12px;">
            @php
                $quarters = ['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'];
                $quarterWindows = [
                    'Q1' => 'January - March',
                    'Q2' => 'April - June',
                    'Q3' => 'July - September',
                    'Q4' => 'October - December',
                ];
            @endphp
            @foreach ($quarters as $quarter => $label)
                @php
                    $configuredQuarterDeadline = $configuredQuarterDeadlines[$quarter] ?? null;
                    $quarterDeadlineDisplay = is_array($configuredQuarterDeadline) ? (string) ($configuredQuarterDeadline['display'] ?? '') : '';
                @endphp
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <button type="button" class="lpmc-accordion-toggle" data-target="lpmc-{{ $quarter }}" style="width: 100%; padding: 14px 16px; background-color: #002C76; color: white; border: none; text-align: left; cursor: pointer; font-weight: 600; font-size: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            {{ $label }}
                            <span style="font-size: 11px; font-weight: 500; opacity: 0.95;">({{ $quarterWindows[$quarter] ?? '' }})</span>
                            <span style="display: block; font-size: 11px; font-weight: 500; opacity: 0.95; margin-top: 2px;">
                                Deadline (CY {{ $deadlineReportingYear }}):
                                {{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}
                            </span>
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; background: {{ $quarterDeadlineDisplay !== '' ? '#0f766e' : '#6b7280' }}; color: #fff;">
                                {{ $quarterDeadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}
                            </span>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s;"></i>
                        </span>
                    </button>
                    <div id="lpmc-{{ $quarter }}" style="display: none; padding: 16px; background-color: #ffffff;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                            @php
                                $quarterDocs = [
                                    ['label' => 'Meetings Conducted', 'doc_type' => 'meetings'],
                                    ['label' => 'Monitoring Conducted', 'doc_type' => 'monitoring'],
                                    ['label' => 'Training Conducted', 'doc_type' => 'training'],
                                ];
                            @endphp
                            @foreach ($quarterDocs as $qDoc)
                                @php
                                    $docKey = $qDoc['doc_type'] . '||' . $quarter;
                                    $doc = $documentsByKey[$docKey] ?? null;
                                    $inputId = 'lpmc-q-input-' . $qDoc['doc_type'] . '-' . $quarter;
                                    $buttonId = 'lpmc-q-btn-' . $qDoc['doc_type'] . '-' . $quarter;
                                    $filenameId = 'lpmc-q-file-' . $qDoc['doc_type'] . '-' . $quarter;
                                    $isRegionalOfficeUserForUpload = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                                    $disableQuarterUpload = is_array($configuredQuarterDeadline)
                                        && !empty($configuredQuarterDeadline['is_closed']);
                                    $hasFile = $doc && $doc->file_path;
                                    $isReturned = $doc && $doc->status === 'returned';
                                    $disableUploadInput = ($hasFile && !$isReturned)
                                        || $isRegionalOfficeUserForUpload
                                        || $disableQuarterUpload;
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
                                    $uploader = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                                    $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                                    $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                                    $uploadedInfo = $resolveUploaderMeta($doc);
                                    $uploadedTime = $uploadedInfo['time'];
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                    $uploaderName = $uploadedInfo['name'];
                                    $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                                    $isDilgMountainUploader = $uploaderUser
                                        && strtoupper(trim((string) ($uploaderUser->agency ?? ''))) === 'DILG'
                                        && strtolower(trim((string) ($uploaderUser->province ?? ''))) === 'mountain province';
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
                                <form method="POST" action="{{ route('local-project-monitoring-committee.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 16px; border-radius: 8px; background-color: #f9fafb;">
                                    @csrf
                                    <input type="hidden" name="doc_type" value="{{ $qDoc['doc_type'] }}">
                                    <input type="hidden" name="quarter" value="{{ $quarter }}">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                                        <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0;">{{ $qDoc['label'] }}</label>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                            <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border-radius: 20px; font-size: 10px; font-weight: 600;">
                                                {{ $statusLabel }}
                                            </span>
                                            @if ($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; padding: 4px 10px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
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

                                        @foreach ($timelineEvents as $timelineEvent)
                                            <div style="display: block; font-size: {{ $timelineEvent['font_size'] }}; font-weight: {{ $timelineEvent['font_weight'] }}; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                                {{ $timelineEvent['message'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($doc && $doc->file_path)
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                            <a href="{{ route('local-project-monitoring-committee.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; color: #002C76; font-size: 12px; text-decoration: none;">
                                                <i class="fas fa-file"></i>&nbsp;View current file
                                            </a>
                                            @if (Auth::user()->isSuperAdmin())
                                                <form method="POST" action="{{ route('local-project-monitoring-committee.delete-document', ['lpmc' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Delete this uploaded document? This action cannot be undone.');" style="display: inline;">
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
                                        @disabled($disableUploadInput)
                                        style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
                                        onchange="showLpmcSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                                    >
                                    @if ($disableUploadInput)
                                        <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                            @if ($disableQuarterUpload)
                                                Uploads are closed for {{ $label }} based on the superadmin deadline{{ $quarterDeadlineDisplay !== '' ? ' (' . $quarterDeadlineDisplay . ')' : '' }}.
                                            @elseif ($isRegionalOfficeUserForUpload)
                                                Regional Office cannot upload files. Choose file is disabled.
                                            @else
                                                File already submitted. Choose file is disabled until the current file is returned.
                                            @endif
                                        </div>
                                    @endif
                                    <div id="{{ $filenameId }}" style="display: none; margin-bottom: 8px; font-size: 12px; color: #6b7280;"></div>
                                    <button
                                        type="submit"
                                        id="{{ $buttonId }}"
                                        @disabled($disableUploadInput)
                                        style="width: 100%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: {{ $disableUploadInput ? 'not-allowed' : 'pointer' }}; font-weight: 600; font-size: 12px; opacity: {{ $disableUploadInput ? '0.55' : '0' }}; pointer-events: none; transition: all 0.3s ease;"
                                    >
                                        Upload
                                    </button>
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
                                    @if ($showApprovalButtons)
                                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                                            <button type="button" onclick="openLpmcApprovalModal({{ $doc->id }}, 'approve')" style="flex: 1; padding: 8px 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                                Approve
                                            </button>
                                            @if (!$hideReturnButton)
                                                <button type="button" onclick="openLpmcApprovalModal({{ $doc->id }}, 'return')" style="flex: 1; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                                    Return
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="lpmcActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="lpmcActivityLogTitle" aria-hidden="true">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px 16px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); border-radius: 12px 12px 0 0; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clipboard-list" style="color: white; font-size: 14px;"></i>
                    </div>
                    <h3 id="lpmcActivityLogTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                </div>
                <button type="button" id="lpmcActivityLogClose" aria-label="Close activity logs" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="padding: 20px 24px; overflow-y: auto; max-height: 65vh;">
                @if (empty($activityLogs))
                    <div style="padding: 40px 20px; text-align: center;">
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

    <div id="lpmcActivityLogBackdrop" aria-hidden="true"></div>

    <button id="lpmcActivityLogFab" type="button" aria-controls="lpmcActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true" style="font-size: 14px;"></i>
        <span>Activity Logs</span>
    </button>

    <script>
        document.querySelectorAll('.lpmc-accordion-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const panel = document.getElementById(targetId);
                if (!panel) return;

                const isOpen = panel.style.display === 'block';

                // Collapse all other open panels first
                if (!isOpen) {
                    document.querySelectorAll('.lpmc-accordion-toggle').forEach(function (otherBtn) {
                        if (otherBtn === button) return;
                        const otherId = otherBtn.getAttribute('data-target');
                        const otherPanel = document.getElementById(otherId);
                        if (otherPanel && otherPanel.style.display === 'block') {
                            otherPanel.style.display = 'none';
                            const otherIcon = otherBtn.querySelector('i');
                            if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                        }
                    });
                }

                panel.style.display = isOpen ? 'none' : 'block';

                const icon = button.querySelector('i');
                if (icon) {
                    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        });
    </script>

    <script>
        function initializeLpmcUploadStyling() {
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

            document.querySelectorAll('.ops-detail-page button[id^="lpmc-doc-btn-"], .ops-detail-page button[id^="lpmc-q-btn-"]').forEach(function (btn) {
                btn.classList.add('ops-upload-submit');
            });

            document.querySelectorAll('.ops-detail-page div[id^="lpmc-doc-file-"], .ops-detail-page div[id^="lpmc-q-file-"]').forEach(function (filenameDiv) {
                filenameDiv.classList.add('ops-upload-filename');
                if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                    filenameDiv.classList.add('has-file');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initializeLpmcUploadStyling);

        function showLpmcSaveButton(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameDiv = document.getElementById(filenameId);
            if (!saveBtn || !filenameDiv) return;

            saveBtn.classList.add('ops-upload-submit');
            filenameDiv.classList.add('ops-upload-filename');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                const icon = document.createElement('i');
                icon.className = 'fas fa-file';
                icon.style.marginRight = '4px';
                filenameDiv.replaceChildren(icon, document.createTextNode(`Selected: ${fileName}`));
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

        #lpmcActivityLogBackdrop {
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

        #lpmcActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #lpmcActivityLogModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(920px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease;
            z-index: 1200;
        }

        #lpmcActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-lpmc-logs {
            overflow: hidden;
        }

        #lpmcActivityLogFab {
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

        #lpmcActivityLogFab:hover {
            background-color: #003d9e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.4);
        }

        #lpmcActivityLogFab:active {
            transform: translateY(0);
        }

        #lpmcActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        @media (max-width: 640px) {
            #lpmcActivityLogModal { width: 94vw; }
            #lpmcActivityLogFab span { display: none; }
            #lpmcActivityLogFab { padding: 14px; border-radius: 50%; }
        }
    </style>

    <script>
        const lpmcActivityLogModal = document.getElementById('lpmcActivityLogModal');
        const lpmcActivityLogBackdrop = document.getElementById('lpmcActivityLogBackdrop');
        const lpmcActivityLogFab = document.getElementById('lpmcActivityLogFab');
        const lpmcActivityLogClose = document.getElementById('lpmcActivityLogClose');

        function setLpmcActivityLogVisibility(isVisible) {
            if (!lpmcActivityLogModal || !lpmcActivityLogBackdrop || !lpmcActivityLogFab) {
                return;
            }

            lpmcActivityLogModal.classList.toggle('is-visible', isVisible);
            lpmcActivityLogBackdrop.classList.toggle('is-visible', isVisible);
            document.body.classList.toggle('modal-open-lpmc-logs', isVisible);
            lpmcActivityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            lpmcActivityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            lpmcActivityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            lpmcActivityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

            const labelSpan = lpmcActivityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Hide Activity Logs' : 'Activity Logs';
            }

            if (isVisible && lpmcActivityLogClose) {
                lpmcActivityLogClose.focus();
            }
        }

        if (lpmcActivityLogFab && lpmcActivityLogModal && lpmcActivityLogBackdrop) {
            lpmcActivityLogFab.addEventListener('click', () => {
                const isOpen = lpmcActivityLogModal.classList.contains('is-visible');
                setLpmcActivityLogVisibility(!isOpen);
            });

            lpmcActivityLogBackdrop.addEventListener('click', () => {
                setLpmcActivityLogVisibility(false);
            });

            if (lpmcActivityLogClose) {
                lpmcActivityLogClose.addEventListener('click', () => {
                    setLpmcActivityLogVisibility(false);
                });
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && lpmcActivityLogModal && lpmcActivityLogModal.classList.contains('is-visible')) {
                setLpmcActivityLogVisibility(false);
            }
        });
    </script>

    <div id="lpmcApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
            <h3 id="lpmcApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
            <form id="lpmcApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="lpmcApprovalAction">
                <textarea id="lpmcApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button type="button" onclick="closeLpmcApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                    <button type="submit" id="lpmcApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLpmcApprovalModal(docId, action) {
            const modal = document.getElementById('lpmcApprovalModal');
            const form = document.getElementById('lpmcApprovalForm');
            const title = document.getElementById('lpmcApprovalTitle');
            const actionInput = document.getElementById('lpmcApprovalAction');
            const remarks = document.getElementById('lpmcApprovalRemarks');
            const submitBtn = document.getElementById('lpmcApprovalSubmit');

            form.action = '{{ url("/local-project-monitoring-committee") }}/{{ $officeName }}/approve/' + docId;
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

        function closeLpmcApprovalModal() {
            document.getElementById('lpmcApprovalModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const modal = document.getElementById('lpmcApprovalModal');
            if (event.target === modal) {
                closeLpmcApprovalModal();
            }
        });
    </script>
    </div>
@endsection
