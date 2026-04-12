@extends('layouts.dashboard')

@section('title', 'Quarterly RPMES Form 2')
@section('page-title', 'Quarterly RPMES Form 2')

@section('content')
<div class="ops-detail-page">
    <style>
        .ops-detail-page .ops-upload-input { flex: 1; min-width: 240px; padding: 10px 12px !important; border: 1.5px dashed #9fb2d4 !important; border-radius: 10px !important; font-size: 12px !important; line-height: 1.4; color: #1f2937; background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%) !important; }
        .ops-detail-page .ops-upload-input:focus { outline: none; border-color: #2563eb !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .ops-detail-page .ops-upload-input.is-disabled { cursor: not-allowed; opacity: 0.65; background: #f3f4f6 !important; border-style: solid !important; }
        .ops-detail-page .ops-upload-input::-webkit-file-upload-button { margin-right: 10px; border: none; border-radius: 999px; padding: 6px 12px; font-weight: 700; font-size: 11px; color: #1e3a8a; background: #dbeafe; cursor: pointer; }
        .ops-detail-page .ops-upload-submit { background: linear-gradient(135deg, #059669, #047857) !important; box-shadow: 0 8px 14px rgba(5, 150, 105, 0.2); }
        .ops-detail-page .ops-upload-submit:disabled { opacity: 0.5 !important; cursor: not-allowed; pointer-events: none; box-shadow: none; }
        .ops-detail-page .ops-upload-filename { padding: 8px 10px; border-radius: 8px; border: 1px solid #d1d5db; background: #f8fafc; color: #334155; font-size: 11px; }
        .ops-detail-page .ops-upload-filename.has-file { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .ops-detail-page .rpmes-modal-backdrop { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.55); z-index: 2000; padding: 20px; }
        .ops-detail-page .rpmes-modal-card { width: min(100%, 520px); background: #fff; border-radius: 16px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28); overflow: hidden; }
        .ops-detail-page .rpmes-modal-textarea { width: 100%; min-height: 120px; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 13px; line-height: 1.5; color: #1f2937; }
        .ops-detail-page .rpmes-modal-textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    </style>

    <div class="content-header">
        <h1>RPMES FORM 2 : Physical and Financial Accomplishment Report</h1>
        <p style="margin: 6px 0 0; color: #475569; font-size: 14px;">Physical and Financial Accomplishment Report</p>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <div style="font-size:18px;font-weight:700;color:#111827;">Project View</div>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;">SubayBayan project details, quarterly uploads, and DILG validation workflow for RPMES Form 2.</div>
        </div>
        <a href="{{ route('reports.quarterly.rpmes.form-2') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    @if(session('success'))
        <div style="margin-bottom:18px;padding:14px 16px;border-radius:10px;border:1px solid #86efac;background:#f0fdf4;color:#166534;font-size:13px;font-weight:600;">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div style="margin-bottom:18px;padding:14px 16px;border-radius:10px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;font-size:13px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden;margin-bottom:24px;">
        <div style="padding:20px 24px;background:linear-gradient(135deg,#002C76 0%,#003d9e 100%);color:#fff;display:flex;justify-content:space-between;align-items:end;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;opacity:.85;">Project Code</div>
                <div style="font-size:20px;font-weight:800;margin-top:4px;">{{ $project->project_code }}</div>
                <div style="font-size:14px;opacity:.92;margin-top:6px;">{{ $project->project_title ?: 'Untitled Project' }}</div>
            </div>
            <div style="padding:8px 12px;border:1px solid rgba(255,255,255,.2);border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;">{{ $project->fund_source ?: 'UNSPECIFIED' }}</div>
        </div>
        <div style="padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Funding Year</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->funding_year ?: '-' }}</div></div>
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Status</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->status ?: '-' }}</div></div>
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Program</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->program ?: '-' }}</div></div>
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Province</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->province ?: '-' }}</div></div>
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">City / Municipality</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->city_municipality ?: '-' }}</div></div>
            <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Barangay</div><div style="font-size:15px;font-weight:700;color:#111827;margin-top:8px;">{{ $project->barangay ?: '-' }}</div></div>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Quarterly Submission</div>
        <div style="font-size:16px;font-weight:700;color:#111827;">Submission of Physical and Financial Accomplishment Report (RPMES FORM 2)</div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">Each quarter supports one report upload plus DILG Provincial Office and DILG Regional Office validation.</div>
    </div>

    @foreach($quarters as $quarterCode => $quarterLabel)
        @php
            $upload = $uploadsByQuarter[$quarterCode] ?? null;
            $hasUpload = $upload && $upload->file_path;
            $status = $upload?->status;
            $isReturned = $status === 'returned';
            $isApproved = $status === 'approved' || ($upload && $upload->approved_at_dilg_ro);
            $isPendingRo = $status === 'pending_ro' || ($upload && $upload->approved_at_dilg_po && !$upload->approved_at_dilg_ro && $status !== 'returned');
            $isPendingPo = $hasUpload && !$isReturned && !$isPendingRo && !$isApproved;
            $displayStyle = $selectedQuarter === $quarterCode ? 'block' : 'none';
            $iconRotation = $selectedQuarter === $quarterCode ? 'rotate(180deg)' : 'rotate(0deg)';
            $statusLabel = 'Pending Upload';
            $statusBackground = 'rgba(245,158,11,0.28)';
            $statusCardBackground = '#fef3c7';
            $statusCardText = '#92400e';
            if ($isReturned) { $statusLabel = 'Returned'; $statusBackground = 'rgba(220,38,38,0.30)'; $statusCardBackground = '#fee2e2'; $statusCardText = '#b91c1c'; }
            elseif ($isApproved) { $statusLabel = 'Approved'; $statusBackground = 'rgba(16,185,129,0.30)'; $statusCardBackground = '#dcfce7'; $statusCardText = '#166534'; }
            elseif ($isPendingRo) { $statusLabel = 'For DILG Regional Office Validation'; $statusBackground = 'rgba(59,130,246,0.30)'; $statusCardBackground = '#dbeafe'; $statusCardText = '#1d4ed8'; }
            elseif ($isPendingPo) { $statusLabel = 'For DILG Provincial Office Validation'; }
            $uploadedAt = $upload && $upload->uploaded_at ? $upload->uploaded_at->format('M d, Y h:i A') : 'Not uploaded yet';
            $uploadedBy = $upload && $upload->uploader ? $upload->uploader->fullName() : '-';
            $displayName = $upload && $upload->file_path ? ($upload->original_name ?: basename($upload->file_path)) : null;
            $poValidatedAt = $upload && $upload->approved_at_dilg_po ? $upload->approved_at_dilg_po->format('M d, Y h:i A') : '-';
            $poValidatedBy = $upload && $upload->dilgPoApprover ? $upload->dilgPoApprover->fullName() : '-';
            $roValidatedAt = $upload && $upload->approved_at_dilg_ro ? $upload->approved_at_dilg_ro->format('M d, Y h:i A') : '-';
            $roValidatedBy = $upload && $upload->dilgRoApprover ? $upload->dilgRoApprover->fullName() : '-';
            $returnedAt = $upload && $upload->approved_at && $isReturned ? $upload->approved_at->format('M d, Y h:i A') : '-';
            $returnedBy = $upload && $upload->approver && $isReturned ? $upload->approver->fullName() : '-';
            $remarks = $upload?->approval_remarks ?: $upload?->user_remarks;
            $configuredQuarterDeadline = $configuredQuarterDeadlines[$quarterCode] ?? null;
            $quarterDeadlineDisplay = trim((string) ($configuredQuarterDeadline['display'] ?? ''));
            $timelinessBadge = null;
            if ($hasUpload && $upload?->uploaded_at && !empty($configuredQuarterDeadline['deadline_at'])) {
                $timezone = config('app.timezone');
                $submittedAt = $upload->uploaded_at instanceof \Carbon\CarbonInterface
                    ? $upload->uploaded_at->copy()->setTimezone($timezone)
                    : \Carbon\Carbon::parse($upload->uploaded_at)->setTimezone($timezone);
                $deadlineTime = $configuredQuarterDeadline['deadline_at'] instanceof \Carbon\CarbonInterface
                    ? $configuredQuarterDeadline['deadline_at']->copy()->setTimezone($timezone)
                    : \Carbon\Carbon::parse($configuredQuarterDeadline['deadline_at'])->setTimezone($timezone);
                $isLate = $submittedAt->greaterThan($deadlineTime);
                $timelinessBadge = [
                    'label' => $isLate ? 'Late' : 'On Time',
                    'background' => $isLate ? '#fef2f2' : '#ecfdf5',
                    'color' => $isLate ? '#b91c1c' : '#047857',
                    'border' => $isLate ? '#fecaca' : '#a7f3d0',
                    'title' => $isLate
                        ? 'Submitted after the configured deadline of ' . $deadlineTime->format('M d, Y h:i A')
                        : 'Submitted on or before the configured deadline of ' . $deadlineTime->format('M d, Y h:i A'),
                ];
            }
            $canUpload = !$isRegionalDilgViewer && (!$hasUpload || $isReturned);
            $canDelete = $hasUpload && !$isRegionalDilgViewer && in_array((string) $status, ['pending', 'returned'], true);
            $canApproveByPo = $isProvincialDilgViewer && $hasUpload && $status === 'pending';
            $canApproveByRo = $isRegionalDilgViewer && $hasUpload && $status === 'pending_ro';
        @endphp

        <div style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(15,23,42,.09);margin-bottom:24px;border:1px solid #e5e7eb;overflow:hidden;">
            <button type="button" onclick="toggleAccordion('quarter-{{ $quarterCode }}')" style="width:100%;padding:18px 24px;background:linear-gradient(135deg,#002C76 0%,#003d9e 100%);color:#fff;border:none;text-align:left;cursor:pointer;font-weight:700;font-size:15px;display:flex;justify-content:space-between;align-items:center;gap:16px;" onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter='brightness(1)'">
                <span style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
                    <span style="width:34px;height:34px;background:rgba(255,255,255,.15);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-calendar-alt" style="font-size:14px;"></i></span>
                    <span style="display:flex;flex-direction:column;gap:4px;">
                        <span style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <span>{{ $quarterLabel }}</span>
                            <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $statusBackground }};color:#fff;">{{ $statusLabel }}</span>
                            <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;background:{{ $quarterDeadlineDisplay !== '' ? 'rgba(15,118,110,0.32)' : 'rgba(107,114,128,0.35)' }};color:#fff;">{{ $quarterDeadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}</span>
                        </span>
                        <span style="font-size:11px;opacity:.95;">Deadline (CY {{ $deadlineReportingYear }}): {{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}</span>
                    </span>
                </span>
                <i class="fas fa-chevron-down" id="icon-quarter-{{ $quarterCode }}" style="transition:transform .3s;transform:{{ $iconRotation }};opacity:.9;"></i>
            </button>

            <div id="quarter-{{ $quarterCode }}" style="display:{{ $displayStyle }};padding:22px 24px;">
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:20px;background:{{ $hasUpload ? '#f8fafc' : '#ffffff' }};">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                        <div>
                            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">RPMES FORM 2</div>
                            <div style="font-size:16px;font-weight:700;color:#111827;margin-top:6px;">Physical and Financial Accomplishment Report</div>
                            <div style="font-size:12px;color:#64748b;margin-top:6px;">Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 10 MB.</div>
                            <div style="font-size:12px;color:#64748b;margin-top:4px;">Configured deadline for CY {{ $deadlineReportingYear }}: {{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:{{ $statusCardBackground }};color:{{ $statusCardText }};font-size:12px;font-weight:700;text-align:center;">{{ $statusLabel }}</div>
                    </div>

                    @if($isReturned && $remarks)
                        <div style="margin-bottom:14px;padding:12px 14px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:13px;line-height:1.6;"><div style="font-weight:700;margin-bottom:4px;">Returned with remarks</div><div>{{ $remarks }}</div></div>
                    @elseif($isPendingRo)
                        <div style="margin-bottom:14px;padding:12px 14px;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;color:#1d4ed8;font-size:13px;line-height:1.6;">Provincial validation is complete. This quarter is awaiting DILG Regional Office approval.</div>
                    @elseif($isPendingPo)
                        <div style="margin-bottom:14px;padding:12px 14px;border:1px solid #fde68a;border-radius:10px;background:#fffbeb;color:#92400e;font-size:13px;line-height:1.6;">The report has been uploaded and is waiting for DILG Provincial Office validation.</div>
                    @elseif(!$hasUpload && $isRegionalDilgViewer)
                        <div style="margin-bottom:14px;padding:12px 14px;border:1px solid #d1d5db;border-radius:10px;background:#f8fafc;color:#475569;font-size:13px;line-height:1.6;">Regional Office users cannot upload files. Validation buttons will appear here after a report is submitted.</div>
                    @endif

                    <form action="{{ route('reports.quarterly.rpmes.form-2.upload', ['projectCode' => $project->project_code]) }}" method="POST" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:0 0 12px;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarterCode }}">
                        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" onchange="showSaveButton(this, 'rpmes-save-btn-{{ $quarterCode }}', 'rpmes-filename-{{ $quarterCode }}')" class="ops-upload-input{{ $canUpload ? '' : ' is-disabled' }}" {{ $canUpload ? '' : 'disabled' }}>
                        <button type="submit" id="rpmes-save-btn-{{ $quarterCode }}" class="ops-upload-submit" style="padding:10px 20px;color:white;border:none;border-radius:10px;cursor:pointer;font-weight:700;font-size:12px;white-space:nowrap;opacity:0;pointer-events:none;width:auto;" {{ $canUpload ? '' : 'disabled' }}><i class="fas fa-upload"></i> Submit</button>
                    </form>

                    <div id="rpmes-filename-{{ $quarterCode }}" class="ops-upload-filename{{ $hasUpload ? ' has-file' : '' }}" style="display:{{ $hasUpload ? 'block' : 'none' }};margin-bottom:12px;">
                        @if($hasUpload)
                            <i class="fas fa-file" style="margin-right:4px;"></i> Current file: {{ $displayName }}
                        @endif
                    </div>
                    @if(!$canUpload)
                        <div style="margin-bottom:12px;font-size:12px;color:#64748b;">
                            @if($isRegionalDilgViewer)
                                Upload is disabled for DILG Regional Office users.
                            @elseif($hasUpload && !$isReturned)
                                Upload is locked while the submitted report is under review or already approved.
                            @endif
                        </div>
                    @elseif($isReturned)
                        <div style="margin-bottom:12px;font-size:12px;color:#b91c1c;">This quarter was returned. Upload a corrected file to resubmit it into the approval flow.</div>
                    @endif

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:14px;">
                        <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:white;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Quarter Deadline</div><div style="font-size:14px;font-weight:700;color:#111827;margin-top:8px;">{{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}</div><div style="font-size:11px;color:#64748b;margin-top:4px;">Configuration year: CY {{ $deadlineReportingYear }}</div></div>
                        <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:white;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Uploaded At</div><div style="font-size:14px;font-weight:700;color:#111827;margin-top:8px;">{{ $uploadedAt }}</div>@if($timelinessBadge)<div title="{{ $timelinessBadge['title'] }}" style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $timelinessBadge['background'] }};color:{{ $timelinessBadge['color'] }};border:1px solid {{ $timelinessBadge['border'] }};margin-top:8px;">{{ $timelinessBadge['label'] }}</div>@endif</div>
                        <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:white;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Uploaded By</div><div style="font-size:14px;font-weight:700;color:#111827;margin-top:8px;">{{ $uploadedBy }}</div></div>
                        <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:white;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">DILG Provincial Validation</div><div style="font-size:14px;font-weight:700;color:#111827;margin-top:8px;">{{ $poValidatedAt }}</div><div style="font-size:11px;color:#64748b;margin-top:4px;">{{ $poValidatedBy }}</div></div>
                        <div style="padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:white;"><div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">DILG Regional Validation</div><div style="font-size:14px;font-weight:700;color:#111827;margin-top:8px;">{{ $roValidatedAt }}</div><div style="font-size:11px;color:#64748b;margin-top:4px;">{{ $roValidatedBy }}</div></div>
                        @if($isReturned)
                            <div style="padding:14px;border:1px solid #fecaca;border-radius:10px;background:#fff7f7;"><div style="font-size:11px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.05em;">Returned At</div><div style="font-size:14px;font-weight:700;color:#991b1b;margin-top:8px;">{{ $returnedAt }}</div><div style="font-size:11px;color:#b91c1c;margin-top:4px;">{{ $returnedBy }}</div></div>
                        @endif
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        @if($hasUpload)
                            <a href="{{ route('reports.quarterly.rpmes.form-2.document', ['projectCode' => $project->project_code, 'quarter' => $quarterCode]) }}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:#2563eb;color:white;border-radius:8px;text-decoration:none;font-size:12px;font-weight:700;"><i class="fas fa-eye"></i> View Report</a>
                        @endif

                        @if($canDelete)
                            <form action="{{ route('reports.quarterly.rpmes.form-2.delete-document', ['projectCode' => $project->project_code, 'quarter' => $quarterCode]) }}" method="POST" style="margin:0;" onsubmit="return confirm('Delete the uploaded RPMES Form 2 report for {{ $quarterLabel }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:#dc2626;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        @endif

                        @if($canApproveByPo || $canApproveByRo)
                            <button type="button" onclick='openRpmesApprovalModal(@json($project->project_code), @json($quarterCode), "approve", @json($quarterLabel))' style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:#10b981;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;"><i class="fas fa-check"></i> Approve</button>
                            <button type="button" onclick='openRpmesApprovalModal(@json($project->project_code), @json($quarterCode), "return", @json($quarterLabel))' style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;background:#dc2626;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;"><i class="fas fa-undo"></i> Return</button>
                        @endif

                        @if(!$hasUpload)
                            <span style="font-size:12px;color:#64748b;">No RPMES Form 2 report uploaded for {{ $quarterLabel }} yet.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div id="rpmesApprovalModal" class="rpmes-modal-backdrop" onclick="closeRpmesApprovalModal(event)">
        <div class="rpmes-modal-card" onclick="event.stopPropagation()">
            <div style="padding:18px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <div id="rpmesApprovalTitle" style="font-size:18px;font-weight:700;color:#111827;">Approval Action</div>
                    <div id="rpmesApprovalSubtitle" style="font-size:12px;color:#6b7280;margin-top:4px;">RPMES Form 2 quarterly validation</div>
                </div>
                <button type="button" onclick="closeRpmesApprovalModal()" style="background:transparent;border:none;color:#6b7280;font-size:18px;cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <form id="rpmesApprovalForm" method="POST" style="padding:20px;">
                @csrf
                <input type="hidden" name="action" id="rpmesApprovalAction" value="approve">
                <div style="font-size:13px;color:#475569;margin-bottom:10px;line-height:1.6;">Use remarks when returning a report or when you want to capture an approval note.</div>
                <label for="rpmesApprovalRemarks" style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:8px;">Remarks</label>
                <textarea id="rpmesApprovalRemarks" name="remarks" class="rpmes-modal-textarea" placeholder="Enter remarks..."></textarea>
                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
                    <button type="button" onclick="closeRpmesApprovalModal()" style="padding:10px 16px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:10px;cursor:pointer;font-size:12px;font-weight:700;">Cancel</button>
                    <button type="submit" id="rpmesApprovalSubmit" style="padding:10px 16px;background:#10b981;color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:12px;font-weight:700;">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const rpmesApprovalBaseUrl = @json(url('/reports/quarterly/rpmes/form-2'));

    function toggleAccordion(elementId) {
        const element = document.getElementById(elementId);
        const icon = document.getElementById('icon-' + elementId);
        if (!element) return;
        const isOpen = !(element.style.display === 'none' || element.style.display === '');
        if (!isOpen && elementId.startsWith('quarter-')) {
            document.querySelectorAll('[id^="quarter-"]').forEach(function (otherPanel) {
                if (otherPanel === element) return;
                if (otherPanel.style.display === 'block') {
                    otherPanel.style.display = 'none';
                    const otherIcon = document.getElementById('icon-' + otherPanel.id);
                    if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                }
            });
        }
        if (!isOpen) {
            element.style.display = 'block';
            if (icon) icon.style.transform = 'rotate(180deg)';
            if (elementId.startsWith('quarter-')) {
                const quarterCode = elementId.replace('quarter-', '');
                const url = new URL(window.location.href);
                url.searchParams.set('quarter', quarterCode);
                window.history.replaceState({}, '', url.toString());
            }
        } else {
            element.style.display = 'none';
            if (icon) icon.style.transform = 'rotate(0deg)';
        }
    }

    function renderSelectedFileName(filenameDiv, fileName) {
        const icon = document.createElement('i');
        icon.className = 'fas fa-file';
        icon.style.marginRight = '4px';
        filenameDiv.replaceChildren(icon, document.createTextNode('Selected: ' + fileName));
    }

    function showSaveButton(fileInput, buttonId, filenameId) {
        const saveBtn = document.getElementById(buttonId);
        const filenameDiv = document.getElementById(filenameId);
        if (!saveBtn || !filenameDiv) return;
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            saveBtn.style.opacity = '1';
            saveBtn.style.pointerEvents = 'auto';
            renderSelectedFileName(filenameDiv, fileInput.files[0].name);
            filenameDiv.style.display = 'block';
            filenameDiv.classList.add('has-file');
        } else {
            saveBtn.style.opacity = '0';
            saveBtn.style.pointerEvents = 'none';
            filenameDiv.style.display = 'none';
            filenameDiv.classList.remove('has-file');
        }
    }

    function openRpmesApprovalModal(projectCode, quarter, action, quarterLabel) {
        const modal = document.getElementById('rpmesApprovalModal');
        const form = document.getElementById('rpmesApprovalForm');
        const title = document.getElementById('rpmesApprovalTitle');
        const subtitle = document.getElementById('rpmesApprovalSubtitle');
        const remarks = document.getElementById('rpmesApprovalRemarks');
        const actionInput = document.getElementById('rpmesApprovalAction');
        const submitBtn = document.getElementById('rpmesApprovalSubmit');
        actionInput.value = action;
        form.action = `${rpmesApprovalBaseUrl}/${encodeURIComponent(projectCode)}/approve/${encodeURIComponent(quarter)}`;
        subtitle.textContent = `${quarterLabel} validation for RPMES Form 2`;
        remarks.value = '';
        if (action === 'approve') {
            title.textContent = 'Approve RPMES Form 2';
            remarks.placeholder = 'Enter optional remarks for approval...';
            remarks.required = false;
            submitBtn.textContent = 'Approve';
            submitBtn.style.background = '#10b981';
        } else {
            title.textContent = 'Return RPMES Form 2';
            remarks.placeholder = 'Enter the reason for returning this report...';
            remarks.required = true;
            submitBtn.textContent = 'Return';
            submitBtn.style.background = '#dc2626';
        }
        modal.style.display = 'flex';
    }

    function closeRpmesApprovalModal(event) {
        if (event && event.target && event.target.id !== 'rpmesApprovalModal') return;
        const modal = document.getElementById('rpmesApprovalModal');
        if (modal) modal.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ops-upload-filename').forEach(function (filenameDiv) {
            if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                filenameDiv.classList.add('has-file');
            }
        });
    });
</script>
@endsection
