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
                    $isPhotoMultiUpload = $isMultiUpload && $field === 'geotagged_photos_path';
                    $uploadCount = $fileRecordsForField instanceof \Illuminate\Support\Collection ? $fileRecordsForField->count() : 0;
                    $hasFile = !empty($path);
                    $fileViewUrl = $hasFile ? route($routeConfig['document'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) : null;
                    $isReturned = $fileRecord && $fileRecord->status === 'returned';
                    $isApprovedRo = $fileRecord && $fileRecord->status === 'approved';
                    $isPendingRo = $fileRecord && $fileRecord->status === 'pending_ro';
                    $disableUpload = !$isMultiUpload && $hasFile;
                    $canDeleteReturnedDocument = !$isMultiUpload
                        && $hasFile
                        && $isReturned
                        && !$isRegionalDilg
                        && $currentUser
                        && $currentUser->hasCrudPermission('pre_implementation_documents', 'add');
                    $uploadDisabledMessage = $canDeleteReturnedDocument
                        ? 'Document was returned. Delete the current file, then upload and submit a replacement.'
                        : null;

                    $statusLabel = 'Pending Upload';
                    $statusBg = '#ffffff';
                    $statusTextColor = '#475569';
                    $cardBg = '#f9fafb';
                    $cardBorder = '#cbd5f5';
                    if ($hasFile) {
                        $statusLabel = 'For DILG Provincial Office Validation';
                        $statusBg = '#ffffff';
                        $statusTextColor = '#075985';
                        $cardBg = '#f0f9ff';
                        $cardBorder = '#bae6fd';
                    }
                    if ($isPendingRo) {
                        $statusLabel = 'For DILG Regional Office Validation';
                        $statusBg = '#ffffff';
                        $statusTextColor = '#c2410c';
                        $cardBg = '#fff7ed';
                        $cardBorder = '#fed7aa';
                    }
                    if ($isApprovedRo) {
                        $statusLabel = 'Approved';
                        $statusBg = '#ffffff';
                        $statusTextColor = '#065f46';
                        $cardBg = '#f0fdf4';
                        $cardBorder = '#a7f3d0';
                    }
                    if ($isReturned) {
                        $statusLabel = 'Returned';
                        $statusBg = '#ffffff';
                        $statusTextColor = '#991b1b';
                        $cardBg = '#fef2f2';
                        $cardBorder = '#fecaca';
                    }

                    $inputId = 'pre-impl-doc-input-' . $field;
                    $buttonId = 'pre-impl-doc-btn-' . $field;
                    $filenameId = 'pre-impl-doc-file-' . $field;
                    $pickerId = 'pre-impl-doc-picker-' . $field;
                    $deleteFormId = 'pre-impl-delete-form-' . $field;

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

                    $showApprovalButtons = $isDilg && $hasFile && in_array($fileRecord?->status, ['uploaded', 'pending_ro', 'returned'], true);
                @endphp

                @if ($isMultiUpload)
                    <div class="document-card-wrapper" style="--card-border: {{ $cardBorder }}; --card-bg: {{ $cardBg }};">
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-bottom: 12px;">
                            <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0;">{{ $label }}</label>
                            <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusBg }}; color: {{ $statusTextColor }}; border-radius: 20px; font-size: 10px; font-weight: 700; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                {{ $statusLabel }}
                            </span>
                        </div>




                        <div class="pre-impl-upload-shell">
                            @if ($uploadCount > 0)
                                <button
                                    type="button"
                                    onclick="openPreImplementationMultiUploadModal('{{ $field }}')"
                                    class="pre-impl-upload-filebar pre-impl-upload-filelink pre-impl-upload-filebutton is-selected"
                                    title="{{ $isPhotoMultiUpload ? 'Open uploaded photos' : 'Open uploaded documents' }}"
                                >
                                    <span class="pre-impl-upload-fileicon">
                                        <i class="fas {{ $isPhotoMultiUpload ? 'fa-images' : 'fa-folder-open' }}"></i>
                                    </span>
                                    <span class="pre-impl-upload-filemeta">
                                        <span class="pre-impl-upload-fileeyebrow">{{ $isPhotoMultiUpload ? 'Uploaded photos' : 'Uploaded documents' }}</span>
                                        <span class="pre-impl-upload-filename">{{ $uploadCount }} {{ \Illuminate\Support\Str::plural($isPhotoMultiUpload ? 'photo' : 'document', $uploadCount) }} available</span>
                                    </span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    onclick="openPreImplementationMultiUploadModal('{{ $field }}')"
                                    class="pre-impl-upload-dropzone"
                                    aria-label="{{ $isPhotoMultiUpload ? 'Browse photos for ' . $label : 'Browse documents for ' . $label }}"
                                >
                                    <span class="pre-impl-upload-dropzone-icon">
                                        <i class="fas {{ $isPhotoMultiUpload ? 'fa-images' : 'fa-cloud-upload-alt' }}"></i>
                                    </span>
                                    <span class="pre-impl-upload-dropzone-title">{{ $isPhotoMultiUpload ? 'Browse Photos' : 'Browse Documents' }}</span>
                                    <span class="pre-impl-upload-dropzone-copy">{{ $isPhotoMultiUpload ? 'JPEG only' : 'PDF only' }}</span>
                                </button>
                            @endif
                        </div>

                        @if ($fileRecordsForField->count() > 0)
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
                                @php
                                    $cntPo = $fileRecordsForField->where('status', 'pending')->count();
                                    $cntRo = $fileRecordsForField->where('status', 'pending_ro')->count();
                                    $cntApproved = $fileRecordsForField->where('status', 'approved')->count();
                                    $cntReturned = $fileRecordsForField->where('status', 'returned')->count();
                                @endphp
                                 @if ($cntPo > 0)
                                    <span style="font-size: 9px; color: #075985; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                        PO Validation: {{ $cntPo }}
                                    </span>
                                @endif
                                @if ($cntRo > 0)
                                    <span style="font-size: 9px; color: #c2410c; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                        RO Validation: {{ $cntRo }}
                                    </span>
                                @endif
                                @if ($cntApproved > 0)
                                    <span style="font-size: 9px; color: #065f46; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                        Approved: {{ $cntApproved }}
                                    </span>
                                @endif
                                @if ($cntReturned > 0)
                                    <span style="font-size: 9px; color: #991b1b; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                        Returned: {{ $cntReturned }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                @if ($canDeleteReturnedDocument)
                    <form id="{{ $deleteFormId }}" method="POST" action="{{ route($routeConfig['delete'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) }}" data-confirm="Delete this returned document? You can upload and resubmit a replacement after this." style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
                <form method="POST" action="{{ route($routeConfig['save'], array_merge(['projectCode' => $project->project_code], $scopeQuery)) }}" enctype="multipart/form-data" class="document-card-wrapper" style="--card-border: {{ $cardBorder }}; --card-bg: {{ $cardBg }};">
                    @csrf

                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-bottom: 12px;">
                        <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0;">{{ $label }}</label>
                        <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusBg }}; color: {{ $statusTextColor }}; border-radius: 20px; font-size: 10px; font-weight: 700; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                            {{ $statusLabel }}
                        </span>
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
                            <div style="display: grid; gap: 10px;">
                                <a
                                    id="{{ $filenameId }}"
                                    href="javascript:void(0)"
                                    onclick="openGlobalDocPreviewModal('{{ $fileViewUrl }}', '{{ e($fileName ?: $label) }}')"
                                    class="pre-impl-upload-filebar pre-impl-upload-filelink is-selected"
                                    data-empty-text="{{ $fileName ?: 'View current file' }}"
                                    data-locked="1"
                                    title="View {{ $fileName ?: 'current file' }}"
                                    style="width: 100%;"
                                >
                                    <span class="pre-impl-upload-fileicon">
                                        <i class="fas fa-file-pdf"></i>
                                    </span>
                                    <span class="pre-impl-upload-filemeta">
                                        <span class="pre-impl-upload-fileeyebrow">Uploaded file</span>
                                        <span class="pre-impl-upload-filename" data-file-name>{{ $fileName ?: 'View current file' }}</span>
                                    </span>
                                </a>
                                <div style="display: flex; gap: 6px; width: 100%;">
                                    <button type="button" onclick="openPreImplementationItemHistory('{{ e($label) }}')" class="btn-history" style="flex: 1; justify-content: center; padding: 10px 14px; border-radius: 10px; font-size: 12px;" title="View history for {{ $label }}">
                                        <i class="fas fa-clock-rotate-left"></i>
                                        <span style="margin-left: 6px;">History</span>
                                    </button>
                                    @if ($canDeleteReturnedDocument)
                                        <button type="submit" form="{{ $deleteFormId }}" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; background: #dc2626; color: #ffffff; border: none; border-radius: 10px; cursor: pointer; font-size: 12px; font-weight: 700; box-shadow: 0 10px 20px rgba(220, 38, 38, 0.16);">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
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

                    @if ($fileRecordsForField->count() > 0)
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; margin-bottom: 8px;">
                            @php
                                $cntPo = $fileRecordsForField->where('status', 'pending')->count();
                                $cntRo = $fileRecordsForField->where('status', 'pending_ro')->count();
                                $cntApproved = $fileRecordsForField->where('status', 'approved')->count();
                                $cntReturned = $fileRecordsForField->where('status', 'returned')->count();
                            @endphp
                            @if ($cntPo > 0)
                                <span style="font-size: 9px; color: #075985; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                    PO Validation: {{ $cntPo }}
                                </span>
                            @endif
                            @if ($cntRo > 0)
                                <span style="font-size: 9px; color: #c2410c; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                    RO Validation: {{ $cntRo }}
                                </span>
                            @endif
                            @if ($cntApproved > 0)
                                <span style="font-size: 9px; color: #065f46; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                    Approved: {{ $cntApproved }}
                                </span>
                            @endif
                            @if ($cntReturned > 0)
                                <span style="font-size: 9px; color: #991b1b; background: #ffffff; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #002c76; box-shadow: 0 2px 4px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.4);">
                                    Returned: {{ $cntReturned }}
                                </span>
                            @endif
                        </div>
                    @endif

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

                    @if ($showApprovalButtons)
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            @if (($fileRecord?->status ?? '') !== 'returned')
                                <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) }}', 'approve')" style="flex: 1; padding: 8px 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                    Approve
                                </button>
                            @endif
                            <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate'], array_merge(['projectCode' => $project->project_code, 'documentType' => $field], $scopeQuery)) }}', 'return')" style="flex: 1; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                                Return
                            </button>
                        </div>
                    @endif
                </form>
                @endif
            @endforeach
                </div>
            </div>
        @endforeach

        <div style="margin-top: 12px; font-size: 11px; color: #6b7280;">
            Accepted format: PDF for documents@if(array_key_exists('geotagged_photos_path', $documentFields)), JPEG for geo-tagged photos@endif. Maximum file size per upload: 15 MB.
        </div>
    </div>

    @foreach ($multiUploadDocumentTypes as $multiField)
        @php
            $multiLabel = $documentFields[$multiField] ?? $multiField;
            $multiFiles = $documentFilesByType[$multiField] ?? collect();
            $modalId = 'preImplMultiModal-' . $multiField;
            $multiUploadCount = $multiFiles instanceof \Illuminate\Support\Collection ? $multiFiles->count() : 0;
            $isPhotoMultiUpload = $multiField === 'geotagged_photos_path';
        @endphp
        <div id="{{ $modalId }}" data-pre-impl-multi-modal style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); z-index: 1100; padding: 24px; overflow-y: auto;">
            <div style="max-width: 1120px; margin: 0 auto; background: linear-gradient(180deg, #f8fbff 0%, #ffffff 120px); border-radius: 20px; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24); overflow: hidden; border: 1px solid rgba(191, 219, 254, 0.9);">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 24px 20px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: rgba(255,255,255,0.14); color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                            <i class="fas {{ $isPhotoMultiUpload ? 'fa-images' : 'fa-folder-open' }}"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; line-height: 1.2;">{{ $multiLabel }}</h3>
                            <div style="margin-top: 6px; color: rgba(255,255,255,0.82); font-size: 12px; line-height: 1.5;">{{ $isPhotoMultiUpload ? 'Multiple photo uploads supported. Latest images appear first and remain available for validation inside this modal.' : 'Multiple uploads supported. Latest files appear first and remain available for validation inside this modal.' }}</div>
                        </div>
                    </div>
                    <button type="button" onclick="closePreImplementationMultiUploadModal('{{ $multiField }}')" style="border: none; background: rgba(255,255,255,0.14); color: #ffffff; width: 36px; height: 36px; border-radius: 999px; cursor: pointer; font-size: 18px; flex-shrink: 0;">&times;</button>
                </div>

                <div style="padding: 22px 24px 24px;">
                    <div style="display: grid; grid-template-columns: minmax(220px, 260px) minmax(0, 1fr); gap: 16px; align-items: stretch; margin-bottom: 18px;">
                        <div style="border: 1px solid #dbe7ff; border-radius: 16px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); padding: 18px 18px 16px;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                                <i class="fas fa-layer-group"></i>
                                <span>Upload Summary</span>
                            </div>
                            <div style="margin-top: 14px; color: #0f172a; font-size: 30px; font-weight: 800; line-height: 1;">{{ $multiUploadCount }}</div>
                            <div style="margin-top: 6px; color: #475569; font-size: 13px; font-weight: 600;">{{ \Illuminate\Support\Str::plural($isPhotoMultiUpload ? 'photo' : 'document', $multiUploadCount) }} in this set</div>
                            <div style="margin-top: 10px; color: #64748b; font-size: 12px; line-height: 1.5;">Use this panel to review every submitted {{ $isPhotoMultiUpload ? 'image' : 'file' }} for this requirement.</div>
                        </div>

                        <div style="border: 1px solid #dbe7ff; border-radius: 16px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); padding: 18px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; flex-wrap: wrap;">
                                <div>
                                    <div style="color: #0f172a; font-size: 15px; font-weight: 700;">Add another {{ $isPhotoMultiUpload ? 'photo' : 'document' }}</div>
                                    <div style="margin-top: 4px; color: #64748b; font-size: 12px;">{{ $isPhotoMultiUpload ? 'JPEG only' : 'PDF only' }}. Maximum file size per upload: 15 MB.</div>
                                </div>
                                @if ($multiUploadCount > 0)
                                    <div style="display: inline-flex; align-items: center; gap: 6px; color: #1d4ed8; font-size: 12px; font-weight: 700;">
                                        <i class="fas fa-clock-rotate-left"></i>
                                        <span>Latest first</span>
                                    </div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route($routeConfig['upload_multi'], array_merge(['projectCode' => $project->project_code, 'documentType' => $multiField], $scopeQuery)) }}" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                @csrf
                                <input type="file" name="document_file" accept="{{ $isPhotoMultiUpload ? '.jpg,.jpeg,image/jpeg' : '.pdf,application/pdf' }}" required class="dashboard-file-input" style="flex: 1 1 260px; min-width: 220px; font-size: 12px; padding: 10px 12px; border: 1px dashed #93c5fd; border-radius: 12px; background: #ffffff;">
                                <button type="submit" style="padding: 10px 16px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); color: #ffffff; border: none; border-radius: 10px; cursor: pointer; font-size: 12px; font-weight: 700; box-shadow: 0 10px 20px rgba(0, 44, 118, 0.18);">
                                    Upload {{ $isPhotoMultiUpload ? 'Photo' : 'Document' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div style="border: 1px solid #dbe7ff; border-radius: 16px; overflow: hidden; background: #ffffff; box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);">
                        <table style="width: 100%; border-collapse: collapse; min-width: 760px;">
                            <thead>
                                <tr style="background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);">
                                    <th style="padding: 13px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Uploaded</th>
                                    <th style="padding: 13px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">File</th>
                                    <th style="padding: 13px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                                    <th style="padding: 13px 14px; text-align: left; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Uploaded By</th>
                                    <th style="padding: 13px 14px; text-align: right; font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($multiFiles as $multiFile)
                                    @php
                                        $uploadedAt = $asLocalTime($multiFile->uploaded_at ?? $multiFile->created_at ?? null);
                                        $uploadedBy = $resolveUserName($multiFile->uploaded_by ?? null);
                                        $multiStatus = $multiFile->status ?? 'pending';
                                        $canDeleteReturnedMultiFile = $multiFile->status === 'returned'
                                            && !$isRegionalDilg
                                            && $currentUser
                                            && $currentUser->hasCrudPermission('pre_implementation_documents', 'add');
                                        $statusMeta = ['label' => 'Pending', 'bg' => '#fef3c7', 'color' => '#92400e'];
                                        if (($multiFile->status ?? null) === 'approved' || $multiFile->approved_at_dilg_ro) {
                                            $statusMeta = ['label' => 'Approved', 'bg' => '#d1fae5', 'color' => '#065f46'];
                                        } elseif ($multiFile->status === 'returned') {
                                            $statusMeta = ['label' => 'Returned', 'bg' => '#fee2e2', 'color' => '#991b1b'];
                                        } elseif ($multiFile->approved_at_dilg_po) {
                                            $statusMeta = ['label' => 'For DILG RO Validation', 'bg' => '#ffedd5', 'color' => '#c2410c'];
                                        } elseif ($multiStatus === 'pending') {
                                            $statusMeta = ['label' => 'For DILG PO Validation', 'bg' => '#e0f2fe', 'color' => '#075985'];
                                        }
                                    @endphp
                                    <tr style="border-top: 1px solid #e5e7eb; background-color: {{ $loop->odd ? '#ffffff' : '#f8fafc' }};">
                                        <td style="padding: 12px 14px; font-size: 12px; color: #374151; white-space: nowrap;">{{ $uploadedAt ? $uploadedAt->format('M d, Y h:i A') : '-' }}</td>
                                        <td style="padding: 12px 14px; font-size: 12px; color: #111827; font-weight: 600;">{{ basename($multiFile->file_path ?? ('File #' . $multiFile->id)) }}</td>
                                        <td style="padding: 12px 14px;">
                                            <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; background-color: {{ $statusMeta['bg'] }}; color: {{ $statusMeta['color'] }}; font-size: 11px; font-weight: 700;">
                                                {{ $statusMeta['label'] }}
                                            </span>
                                        </td>
                                        <td style="padding: 12px 14px; font-size: 12px; color: #374151;">{{ $uploadedBy }}</td>
                                        <td style="padding: 12px 14px; text-align: right; white-space: nowrap;">
                                            <a href="javascript:void(0)" onclick="openGlobalDocPreviewModal('{{ route($routeConfig['document_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}', '{{ e(basename($multiFile->file_path)) }}')" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #0f172a; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                                <i class="fas fa-eye"></i>
                                                View
                                            </a>
                                            <button type="button" onclick="openPreImplementationItemHistory('{{ e($multiLabel) }}')" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700; margin-left: 6px;" class="btn-history">
                                                <i class="fas fa-clock-rotate-left"></i>
                                                History
                                            </button>
                                            @if ($isDilg && in_array($multiFile->status, ['uploaded', 'pending_ro', 'returned'], true))
                                                @if ($multiFile->status !== 'returned')
                                                    <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}', 'approve')" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #10b981; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700; margin-left: 6px;">
                                                        <i class="fas fa-check"></i>
                                                        Approve
                                                    </button>
                                                @endif
                                                <button type="button" onclick="openPreImplementationApprovalModal('{{ route($routeConfig['validate_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}', 'return')" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #dc2626; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700; margin-left: 6px;">
                                                    <i class="fas fa-times"></i>
                                                    Return
                                                </button>
                                            @endif
                                            @if ($canDeleteReturnedMultiFile)
                                                <form method="POST" action="{{ route($routeConfig['delete_file'], array_merge(['projectCode' => $project->project_code, 'fileId' => $multiFile->id], $scopeQuery)) }}" data-confirm="Delete this returned document? You can upload and resubmit a replacement after this." style="display: inline-flex; margin-left: 6px;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; background-color: #6b7280; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 700;">
                                                        <i class="fas fa-trash-alt"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 38px 24px; text-align: center;">
                                            <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 10px; color: #64748b;">
                                                <span style="width: 56px; height: 56px; border-radius: 18px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 22px;">
                                                    <i class="fas {{ $isPhotoMultiUpload ? 'fa-images' : 'fa-folder-open' }}"></i>
                                                </span>
                                                <div style="font-size: 14px; font-weight: 700; color: #334155;">No uploads yet</div>
                                                <div style="font-size: 12px; line-height: 1.5; max-width: 320px;">Upload the first {{ $isPhotoMultiUpload ? 'photo' : 'supporting document' }} above to start the document history for this requirement.</div>
                                            </div>
                                        </td>
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
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255,255,255,0.6);
        }

        .pre-impl-upload-dropzone:hover {
            border-color: #2563eb;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12), inset 0 1px 0 rgba(255,255,255,0.6);
            transform: translateY(-3px) scale(1.01);
        }

        .pre-impl-upload-dropzone:active {
            transform: translateY(-1px) scale(0.99);
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
            box-shadow: 0 4px 6px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255,255,255,0.6);
        }

        .pre-impl-upload-filebar.is-selected {
            border-color: #93c5fd;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        }

        .pre-impl-upload-filelink {
            text-decoration: none;
            cursor: pointer;
        }

        .pre-impl-upload-filebutton {
            appearance: none;
            width: 100%;
            text-align: center;
            font: inherit;
            border: 1px solid #dbe7ff;
        }

        .pre-impl-upload-filelink:hover {
            border-color: #60a5fa;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12), inset 0 1px 0 rgba(255,255,255,0.6);
            transform: translateY(-3px) scale(1.01);
        }

        .pre-impl-upload-filelink:active {
            transform: translateY(-1px) scale(0.99);
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

    <div id="preImplApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1300;">
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

    <div id="preImplItemHistoryModal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1100; padding: 24px; align-items: center; justify-content: center;">
        <div style="max-width: 900px; width: 100%; padding: 0; overflow: hidden; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); max-height: 80vh; display: flex; flex-direction: column; background: white;">
            <div style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-history" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 id="preImplItemHistoryTitle" style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Document History</h2>
                </div>
                <button class="close-modal" onclick="closePreImplementationItemHistory()" style="color: rgba(255,255,255,0.8); font-size: 22px; line-height: 1; border: none; background: transparent; cursor: pointer;">×</button>
            </div>
            <div id="preImplItemHistoryBody" style="padding: 20px; overflow-y: auto; background: white; flex-grow: 1;">
                <div class="timeline" style="position: relative; padding: 20px 0;">
                    <div style="text-align: center; color: #9ca3af;">No activity recorded for this document.</div>
                </div>
            </div>
        </div>
    </div>

    <style>
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
            margin-bottom: 10px;
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
            z-index: 10;
        }

        .timeline-bullet.returned { border-color: #ef4444; background: #ef4444; }
        .timeline-bullet.approved { border-color: #10b981; background: #10b981; }
        .timeline-bullet.submitted { border-color: #3b82f6; background: #3b82f6; }
        .timeline-bullet.upload { border-color: #10b981; background: #10b981; }
        .timeline-bullet.return { border-color: #ef4444; background: #ef4444; }
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

        .timeline-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .avatar {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .doc-chip {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 999px;
            background: #f1f5f9;
            color: #0f172a;
            margin-left: auto;
        }

        .action-pill {
            display: inline-block;
            padding: 6px 8px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 999px;
            color: white;
            text-transform: uppercase;
        }

        .action-returned { background: #ef4444; }
        .action-approved { background: #10b981; }
        .action-submitted { background: #3b82f6; }
        .action-upload { background: #10b981; }
        .action-return { background: #ef4444; }
        .action-update { background: #6b7280; }

        .timeline-title {
            display: flex;
            gap: 8px;
            align-items: center;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .timeline-remarks {
            white-space: pre-wrap;
            color: #374151;
            font-size: 13px;
            margin-top: 6px;
            text-align: left;
        }

        .timeline-remarks strong {
            color: #0f172a;
        }

        .btn-history {
            padding: 4px 8px;
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .btn-history:hover {
            background-color: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.06);
        }

        .btn-history:focus-visible {
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
            border-color: #3b82f6;
        }

        .btn-history:active {
            transform: scale(0.96);
        }

        .btn-history i {
            font-size: 10px;
            transition: transform 0.2s ease;
        }

        .btn-history:hover i {
            transform: rotate(-30deg);
        }

        .btn-individual-file {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background-color: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-individual-file:hover {
            background-color: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.04);
        }

        .btn-individual-file:hover i {
            color: #2563eb !important;
        }

        .document-card-wrapper {
            border: 1px dashed var(--card-border, #cbd5f5);
            padding: 18px;
            border-radius: 12px;
            background-color: var(--card-bg, #f9fafb);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.03), 0 2px 4px -1px rgba(15, 23, 42, 0.01);
            position: relative;
        }

        .document-card-wrapper:hover {
            transform: translateY(-5px) scale(1.015);
            box-shadow: 0 25px 35px -5px rgba(15, 23, 42, 0.08), 0 12px 12px -5px rgba(15, 23, 42, 0.03);
            border-style: solid;
        }
    </style>
    <script>
        const preImplActivityLogsData = @json($activityLogs ?? []);
        const preImplUsersData = @json($usersById ?? []);

        function getInitials(name) {
            if (!name) return '?';
            const parts = name.trim().split(/\s+/);
            return parts.map(p => p[0]).join('').toUpperCase().substring(0, 2);
        }

        function getAvatarColor(name) {
            const colors = ['#6b7280', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            return colors[Math.abs(hash) % colors.length];
        }

        function getActionClass(action) {
            if (!action) return 'submitted';
            const actionLower = action.toLowerCase();
            if (actionLower.includes('return')) return 'returned';
            if (actionLower.includes('approved') || actionLower.includes('validated')) return 'approved';
            if (actionLower.includes('upload')) return 'submitted';
            return 'submitted';
        }

        function getActionPillClass(action) {
            if (!action) return 'action-submitted';
            const actionLower = action.toLowerCase();
            if (actionLower.includes('return')) return 'action-returned';
            if (actionLower.includes('approved') || actionLower.includes('validated')) return 'action-approved';
            if (actionLower.includes('upload')) return 'action-submitted';
            return 'action-submitted';
        }

        function formatActionText(action) {
            if (!action) return 'SUBMITTED';
            const actionLower = action.toLowerCase();
            if (actionLower.includes('return')) return 'RETURNED';
            if (actionLower.includes('approved') || actionLower.includes('validated')) return 'APPROVED';
            if (actionLower.includes('upload')) return 'SUBMITTED';
            return action.toUpperCase();
        }

        function openPreImplementationItemHistory(documentLabel) {
            const modal = document.getElementById('preImplItemHistoryModal');
            const title = document.getElementById('preImplItemHistoryTitle');
            const historyBody = document.getElementById('preImplItemHistoryBody');

            title.textContent = documentLabel + ' — History';

            // Filter activity logs for this document
            const filteredLogs = preImplActivityLogsData.filter(log => log.document === documentLabel);

            // Build timeline
            if (filteredLogs.length === 0) {
                historyBody.innerHTML = '<div class="timeline" style="position: relative; padding: 20px 0;"><div style="text-align: center; color: #9ca3af;">No activity recorded for this document.</div></div>';
            } else {
                const timelineItems = filteredLogs.map((log, index) => {
                    const timestamp = log.timestamp ? new Date(log.timestamp).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '—';
                    const action = log.action || '—';
                    const userData = log.user_id && preImplUsersData[log.user_id];
                    const userName = userData ? (userData.fname + ' ' + userData.lname).trim() : 'Unknown';
                    const remarks = log.remarks || null;
                    
                    const actionClass = getActionClass(action);
                    const actionPillClass = getActionPillClass(action);
                    const actionText = formatActionText(action);
                    const avatarColor = getAvatarColor(userName);
                    const initials = getInitials(userName);
                    const isLeft = index % 2 === 0;

                    return `
                        <div class="timeline-item ${isLeft ? 'left' : 'right'}">
                            <div class="timeline-bullet ${actionClass}" aria-hidden="true"></div>
                            <div class="timeline-card">
                                <div class="timeline-meta">
                                    <span class="avatar" style="background:${avatarColor}">${initials}</span>
                                    <div style="margin-left:8px; display:inline-block; vertical-align:middle; text-align:left;">
                                        <div style="font-size:12px;color:#6b7280">${timestamp}</div>
                                        <div style="font-weight:700;color:#0f172a">${userName}</div>
                                    </div>
                                    <span class="doc-chip">${documentLabel}</span>
                                </div>
                                <div class="timeline-title">
                                    <span class="action-pill ${actionPillClass}">${actionText}</span>
                                </div>
                                <div class="timeline-remarks"><strong>Remarks :</strong> ${remarks || '—'}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                historyBody.innerHTML = '<div class="timeline" style="position: relative;">' + timelineItems + '</div>';
            }

            modal.style.display = 'flex';
        }

        function closePreImplementationItemHistory() {
            document.getElementById('preImplItemHistoryModal').style.display = 'none';
        }

        window.addEventListener('click', function (event) {
            const itemHistoryModal = document.getElementById('preImplItemHistoryModal');
            if (event.target === itemHistoryModal) {
                closePreImplementationItemHistory();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const itemHistoryModal = document.getElementById('preImplItemHistoryModal');
                if (itemHistoryModal && (itemHistoryModal.style.display === 'block' || itemHistoryModal.style.display === 'flex')) {
                    closePreImplementationItemHistory();
                }
            }
        });

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
