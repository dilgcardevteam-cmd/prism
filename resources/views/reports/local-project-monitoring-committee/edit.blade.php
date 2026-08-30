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
            <button type="button" onclick="openLpmcDocHistoryModal('', '', 'All Documents')" style="display: inline-flex; padding: 10px 18px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-clock-rotate-left"></i> History
            </button>
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
                $resolveStatusTheme = function ($doc) {
                    $hasFile = $doc && ($doc->file_path || ($doc->files && $doc->files->isNotEmpty()));
                    $isReturned = $doc && $doc->status === 'returned';
                    $isApprovedRo = $doc && $doc->approved_at_dilg_ro;
                    $isPendingRo = $doc && $doc->approved_at_dilg_po && !$doc->approved_at_dilg_ro;

                    if ($isApprovedRo) {
                        return [
                            'label' => 'Approved',
                            'icon' => 'fa-check-circle',
                            'badgeBg' => '#059669',
                            'badgeColor' => '#ffffff',
                            'cardBg' => '#f0fdf4',
                            'cardBorder' => '#a7f3d0',
                            'fileBg' => '#ecfdf5',
                            'fileBorder' => '#a7f3d0',
                            'fileColor' => '#047857',
                        ];
                    }

                    if ($isReturned) {
                        return [
                            'label' => 'Returned',
                            'icon' => 'fa-undo',
                            'badgeBg' => '#dc2626',
                            'badgeColor' => '#ffffff',
                            'cardBg' => '#fef2f2',
                            'cardBorder' => '#fecaca',
                            'fileBg' => '#fff1f1',
                            'fileBorder' => '#fca5a5',
                            'fileColor' => '#991b1b',
                        ];
                    }

                    if ($isPendingRo) {
                        return [
                            'label' => 'For DILG Regional Office Validation',
                            'icon' => 'fa-clock',
                            'badgeBg' => '#eab308',
                            'badgeColor' => '#ffffff',
                            'cardBg' => '#fefce8',
                            'cardBorder' => '#fef08a',
                            'fileBg' => '#fef9c3',
                            'fileBorder' => '#fde047',
                            'fileColor' => '#713f12',
                        ];
                    }

                    if ($hasFile) {
                        return [
                            'label' => 'For DILG Provincial Office Validation',
                            'icon' => 'fa-clock',
                            'badgeBg' => '#eab308',
                            'badgeColor' => '#ffffff',
                            'cardBg' => '#fefce8',
                            'cardBorder' => '#fef08a',
                            'fileBg' => '#fef9c3',
                            'fileBorder' => '#fde047',
                            'fileColor' => '#713f12',
                        ];
                    }

                    return [
                        'label' => 'Pending Upload',
                        'icon' => 'fa-hourglass-start',
                        'badgeBg' => '#6b7280',
                        'badgeColor' => '#ffffff',
                        'cardBg' => '#f8fafc',
                        'cardBorder' => '#e2e8f0',
                        'fileBg' => '#f1f5f9',
                        'fileBorder' => '#cbd5e1',
                        'fileColor' => '#334155',
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
                    $statusTheme = $resolveStatusTheme($doc);
                    $uploader = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                    $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                    $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                    $uploadedInfo = $resolveUploaderMeta($doc);
                    $uploadedTime = $uploadedInfo['time'];
                    $uploaderName = $uploadedInfo['name'];
                    $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                    $isProvincialDilgUploader = $uploaderUser && method_exists($uploaderUser, 'isProvincialDilgAssignment')
                        ? $uploaderUser->isProvincialDilgAssignment()
                        : false;
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
                <form id="lpmc-document-{{ $docBlock['doc_type'] }}-{{ $docBlock['year'] }}" method="POST" action="{{ route('local-project-monitoring-committee.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1.5px solid {{ $statusTheme['cardBorder'] }}; padding: 18px; border-radius: 10px; background-color: {{ $statusTheme['cardBg'] }}; transition: all 0.2s ease; scroll-margin-top: 96px;">
                    @csrf
                    <input type="hidden" name="doc_type" value="{{ $docBlock['doc_type'] }}">
                    <input type="hidden" name="year" value="{{ $docBlock['year'] }}">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                        <label style="display: block; color: #1e293b; font-weight: 700; font-size: 13px; margin: 0;">{{ $docBlock['label'] }}</label>
                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background-color: {{ $statusTheme['badgeBg'] }}; color: {{ $statusTheme['badgeColor'] }}; border-radius: 20px; font-size: 10px; font-weight: 600;">
                            <i class="fas {{ $statusTheme['icon'] }}"></i> {{ $statusTheme['label'] }}
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
                                && $isProvincialDilgUploader
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
                    @if ($doc)
                        @php $docFiles = $doc->files ?? collect(); @endphp
                        @if ($docFiles->isNotEmpty())
                            <div style="margin-bottom: 10px;">
                                @foreach ($docFiles as $docFile)
                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; padding: 6px 10px; background-color: {{ $statusTheme['fileBg'] }}; border: 1px solid {{ $statusTheme['fileBorder'] }}; border-radius: 6px; font-size: 11px; color: {{ $statusTheme['fileColor'] }};">
                                        <i class="fas fa-file-pdf" style="flex-shrink: 0; color: #dc2626;"></i>
                                        <span style="flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $docFile->original_filename ?: basename($docFile->file_path) }}">{{ $docFile->original_filename ?: basename($docFile->file_path) }}</span>
                                        <a href="javascript:void(0)" onclick="openGlobalDocPreviewModal('{{ route('local-project-monitoring-committee.view-file', [$officeName, $docFile->id]) }}', '{{ addslashes($docFile->original_filename ?: basename($docFile->file_path)) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #3b82f6; color: white; border-radius: 4px; font-size: 10px; font-weight: 600; text-decoration: none;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" onclick="openLpmcDocHistoryModal('{{ $docBlock['doc_type'] }}', '{{ $docBlock['year'] }}', '{{ addslashes($docBlock['label']) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 10px; font-weight: 600; cursor: pointer;">
                                            <i class="fas fa-clock-rotate-left"></i> History
                                        </button>
                                        @if (Auth::user()->isSuperAdmin())
                                            <button type="submit" form="lpmc-delete-file-{{ $docFile->id }}" onclick="return confirm('Delete this file? This action cannot be undone.');" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @elseif ($doc->file_path)
                            {{-- Legacy single-file display (before multi-file table existed) --}}
                            @php $displayName = $doc->original_filename ?: basename($doc->file_path); @endphp
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; padding: 6px 10px; background-color: {{ $statusTheme['fileBg'] }}; border: 1px solid {{ $statusTheme['fileBorder'] }}; border-radius: 6px; font-size: 11px; color: {{ $statusTheme['fileColor'] }};">
                                <i class="fas fa-file-pdf" style="flex-shrink: 0; color: #dc2626;"></i>
                                <span style="flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $displayName }}">{{ $displayName }}</span>
                                <a href="javascript:void(0)" onclick="openGlobalDocPreviewModal('{{ route('local-project-monitoring-committee.document', [$officeName, $doc->id]) }}', '{{ addslashes($displayName) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #3b82f6; color: white; border-radius: 4px; font-size: 10px; font-weight: 600; text-decoration: none;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button type="button" onclick="openLpmcDocHistoryModal('{{ $docBlock['doc_type'] }}', '{{ $docBlock['year'] }}', '{{ addslashes($docBlock['label']) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 10px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-clock-rotate-left"></i> History
                                </button>
                            </div>
                        @endif
                    @endif
                    <input
                        id="{{ $inputId }}"
                        type="file"
                        name="document[]"
                        multiple
                        required
                        @disabled($disableUploadInput)
                        class="ops-upload-input dashboard-file-input"
                        data-max-size-kb="25600"
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
                @if (Auth::user()->isSuperAdmin() && $doc)
                    @foreach ($doc->files ?? [] as $docFile)
                        <form id="lpmc-delete-file-{{ $docFile->id }}" method="POST" action="{{ route('local-project-monitoring-committee.delete-file', ['lpmc' => $officeName, 'fileId' => $docFile->id]) }}" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endif
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
                <div id="lpmc-quarter-{{ $quarter }}" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; scroll-margin-top: 96px;">
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
                                    
                                    $statusTheme = $resolveStatusTheme($doc);
                                    $uploader = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                                    $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                                    $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                                    $uploadedInfo = $resolveUploaderMeta($doc);
                                    $uploadedTime = $uploadedInfo['time'];
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                    $uploaderName = $uploadedInfo['name'];
                                    $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                                    $isProvincialDilgUploader = $uploaderUser && method_exists($uploaderUser, 'isProvincialDilgAssignment')
                                        ? $uploaderUser->isProvincialDilgAssignment()
                                        : false;
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
                                <form method="POST" action="{{ route('local-project-monitoring-committee.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1.5px solid {{ $statusTheme['cardBorder'] }}; padding: 16px; border-radius: 10px; background-color: {{ $statusTheme['cardBg'] }}; transition: all 0.2s ease;">
                                    @csrf
                                    <input type="hidden" name="doc_type" value="{{ $qDoc['doc_type'] }}">
                                    <input type="hidden" name="quarter" value="{{ $quarter }}">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                                        <label style="display: block; color: #1e293b; font-weight: 700; font-size: 13px; margin: 0;">{{ $qDoc['label'] }}</label>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background-color: {{ $statusTheme['badgeBg'] }}; color: {{ $statusTheme['badgeColor'] }}; border-radius: 20px; font-size: 10px; font-weight: 600;">
                                                <i class="fas {{ $statusTheme['icon'] }}"></i> {{ $statusTheme['label'] }}
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
                                                && $isProvincialDilgUploader
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
                                    @if ($doc)
                                        @php $docFiles = $doc->files ?? collect(); @endphp
                                        @if ($docFiles->isNotEmpty())
                                            <div style="margin-bottom: 10px;">
                                                @foreach ($docFiles as $docFile)
                                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; padding: 6px 10px; background-color: {{ $statusTheme['fileBg'] }}; border: 1px solid {{ $statusTheme['fileBorder'] }}; border-radius: 6px; font-size: 11px; color: {{ $statusTheme['fileColor'] }};">
                                                        <i class="fas fa-file-pdf" style="flex-shrink: 0; color: #dc2626;"></i>
                                                        <span style="flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $docFile->original_filename ?: basename($docFile->file_path) }}">{{ $docFile->original_filename ?: basename($docFile->file_path) }}</span>
                                                        <a href="javascript:void(0)" onclick="openGlobalDocPreviewModal('{{ route('local-project-monitoring-committee.view-file', [$officeName, $docFile->id]) }}', '{{ addslashes($docFile->original_filename ?: basename($docFile->file_path)) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #3b82f6; color: white; border-radius: 4px; font-size: 10px; font-weight: 600; text-decoration: none;">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <button type="button" onclick="openLpmcDocHistoryModal('{{ $qDoc['doc_type'] }}', '{{ $quarter }}', '{{ addslashes($qDoc['label']) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 10px; font-weight: 600; cursor: pointer;">
                                                            <i class="fas fa-clock-rotate-left"></i> History
                                                        </button>
                                                        @if (Auth::user()->isSuperAdmin())
                                                            <button type="submit" form="lpmc-delete-file-{{ $docFile->id }}" onclick="return confirm('Delete this file? This action cannot be undone.');" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600;">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($doc->file_path)
                                            {{-- Legacy single-file display --}}
                                            @php $displayName = $doc->original_filename ?: basename($doc->file_path); @endphp
                                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; padding: 6px 10px; background-color: {{ $statusTheme['fileBg'] }}; border: 1px solid {{ $statusTheme['fileBorder'] }}; border-radius: 6px; font-size: 11px; color: {{ $statusTheme['fileColor'] }};">
                                                <i class="fas fa-file-pdf" style="flex-shrink: 0; color: #dc2626;"></i>
                                                <span style="flex: 1; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $displayName }}">{{ $displayName }}</span>
                                                <a href="javascript:void(0)" onclick="openGlobalDocPreviewModal('{{ route('local-project-monitoring-committee.document', [$officeName, $doc->id]) }}', '{{ addslashes($displayName) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #3b82f6; color: white; border-radius: 4px; font-size: 10px; font-weight: 600; text-decoration: none;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <button type="button" onclick="openLpmcDocHistoryModal('{{ $qDoc['doc_type'] }}', '{{ $quarter }}', '{{ addslashes($qDoc['label']) }}')" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 10px; font-weight: 600; cursor: pointer;">
                                                    <i class="fas fa-clock-rotate-left"></i> History
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                    <input
                                        id="{{ $inputId }}"
                                        type="file"
                                        name="document[]"
                                        multiple
                                        required
                                        @disabled($disableUploadInput)
                                        class="dashboard-file-input"
                                        data-max-size-kb="25600"
                                        style="width: 100%; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
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
                                @if (Auth::user()->isSuperAdmin() && $doc)
                                    @foreach ($doc->files ?? [] as $docFile)
                                        <form id="lpmc-delete-file-{{ $docFile->id }}" method="POST" action="{{ route('local-project-monitoring-committee.delete-file', ['lpmc' => $officeName, 'fileId' => $docFile->id]) }}" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="lpmcItemHistoryModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1250; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: white; border-radius: 12px; width: min(900px, 94vw); max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); color: white; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-history" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 id="lpmcItemHistoryTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: white;">Item History</h2>
                </div>
                <button type="button" onclick="closeLpmcItemHistoryModal()" aria-label="Close" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; font-size: 22px; display: inline-flex; align-items: center; justify-content: center; transition: background 0.2s;">
                    &times;
                </button>
            </div>
            <div id="lpmcItemHistoryBody" style="padding: 20px 24px; overflow-y: auto; max-height: 65vh;">
                <!-- Populated dynamically via JS -->
            </div>
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

        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            const requestedQuarter = (params.get('quarter') || '').toUpperCase();
            const requestedDocument = (params.get('document') || '').toLowerCase();
            const requestedYear = params.get('year') || '';

            if (requestedQuarter) {
                const card = document.getElementById('lpmc-quarter-' + requestedQuarter);
                const panel = document.getElementById('lpmc-' + requestedQuarter);
                const button = document.querySelector('[data-target="lpmc-' + requestedQuarter + '"]');
                if (card && panel && button) {
                    panel.style.display = 'block';
                    button.setAttribute('aria-expanded', 'true');
                    window.setTimeout(function () {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
                return;
            }

            if (requestedDocument && requestedYear) {
                const documentCard = document.getElementById('lpmc-document-' + requestedDocument + '-' + requestedYear);
                if (documentCard) {
                    window.setTimeout(function () {
                        documentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
            }
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
                const count = fileInput.files.length;
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';

                filenameDiv.replaceChildren();
                if (count === 1) {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-file';
                    icon.style.marginRight = '4px';
                    filenameDiv.appendChild(icon);
                    filenameDiv.appendChild(document.createTextNode(`Selected: ${fileInput.files[0].name}`));
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-copy';
                    icon.style.marginRight = '4px';
                    filenameDiv.appendChild(icon);
                    filenameDiv.appendChild(document.createTextNode(`Selected ${count} files: `));

                    const names = Array.from(fileInput.files).map(f => f.name).join(', ');
                    const namesSpan = document.createElement('span');
                    namesSpan.style.fontWeight = '600';
                    namesSpan.textContent = names;
                    filenameDiv.appendChild(namesSpan);
                }
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

        /* FUR Timeline modal styles for per-item history */
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

        .timeline-bullet.upload, .timeline-bullet.uploaded { border-color: #10b981; background: #10b981; }
        .timeline-bullet.validated, .timeline-bullet.approved { border-color: #3b82f6; background: #3b82f6; }
        .timeline-bullet.return, .timeline-bullet.returned { border-color: #ef4444; background: #ef4444; }
        .timeline-bullet.update { border-color: #6b7280; background: #6b7280; }

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
            text-align: left;
        }

        .timeline-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(2,6,23,0.08);
        }

        .timeline-meta { display:flex; gap:8px; align-items:center; font-size:12px; color:#6b7280; margin-bottom:6px; }
        .avatar {
            width:28px; height:28px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:white; flex-shrink:0;
        }

        .doc-chip {
            display:inline-block; padding:4px 8px; font-size:11px; font-weight:700; border-radius:999px; background:#f1f5f9; color:#0f172a; margin-left:6px;
        }

        .action-pill { display:inline-block; padding:6px 8px; font-size:11px; font-weight:700; border-radius:999px; color:white; }
        .action-upload, .action-uploaded { background: #10b981; }
        .action-validated, .action-approved, .action-validateddilgpo, .action-validateddilgro { background: #3b82f6; }
        .action-return, .action-returned { background: #ef4444; }
        .action-deleted, .action-delete { background: #ef4444; }
        .action-update { background: #6b7280; }

        .timeline-title { display:flex; gap:8px; align-items:center; font-weight:700; color:#0f172a; margin-bottom:6px; font-size:13px; }
        .timeline-remarks { white-space: pre-wrap; color: #374151; font-size: 13px; margin-top: 6px; }

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

    @php
        $lpmcActivityLogsData = collect($activityLogs ?? [])->map(function ($log) use ($usersById) {
            $u = $log['user_id'] && isset($usersById[$log['user_id']]) ? $usersById[$log['user_id']] : null;
            $userName = $u ? trim($u->fname . ' ' . $u->lname) : 'Unknown';
            $userAgency = $u ? ($u->province ?? '') : '';
            return [
                'timestamp' => $log['timestamp'] ? $log['timestamp']->format('M d, Y h:i A') : '—',
                'action' => $log['action'] ?? '',
                'document' => $log['document'] ?? '',
                'user_name' => $userName,
                'user_agency' => $userAgency,
                'remarks' => $log['remarks'] ?? '',
            ];
        })->values();
    @endphp

    <script>
        const lpmcActivityLogsData = @json($lpmcActivityLogsData);
        const lpmcActivityLogModal = document.getElementById('lpmcActivityLogModal');
        const lpmcActivityLogBackdrop = document.getElementById('lpmcActivityLogBackdrop');
        const lpmcActivityLogFab = document.getElementById('lpmcActivityLogFab');
        const lpmcActivityLogClose = document.getElementById('lpmcActivityLogClose');

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function itemMatchesLog(log, targetDocType, targetYq, targetTitle) {
            const reqDocType = String(targetDocType || '').toLowerCase().trim();
            const reqYq = String(targetYq || '').toLowerCase().trim();
            const reqTitle = String(targetTitle || '').toLowerCase().trim();

            const logDocType = String(log.doc_type || '').toLowerCase().trim();
            const logYear = String(log.year || '').toLowerCase().trim();
            const logQuarter = String(log.quarter || '').toLowerCase().trim();
            const logDocLabel = String(log.document || '').toLowerCase().trim();

            // 1. Match Document Type
            let docTypeMatched = false;
            if (reqDocType) {
                if (logDocType) {
                    docTypeMatched = (logDocType === reqDocType);
                } else {
                    if (reqDocType === 'eo' && logDocLabel.includes('executive order')) docTypeMatched = true;
                    else if (reqDocType === 'awfp' && (logDocLabel.includes('work and financial') || logDocLabel.includes('awfp'))) docTypeMatched = true;
                    else if (reqDocType === 'mep' && (logDocLabel.includes('monitoring and evaluation') || logDocLabel.includes('mep'))) docTypeMatched = true;
                    else if (reqDocType === 'meetings' && logDocLabel.includes('meeting')) docTypeMatched = true;
                    else if (reqDocType === 'monitoring' && logDocLabel.includes('monitoring conducted')) docTypeMatched = true;
                    else if (reqDocType === 'training' && logDocLabel.includes('training')) docTypeMatched = true;
                }
            } else if (reqTitle) {
                docTypeMatched = logDocLabel.includes(reqTitle);
            } else {
                docTypeMatched = true;
            }

            if (!docTypeMatched) return false;

            // 2. Match Year or Quarter
            if (reqYq) {
                let yqMatched = false;
                if (logYear && logYear === reqYq) yqMatched = true;
                else if (logQuarter && logQuarter === reqYq) yqMatched = true;
                else if (logDocLabel.includes(reqYq)) yqMatched = true;

                if (!yqMatched) return false;
            }

            return true;
        }

        function openLpmcDocHistoryModal(docType, yearOrQuarter, title) {
            const modal = document.getElementById('lpmcItemHistoryModal');
            const titleEl = document.getElementById('lpmcItemHistoryTitle');
            const bodyEl = document.getElementById('lpmcItemHistoryBody');
            if (!modal || !titleEl || !bodyEl) return;

            const displayTitle = title || 'Document Item';
            const subTitle = yearOrQuarter ? ` (${yearOrQuarter})` : '';
            titleEl.textContent = `${displayTitle}${subTitle} — History`;

            const matchedLogs = lpmcActivityLogsData.filter(log => itemMatchesLog(log, docType, yearOrQuarter, title));

            if (!matchedLogs.length) {
                bodyEl.innerHTML = `
                    <div style="padding: 16px; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                        No activity logs found for this item.
                    </div>
                `;
            } else {
                let html = '<div class="timeline">';
                matchedLogs.forEach((log, idx) => {
                    const side = (idx % 2 === 0) ? 'left' : 'right';
                    const ts = log.timestamp || '—';
                    const userName = log.user_name || 'Unknown';
                    const userAgency = log.user_agency || '';
                    const userDisplay = userName + (userAgency ? ` (${userAgency})` : '');
                    const initials = (userName || 'U').split(' ').map(s => s[0] || '').join('').substring(0, 2).toUpperCase();

                    let avatarBg = '#6b7280';
                    if (userAgency.toLowerCase().includes('regional')) avatarBg = '#0ea5a9';
                    if (userAgency.toLowerCase().includes('provincial')) avatarBg = '#f59e0b';
                    if (userAgency.toLowerCase().includes('lgu')) avatarBg = '#002C76';

                    const actionStr = String(log.action || 'update');
                    const actionKey = actionStr.toLowerCase().replace(/[^a-z0-9]/g, '');
                    const actionLabel = actionStr.toUpperCase();
                    const docChipLabel = displayTitle + (yearOrQuarter ? ` • ${yearOrQuarter}` : '');

                    html += `
                        <div class="timeline-item ${side}">
                            <div class="timeline-bullet ${escapeHtml(actionKey)}" aria-hidden="true"></div>
                            <div class="timeline-card">
                                <div class="timeline-meta">
                                    <span class="avatar" style="background:${avatarBg}">${escapeHtml(initials)}</span>
                                    <div style="margin-left:8px; display:inline-block; vertical-align:middle;">
                                        <div style="font-size:12px;color:#6b7280">${escapeHtml(ts)}</div>
                                        <div style="font-weight:700;color:#0f172a">${escapeHtml(userDisplay)}</div>
                                    </div>
                                    <span class="doc-chip">${escapeHtml(docChipLabel)}</span>
                                </div>
                                <div class="timeline-title">
                                    <span class="action-pill action-${escapeHtml(actionKey)}">${escapeHtml(actionLabel)}</span>
                                </div>
                                <div class="timeline-remarks">
                                    <strong>Remarks :</strong> ${escapeHtml(log.remarks || '—')}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                bodyEl.innerHTML = html;
            }

            modal.style.display = 'flex';
        }

        function closeLpmcItemHistoryModal() {
            const modal = document.getElementById('lpmcItemHistoryModal');
            if (modal) modal.style.display = 'none';
        }

        window.addEventListener('click', function(e) {
            const itemModal = document.getElementById('lpmcItemHistoryModal');
            if (e.target === itemModal) {
                closeLpmcItemHistoryModal();
            }
        });

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
