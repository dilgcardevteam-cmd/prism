@extends('layouts.dashboard')

@section('title', 'Pre-Implementation Documents')
@section('page-title', 'Pre-Implementation Documents')

@section('content')
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div>
            <h1>Update - {{ $project->project_code }}</h1>
            <p>Upload and validate pre-implementation documents for this project.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('pre-implementation-documents.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
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
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Code</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->project_code }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Funding Year</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->funding_year ?: '-' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Fund Source</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->fund_source ?: 'Unspecified' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->province ?: '-' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">City/Municipality</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->city_municipality ?: '-' }}</p>
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Title</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $project->project_title ?: '-' }}</p>
            </div>
        </div>
    </div>

    @php
        $currentUser = Auth::user();
        $isDilg = strtoupper(trim((string) ($currentUser->agency ?? ''))) === 'DILG';
        $isRegionalDilg = $isDilg && strtolower(trim((string) ($currentUser->province ?? ''))) === 'regional office';
        $isProvincialDilg = $isDilg && !$isRegionalDilg;

        $resolveUserName = function ($id) use ($usersById) {
            if (!$id) {
                return 'Unknown';
            }

            $user = $usersById[$id] ?? null;
            if (!$user) {
                return 'Unknown';
            }

            return trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: 'Unknown';
        };

        $asLocalTime = function ($value) {
            if (!$value) {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return \Carbon\Carbon::instance($value)->setTimezone(config('app.timezone'));
            }

            return \Carbon\Carbon::parse($value)->setTimezone(config('app.timezone'));
        };
    @endphp

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <div style="margin-bottom: 18px;">
            <h2 style="color: #002C76; font-size: 18px; margin: 0; font-weight: 600;">Uploading of Documents</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
            @foreach ($documentFields as $field => $label)
                @php
                    $fileRecord = $documentFilesByType[$field] ?? null;
                    $path = $fileRecord->file_path ?? ($document->{$field} ?? null);
                    $fileName = $path ? basename($path) : null;

                    $hasFile = !empty($path);
                    $fileViewUrl = $hasFile ? route('pre-implementation-documents.document', [$project->project_code, $field]) : null;
                    $isReturned = $fileRecord && $fileRecord->status === 'returned';
                    $isApprovedRo = $fileRecord && $fileRecord->approved_at_dilg_ro;
                    $isPendingRo = $fileRecord && $fileRecord->approved_at_dilg_po && !$fileRecord->approved_at_dilg_ro;
                    $disableUpload = $hasFile || $isRegionalDilg;
                    $uploadDisabledMessage = $isRegionalDilg && !$hasFile
                        ? 'Regional Office cannot upload files. Choose file is disabled.'
                        : null;

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

                    $inputId = 'pre-impl-doc-input-' . $field;
                    $buttonId = 'pre-impl-doc-btn-' . $field;
                    $filenameId = 'pre-impl-doc-file-' . $field;

                    $uploadedTime = $asLocalTime($fileRecord->uploaded_at ?? $fileRecord->created_at ?? $fileRecord->updated_at ?? null);
                    $uploaderName = $resolveUserName($fileRecord->uploaded_by ?? null);
                    $poValidatedAt = $asLocalTime($fileRecord->approved_at_dilg_po ?? null);
                    $poApproverName = $resolveUserName($fileRecord->approved_by_dilg_po ?? null);
                    $roValidatedAt = $asLocalTime($fileRecord->approved_at_dilg_ro ?? null);
                    $roApproverName = $resolveUserName($fileRecord->approved_by_dilg_ro ?? null);

                    $uploaderUser = $fileRecord && $fileRecord->uploaded_by && isset($usersById[$fileRecord->uploaded_by])
                        ? $usersById[$fileRecord->uploaded_by]
                        : null;
                    $isDilgMountainUploader = $uploaderUser
                        && strtoupper(trim((string) ($uploaderUser->agency ?? ''))) === 'DILG'
                        && strtolower(trim((string) ($uploaderUser->province ?? ''))) === 'mountain province';

                    $isUploadedAndPoValidatedBySameUser = $fileRecord
                        && $uploadedTime
                        && $poValidatedAt
                        && $isDilgMountainUploader
                        && !empty($fileRecord->uploaded_by)
                        && !empty($fileRecord->approved_by_dilg_po)
                        && (string) $fileRecord->uploaded_by === (string) $fileRecord->approved_by_dilg_po
                        && $uploadedTime->getTimestamp() === $poValidatedAt->getTimestamp();

                    $returnedAt = $asLocalTime($fileRecord->approved_at ?? null);
                    $returnedByName = $resolveUserName($fileRecord?->approved_by_dilg_ro ?: $fileRecord?->approved_by_dilg_po ?: $fileRecord?->approved_by);
                    $returnedByLevel = null;
                    if (!empty($fileRecord?->approved_by_dilg_ro)) {
                        $returnedByLevel = 'DILG Regional Office';
                    } elseif (!empty($fileRecord?->approved_by_dilg_po)) {
                        $returnedByLevel = 'DILG Provincial Office';
                    }
                    $returnedRemarks = trim((string) ($fileRecord->approval_remarks ?? ''));
                    $returnedRemarks = $returnedRemarks !== '' ? $returnedRemarks : null;

                    $timelineEvents = [];
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

                    if ($roValidatedAt) {
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

                    $isForRegionalValidation = $fileRecord && $fileRecord->approved_at_dilg_po && !$fileRecord->approved_at_dilg_ro;
                    $isApproved = $fileRecord && $fileRecord->status === 'approved';
                    $hideReturnButton = $isProvincialDilg && $isReturned;
                    $showApprovalButtons = $fileRecord
                        && $hasFile
                        && $isDilg
                        && !($isProvincialDilg && $isForRegionalValidation)
                        && !($isRegionalDilg && $isReturned)
                        && !($isRegionalDilg && $isApproved)
                        && !($isProvincialDilg && $isApproved);
                @endphp

                <form method="POST" action="{{ route('pre-implementation-documents.save', $project->project_code) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 18px; border-radius: 8px; background-color: #f9fafb;">
                    @csrf

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                        <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0;">{{ $label }}</label>
                        <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border-radius: 20px; font-size: 10px; font-weight: 600;">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px; min-height: 40px;">
                        @if (empty($timelineEvents))
                            <div style="color: #9ca3af;">No upload activity yet.</div>
                        @endif
                        @foreach ($timelineEvents as $timelineEvent)
                            <div style="display: block; font-size: {{ $timelineEvent['font_size'] }}; font-weight: {{ $timelineEvent['font_weight'] }}; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                {{ $timelineEvent['message'] }}
                            </div>
                        @endforeach
                    </div>

                    <div class="pre-impl-upload-shell{{ $disableUpload ? ' is-disabled' : '' }}">
                        <input
                            id="{{ $inputId }}"
                            type="file"
                            name="{{ $field }}"
                            accept=".pdf,application/pdf"
                            required
                            @disabled($disableUpload)
                            class="pre-impl-upload-input"
                            data-pre-impl-upload-input
                            data-button-id="{{ $buttonId }}"
                            data-filename-id="{{ $filenameId }}"
                            onchange="showPreImplementationSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                        >
                        @unless ($hasFile)
                            <label for="{{ $inputId }}" class="pre-impl-upload-dropzone{{ $disableUpload ? ' is-disabled' : '' }}" tabindex="{{ $disableUpload ? '-1' : '0' }}" role="button" aria-controls="{{ $inputId }}">
                                <span class="pre-impl-upload-dropzone-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </span>
                                <span class="pre-impl-upload-dropzone-title">Browse Files to upload</span>
                                <span class="pre-impl-upload-dropzone-copy">PDF only</span>
                            </label>
                        @endunless

                        @if ($hasFile && $fileViewUrl)
                            <a
                                id="{{ $filenameId }}"
                                href="{{ $fileViewUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="pre-impl-upload-filebar pre-impl-upload-filelink is-selected"
                                data-empty-text="{{ $fileName ?: 'View current file' }}"
                                data-locked="1"
                                title="View {{ $fileName ?: 'current file' }}"
                            >
                                <span class="pre-impl-upload-fileicon">
                                    <i class="far fa-file-alt"></i>
                                </span>
                                <span class="pre-impl-upload-filename" data-file-name>{{ $fileName ?: 'View current file' }}</span>
                            </a>
                        @else
                            <div id="{{ $filenameId }}" class="pre-impl-upload-filebar" data-empty-text="No selected file">
                                <span class="pre-impl-upload-fileicon">
                                    <i class="far fa-file-alt"></i>
                                </span>
                                <span class="pre-impl-upload-filename" data-file-name>No selected file</span>
                                <button
                                    type="button"
                                    class="pre-impl-upload-clear"
                                    data-file-clear
                                    hidden
                                    @disabled($disableUpload)
                                    onclick="clearPreImplementationFileSelection('{{ $inputId }}', '{{ $buttonId }}', '{{ $filenameId }}')"
                                    aria-label="Clear selected file"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    @if ($uploadDisabledMessage)
                        <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                            {{ $uploadDisabledMessage }}
                        </div>
                    @endif

                    @error($field)
                        <div style="margin-bottom: 8px; color: #dc2626; font-size: 11px;">{{ $message }}</div>
                    @enderror

                    <button
                        type="submit"
                        id="{{ $buttonId }}"
                        style="width: 100%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; opacity: 0; pointer-events: none; transition: all 0.3s ease;"
                    >
                        Upload
                    </button>

                    @if ($isRegionalDilg && $hasFile && (!$fileRecord || !$fileRecord->approved_at_dilg_po))
                        <div style="font-size: 11px; color: #92400e; margin-top: 8px;">
                            Waiting for DILG Provincial validation.
                        </div>
                    @endif

                    @if ($showApprovalButtons)
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <button type="button" onclick="openPreImplementationApprovalModal('{{ $field }}', 'approve')" style="flex: 1; padding: 8px 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                Approve
                            </button>
                            @if (!$hideReturnButton)
                                <button type="button" onclick="openPreImplementationApprovalModal('{{ $field }}', 'return')" style="flex: 1; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                    Return
                                </button>
                            @endif
                        </div>
                    @endif
                </form>
            @endforeach
        </div>

        <div style="margin-top: 12px; font-size: 11px; color: #6b7280;">
            Accepted format: PDF only. Maximum file size per document: 15 MB.
        </div>
    </div>

    <div id="preImplActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="preImplActivityLogTitle" aria-hidden="true">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px 16px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); border-radius: 12px 12px 0 0; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clipboard-list" style="color: white; font-size: 14px;"></i>
                    </div>
                    <h3 id="preImplActivityLogTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                </div>
                <button type="button" id="preImplActivityLogClose" aria-label="Close activity logs" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
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

    <div id="preImplActivityLogBackdrop" aria-hidden="true"></div>

    <button id="preImplActivityLogFab" type="button" aria-controls="preImplActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
        <span>Activity Logs</span>
    </button>

    <style>
        #preImplActivityLogBackdrop {
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

        #preImplActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #preImplActivityLogModal {
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
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1200;
        }

        #preImplActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-pre-impl-logs {
            overflow: hidden;
        }

        #preImplActivityLogFab {
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

        #preImplActivityLogFab:hover {
            background-color: #003d9e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.4);
        }

        #preImplActivityLogFab:active {
            transform: translateY(0);
        }

        #preImplActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        .pre-impl-upload-shell {
            margin-bottom: 8px;
        }

        .pre-impl-upload-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            clip-path: inset(50%);
            white-space: nowrap;
            border: 0;
        }

        .pre-impl-upload-dropzone {
            display: grid;
            justify-items: center;
            gap: 8px;
            padding: 26px 16px;
            margin-bottom: 10px;
            border: 2px dashed #60a5fa;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .pre-impl-upload-dropzone:hover {
            border-color: #2563eb;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
            transform: translateY(-1px);
        }

        .pre-impl-upload-dropzone.is-disabled {
            cursor: not-allowed;
            opacity: 0.65;
            box-shadow: none;
            transform: none;
        }

        .pre-impl-upload-dropzone-icon {
            width: 58px;
            height: 58px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.25);
        }

        .pre-impl-upload-dropzone-title {
            display: block;
            color: #111827;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        .pre-impl-upload-dropzone-copy {
            display: block;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .pre-impl-upload-filebar {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 10px 12px;
            border: 1px solid #dbe7ff;
            border-radius: 12px;
            background: #edf4ff;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .pre-impl-upload-filebar.is-selected {
            border-color: #93c5fd;
            background: #dbeafe;
        }

        .pre-impl-upload-filelink {
            text-decoration: none;
            cursor: pointer;
        }

        .pre-impl-upload-filelink:hover {
            border-color: #60a5fa;
            background: #dbeafe;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.10);
        }

        .pre-impl-upload-filelink:focus-visible {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        }

        .pre-impl-upload-fileicon {
            width: 24px;
            height: 24px;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .pre-impl-upload-filename {
            flex: 1 1 auto;
            min-width: 0;
            color: #334155;
            font-size: 13px;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pre-impl-upload-clear {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 999px;
            background: transparent;
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .pre-impl-upload-clear:hover:not(:disabled) {
            background: rgba(15, 23, 42, 0.08);
            color: #b91c1c;
        }

        .pre-impl-upload-clear:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        @media (max-width: 640px) {
            .pre-impl-upload-dropzone {
                padding: 22px 14px;
            }

            .pre-impl-upload-dropzone-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .pre-impl-upload-dropzone-title {
                font-size: 16px;
            }

            #preImplActivityLogFab span { display: none; }
            #preImplActivityLogFab { padding: 14px; border-radius: 50%; }
        }

        @media (max-width: 900px) {
            div[style*="grid-template-columns: repeat(3, minmax(260px, 1fr));"] {
                grid-template-columns: repeat(2, minmax(240px, 1fr)) !important;
            }
        }

        @media (max-width: 768px) {
            #preImplActivityLogModal {
                width: 94vw;
            }

            div[style*="grid-template-columns: repeat(3, minmax(260px, 1fr));"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script>
        function syncPreImplementationUploadUi(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameBar = document.getElementById(filenameId);
            const filenameText = filenameBar ? filenameBar.querySelector('[data-file-name]') : null;
            const clearBtn = filenameBar ? filenameBar.querySelector('[data-file-clear]') : null;
            if (!saveBtn || !filenameBar || !filenameText) return;

            const isLocked = filenameBar.dataset.locked === '1';
            if (isLocked) {
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                filenameBar.classList.add('is-selected');
                if (clearBtn) {
                    clearBtn.hidden = true;
                }
                return;
            }

            const hasFile = !!(fileInput && fileInput.files && fileInput.files.length > 0);
            const emptyText = filenameBar.dataset.emptyText || 'No selected file';

            if (hasFile) {
                const fileName = fileInput.files[0].name;
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                filenameText.textContent = fileName;
                filenameBar.classList.add('is-selected');
                if (clearBtn) {
                    clearBtn.hidden = false;
                }
                return;
            }

            saveBtn.style.opacity = '0';
            saveBtn.style.pointerEvents = 'none';
            filenameText.textContent = emptyText;
            filenameBar.classList.remove('is-selected');
            if (clearBtn) {
                clearBtn.hidden = true;
            }
        }

        function showPreImplementationSaveButton(fileInput, buttonId, filenameId) {
            syncPreImplementationUploadUi(fileInput, buttonId, filenameId);
        }

        function clearPreImplementationFileSelection(inputId, buttonId, filenameId) {
            const fileInput = document.getElementById(inputId);
            if (!(fileInput instanceof HTMLInputElement) || fileInput.disabled) {
                return;
            }

            fileInput.value = '';
            syncPreImplementationUploadUi(fileInput, buttonId, filenameId);
        }

        document.querySelectorAll('[data-pre-impl-upload-input]').forEach((fileInput) => {
            const buttonId = fileInput.getAttribute('data-button-id') || '';
            const filenameId = fileInput.getAttribute('data-filename-id') || '';
            syncPreImplementationUploadUi(fileInput, buttonId, filenameId);
        });
    </script>

    <script>
        document.querySelectorAll('.pre-impl-upload-dropzone').forEach((dropzone) => {
            dropzone.addEventListener('keydown', (event) => {
                if ((event.key !== 'Enter' && event.key !== ' ') || dropzone.classList.contains('is-disabled')) {
                    return;
                }

                event.preventDefault();
                const inputId = dropzone.getAttribute('for');
                const input = inputId ? document.getElementById(inputId) : null;
                if (input instanceof HTMLInputElement && !input.disabled) {
                    input.click();
                }
            });
        });
    </script>

    <script>
        const preImplActivityLogModal = document.getElementById('preImplActivityLogModal');
        const preImplActivityLogBackdrop = document.getElementById('preImplActivityLogBackdrop');
        const preImplActivityLogFab = document.getElementById('preImplActivityLogFab');
        const preImplActivityLogClose = document.getElementById('preImplActivityLogClose');

        function setPreImplActivityLogVisibility(isVisible) {
            if (!preImplActivityLogModal || !preImplActivityLogBackdrop || !preImplActivityLogFab) {
                return;
            }

            preImplActivityLogModal.classList.toggle('is-visible', isVisible);
            preImplActivityLogBackdrop.classList.toggle('is-visible', isVisible);
            document.body.classList.toggle('modal-open-pre-impl-logs', isVisible);
            preImplActivityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            preImplActivityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            preImplActivityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            preImplActivityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

            const labelSpan = preImplActivityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Hide Activity Logs' : 'Activity Logs';
            }

            if (isVisible && preImplActivityLogClose) {
                preImplActivityLogClose.focus();
            }
        }

        if (preImplActivityLogFab && preImplActivityLogModal && preImplActivityLogBackdrop) {
            preImplActivityLogFab.addEventListener('click', () => {
                const isOpen = preImplActivityLogModal.classList.contains('is-visible');
                setPreImplActivityLogVisibility(!isOpen);
            });

            preImplActivityLogBackdrop.addEventListener('click', () => {
                setPreImplActivityLogVisibility(false);
            });

            if (preImplActivityLogClose) {
                preImplActivityLogClose.addEventListener('click', () => {
                    setPreImplActivityLogVisibility(false);
                });
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && preImplActivityLogModal && preImplActivityLogModal.classList.contains('is-visible')) {
                setPreImplActivityLogVisibility(false);
            }
        });
    </script>

    <div id="preImplApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
            <h3 id="preImplApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
            <form id="preImplApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="preImplApprovalAction">
                <textarea id="preImplApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                    <button type="button" onclick="closePreImplementationApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                    <button type="submit" id="preImplApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const preImplValidateBaseUrl = @json(url('/pre-implementation-documents/projects/' . $project->project_code . '/validate'));

        function openPreImplementationApprovalModal(documentType, action) {
            const modal = document.getElementById('preImplApprovalModal');
            const form = document.getElementById('preImplApprovalForm');
            const title = document.getElementById('preImplApprovalTitle');
            const actionInput = document.getElementById('preImplApprovalAction');
            const remarks = document.getElementById('preImplApprovalRemarks');
            const submitBtn = document.getElementById('preImplApprovalSubmit');

            form.action = preImplValidateBaseUrl + '/' + encodeURIComponent(documentType);
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

        function closePreImplementationApprovalModal() {
            document.getElementById('preImplApprovalModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const modal = document.getElementById('preImplApprovalModal');
            if (event.target === modal) {
                closePreImplementationApprovalModal();
            }
        });
    </script>
@endsection
