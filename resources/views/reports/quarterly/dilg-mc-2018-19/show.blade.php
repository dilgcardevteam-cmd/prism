@extends('layouts.dashboard')

@section('title', 'DILG MC No. 2018-19')
@section('page-title', 'DILG MC No. 2018-19')

@section('content')
    @php
        $quarters = ['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'];
        $quarterWindows = [
            'Q1' => 'January - March',
            'Q2' => 'April - June',
            'Q3' => 'July - September',
            'Q4' => 'October - December',
        ];
    @endphp

    <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
        <div>
            <h1>DILG MC No. 2018-19 - {{ $office }}</h1>
            <p>Quarterly uploading for monitoring of roads and other similar public works.</p>
        </div>
        <a href="{{ route('reports.quarterly.dilg-mc-2018-19', ['year' => $reportingYear]) }}"
            style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#6b7280;color:#fff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
            <i class="fas fa-arrow-left"></i>
            <span>Back to List</span>
        </a>
    </div>

    @if (session('success'))
        <div style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;padding:14px 16px;border-radius:10px;margin-bottom:18px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:10px;margin-bottom:18px;">
            {{ session('error') }}
        </div>
    @endif

    @if (!empty($setupWarning))
        <div style="background:#fff7ed;border:1px solid #fdba74;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:18px;">
            {{ $setupWarning }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:10px;margin-bottom:18px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
            <div>
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:4px;">Province</div>
                <div style="font-size:15px;font-weight:600;color:#111827;">{{ $province ?? 'Unknown' }}</div>
            </div>
            <div>
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:4px;">City / Municipality</div>
                <div style="font-size:15px;font-weight:600;color:#111827;">{{ $office }}</div>
            </div>
            <div>
                <form method="GET" style="display:flex;flex-direction:column;gap:4px;align-items:flex-start;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Reporting Year</div>
                    <select name="year" onchange="this.form.submit()"
                        style="padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;">
                        @for ($yearOption = now()->year + 1; $yearOption >= now()->year - 5; $yearOption--)
                            <option value="{{ $yearOption }}" @selected($reportingYear === $yearOption)>{{ $yearOption }}</option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="quarterly-submission-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start;">
        @foreach ($quarters as $quarterCode => $quarterLabel)
            @php
                $quarterDocuments = $documentsByQuarter[$quarterCode] ?? collect();
                $latestDocument = $quarterDocuments->first();
            @endphp
            <div class="quarterly-submission-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.05);height:100%;">
                <div style="padding:16px 18px;background:#002c76;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:16px;font-weight:700;">{{ $quarterLabel }}</div>
                        <div style="font-size:12px;opacity:.9;">{{ $quarterWindows[$quarterCode] ?? '' }}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
                        <span style="display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $latestDocument ? '#dcfce7' : '#fef3c7' }};color:{{ $latestDocument ? '#166534' : '#92400e' }};">
                            {{ $latestDocument ? $quarterDocuments->count() . ' Upload' . ($quarterDocuments->count() === 1 ? '' : 's') : 'Pending Upload' }}
                        </span>
                        @if ($canUpload)
                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.edit', ['office' => $office, 'quarter' => $quarterCode, 'year' => $reportingYear]) }}"
                                style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;background:#eff6ff;color:#1d4ed8;text-decoration:none;border:1px solid #bfdbfe;border-radius:999px;font-size:11px;font-weight:700;">
                                <i class="fas fa-pen"></i>
                                <span>Go to Encoding Form</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div style="padding:18px;">
                    @if ($canUpload)
                        <form method="POST"
                            action="{{ route('reports.quarterly.dilg-mc-2018-19.upload', ['office' => $office]) }}"
                            enctype="multipart/form-data"
                            id="dilg-mc-2018-19-upload-form-{{ $quarterCode }}"
                            style="border:1px dashed #bfdbfe;background:#f8fbff;border-radius:12px;padding:16px;display:grid;gap:12px;">
                            @csrf
                            <input type="hidden" name="year" value="{{ $reportingYear }}">
                            <input type="hidden" name="quarter" value="{{ $quarterCode }}">
                            <input type="hidden" name="reupload_document_id" value="">

                            <div style="font-size:13px;font-weight:600;color:#1f2937;">
                                Upload quarterly PDF report
                            </div>

                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                                <label style="flex:1;min-width:220px;display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px dashed #93c5fd;border-radius:10px;background:#eff6ff;color:#1e3a8a;cursor:pointer;">
                                    <i class="fas fa-file-arrow-up" aria-hidden="true"></i>
                                    <span style="font-size:12px;font-weight:700;white-space:nowrap;">Choose PDF</span>
                                    <input type="file" id="dilg-mc-2018-19-upload-{{ $quarterCode }}" name="document" accept="application/pdf"
                                        data-form-id="dilg-mc-2018-19-upload-form-{{ $quarterCode }}"
                                        style="flex:1;min-width:0;border:none;background:transparent;font-size:12px;color:#1e3a8a;cursor:pointer;">
                                </label>
                                <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#002c76;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload Report</span>
                                </button>
                            </div>

                            <div style="font-size:12px;color:#6b7280;">Accepted file type: PDF. Maximum size: 10 MB.</div>
                        </form>
                    @else
                        <div style="padding:14px 16px;border:1px dashed #d1d5db;border-radius:12px;background:#f9fafb;font-size:13px;color:#4b5563;">
                            Uploading is not available for your current account on this workspace.
                        </div>
                    @endif

                    @if ($quarterDocuments->isNotEmpty())
                        <div style="margin-top:16px;border-top:1px solid #e5e7eb;padding-top:16px;">
                            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:12px;">
                                Upload History
                            </div>
                            <div style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;overflow:hidden;">
                                <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                                    <thead>
                                        <tr style="background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                                            <th style="width:28%;padding:10px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">File</th>
                                            <th style="width:18%;padding:10px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Status</th>
                                            <th style="width:18%;padding:10px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Uploaded</th>
                                            <th style="width:18%;padding:10px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Validation</th>
                                            <th style="width:18%;padding:10px 8px;text-align:center;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($quarterDocuments as $document)
                                            @php
                                                $uploadedBy = $document->uploaded_by ? ($usersById[$document->uploaded_by] ?? null) : null;
                                                $uploadedByName = $uploadedBy ? $uploadedBy->fullName() : 'Unknown';
                                                $poApprover = $document->approved_by_dilg_po ? ($usersById[$document->approved_by_dilg_po] ?? null) : null;
                                                $roApprover = $document->approved_by_dilg_ro ? ($usersById[$document->approved_by_dilg_ro] ?? null) : null;
                                                $status = strtolower(trim((string) ($document->status ?? 'pending')));
                                                $statusLabel = 'Pending';
                                                $statusBackground = '#fef3c7';
                                                $statusColor = '#92400e';
                                                $statusBorder = '#fcd34d';
                                                $validationSummary = 'DILG PO';
                                                $validationBadgeBackground = '#eef2ff';
                                                $validationBadgeColor = '#3730a3';
                                                $validationBadgeBorder = '#c7d2fe';
                                                if ($status === 'pending_ro') {
                                                    $statusLabel = 'Pending';
                                                    $statusBackground = '#dbeafe';
                                                    $statusColor = '#1d4ed8';
                                                    $statusBorder = '#93c5fd';
                                                    $validationSummary = 'DILG RO';
                                                    $validationBadgeBackground = '#dbeafe';
                                                    $validationBadgeColor = '#1d4ed8';
                                                    $validationBadgeBorder = '#93c5fd';
                                                } elseif ($status === 'approved') {
                                                    $statusLabel = 'Approved';
                                                    $statusBackground = '#ecfdf5';
                                                    $statusColor = '#047857';
                                                    $statusBorder = '#6ee7b7';
                                                    $validationSummary = null;
                                                } elseif ($status === 'returned') {
                                                    $statusLabel = 'Returned';
                                                    $statusBackground = '#fef2f2';
                                                    $statusColor = '#b91c1c';
                                                    $statusBorder = '#fca5a5';
                                                    $validationSummary = 'Returned';
                                                    $validationBadgeBackground = '#fee2e2';
                                                    $validationBadgeColor = '#b91c1c';
                                                    $validationBadgeBorder = '#fca5a5';
                                                }
                                                $isRegionalOfficeUser = auth()->user()?->isRegionalOfficeAssignment() ?? false;
                                                $isProvincialDilgUser = (auth()->user()?->isDilgUser() ?? false) && !$isRegionalOfficeUser;
                                                $isLguUser = auth()->user()?->isLguScopedUser() ?? false;
                                                $canReviewThisDocument = ($canValidate ?? false) && (
                                                    ($isProvincialDilgUser && in_array($status, ['pending', 'returned'], true))
                                                    || ($isRegionalOfficeUser && $status === 'pending_ro')
                                                );
                                            @endphp
                                            <tr style="border-bottom:1px solid #eef2f7;vertical-align:top;">
                                                <td style="padding:10px 8px;text-align:center;">
                                                    <div style="font-size:12px;font-weight:700;color:#111827;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;margin:0 auto;max-width:240px;" title="{{ $document->original_name ?: ('Upload #' . $loop->iteration) }}">
                                                        {{ $document->original_name ?: ('Upload #' . $loop->iteration) }}
                                                    </div>
                                                </td>
                                                <td style="padding:10px 8px;text-align:center;">
                                                    @if ($statusLabel === 'Returned' && !empty($document->approval_remarks))
                                                        <button type="button"
                                                            class="dilg-mc-2018-19-remarks-trigger"
                                                            data-remarks="{{ $document->approval_remarks }}"
                                                            data-remarks-title="Returned Remarks"
                                                            style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;border:1px solid {{ $statusBorder }};background:{{ $statusBackground }};color:{{ $statusColor }};font-size:10px;font-weight:700;line-height:1.2;cursor:pointer;">
                                                            {{ $statusLabel }}
                                                        </button>
                                                    @else
                                                        <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;border:1px solid {{ $statusBorder }};background:{{ $statusBackground }};color:{{ $statusColor }};font-size:10px;font-weight:700;line-height:1.2;white-space:normal;">
                                                            {{ $statusLabel }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td style="padding:10px 8px;text-align:center;">
                                                    <div style="font-size:11px;color:#334155;line-height:1.35;text-align:center;">
                                                        <div style="white-space:nowrap;">{{ $document->uploaded_at ? $document->uploaded_at->setTimezone(config('app.timezone'))->format('M d, h:i A') : 'Unknown' }}</div>
                                                        <div style="word-break:break-word;">{{ $uploadedByName !== '' ? $uploadedByName : 'Unknown' }}</div>
                                                    </div>
                                                </td>
                                                <td style="padding:10px 8px;text-align:center;">
                                                    <div style="display:grid;gap:4px;font-size:11px;line-height:1.3;color:#334155;justify-items:center;text-align:center;">
                                                        @if ($validationSummary)
                                                            <div>
                                                                @if ($validationSummary === 'Returned' && !empty($document->approval_remarks))
                                                                    <button type="button"
                                                                        class="dilg-mc-2018-19-remarks-trigger"
                                                                        data-remarks="{{ $document->approval_remarks }}"
                                                                        data-remarks-title="Returned Remarks"
                                                                        style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;border:1px solid {{ $validationBadgeBorder }};background:{{ $validationBadgeBackground }};color:{{ $validationBadgeColor }};font-size:10px;font-weight:700;line-height:1.2;cursor:pointer;">
                                                                        {{ $validationSummary }}
                                                                    </button>
                                                                @else
                                                                    <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:999px;border:1px solid {{ $validationBadgeBorder }};background:{{ $validationBadgeBackground }};color:{{ $validationBadgeColor }};font-size:10px;font-weight:700;line-height:1.2;">
                                                                        {{ $validationSummary }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        @if ($document->approved_at_dilg_ro)
                                                            <div style="color:#0f766e;">
                                                                RO: {{ $document->approved_at_dilg_ro->setTimezone(config('app.timezone'))->format('M d h:i A') }}
                                                            </div>
                                                        @endif
                                                        @if (!$validationSummary && !$document->approved_at_dilg_po && !$document->approved_at_dilg_ro)
                                                            <div style="color:#64748b;">No validation action yet.</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td style="padding:10px 8px;text-align:center;">
                                                    <details class="quarterly-action-menu">
                                                        <summary class="quarterly-action-trigger">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                            <span>Actions</span>
                                                        </summary>
                                                        <div class="quarterly-action-panel">
                                                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.document', ['office' => $office, 'docId' => $document->id]) }}"
                                                                target="_blank" rel="noopener noreferrer"
                                                                class="quarterly-action-link">
                                                                <i class="fas fa-eye"></i>
                                                                <span>View</span>
                                                            </a>

                                                            @if ($canDelete)
                                                                <form method="POST"
                                                                    action="{{ route('reports.quarterly.dilg-mc-2018-19.delete-document', ['office' => $office, 'docId' => $document->id, 'year' => $reportingYear]) }}"
                                                                    onsubmit="return confirm('Delete this uploaded document for {{ $quarterLabel }}?');"
                                                                    class="quarterly-action-form">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="quarterly-action-item quarterly-action-item-delete">
                                                                        <i class="fas fa-trash"></i>
                                                                        <span>Delete</span>
                                                                    </button>
                                                                </form>
                                                            @endif

                                                            @if ($canReviewThisDocument)
                                                                <div class="quarterly-action-form">
                                                                    <button type="button"
                                                                        class="quarterly-action-item quarterly-action-item-approve"
                                                                        data-decision-action="approve"
                                                                        data-decision-url="{{ route('reports.quarterly.dilg-mc-2018-19.approve', ['office' => $office, 'docId' => $document->id, 'year' => $reportingYear]) }}"
                                                                        data-decision-subject="{{ $quarterLabel }} · {{ $document->original_name ?: ('Upload #' . $loop->iteration) }}">
                                                                        <i class="fas fa-check"></i>
                                                                        <span>Approve</span>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="quarterly-action-item quarterly-action-item-return"
                                                                        data-decision-action="return"
                                                                        data-decision-url="{{ route('reports.quarterly.dilg-mc-2018-19.approve', ['office' => $office, 'docId' => $document->id, 'year' => $reportingYear]) }}"
                                                                        data-decision-subject="{{ $quarterLabel }} · {{ $document->original_name ?: ('Upload #' . $loop->iteration) }}">
                                                                        <i class="fas fa-undo"></i>
                                                                        <span>Return</span>
                                                                    </button>
                                                                </div>
                                                            @endif

                                                            @if ($status === 'returned' && ($isLguUser || $isProvincialDilgUser))
                                                                <button type="button"
                                                                    class="quarterly-action-item quarterly-action-item-reupload dilg-mc-2018-19-reupload-trigger"
                                                                    data-upload-target="dilg-mc-2018-19-upload-{{ $document->quarter }}"
                                                                    data-document-id="{{ $document->id }}">
                                                                    <i class="fas fa-upload"></i>
                                                                    <span>Reupload</span>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </details>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div id="dilgMc201819UploadBackdrop" aria-hidden="true"></div>
    <div id="dilgMc201819UploadModal" role="dialog" aria-modal="true" aria-labelledby="dilgMc201819UploadTitle" aria-hidden="true">
        <div style="display:flex;flex-direction:column;height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg, #002C76 0%, #003d9e 100%);border-radius:12px 12px 0 0;flex-shrink:0;">
                <div>
                    <h3 id="dilgMc201819UploadTitle" style="color:white;font-size:16px;font-weight:700;margin:0;">Confirm Upload</h3>
                    <p id="dilgMc201819UploadSubtitle" style="margin:4px 0 0;color:rgba(255,255,255,0.82);font-size:12px;"></p>
                </div>
                <button type="button" id="dilgMc201819UploadClose" aria-label="Close upload confirmation" style="border:none;background:rgba(255,255,255,0.15);color:white;width:30px;height:30px;border-radius:999px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="padding:18px 20px;display:grid;gap:14px;">
                <div id="dilgMc201819UploadMessage" style="font-size:13px;line-height:1.6;color:#334155;"></div>
                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                    <button type="button" id="dilgMc201819UploadCancel" style="padding:8px 14px;background:#e5e7eb;color:#111827;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">Cancel</button>
                    <button type="button" id="dilgMc201819UploadConfirm" style="padding:8px 14px;background:#002C76;color:#ffffff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">Continue Upload</button>
                </div>
            </div>
        </div>
    </div>

    <div id="dilgMc201819DecisionBackdrop" aria-hidden="true"></div>
    <div id="dilgMc201819DecisionModal" role="dialog" aria-modal="true" aria-labelledby="dilgMc201819DecisionTitle" aria-hidden="true">
        <div style="display:flex;flex-direction:column;height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg, #002C76 0%, #003d9e 100%);border-radius:12px 12px 0 0;flex-shrink:0;">
                <div>
                    <h3 id="dilgMc201819DecisionTitle" style="color:white;font-size:16px;font-weight:700;margin:0;">Confirm Action</h3>
                    <p id="dilgMc201819DecisionSubtitle" style="margin:4px 0 0;color:rgba(255,255,255,0.82);font-size:12px;"></p>
                </div>
                <button type="button" id="dilgMc201819DecisionClose" aria-label="Close decision modal" style="border:none;background:rgba(255,255,255,0.15);color:white;width:30px;height:30px;border-radius:999px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="dilgMc201819DecisionForm" method="POST" action="" style="padding:18px 20px;display:grid;gap:12px;">
                @csrf
                <input type="hidden" name="action" id="dilgMc201819DecisionAction" value="">
                <div style="font-size:12px;color:#475569;">Add remarks (optional for approve, required for return).</div>
                <textarea name="remarks" id="dilgMc201819DecisionRemarks" rows="3" placeholder="Add remarks..." class="quarterly-action-remarks"></textarea>
                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                    <button type="button" id="dilgMc201819DecisionCancel" style="padding:8px 14px;background:#e5e7eb;color:#111827;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">Cancel</button>
                    <button type="submit" id="dilgMc201819DecisionSubmit" style="padding:8px 14px;background:#002C76;color:#ffffff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dilgMc201819RemarksBackdrop" aria-hidden="true"></div>
    <div id="dilgMc201819RemarksModal" role="dialog" aria-modal="true" aria-labelledby="dilgMc201819RemarksTitle" aria-hidden="true">
        <div style="display:flex;flex-direction:column;height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg, #991b1b 0%, #dc2626 100%);border-radius:12px 12px 0 0;flex-shrink:0;">
                <div>
                    <h3 id="dilgMc201819RemarksTitle" style="color:white;font-size:16px;font-weight:700;margin:0;">Returned Remarks</h3>
                    <p id="dilgMc201819RemarksSubtitle" style="margin:4px 0 0;color:rgba(255,255,255,0.82);font-size:12px;"></p>
                </div>
                <button type="button" id="dilgMc201819RemarksClose" aria-label="Close remarks" style="border:none;background:rgba(255,255,255,0.15);color:white;width:30px;height:30px;border-radius:999px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="padding:18px 20px;">
                <div id="dilgMc201819RemarksBody" style="white-space:pre-wrap;font-size:12px;color:#111827;line-height:1.6;"></div>
                <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                    <button type="button" id="dilgMc201819RemarksOk" style="padding:8px 14px;background:#991b1b;color:#ffffff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">Close</button>
                </div>
            </div>
        </div>
    </div>

        <div id="dilgMc201819ActivityLogModal" role="dialog" aria-modal="true" aria-labelledby="dilgMc201819ActivityLogTitle" aria-hidden="true">
            @php
            $filteredActivityLogs = collect($activityLogs ?? [])
                ->filter(fn ($log) => in_array(($log['category'] ?? null), ['approval', 'create', 'upload'], true))
                ->values();
            @endphp
        <div style="display:flex;flex-direction:column;height:100%;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 24px 16px;background:linear-gradient(135deg, #002C76 0%, #003d9e 100%);border-radius:12px 12px 0 0;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;background:rgba(255,255,255,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-clipboard-list" style="color:white;font-size:14px;"></i>
                    </div>
                    <div>
                        <h3 id="dilgMc201819ActivityLogTitle" style="color:white;font-size:16px;font-weight:700;margin:0;">Activity Logs</h3>
                        <p style="margin:4px 0 0;color:rgba(255,255,255,0.82);font-size:12px;">{{ $office }} workspace audit trail</p>
                    </div>
                </div>
                <button type="button" id="dilgMc201819ActivityLogClose" aria-label="Close activity logs" style="border:none;background:rgba(255,255,255,0.15);color:white;width:30px;height:30px;border-radius:999px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:18px;transition:background 0.2s;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div style="padding:20px 24px;overflow-y:auto;max-height:65vh;">
                @if ($filteredActivityLogs->isEmpty())
                    <div style="padding:40px 20px;text-align:center;">
                        <i class="fas fa-clipboard" style="font-size:36px;margin-bottom:12px;display:block;color:#d1d5db;"></i>
                        <div style="font-size:14px;font-weight:600;color:#6b7280;">No activity logs found for this workspace.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:720px;">
                            <thead>
                                <tr style="background:linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                                    <th style="padding:10px 12px;text-align:left;color:white;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;white-space:nowrap;">Date/Time</th>
                                    <th style="padding:10px 12px;text-align:left;color:white;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Action</th>
                                    <th style="padding:10px 12px;text-align:left;color:white;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Subject</th>
                                    <th style="padding:10px 12px;text-align:left;color:white;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">User</th>
                                    <th style="padding:10px 12px;text-align:left;color:white;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filteredActivityLogs as $index => $log)
                                    @php
                                        $rowBg = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
                                        $actionKey = strtolower((string) ($log['action'] ?? ''));
                                        if (str_contains($actionKey, 'upload') || str_contains($actionKey, 'create')) {
                                            $pillBg = '#d1fae5'; $pillColor = '#065f46';
                                        } elseif (str_contains($actionKey, 'delete')) {
                                            $pillBg = '#fee2e2'; $pillColor = '#991b1b';
                                        } elseif (str_contains($actionKey, 'update')) {
                                            $pillBg = '#dbeafe'; $pillColor = '#1d4ed8';
                                        } elseif (str_contains($actionKey, 'export')) {
                                            $pillBg = '#fef3c7'; $pillColor = '#92400e';
                                        } else {
                                            $pillBg = '#e5e7eb'; $pillColor = '#374151';
                                        }
                                    @endphp
                                    <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding:10px 12px;color:#374151;font-size:12px;white-space:nowrap;">{{ $log['timestamp'] ? $log['timestamp']->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}</td>
                                        <td style="padding:10px 12px;font-size:12px;">
                                            <span style="display:inline-block;padding:2px 8px;background-color:{{ $pillBg }};color:{{ $pillColor }};border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">{{ $log['action'] }}</span>
                                        </td>
                                        <td style="padding:10px 12px;color:#374151;font-size:12px;">{{ $log['subject'] ?: 'Workspace' }}</td>
                                        <td style="padding:10px 12px;color:#374151;font-size:12px;white-space:nowrap;">
                                            {{ $log['user_name'] ?: 'Unknown' }}
                                            @if (!empty($log['device']))
                                                <div style="margin-top:4px;color:#6b7280;font-size:10px;white-space:normal;">{{ $log['device'] }}</div>
                                            @endif
                                        </td>
                                        <td style="padding:10px 12px;color:#6b7280;font-size:12px;">{{ $log['details'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="dilgMc201819ActivityLogBackdrop" aria-hidden="true"></div>

    <button id="dilgMc201819ActivityLogFab" type="button" aria-controls="dilgMc201819ActivityLogModal" aria-expanded="false" data-state="closed">
        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
        <span>Activity Logs</span>
    </button>

    <style>
        .quarterly-submission-card {
            display: flex;
            flex-direction: column;
        }

        .quarterly-action-menu {
            position: relative;
            display: inline-block;
        }

        .quarterly-action-menu summary::-webkit-details-marker {
            display: none;
        }

        .quarterly-action-trigger {
            list-style: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 88px;
            padding: 7px 12px;
            border: 1px solid #1e293b;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #1e3a8a;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
            transition: border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .quarterly-action-trigger:hover {
            border-color: #2563eb;
            color: #1d4ed8;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
            transform: translateY(-1px);
        }

        .quarterly-action-menu[open] .quarterly-action-trigger {
            border-color: #93c5fd;
            color: #1d4ed8;
            background: #eff6ff;
        }

        .quarterly-action-panel {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            padding: 12px;
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.2);
            display: grid;
            gap: 10px;
            z-index: 1200;
        }

        .quarterly-action-link,
        .quarterly-action-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: #0f172a;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-align: left;
        }

        .quarterly-action-link {
            justify-content: flex-start;
        }

        .quarterly-action-link:hover,
        .quarterly-action-item:hover {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .quarterly-action-form {
            display: grid;
            gap: 8px;
        }

        .quarterly-action-remarks {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            font-size: 11px;
            resize: vertical;
        }

        .quarterly-action-item-approve {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-color: #6ee7b7;
            color: #065f46;
        }

        .quarterly-action-item-approve:hover {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #34d399;
            color: #065f46;
        }

        .quarterly-action-item-return {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #fca5a5;
            color: #991b1b;
        }

        .quarterly-action-item-return:hover {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-color: #f87171;
            color: #991b1b;
        }

        #dilgMc201819ActivityLogBackdrop {
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

        #dilgMc201819ActivityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #dilgMc201819ActivityLogModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(960px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1200;
        }

        #dilgMc201819ActivityLogModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        #dilgMc201819DecisionBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1210;
        }

        #dilgMc201819DecisionBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #dilgMc201819UploadBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1200;
        }

        #dilgMc201819UploadBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #dilgMc201819UploadModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(520px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1215;
        }

        #dilgMc201819UploadModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        #dilgMc201819RemarksBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1230;
        }

        #dilgMc201819RemarksBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #dilgMc201819RemarksModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(520px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1240;
        }

        #dilgMc201819RemarksModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        #dilgMc201819DecisionModal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.96);
            opacity: 0;
            visibility: hidden;
            width: min(520px, 92vw);
            max-height: 85vh;
            overflow: hidden;
            background: white;
            border-radius: 12px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1220;
        }

        #dilgMc201819DecisionModal.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        body.modal-open-dilg-mc-2018-19-logs {
            overflow: hidden;
        }

        body.modal-open-dilg-mc-2018-19-decision {
            overflow: hidden;
        }

        body.modal-open-dilg-mc-2018-19-upload {
            overflow: hidden;
        }

        body.modal-open-dilg-mc-2018-19-remarks {
            overflow: hidden;
        }

        #dilgMc201819ActivityLogFab {
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

        #dilgMc201819ActivityLogFab:hover {
            background-color: #003d9e;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 44, 118, 0.4);
        }

        #dilgMc201819ActivityLogFab:active {
            transform: translateY(0);
        }

        #dilgMc201819ActivityLogFab[data-state="open"] {
            background-color: #0f172a;
        }

        .quarterly-action-item-delete {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border-color: #fdba74;
            color: #9a3412;
        }

        .quarterly-action-item-delete:hover {
            background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
            border-color: #fb923c;
            color: #9a3412;
        }

        .quarterly-action-item-reupload {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .quarterly-action-item-reupload:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #60a5fa;
            color: #1e40af;
        }

        @media (max-width: 991.98px) {
            .quarterly-submission-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 767.98px) {
            .quarterly-submission-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 575.98px) {
            .quarterly-action-panel {
                width: min(240px, calc(100vw - 24px));
            }

            #dilgMc201819ActivityLogFab span {
                display: none;
            }

            #dilgMc201819ActivityLogFab {
                width: 52px;
                height: 52px;
                padding: 0;
                border-radius: 50%;
            }

            #dilgMc201819ActivityLogModal {
                width: min(96vw, 96vw);
            }

            #dilgMc201819DecisionModal {
                width: min(94vw, 94vw);
            }

            #dilgMc201819UploadModal {
                width: min(94vw, 94vw);
            }

            #dilgMc201819RemarksModal {
                width: min(94vw, 94vw);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewportPadding = 12;
            const menus = Array.from(document.querySelectorAll('.quarterly-action-menu'));

            function positionActionMenu(menu) {
                const trigger = menu.querySelector('.quarterly-action-trigger');
                const panel = menu.querySelector('.quarterly-action-panel');

                if (!trigger || !panel || !menu.hasAttribute('open')) {
                    return;
                }

                panel.style.top = '0px';
                panel.style.left = '0px';

                const triggerRect = trigger.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const spaceOnLeft = triggerRect.left - viewportPadding;
                const spaceOnRight = window.innerWidth - triggerRect.right - viewportPadding;

                let left = triggerRect.left - panelRect.width - 10;
                let top = triggerRect.top;

                if (spaceOnLeft < panelRect.width && spaceOnRight > spaceOnLeft) {
                    left = triggerRect.right + 10;
                }

                if (left < viewportPadding) {
                    left = Math.max(viewportPadding, triggerRect.right - panelRect.width);
                    top = triggerRect.bottom + 8;
                }

                if (left + panelRect.width > window.innerWidth - viewportPadding) {
                    left = window.innerWidth - panelRect.width - viewportPadding;
                }

                if (top + panelRect.height > window.innerHeight - viewportPadding) {
                    top = Math.max(viewportPadding, window.innerHeight - panelRect.height - viewportPadding);
                }

                panel.style.left = `${Math.round(left)}px`;
                panel.style.top = `${Math.round(top)}px`;
            }

            function closeOtherMenus(activeMenu) {
                menus.forEach(function (menu) {
                    if (menu !== activeMenu) {
                        menu.removeAttribute('open');
                    }
                });
            }

            menus.forEach(function (menu) {
                menu.addEventListener('toggle', function () {
                    if (menu.hasAttribute('open')) {
                        closeOtherMenus(menu);
                        positionActionMenu(menu);
                    }
                });
            });

            window.addEventListener('resize', function () {
                document.querySelectorAll('.quarterly-action-menu[open]').forEach(function (menu) {
                    positionActionMenu(menu);
                });
            });

            window.addEventListener('scroll', function () {
                document.querySelectorAll('.quarterly-action-menu[open]').forEach(function (menu) {
                    positionActionMenu(menu);
                });
            }, true);

            document.addEventListener('click', function (event) {
                if (event.target.closest('.quarterly-action-menu')) {
                    return;
                }

                document.querySelectorAll('.quarterly-action-menu[open]').forEach(function (menu) {
                    menu.removeAttribute('open');
                });
            });

            document.querySelectorAll('.dilg-mc-2018-19-reupload-trigger').forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.dataset.uploadTarget || '';
                    const input = targetId ? document.getElementById(targetId) : null;
                    if (!input) {
                        return;
                    }

                    const formId = input.dataset.formId || '';
                    const form = formId ? document.getElementById(formId) : input.closest('form');
                    const reuploadField = form ? form.querySelector('input[name="reupload_document_id"]') : null;
                    if (reuploadField) {
                        reuploadField.value = button.dataset.documentId || '';
                    }

                    input.dataset.autoSubmit = '1';
                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    input.click();
                });
            });

            const uploadModal = document.getElementById('dilgMc201819UploadModal');
            const uploadBackdrop = document.getElementById('dilgMc201819UploadBackdrop');
            const uploadClose = document.getElementById('dilgMc201819UploadClose');
            const uploadCancel = document.getElementById('dilgMc201819UploadCancel');
            const uploadConfirm = document.getElementById('dilgMc201819UploadConfirm');
            const uploadTitle = document.getElementById('dilgMc201819UploadTitle');
            const uploadSubtitle = document.getElementById('dilgMc201819UploadSubtitle');
            const uploadMessage = document.getElementById('dilgMc201819UploadMessage');
            let pendingUploadForm = null;
            let pendingUploadInput = null;
            let pendingUploadMode = 'upload';

            function setUploadVisibility(isVisible) {
                if (!uploadModal || !uploadBackdrop) {
                    return;
                }

                uploadModal.classList.toggle('is-visible', isVisible);
                uploadBackdrop.classList.toggle('is-visible', isVisible);
                uploadModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                uploadBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                document.body.classList.toggle('modal-open-dilg-mc-2018-19-upload', isVisible);

                if (isVisible && uploadConfirm) {
                    uploadConfirm.focus();
                }
            }

            function clearPendingUpload(options) {
                const shouldClearFile = !!(options && options.clearFile);
                if (shouldClearFile && pendingUploadInput) {
                    pendingUploadInput.value = '';
                }

                if (pendingUploadForm) {
                    const reuploadField = pendingUploadForm.querySelector('input[name="reupload_document_id"]');
                    if (reuploadField && pendingUploadMode === 'replace') {
                        reuploadField.value = '';
                    }
                    pendingUploadForm.dataset.uploadConfirmed = '0';
                }

                pendingUploadForm = null;
                pendingUploadInput = null;
                pendingUploadMode = 'upload';
            }

            function openUploadModal(form, input, mode) {
                if (!form || !input || !input.files || input.files.length === 0) {
                    return;
                }

                pendingUploadForm = form;
                pendingUploadInput = input;
                pendingUploadMode = mode === 'replace' ? 'replace' : 'upload';

                const fileName = input.files[0] ? input.files[0].name : 'this file';
                const quarterField = form.querySelector('input[name="quarter"]');
                const quarterLabel = quarterField ? quarterField.value : '';

                if (uploadTitle) {
                    uploadTitle.textContent = pendingUploadMode === 'replace' ? 'Confirm Reupload' : 'Confirm Upload';
                }

                if (uploadSubtitle) {
                    uploadSubtitle.textContent = quarterLabel ? `${quarterLabel} submission` : 'Quarterly report submission';
                }

                if (uploadMessage) {
                    uploadMessage.textContent = pendingUploadMode === 'replace'
                        ? `Replace the returned document with "${fileName}"?`
                        : `Upload "${fileName}" for this quarter?`;
                }

                if (uploadConfirm) {
                    uploadConfirm.textContent = pendingUploadMode === 'replace' ? 'Replace File' : 'Upload File';
                }

                setUploadVisibility(true);
            }

            document.querySelectorAll('form[id^="dilg-mc-2018-19-upload-form-"]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.uploadConfirmed === '1') {
                        form.dataset.uploadConfirmed = '0';
                        return;
                    }

                    const input = form.querySelector('input[name="document"]');
                    if (!input || !input.files || input.files.length === 0) {
                        return;
                    }

                    event.preventDefault();
                    const reuploadField = form.querySelector('input[name="reupload_document_id"]');
                    const mode = reuploadField && reuploadField.value ? 'replace' : 'upload';
                    openUploadModal(form, input, mode);
                });
            });

            document.querySelectorAll('[data-form-id]').forEach(function (input) {
                if (input.dataset.autoSubmitBound === '1') {
                    return;
                }

                input.dataset.autoSubmitBound = '1';
                input.addEventListener('change', function () {
                    if (input.dataset.autoSubmit !== '1') {
                        return;
                    }

                    const hasFile = input.files && input.files.length > 0;
                    input.dataset.autoSubmit = '0';
                    const formId = input.dataset.formId || '';
                    const form = formId ? document.getElementById(formId) : input.closest('form');
                    const reuploadField = form ? form.querySelector('input[name="reupload_document_id"]') : null;

                    if (!hasFile) {
                        if (reuploadField) {
                            reuploadField.value = '';
                        }
                        return;
                    }

                    if (form) {
                        openUploadModal(form, input, reuploadField && reuploadField.value ? 'replace' : 'upload');
                    }
                });
            });

            if (uploadBackdrop) {
                uploadBackdrop.addEventListener('click', function () {
                    setUploadVisibility(false);
                    clearPendingUpload({ clearFile: pendingUploadMode === 'replace' });
                });
            }

            if (uploadClose) {
                uploadClose.addEventListener('click', function () {
                    setUploadVisibility(false);
                    clearPendingUpload({ clearFile: pendingUploadMode === 'replace' });
                });
            }

            if (uploadCancel) {
                uploadCancel.addEventListener('click', function () {
                    setUploadVisibility(false);
                    clearPendingUpload({ clearFile: pendingUploadMode === 'replace' });
                });
            }

            if (uploadConfirm) {
                uploadConfirm.addEventListener('click', function () {
                    if (!pendingUploadForm) {
                        setUploadVisibility(false);
                        return;
                    }

                    pendingUploadForm.dataset.uploadConfirmed = '1';
                    setUploadVisibility(false);

                    if (typeof pendingUploadForm.requestSubmit === 'function') {
                        pendingUploadForm.requestSubmit();
                    } else {
                        pendingUploadForm.submit();
                    }

                    clearPendingUpload();
                });
            }

            const decisionModal = document.getElementById('dilgMc201819DecisionModal');
            const decisionBackdrop = document.getElementById('dilgMc201819DecisionBackdrop');
            const decisionClose = document.getElementById('dilgMc201819DecisionClose');
            const decisionCancel = document.getElementById('dilgMc201819DecisionCancel');
            const decisionForm = document.getElementById('dilgMc201819DecisionForm');
            const decisionActionInput = document.getElementById('dilgMc201819DecisionAction');
            const decisionRemarks = document.getElementById('dilgMc201819DecisionRemarks');
            const decisionTitle = document.getElementById('dilgMc201819DecisionTitle');
            const decisionSubtitle = document.getElementById('dilgMc201819DecisionSubtitle');
            const decisionSubmit = document.getElementById('dilgMc201819DecisionSubmit');

            function setDecisionVisibility(isVisible) {
                if (!decisionModal || !decisionBackdrop) {
                    return;
                }

                decisionModal.classList.toggle('is-visible', isVisible);
                decisionBackdrop.classList.toggle('is-visible', isVisible);
                decisionModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                decisionBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                document.body.classList.toggle('modal-open-dilg-mc-2018-19-decision', isVisible);

                if (isVisible && decisionRemarks) {
                    decisionRemarks.focus();
                }
            }

            function openDecisionModal(action, url, subject) {
                if (!decisionForm || !decisionActionInput) {
                    return;
                }

                decisionForm.action = url || '';
                decisionActionInput.value = action;
                if (decisionRemarks) {
                    decisionRemarks.value = '';
                    decisionRemarks.required = action === 'return';
                    decisionRemarks.placeholder = action === 'return' ? 'Remarks required for return.' : 'Add remarks (optional).';
                }

                if (decisionTitle) {
                    decisionTitle.textContent = action === 'return' ? 'Return Document' : 'Approve Document';
                }

                if (decisionSubtitle) {
                    decisionSubtitle.textContent = subject ? subject : '';
                }

                if (decisionSubmit) {
                    decisionSubmit.textContent = action === 'return' ? 'Return' : 'Approve';
                    decisionSubmit.style.backgroundColor = action === 'return' ? '#dc2626' : '#002C76';
                }

                setDecisionVisibility(true);
            }

            document.querySelectorAll('[data-decision-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const action = button.dataset.decisionAction || 'approve';
                    const url = button.dataset.decisionUrl || '';
                    const subject = button.dataset.decisionSubject || '';
                    document.querySelectorAll('.quarterly-action-menu[open]').forEach(function (menu) {
                        menu.removeAttribute('open');
                    });
                    openDecisionModal(action, url, subject);
                });
            });

            if (decisionBackdrop) {
                decisionBackdrop.addEventListener('click', function () {
                    setDecisionVisibility(false);
                });
            }

            if (decisionClose) {
                decisionClose.addEventListener('click', function () {
                    setDecisionVisibility(false);
                });
            }

            if (decisionCancel) {
                decisionCancel.addEventListener('click', function () {
                    setDecisionVisibility(false);
                });
            }

            const remarksModal = document.getElementById('dilgMc201819RemarksModal');
            const remarksBackdrop = document.getElementById('dilgMc201819RemarksBackdrop');
            const remarksClose = document.getElementById('dilgMc201819RemarksClose');
            const remarksOk = document.getElementById('dilgMc201819RemarksOk');
            const remarksBody = document.getElementById('dilgMc201819RemarksBody');
            const remarksSubtitle = document.getElementById('dilgMc201819RemarksSubtitle');

            function setRemarksVisibility(isVisible) {
                if (!remarksModal || !remarksBackdrop) {
                    return;
                }

                remarksModal.classList.toggle('is-visible', isVisible);
                remarksBackdrop.classList.toggle('is-visible', isVisible);
                remarksModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                remarksBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                document.body.classList.toggle('modal-open-dilg-mc-2018-19-remarks', isVisible);
            }

            document.querySelectorAll('.dilg-mc-2018-19-remarks-trigger').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!remarksBody) {
                        return;
                    }

                    const remarks = button.dataset.remarks || 'No remarks provided.';
                    const title = button.dataset.remarksTitle || '';
                    if (remarksSubtitle) {
                        remarksSubtitle.textContent = title;
                    }
                    remarksBody.textContent = remarks;
                    setRemarksVisibility(true);
                });
            });

            if (remarksBackdrop) {
                remarksBackdrop.addEventListener('click', function () {
                    setRemarksVisibility(false);
                });
            }

            if (remarksClose) {
                remarksClose.addEventListener('click', function () {
                    setRemarksVisibility(false);
                });
            }

            if (remarksOk) {
                remarksOk.addEventListener('click', function () {
                    setRemarksVisibility(false);
                });
            }

            const activityLogModal = document.getElementById('dilgMc201819ActivityLogModal');
            const activityLogBackdrop = document.getElementById('dilgMc201819ActivityLogBackdrop');
            const activityLogFab = document.getElementById('dilgMc201819ActivityLogFab');
            const activityLogClose = document.getElementById('dilgMc201819ActivityLogClose');

            function setActivityLogVisibility(isVisible) {
                if (!activityLogModal || !activityLogBackdrop || !activityLogFab) {
                    return;
                }

                activityLogModal.classList.toggle('is-visible', isVisible);
                activityLogBackdrop.classList.toggle('is-visible', isVisible);
                activityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
                activityLogFab.dataset.state = isVisible ? 'open' : 'closed';
                activityLogModal.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                activityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
                document.body.classList.toggle('modal-open-dilg-mc-2018-19-logs', isVisible);

                if (isVisible && activityLogClose) {
                    activityLogClose.focus();
                }
            }

            if (activityLogFab && activityLogModal && activityLogBackdrop) {
                activityLogFab.addEventListener('click', function () {
                    const isOpen = activityLogModal.classList.contains('is-visible');
                    setActivityLogVisibility(!isOpen);
                });

                activityLogBackdrop.addEventListener('click', function () {
                    setActivityLogVisibility(false);
                });

                if (activityLogClose) {
                    activityLogClose.addEventListener('click', function () {
                        setActivityLogVisibility(false);
                    });
                }
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    if (uploadModal && uploadModal.classList.contains('is-visible')) {
                        setUploadVisibility(false);
                        clearPendingUpload({ clearFile: pendingUploadMode === 'replace' });
                    }

                    if (remarksModal && remarksModal.classList.contains('is-visible')) {
                        setRemarksVisibility(false);
                    }

                    if (decisionModal && decisionModal.classList.contains('is-visible')) {
                        setDecisionVisibility(false);
                    }

                    if (activityLogModal && activityLogModal.classList.contains('is-visible')) {
                        setActivityLogVisibility(false);
                    }
                }
            });
        });
    </script>
@endsection
