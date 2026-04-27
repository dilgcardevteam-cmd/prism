@extends('layouts.dashboard')

@section('title', 'Confirmation of Fund Receipt')
@section('page-title', 'Confirmation of Fund Receipt')

@section('content')
@php
    $totalNadaiRecords = $documents->count();
    $acceptedNadaiRecords = $documents->whereNotNull('confirmation_accepted_at')->count();
    $uploadedCfrRecords = $confirmationDocumentsByNadaiId->count();
    $pendingAcceptanceRecords = $documents->whereNull('confirmation_accepted_at')->count();
    $pendingCfrUploadRecords = $documents->filter(function ($document) use ($confirmationDocumentsByNadaiId) {
        return $document->confirmation_accepted_at && !$confirmationDocumentsByNadaiId->has($document->id);
    })->count();
@endphp

<style>
    .cfr-show-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 14px;
    }

    .cfr-show-card,
    .cfr-show-table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .cfr-show-card {
        padding: 18px 20px;
    }

    .cfr-show-card-label {
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .cfr-show-card-value {
        color: #111827;
        font-size: 24px;
        font-weight: 800;
        margin-top: 10px;
        line-height: 1;
    }

    .cfr-show-card-note {
        color: #6b7280;
        font-size: 12px;
        margin-top: 8px;
    }

    .cfr-show-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .cfr-show-meta-label {
        display: block;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }

    .cfr-show-meta-value {
        color: #111827;
        font-size: 15px;
        font-weight: 600;
        margin: 0;
    }

    .cfr-show-note-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
    }

    .cfr-show-table-card {
        padding: 22px;
    }

    .cfr-show-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1260px;
    }

    .cfr-show-table thead th {
        padding: 14px 16px;
        text-align: left;
        color: #374151;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .cfr-show-table tbody td {
        padding: 16px;
        vertical-align: top;
        border-bottom: 1px solid #edf2f7;
        color: #111827;
        font-size: 13px;
    }

    .cfr-show-table tbody tr:hover {
        background: #f8fbff;
    }

    .cfr-show-table tbody tr.cfr-show-row-needs-action {
        background: #fff7ed;
    }

    .cfr-show-table tbody tr.cfr-show-row-needs-action:hover {
        background: #ffedd5;
    }

    .cfr-show-project-title {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.35;
        margin-bottom: 8px;
    }

    .cfr-show-subline {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.55;
    }

    .cfr-show-file-link {
        color: #1d4ed8;
        font-weight: 700;
        text-decoration: none;
        line-height: 1.45;
        word-break: break-word;
    }

    .cfr-show-file-link:hover {
        text-decoration: underline;
    }

    .cfr-show-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .cfr-show-status-accepted {
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        color: #047857;
    }

    .cfr-show-status-pending {
        background: #fffbeb;
        border: 1px solid #fcd34d;
        color: #92400e;
    }

    .cfr-show-status-empty {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        color: #6b7280;
    }

    .cfr-show-action-note {
        margin-top: 10px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }

    .cfr-show-empty {
        padding: 48px 24px;
        text-align: center;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .cfr-show-table-card {
            padding: 18px;
        }

        .cfr-show-card {
            padding: 16px;
        }

        .cfr-show-card-value {
            font-size: 22px;
        }
    }
</style>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
    <div>
        <h1>Confirmation of Fund Receipt - {{ $officeName }}</h1>
        <p>Track NADAI acceptance and the attached Confirmation of Fund Receipt per project for this LGU/PLGU.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

@if (session('success'))
    <div style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 16px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 16px; border-radius: 10px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; margin-bottom: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <span>Unable to process the Confirmation of Fund Receipt action.</span>
        </div>
        @foreach ($errors->all() as $error)
            <div style="font-size: 13px; line-height: 1.5;">{{ $error }}</div>
        @endforeach
    </div>
@endif

<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
    @if ($pendingAcceptanceRecords > 0)
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; border-radius: 999px; padding: 8px 14px; font-size: 12px; font-weight: 700;">
            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; background: #f59e0b; color: #fff; font-size: 11px;">{{ $pendingAcceptanceRecords }}</span>
            For NADAI Acceptance
        </div>
    @endif
    @if ($pendingCfrUploadRecords > 0)
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 999px; padding: 8px 14px; font-size: 12px; font-weight: 700;">
            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; background: #2563eb; color: #fff; font-size: 11px;">{{ $pendingCfrUploadRecords }}</span>
            For Upload of CFR
        </div>
    @endif
</div>

<div class="cfr-show-grid" style="margin-bottom: 20px;">
    <div class="cfr-show-card">
        <div class="cfr-show-card-label">Total NADAI Records</div>
        <div class="cfr-show-card-value">{{ $totalNadaiRecords }}</div>
        <div class="cfr-show-card-note">All NADAI submissions adopted from NADAI Management.</div>
    </div>
    <div class="cfr-show-card">
        <div class="cfr-show-card-label">Accepted by LGU</div>
        <div class="cfr-show-card-value">{{ $acceptedNadaiRecords }}</div>
        <div class="cfr-show-card-note">Accepted NADAI records ready for CFR processing.</div>
    </div>
    <div class="cfr-show-card">
        <div class="cfr-show-card-label">CFR Uploaded</div>
        <div class="cfr-show-card-value">{{ $uploadedCfrRecords }}</div>
        <div class="cfr-show-card-note">Projects with attached Confirmation of Fund Receipt files.</div>
    </div>
    <div class="cfr-show-card">
        <div class="cfr-show-card-label">Pending Next Step</div>
        <div class="cfr-show-card-value">{{ $pendingAcceptanceRecords + $pendingCfrUploadRecords }}</div>
        <div class="cfr-show-card-note">{{ $pendingAcceptanceRecords }} awaiting acceptance, {{ $pendingCfrUploadRecords }} awaiting CFR upload.</div>
    </div>
</div>

<div class="cfr-show-card" style="margin-bottom: 20px;">
    <div class="cfr-show-meta-grid">
        <div>
            <label class="cfr-show-meta-label">Province</label>
            <p class="cfr-show-meta-value">{{ $province }}</p>
        </div>
        <div>
            <label class="cfr-show-meta-label">City / Municipality or PLGU</label>
            <p class="cfr-show-meta-value">{{ $officeName }}</p>
        </div>
        <div>
            <label class="cfr-show-meta-label">Source</label>
            <p class="cfr-show-meta-value">NADAI Management uploads</p>
        </div>
        <div>
            <label class="cfr-show-meta-label">Workflow</label>
            <p class="cfr-show-meta-value">Accept NADAI first, then upload CFR</p>
        </div>
    </div>
</div>

<div class="cfr-show-table-card">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
        <div>
            <h2 style="color: #002C76; font-size: 18px; margin: 0; font-weight: 700;">Project Workflow Table</h2>
            <p style="margin: 6px 0 0; color: #6b7280; font-size: 13px; line-height: 1.6;">Each row represents one NADAI record. Users can open the NADAI attachment, confirm acceptance, and upload the matching Confirmation of Fund Receipt without leaving this page.</p>
        </div>
        <div class="cfr-show-note-pill">
            <i class="fas fa-circle-info"></i>
            Same process retained, interface improved for easier scanning.
        </div>
    </div>

    <div class="report-table-scroll">
        <table class="cfr-show-table">
            <thead>
                <tr>
                    <th style="min-width: 230px;">Project</th>
                    <th style="min-width: 270px;">NADAI Submission</th>
                    <th style="min-width: 230px;">Document Status</th>
                    <th style="min-width: 280px;">CFR Attachment</th>
                    <th style="min-width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    @php
                        $uploader = $document->uploaded_by ? ($usersById[$document->uploaded_by] ?? null) : null;
                        $uploaderName = $uploader ? trim(($uploader->fname ?? '') . ' ' . ($uploader->lname ?? '')) : 'Unknown';
                        $acceptor = $document->confirmation_accepted_by ? ($usersById[$document->confirmation_accepted_by] ?? null) : null;
                        $acceptorName = $acceptor ? trim(($acceptor->fname ?? '') . ' ' . ($acceptor->lname ?? '')) : 'Unknown';
                        $isAccepted = (bool) $document->confirmation_accepted_at;
                        $confirmationDocument = $confirmationDocumentsByNadaiId->get($document->id);
                        $needsCfrAction = $isAccepted && !$confirmationDocument;
                        $confirmationUploader = $confirmationDocument?->uploaded_by ? ($usersById[$confirmationDocument->uploaded_by] ?? null) : null;
                        $confirmationUploaderName = $confirmationUploader ? trim(($confirmationUploader->fname ?? '') . ' ' . ($confirmationUploader->lname ?? '')) : 'Unknown';
                    @endphp
                    <tr class="{{ $needsCfrAction ? 'cfr-show-row-needs-action' : '' }}">
                        <td>
                            <div class="cfr-show-project-title">{{ $document->project_title }}</div>
                            <div class="cfr-show-subline">
                                NADAI Date: {{ $document->nadai_date?->format('M d, Y') ?? '—' }}
                            </div>
                            <div class="cfr-show-subline">
                                Record ID: #{{ $document->id }}
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.document', ['office' => $officeName, 'docId' => $document->id]) }}" target="_blank" class="cfr-show-file-link">
                                {{ $document->original_filename }}
                            </a>
                            <div class="cfr-show-subline" style="margin-top: 8px;">
                                Uploaded by: {{ $uploaderName !== '' ? $uploaderName : 'Unknown' }}
                            </div>
                            <div class="cfr-show-subline">
                                Uploaded at: {{ $document->uploaded_at ? $document->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                            </div>
                        </td>
                        <td>
                            @if (!$isAccepted)
                                <span class="cfr-show-status cfr-show-status-pending">
                                    <i class="fas fa-hourglass-half"></i> Pending Acceptance
                                </span>
                                <div style="margin-top: 10px;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: #f59e0b; color: #fff; font-size: 11px; font-weight: 800;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; border-radius: 999px; background: rgba(255,255,255,0.22);">1</span>
                                        For NADAI Acceptance
                                    </span>
                                </div>
                                <div class="cfr-show-action-note">The assigned LGU user needs to confirm receipt of the NADAI first.</div>
                            @elseif (!$confirmationDocument)
                                <span class="cfr-show-status cfr-show-status-accepted">
                                    <i class="fas fa-circle-check"></i> NADAI Accepted
                                </span>
                                <div style="margin-top: 10px;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: #2563eb; color: #fff; font-size: 11px; font-weight: 800;">
                                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; border-radius: 999px; background: rgba(255,255,255,0.22);">1</span>
                                        For Upload of CFR
                                    </span>
                                </div>
                                <div class="cfr-show-subline" style="margin-top: 10px;">
                                    Accepted by: {{ $acceptorName !== '' ? $acceptorName : 'Unknown' }}
                                </div>
                                <div class="cfr-show-subline">
                                    Accepted at: {{ $document->confirmation_accepted_at?->setTimezone(config('app.timezone'))->format('M d, Y h:i A') ?? '—' }}
                                </div>
                            @else
                                <span class="cfr-show-status cfr-show-status-accepted">
                                    <i class="fas fa-circle-check"></i> Complete
                                </span>
                                <div class="cfr-show-subline" style="margin-top: 10px;">
                                    Accepted by: {{ $acceptorName !== '' ? $acceptorName : 'Unknown' }}
                                </div>
                                <div class="cfr-show-subline">
                                    Accepted at: {{ $document->confirmation_accepted_at?->setTimezone(config('app.timezone'))->format('M d, Y h:i A') ?? '—' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($confirmationDocument)
                                <a href="{{ route('reports.one-time.confirmation-of-fund-receipt.attachment', ['office' => $officeName, 'attachmentId' => $confirmationDocument->id]) }}" target="_blank" class="cfr-show-file-link">
                                    {{ $confirmationDocument->original_filename }}
                                </a>
                                <div class="cfr-show-subline" style="margin-top: 8px;">
                                    Uploaded by: {{ $confirmationUploaderName !== '' ? $confirmationUploaderName : 'Unknown' }}
                                </div>
                                <div class="cfr-show-subline">
                                    Uploaded at: {{ $confirmationDocument->uploaded_at ? $confirmationDocument->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                                </div>
                                <div class="cfr-show-subline">
                                    CFR Date: {{ $confirmationDocument->confirmation_date?->format('M d, Y') ?? '—' }}
                                </div>
                            @else
                                <span class="cfr-show-status cfr-show-status-empty">
                                    <i class="fas fa-file-circle-xmark"></i> No CFR Uploaded
                                </span>
                                <div class="cfr-show-action-note">The Confirmation of Fund Receipt attachment will appear here after upload.</div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                @if ($canAccept && !$isAccepted)
                                    <form method="POST" action="{{ route('reports.one-time.confirmation-of-fund-receipt.accept', ['office' => $officeName, 'docId' => $document->id]) }}" onsubmit="return confirm('Accept this uploaded NADAI document for Confirmation of Fund Receipt?');">
                                        @csrf
                                        <button type="submit" style="display: inline-flex; padding: 9px 14px; background-color: #059669; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; align-items: center; gap: 6px;">
                                            <i class="fas fa-check-circle"></i> Accept
                                        </button>
                                    </form>
                                @elseif ($canUploadConfirmation && $isAccepted && !$confirmationDocument)
                                    <button type="button" title="Upload Confirmation of Fund Receipt" onclick="openConfirmationUploadModal({{ $document->id }}, @js($document->project_title))" style="display: inline-flex; padding: 9px 14px; background-color: #002C76; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; align-items: center; gap: 6px;">
                                        <i class="fas fa-upload"></i> Upload CFR
                                    </button>
                                    <div class="cfr-show-subline">Accepted. Upload the matching CFR PDF.</div>
                                @else
                                    <span class="cfr-show-status cfr-show-status-empty">
                                        <i class="fas fa-check"></i> No Action Needed
                                    </span>
                                    @if ($confirmationDocument)
                                        <div class="cfr-show-subline">NADAI accepted and CFR attachment already uploaded.</div>
                                    @elseif (!$isAccepted)
                                        <div class="cfr-show-subline">Waiting for LGU acceptance before CFR upload.</div>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="cfr-show-empty">
                            <i class="fas fa-file-circle-xmark" style="font-size: 30px; margin-bottom: 8px; display: block;"></i>
                            No NADAI documents uploaded for this LGU/PLGU yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($canUploadConfirmation)
    <div id="confirmationUploadModal" style="position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.55); z-index: 2000; padding: 20px;">
        <div style="width: min(100%, 560px); background: #fff; border-radius: 16px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28); overflow: hidden;" onclick="event.stopPropagation()">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 22px; background: #002C76; color: #fff;">
                <div>
                    <div style="font-size: 17px; font-weight: 700;">Upload Confirmation of Fund Receipt</div>
                    <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">Provide the project title, confirmation date, and PDF attachment.</div>
                </div>
                <button type="button" onclick="closeConfirmationUploadModal()" style="border: none; background: transparent; color: rgba(255,255,255,0.8); font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form id="confirmationUploadForm" method="POST" action="" enctype="multipart/form-data" style="padding: 22px;">
                @csrf
                <div style="display: grid; gap: 14px;">
                    <input id="confirmation_nadai_document_id" type="hidden" name="nadai_document_id" value="{{ old('nadai_document_id') }}">
                    <div>
                        <label for="confirmation_project_title" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 700;">Project Title</label>
                        <input id="confirmation_project_title" type="text" name="project_title" value="{{ old('project_title') }}" required style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; color: #111827;">
                    </div>
                    <div>
                        <label for="confirmation_date" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 700;">Date of Confirmation of Fund Receipt</label>
                        <input id="confirmation_date" type="date" name="confirmation_date" value="{{ old('confirmation_date') }}" required style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; color: #111827;">
                    </div>
                    <div>
                        <label for="confirmation_document" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 700;">Upload Confirmation of Fund Receipt Attachment</label>
                        <input id="confirmation_document" type="file" name="document" accept="application/pdf,.pdf" required style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; color: #111827; background: #fff;">
                        <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">Allowed format: PDF only, maximum 15MB.</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeConfirmationUploadModal()" style="padding: 10px 18px; background: #e5e7eb; color: #111827; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 18px; background: #002C76; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;">
                        Save Confirmation of Fund Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openConfirmationUploadModal(docId, projectTitle) {
            const modal = document.getElementById('confirmationUploadModal');
            const form = document.getElementById('confirmationUploadForm');
            const projectTitleInput = document.getElementById('confirmation_project_title');
            const nadaiDocumentIdInput = document.getElementById('confirmation_nadai_document_id');

            if (!modal || !form || !projectTitleInput || !nadaiDocumentIdInput) {
                return;
            }

            nadaiDocumentIdInput.value = docId;
            projectTitleInput.value = projectTitle || '';
            form.action = "{{ url('/reports/one-time/confirmation-of-fund-receipt/' . rawurlencode($officeName) . '/upload') }}/" + docId;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmationUploadModal() {
            const modal = document.getElementById('confirmationUploadModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('confirmationUploadModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeConfirmationUploadModal();
            }
        });

        @if ($errors->has('project_title') || $errors->has('confirmation_date') || $errors->has('document') || $errors->has('confirmation_document'))
            openConfirmationUploadModal({{ (int) old('nadai_document_id', 0) }}, @js(old('project_title', '')));
        @endif
    </script>
@endif
@endsection
