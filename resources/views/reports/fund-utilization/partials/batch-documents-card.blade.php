<!-- Batch Documents Section -->
@php
    $isWorkflowValidator = $isWorkflowValidator ?? ((Auth::user() && Auth::user()->isProvincialDilgAssignment()) || (Auth::user() && (Auth::user()->normalizedRole() === \App\Models\User::ROLE_REGIONAL || Auth::user()->isRegionalOfficeAssignment())));
    $isLguWorkflowUser = $isLguWorkflowUser ?? (Auth::user() && Auth::user()->isLguScopedUser());
    $storedBatchDocumentFiles = [];
    if ($batchDocuments[$quarter]) {
        $storedBatchDocumentFiles = $batchDocuments[$quarter]->batch_document_files_json ?? [];
        if (is_string($storedBatchDocumentFiles)) {
            $decodedBatchDocumentFiles = json_decode($storedBatchDocumentFiles, true);
            $storedBatchDocumentFiles = is_array($decodedBatchDocumentFiles) ? $decodedBatchDocumentFiles : [];
        }

        if (!is_array($storedBatchDocumentFiles)) {
            $storedBatchDocumentFiles = [];
        }

        $storedBatchDocumentFiles = collect($storedBatchDocumentFiles)
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($storedBatchDocumentFiles) && trim((string) ($batchDocuments[$quarter]->batch_document_file_path ?? '')) !== '') {
            $storedBatchDocumentFiles = [trim((string) $batchDocuments[$quarter]->batch_document_file_path)];
        }
    }

    $hasBatchDocumentFile = !empty($storedBatchDocumentFiles);
    $batchDocumentValidationState = $resolveValidationState(
        $batchDocuments[$quarter],
        $hasBatchDocumentFile,
        'batch-document',
        $quarter,
        'status',
        'approved_at_dilg_po',
        'approved_at_dilg_ro',
        'batch_document_encoder_id'
    );
    $batchDocumentStatusColor = $hasBatchDocumentFile ? '#10b981' : '#f59e0b';
    $batchDocumentBackgroundColor = $hasBatchDocumentFile ? '#fffbeb' : 'transparent';

    $isBatchDocumentPendingDilgRoValidation = $batchDocumentValidationState['is_pending_regional'];
    $isBatchDocumentApprovedByDilgRo = $batchDocumentValidationState['is_approved'] && $batchDocumentValidationState['required_validator'] === 'regional';
    $isBatchDocumentReturned = $batchDocumentValidationState['is_returned'];

    if ($isBatchDocumentReturned) {
        $batchDocumentStatusColor = '#ef4444';
        $batchDocumentStatusLabel = 'Returned';
        $batchDocumentBackgroundColor = '#fee2e2';
    } else {
        if ($batchDocumentValidationState['is_approved']) {
            $batchDocumentStatusColor = '#059669';
            $batchDocumentStatusLabel = 'Approved';
        } elseif ($batchDocumentValidationState['is_pending_regional']) {
            $batchDocumentStatusColor = '#3b82f6';
            $batchDocumentStatusLabel = 'For DILG Regional Office Validation';
        } else {
            $batchDocumentStatusLabel = $hasBatchDocumentFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
        }
    }

    $isBatchDocumentForPoValidation = $batchDocumentValidationState['is_pending_provincial'];
    $isBatchDocumentUnderValidation = $isBatchDocumentPendingDilgRoValidation || $isBatchDocumentForPoValidation;
@endphp
<div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $batchDocumentStatusColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
    <h3 style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400;">
        <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
            <span style="width: 30px; height: 30px; background: rgba(217,119,6,0.12); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-layer-group" style="color: #d97706; font-size: 13px;"></i>
            </span>
            <span style="display: flex; flex-direction: column; gap: 1px;">
                <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Batch Documents</span>
                <span style="color: #64748b; font-size: 11px; font-weight: 400;">MOV on PDF Format</span>
            </span>
        </span>
        <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $batchDocumentStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
            {{ $batchDocumentStatusLabel }}
        </span>
    </h3>
    <div style="padding: 16px;">
    <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
        <label style="display: none;"></label>
        <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
            @if($hasBatchDocumentFile)
                <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                    @php
                        $uploadedInfo = $resolveUploaderMeta($batchDocuments[$quarter], 'batch_document_uploaded_at', 'batch_document_encoder_id');
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
                    $hasPoApproval = $batchDocuments[$quarter] && $batchDocuments[$quarter]->approved_at_dilg_po;
                @endphp
                @if($hasPoApproval)
                    <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                        @php
                            $poApprovedAt = is_string($batchDocuments[$quarter]->approved_at_dilg_po) ? \Carbon\Carbon::parse($batchDocuments[$quarter]->approved_at_dilg_po)->setTimezone(config('app.timezone')) : $batchDocuments[$quarter]->approved_at_dilg_po->setTimezone(config('app.timezone'));
                            $poApproverId = $batchDocuments[$quarter]->approved_by_dilg_po ?? $batchDocuments[$quarter]->approved_by;
                            $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                            $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                        @endphp
                        DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}
                    </span>
                @endif
                @if($batchDocuments[$quarter] && $batchDocuments[$quarter]->approved_at_dilg_ro)
                    <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                        @php
                            $roApprovedAt = is_string($batchDocuments[$quarter]->approved_at_dilg_ro) ? \Carbon\Carbon::parse($batchDocuments[$quarter]->approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $batchDocuments[$quarter]->approved_at_dilg_ro->setTimezone(config('app.timezone'));
                            $roApproverId = $batchDocuments[$quarter]->approved_by_dilg_ro ?? $batchDocuments[$quarter]->approved_by;
                            $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                            $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                        @endphp
                        DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}
                    </span>
                @endif
                @if($isBatchDocumentReturned && $batchDocuments[$quarter] && $batchDocuments[$quarter]->approved_at)
                    <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                        @php
                            $returnedAt = is_string($batchDocuments[$quarter]->approved_at) ? \Carbon\Carbon::parse($batchDocuments[$quarter]->approved_at)->setTimezone(config('app.timezone')) : $batchDocuments[$quarter]->approved_at->setTimezone(config('app.timezone'));
                            $returnedByUser = $batchDocuments[$quarter]->approver ? trim($batchDocuments[$quarter]->approver->fname . ' ' . $batchDocuments[$quarter]->approver->lname) : 'Unknown';
                        @endphp
                        Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $returnedByUser }}
                    </span>
                @endif
            @endif
        </label>
        @php
            $isBatchDocumentUploadDisabled = !$canUploadFundUtilizationDocuments || $hasBatchDocumentFile;
            $batchDocumentUploadTitle = $isBatchDocumentUploadDisabled
                ? (!$canUploadFundUtilizationDocuments
                    ? 'Only LGU User and DILG Provincial Office users can upload documents.'
                    : ($isBatchDocumentReturned
                    ? 'Document was returned. Delete the current file to upload a new one.'
                    : 'Documents are already attached. Delete the current file to upload a new one.'))
                : '';
        @endphp
        <form action="{{ route('fund-utilization.upload-batch-document', $report->project_code) }}" method="POST" enctype="multipart/form-data" data-batch-upload-form="1" data-confirm-skip="true" style="margin-bottom: 8px;">
            @csrf
            <input type="hidden" name="quarter" value="{{ $quarter }}">
            <div style="position: relative; padding: 16px 18px; border: 1px solid #dbe4f0; border-radius: 14px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between; {{ $isBatchDocumentUploadDisabled ? 'opacity: 0.7;' : '' }}">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1 1 260px;">
                    <div style="width: 42px; height: 42px; border-radius: 14px; background: rgba(0, 44, 118, 0.08); color: #002C76; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                    </div>
                    <div style="min-width: 0;">
                        <h5 style="margin: 0; color: #0f172a; font-size: 13px; font-weight: 800; line-height: 1.35;">Choose one or more PDF documents</h5>
                        <p style="margin: 4px 0 0; color: #475569; font-size: 11px; line-height: 1.45;">Only PDF files are allowed for the quarterly batch document upload.</p>
                    </div>
                </div>

                <label for="batch-document-upload-{{ $quarter }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; border-radius: 999px; background: {{ $isBatchDocumentUploadDisabled ? '#94a3b8' : '#002C76' }}; color: #ffffff; font-size: 11px; font-weight: 800; text-decoration: none; cursor: {{ $isBatchDocumentUploadDisabled ? 'not-allowed' : 'pointer' }}; box-shadow: {{ $isBatchDocumentUploadDisabled ? 'none' : '0 6px 12px rgba(0, 44, 118, 0.12)' }}; white-space: nowrap; {{ $isBatchDocumentUploadDisabled ? 'pointer-events: none;' : '' }}">
                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                    Select Documents
                </label>
                <input id="batch-document-upload-{{ $quarter }}" type="file" name="batch_document_file[]" multiple class="dashboard-file-input" accept="application/pdf" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;" onchange="showSaveButton(this, 'batch-document-save-btn-{{ $quarter }}', 'batch-document-filename-{{ $quarter }}')" {{ $isBatchDocumentUploadDisabled ? 'disabled' : '' }} title="{{ $batchDocumentUploadTitle }}">
            </div>
            <button type="submit" id="batch-document-save-btn-{{ $quarter }}" style="margin-top: 10px; padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                <i class="fas fa-upload"></i> Submit
            </button>
        </form>
        <div id="batch-document-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
            @if($hasBatchDocumentFile)
                @php
                    $uploadedInfo = $resolveUploaderMeta($batchDocuments[$quarter], 'batch_document_uploaded_at', 'batch_document_encoder_id');
                    $uploadedTime = $uploadedInfo['time'];
                    $uploadedBy = $uploadedInfo['name'];
                    $uploadedAtLabel = $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '—';
                @endphp
                <div style="margin-bottom: 6px;"><i class="fas fa-file" style="margin-right: 4px;"></i>Attached Documents:</div>
                <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff;">
                    <table style="width: 100%; min-width: 640px; border-collapse: collapse; font-size: 11px; color: #374151;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 10px 12px; text-align: left; font-weight: 700; color: #334155;">Document Title</th>
                                <th style="padding: 10px 12px; text-align: left; font-weight: 700; color: #334155;">Date &amp; Time Uploaded</th>
                                <th style="padding: 10px 12px; text-align: left; font-weight: 700; color: #334155;">Uploaded By</th>
                                <th style="padding: 10px 12px; text-align: right; font-weight: 700; color: #334155; width: 72px;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($storedBatchDocumentFiles as $batchDocumentFileIndex => $uploadedBatchDocumentPath)
                                @php
                                    $uploadedDocumentTitle = basename($uploadedBatchDocumentPath);
                                    $uploadedDocumentUrl = route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'batch-document', 'quarter' => $quarter, 'file' => $batchDocumentFileIndex]);
                                @endphp
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px 12px; vertical-align: top;">
                                        <div style="display: flex; align-items: center; gap: 8px; color: #0f172a; font-weight: 600;">
                                            <i class="fas fa-file-pdf" style="color: #dc2626;"></i>
                                            <span style="word-break: break-word;">{{ $uploadedDocumentTitle }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 10px 12px; vertical-align: top; white-space: nowrap;">
                                        {{ $uploadedAtLabel }}
                                    </td>
                                    <td style="padding: 10px 12px; vertical-align: top; white-space: nowrap;">
                                        {{ $uploadedBy }}
                                    </td>
                                    <td style="padding: 10px 12px; vertical-align: top; text-align: right;">
                                        <button type="button" data-document-url="{{ $uploadedDocumentUrl }}" data-document-title="{{ $uploadedDocumentTitle }}" onclick="openBatchDocumentViewerModal(this.dataset.documentUrl, this.dataset.documentTitle)" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 999px; background: #eff6ff; color: #2563eb; text-decoration: none; border: 1px solid #bfdbfe; cursor: pointer;" title="View document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($isLguWorkflowUser)
            @if($batchDocuments[$quarter] && ($hasBatchDocumentFile || $isBatchDocumentReturned))
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                    @if($canDeleteFundUtilizationDocument($batchDocuments[$quarter], 'status', 'batch_document_encoder_id') && !$isBatchDocumentUnderValidation && $batchDocuments[$quarter]->status !== 'approved' && !($batchDocumentValidationState['uploader_level'] === 'lgu' && (($batchDocumentValidationState['required_validator'] ?? 'provincial') === 'provincial') && !(($batchDocumentValidationState['is_returned'] ?? false))))
                        <button type="button" onclick="deleteDocument('batch-document', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    @endif
                </div>
            @endif

            @if($isLguWorkflowUser && $batchDocuments[$quarter])
                <button type="button" onclick="toggleAccordion('batch-document-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                    <i class="fas fa-chevron-down" id="icon-batch-document-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                </button>
                <div id="batch-document-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <textarea id="textarea-batch-document-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $isBatchDocumentReturned ? ($batchDocuments[$quarter]->approval_remarks ?? '') : ($batchDocuments[$quarter]->user_remarks ?? '') }}</textarea>
                    <button type="button" onclick="saveRemarksAjax('batch-document', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                </div>
            @endif
        @elseif($isWorkflowValidator)
            @if($batchDocuments[$quarter] && $hasBatchDocumentFile)
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                    @if($canDeleteFundUtilizationDocument($batchDocuments[$quarter], 'status', 'batch_document_encoder_id') && !$isBatchDocumentUnderValidation && $batchDocuments[$quarter]->status !== 'approved')
                        <button type="button" onclick="deleteDocument('batch-document', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    @endif
                    @if($shouldShowValidationActions($batchDocumentValidationState) && $batchDocuments[$quarter]->status !== 'approved')
                        @if(!($batchDocumentValidationState['return_only'] ?? false))
                            <button type="button" onclick="openRemarksModal('batch-document', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        @endif
                        <button type="button" onclick="openRemarksModal('batch-document', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-undo"></i> Return
                        </button>
                    @endif
                </div>
            @endif
            @if($batchDocuments[$quarter] && ($hasBatchDocumentFile || $batchDocuments[$quarter]->user_remarks || $isBatchDocumentReturned))
                <button type="button" onclick="toggleAccordion('batch-document-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                    <i class="fas fa-chevron-down" id="icon-batch-document-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                </button>
                <div id="batch-document-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                    <textarea id="textarea-batch-document-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;" {{ $isBatchDocumentReturned ? 'readonly' : '' }}>{{ $isBatchDocumentReturned ? ($batchDocuments[$quarter]->approval_remarks ?? '') : ($batchDocuments[$quarter]->user_remarks ?? '') }}</textarea>
                    @if(!$isBatchDocumentReturned)
                        <button type="button" onclick="saveRemarksAjax('batch-document', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                    @endif
                </div>
            @endif
        @endif
    </div>
    @if ($batchDocuments[$quarter])
        @if($isWorkflowValidator)
            @if($batchDocuments[$quarter]->approval_remarks)
                <div style="margin-top: 12px; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                    <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Approval Remarks:</p>
                    <p style="color: #374151; font-size: 13px; margin: 0;">{{ $batchDocuments[$quarter]->approval_remarks }}</p>
                </div>
            @endif
        @elseif($isLguWorkflowUser && $batchDocuments[$quarter]->approval_remarks)
            <div style="margin-top: 12px; padding: 10px; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">DILG Remarks:</p>
                <p style="color: #374151; font-size: 13px; margin: 0;">{{ $batchDocuments[$quarter]->approval_remarks }}</p>
            </div>
        @endif
    @endif
</div>
</div>
