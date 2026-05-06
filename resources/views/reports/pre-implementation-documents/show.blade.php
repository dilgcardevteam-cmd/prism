@extends('layouts.dashboard')

@section('title', $pageConfig['title'])
@section('page-title', $pageConfig['title'])

@section('content')
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
        <div>
            <h1>Update - {{ $project->project_code }}</h1>
            <p>{{ $pageConfig['show_description'] }}</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route($routeConfig['index'], $scopeQuery) }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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

        $documentGroupMeta = [
            'Initial Project Documents' => [
                'icon' => 'fas fa-folder-open',
                'accent' => '#2563eb',
                'soft' => '#eff6ff',
                'border' => '#bfdbfe',
                'subtitle' => 'Base project setup and initial fund-transfer records.',
            ],
            'Permits and Certifications' => [
                'icon' => 'fas fa-stamp',
                'accent' => '#7c3aed',
                'soft' => '#f5f3ff',
                'border' => '#ddd6fe',
                'subtitle' => 'Regulatory clearances, ownership, and supporting certifications.',
            ],
            'Contract Implementation Documents' => [
                'icon' => 'fas fa-file-signature',
                'accent' => '#d97706',
                'soft' => '#fff7ed',
                'border' => '#fed7aa',
                'subtitle' => 'Procurement and contract award documentation.',
            ],
            'Implementation Documents' => [
                'icon' => 'fas fa-person-digging',
                'accent' => '#059669',
                'soft' => '#ecfdf5',
                'border' => '#a7f3d0',
                'subtitle' => 'Implementation-phase records, adjustments, and project actions.',
            ],
        ];
    @endphp

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <div style="margin-bottom: 18px;">
            <h2 style="color: #002C76; font-size: 18px; margin: 0; font-weight: 600;">Uploading of Documents</h2>
        </div>

        @foreach ($documentGroups as $groupTitle => $groupFields)
            @php
                $groupMeta = $documentGroupMeta[$groupTitle] ?? [
                    'icon' => 'fas fa-folder',
                    'accent' => '#1d4ed8',
                    'soft' => '#eff6ff',
                    'border' => '#bfdbfe',
                    'subtitle' => 'Project document requirements.',
                ];
            @endphp
            <div style="{{ $loop->first ? '' : 'margin-top: 28px;' }}">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; padding: 14px 16px; border: 1px solid {{ $groupMeta['border'] }}; border-radius: 10px; background: linear-gradient(180deg, {{ $groupMeta['soft'] }} 0%, #ffffff 100%);">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background-color: {{ $groupMeta['accent'] }}; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                            <i class="{{ $groupMeta['icon'] }}"></i>
                        </div>
                        <div style="min-width: 0;">
                            <h3 style="margin: 0; color: #111827; font-size: 16px; font-weight: 700;">{{ $groupTitle }}</h3>
                            <div style="margin-top: 3px; color: #4b5563; font-size: 12px; line-height: 1.4;">{{ $groupMeta['subtitle'] }}</div>
                        </div>
                    </div>
                    <div style="display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 28px; padding: 0 10px; border-radius: 999px; background-color: #ffffff; border: 1px solid {{ $groupMeta['border'] }}; color: {{ $groupMeta['accent'] }}; font-size: 12px; font-weight: 700; flex-shrink: 0;">
                        {{ count($groupFields) }}
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, minmax(210px, 1fr)); gap: 14px;">
            @foreach ($groupFields as $field)
                @php
                    $label = $documentFields[$field] ?? $field;
                @endphp
                @php
                    $fileRecordsForField = $documentFilesByType[$field] ?? collect();
                    $fileRecord = $latestDocumentFilesByType[$field] ?? null;
                    $path = $fileRecord->file_path ?? ($document->{$field} ?? null);
                    $fileName = $path ? basename($path) : null;

                    $isMultiUpload = in_array($field, $multiUploadDocumentTypes, true);
                    $uploadCount = $fileRecordsForField instanceof \Illuminate\Support\Collection ? $fileRecordsForField->count() : 0;
                    $hasFile = !empty($path);
                    $fileViewUrl = $hasFile ? route($routeConfig['document'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) : null;
                    $isReturned = $fileRecord && $fileRecord->status === 'returned';
                    $isApprovedRo = $fileRecord && $fileRecord->approved_at_dilg_ro;
                    $isPendingRo = $fileRecord && $fileRecord->approved_at_dilg_po && !$fileRecord->approved_at_dilg_ro;
                    $disableUpload = $isMultiUpload ? $isRegionalDilg : ($hasFile || $isRegionalDilg);
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
                    $pickerId = 'pre-impl-doc-picker-' . $field;

                    $uploadedTime = $asLocalTime($fileRecord->uploaded_at ?? $fileRecord->created_at ?? $fileRecord->updated_at ?? null);
                    $uploaderName = $resolveUserName($fileRecord->uploaded_by ?? null);
                    $poValidatedAt = $asLocalTime($fileRecord->approved_at_dilg_po ?? null);
                    $poApproverName = $resolveUserName($fileRecord->approved_by_dilg_po ?? null);
                    $roValidatedAt = $asLocalTime($fileRecord->approved_at_dilg_ro ?? null);
                    $roApproverName = $resolveUserName($fileRecord->approved_by_dilg_ro ?? null);

                    $uploaderUser = $fileRecord && $fileRecord->uploaded_by && isset($usersById[$fileRecord->uploaded_by])
                        ? $usersById[$fileRecord->uploaded_by]
                        : null;
                    $isProvincialDilgUploader = $uploaderUser
                        && method_exists($uploaderUser, 'isDilgUser')
                        && method_exists($uploaderUser, 'isRegionalOfficeAssignment')
                        && $uploaderUser->isDilgUser()
                        && !$uploaderUser->isRegionalOfficeAssignment();

                    $isUploadedAndPoValidatedBySameUser = $fileRecord
                        && $uploadedTime
                        && $poValidatedAt
                        && $isProvincialDilgUploader
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

                @if ($isMultiUpload)
                    <button
                        type="button"
                        onclick="openPreImplementationMultiUploadModal('{{ $field }}')"
                        style="width: 100%; text-align: left; border: 1px dashed #cbd5f5; padding: 18px; border-radius: 8px; background-color: #f9fafb; cursor: pointer;"
                    >
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                            <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0; cursor: pointer;">{{ $label }}</label>
                            <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border-radius: 20px; font-size: 10px; font-weight: 600;">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $uploadCount }} {{ \Illuminate\Support\Str::plural('upload', $uploadCount) }}
                            </div>
                            <div style="display: inline-flex; align-items: center; gap: 6px; color: #002C76; font-size: 11px; font-weight: 700;">
                                <i class="fas fa-layer-group"></i>
                                <span>Manage Files</span>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #6b7280; min-height: 40px;">
                            @if (empty($timelineEvents))
                                <div style="color: #9ca3af;">No upload activity yet.</div>
                            @else
                                <div style="display: block; font-size: {{ $timelineEvents[0]['font_size'] ?? '11px' }}; font-weight: {{ $timelineEvents[0]['font_weight'] ?? 'normal' }}; color: {{ $timelineEvents[0]['color'] ?? '#6b7280' }};">
                                    {{ $timelineEvents[0]['message'] ?? '' }}
                                </div>
                            @endif
                        </div>
                    </button>
                @else
                <form method="POST" action="{{ route($routeConfig['save'], array_merge(['projectCode' => $project->project_code], $scopeQuery)) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 18px; border-radius: 8px; background-color: #f9fafb;">
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
                            data-picker-id="{{ $pickerId }}"
                            onchange="showPreImplementationSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                        >
                        @unless ($hasFile)
                            <button id="{{ $pickerId }}" type="button" class="pre-impl-upload-dropzone{{ $disableUpload ? ' is-disabled' : '' }}" data-pre-impl-picker-button data-input-id="{{ $inputId }}" @disabled($disableUpload) aria-controls="{{ $inputId }}">
                                <span class="pre-impl-upload-dropzone-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </span>
                                <span class="pre-impl-upload-dropzone-title">Browse Files to upload</span>
                                <span class="pre-impl-upload-dropzone-copy">PDF only</span>
                            </button>
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
                                    <i class="fas fa-file-pdf"></i>
                                </span>
                                <span class="pre-impl-upload-filemeta">
                                    <span class="pre-impl-upload-fileeyebrow">Uploaded file</span>
                                    <span class="pre-impl-upload-filename" data-file-name>{{ $fileName ?: 'View current file' }}</span>
                                </span>
                            </a>
                        @else
                            <div id="{{ $filenameId }}" class="pre-impl-upload-filebar" data-empty-text="No selected file" hidden>
                                <span class="pre-impl-upload-fileicon">
                                    <i class="fas fa-file-pdf"></i>
                                </span>
                                <span class="pre-impl-upload-filemeta">
                                    <span class="pre-impl-upload-fileeyebrow">Selected file</span>
                                    <span class="pre-impl-upload-filename" data-file-name>No selected file</span>
                                </span>
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
                            <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) }}', 'approve')" style="flex: 1; padding: 8px 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                Approve
                            </button>
                            @if (!$hideReturnButton)
                                <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) }}', 'return')" style="flex: 1; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                    Return
                                </button>
                            @endif
                        </div>
                    @endif
                </form>
                @endif
            @endforeach
                </div>
            </div>
        @endforeach

        <div style="margin-top: 12px; font-size: 11px; color: #6b7280;">
            Accepted format: PDF only. Maximum file size per document: 15 MB.
        </div>
    </div>

    @foreach ($multiUploadDocumentTypes as $multiField)
        @php
            $multiLabel = $documentFields[$multiField] ?? $multiField;
            $multiFiles = $documentFilesByType[$multiField] ?? collect();
            $modalId = 'preImplMultiModal-' . $multiField;
        @endphp
        <div id="{{ $modalId }}" data-pre-impl-multi-modal style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1100; padding: 24px; overflow-y: auto;">
            <div style="max-width: 1100px; margin: 0 auto; background: #ffffff; border-radius: 14px; box-shadow: 0 18px 48px rgba(15, 23, 42, 0.22); overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                    <div>
                        <h3 style="margin: 0; color: #ffffff; font-size: 18px; font-weight: 700;">{{ $multiLabel }}</h3>
                        <div style="margin-top: 4px; color: rgba(255,255,255,0.82); font-size: 12px;">Multiple uploads supported. Latest files are shown first.</div>
                    </div>
                    <button type="button" onclick="closePreImplementationMultiUploadModal('{{ $multiField }}')" style="border: none; background: rgba(255,255,255,0.14); color: #ffffff; width: 34px; height: 34px; border-radius: 999px; cursor: pointer; font-size: 18px;">&times;</button>
                </div>

                <div style="padding: 20px 22px 22px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 28px; padding: 0 10px; border-radius: 999px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 700;">
                                {{ $multiFiles instanceof \Illuminate\Support\Collection ? $multiFiles->count() : 0 }}
                            </span>
                            <span style="font-size: 12px; color: #4b5563;">Uploads</span>
                        </div>

                        @if (!$isRegionalDilg)
                            <form method="POST" action="{{ route($routeConfig['upload_multi'], array_merge(['projectCode' => $project->project_code, 'documentType' => $multiField], $scopeQuery)) }}" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                @csrf
                                <input type="file" name="document_file" accept=".pdf,application/pdf" required class="dashboard-file-input" style="font-size: 12px;">
                                <button type="submit" style="padding: 9px 14px; background-color: #002C76; color: #ffffff; border: none; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 700;">
                                    Upload File
                                </button>
                            </form>
                        @else
                            <div style="font-size: 12px; color: #6b7280;">Regional Office cannot upload files.</div>
                        @endif
                    </div>

                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 760px;">
                            <thead>
                                <tr style="background-color: #f8fafc;">
                                    <th style="padding: 12px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Uploaded</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">File</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Uploaded By</th>
                                    <th style="padding: 12px 14px; text-align: right; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($multiFiles as $multiFile)
                                    @php
                                        $uploadedAt = $asLocalTime($multiFile->uploaded_at ?? $multiFile->created_at ?? null);
                                        $uploadedBy = $resolveUserName($multiFile->uploaded_by ?? null);
                                        $multiStatus = $multiFile->status ?? 'pending';
                                        $statusMeta = ['label' => 'Pending', 'bg' => '#fef3c7', 'color' => '#92400e'];
                                        if ($multiFile->approved_at_dilg_ro) {
                                            $statusMeta = ['label' => 'Approved', 'bg' => '#d1fae5', 'color' => '#065f46'];
                                        } elseif ($multiFile->status === 'returned') {
                                            $statusMeta = ['label' => 'Returned', 'bg' => '#fee2e2', 'color' => '#991b1b'];
                                        } elseif ($multiFile->approved_at_dilg_po) {
                                            $statusMeta = ['label' => 'For DILG RO Validation', 'bg' => '#dbeafe', 'color' => '#1d4ed8'];
                                        } elseif ($multiStatus === 'pending') {
                                            $statusMeta = ['label' => 'For DILG PO Validation', 'bg' => '#e0f2fe', 'color' => '#075985'];
                                        }
                                    @endphp
                                    <tr style="border-top: 1px solid #e5e7eb;">
                                        <td style="padding: 12px 14px; font-size: 12px; color: #374151; white-space: nowrap;">{{ $uploadedAt ? $uploadedAt->format('M d, Y h:i A') : '-' }}</td>
                                        <td style="padding: 12px 14px; font-size: 12px; color: #111827;">{{ basename($multiFile->file_path ?? ('File #' . $multiFile->id)) }}</td>
                                        <td style="padding: 12px 14px;">
                                            <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; background-color: {{ $statusMeta['bg'] }}; color: {{ $statusMeta['color'] }}; font-size: 11px; font-weight: 700;">
                                                {{ $statusMeta['label'] }}
                                            </span>
                                        </td>
                                        <td style="padding: 12px 14px; font-size: 12px; color: #374151;">{{ $uploadedBy }}</td>
                                        <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                            <a href="{{ route($routeConfig['document_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                <i class="fas fa-eye"></i>
                                                View
                                            </a>
                                            @if ($isDilg && !($isProvincialDilg && $multiFile->approved_at_dilg_po && !$multiFile->approved_at_dilg_ro) && !($isRegionalDilg && $multiFile->status === 'returned') && !($isRegionalDilg && $multiFile->status === 'approved') && !($isProvincialDilg && $multiFile->status === 'approved'))
                                                <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}', 'approve')" style="margin-left: 6px; padding: 7px 11px; background-color: #10b981; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700;">
                                                    Approve
                                                </button>
                                                @if (!($isProvincialDilg && $multiFile->status === 'returned'))
                                                    <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}', 'return')" style="margin-left: 6px; padding: 7px 11px; background-color: #dc2626; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700;">
                                                        Return
                                                    </button>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 24px 18px; text-align: center; color: #6b7280; font-size: 12px;">No uploads yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

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
            appearance: none;
            width: 100%;
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
            font: inherit;
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
            position: relative;
            display: grid;
            justify-items: center;
            gap: 10px;
            min-height: 120px;
            padding: 16px 14px 14px;
            border: 1px solid #dbe7ff;
            border-radius: 16px;
            background: linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            text-align: center;
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .pre-impl-upload-filebar.is-selected {
            border-color: #93c5fd;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        }

        .pre-impl-upload-filelink {
            text-decoration: none;
            cursor: pointer;
        }

        .pre-impl-upload-filelink:hover {
            border-color: #60a5fa;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.10);
            transform: translateY(-1px);
        }

        .pre-impl-upload-filelink:focus-visible {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        }

        .pre-impl-upload-fileicon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
        }

        .pre-impl-upload-filemeta {
            display: grid;
            gap: 4px;
            width: 100%;
        }

        .pre-impl-upload-fileeyebrow {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pre-impl-upload-filename {
            display: block;
            min-width: 0;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.4;
            font-weight: 600;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .pre-impl-upload-clear {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.85);
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.10);
            transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .pre-impl-upload-clear:hover:not(:disabled) {
            background: #ffffff;
            color: #b91c1c;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.14);
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
            const pickerId = fileInput ? fileInput.getAttribute('data-picker-id') || '' : '';
            const pickerButton = pickerId ? document.getElementById(pickerId) : null;
            const filenameText = filenameBar ? filenameBar.querySelector('[data-file-name]') : null;
            const clearBtn = filenameBar ? filenameBar.querySelector('[data-file-clear]') : null;
            if (!saveBtn || !filenameBar || !filenameText) return;

            const isLocked = filenameBar.dataset.locked === '1';
            if (isLocked) {
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                filenameBar.hidden = false;
                if (pickerButton) {
                    pickerButton.hidden = true;
                }
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
                filenameBar.hidden = false;
                if (pickerButton) {
                    pickerButton.hidden = true;
                }
                filenameText.textContent = fileName;
                filenameBar.classList.add('is-selected');
                if (clearBtn) {
                    clearBtn.hidden = false;
                }
                return;
            }

            saveBtn.style.opacity = '0';
            saveBtn.style.pointerEvents = 'none';
            filenameBar.hidden = true;
            if (pickerButton) {
                pickerButton.hidden = false;
            }
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
        function openPreImplementationFilePicker(inputId) {
            const input = inputId ? document.getElementById(inputId) : null;
            if (input instanceof HTMLInputElement && !input.disabled) {
                input.click();
            }
        }

        document.querySelectorAll('[data-pre-impl-picker-button]').forEach((dropzone) => {
            dropzone.addEventListener('click', () => {
                if (dropzone.classList.contains('is-disabled')) {
                    return;
                }

                openPreImplementationFilePicker(dropzone.getAttribute('data-input-id'));
            });
        });
    </script>

    <script>
        function openPreImplementationMultiUploadModal(documentType) {
            const modal = document.getElementById(`preImplMultiModal-${documentType}`);
            if (!modal) {
                return;
            }

            modal.style.display = 'block';
            document.body.classList.add('modal-open-pre-impl-multi');
        }

        function closePreImplementationMultiUploadModal(documentType) {
            const modal = document.getElementById(`preImplMultiModal-${documentType}`);
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            document.body.classList.remove('modal-open-pre-impl-multi');
        }

        document.querySelectorAll('[data-pre-impl-multi-modal]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open-pre-impl-multi');
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
        function openPreImplementationApprovalModal(actionUrl, action) {
            const modal = document.getElementById('preImplApprovalModal');
            const form = document.getElementById('preImplApprovalForm');
            const title = document.getElementById('preImplApprovalTitle');
            const actionInput = document.getElementById('preImplApprovalAction');
            const remarks = document.getElementById('preImplApprovalRemarks');
            const submitBtn = document.getElementById('preImplApprovalSubmit');

            form.action = actionUrl;
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
