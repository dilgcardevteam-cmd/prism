@extends('layouts.dashboard')

@section('title', 'SWA- Annex F - Update')
@section('page-title', 'Update SWA- Annex F')

@section('content')
    <div class="ops-detail-page">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
            <div>
                <h1>Update - {{ $officeName }}</h1>
                <p>Upload or update monthly SWA- Annex F submissions for this office.</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('reports.monthly.swa-annex-f', ['year' => $reportingYear]) }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
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
                        <label for="swa-annex-f-year" style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">Reporting Year</label>
                        <select id="swa-annex-f-year" name="year" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background-color: #fff;">
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
                Monthly SWA- Annex F Uploads (CY {{ $reportingYear }})
            </h2>

            <div style="display: grid; gap: 12px;">
                @foreach ($months as $monthCode => $label)
                    @php
                        $docKey = 'swa_annex_f|' . $reportingYear . '|' . $monthCode;
                        $doc = $documentsByKey[$docKey] ?? null;
                        $inputId = 'swa-annex-f-input-' . $monthCode;
                        $buttonId = 'swa-annex-f-btn-' . $monthCode;
                        $filenameId = 'swa-annex-f-file-' . $monthCode;
                        $isRegionalOfficeUserForUpload = Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office';
                        $hasFile = $doc && $doc->file_path;
                        $isReturned = $doc && $doc->status === 'returned';
                        $disableUploadInput = ($hasFile && !$isReturned) || $isRegionalOfficeUserForUpload;
                        $isApprovedRo = $doc && $doc->approved_at_dilg_ro;
                        $isPendingRo = $doc && $doc->approved_at_dilg_po && !$doc->approved_at_dilg_ro;
                        $isExpandedByDefault = $loop->first;
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

                        $uploaderUser = $doc && $doc->uploaded_by && isset($usersById[$doc->uploaded_by]) ? $usersById[$doc->uploaded_by] : null;
                        $poApprover = $doc && $doc->approved_by_dilg_po && isset($usersById[$doc->approved_by_dilg_po]) ? $usersById[$doc->approved_by_dilg_po] : null;
                        $roApprover = $doc && $doc->approved_by_dilg_ro && isset($usersById[$doc->approved_by_dilg_ro]) ? $usersById[$doc->approved_by_dilg_ro] : null;
                        $uploaderName = $uploaderUser ? trim($uploaderUser->fname . ' ' . $uploaderUser->lname) : 'Unknown';
                        $poApproverName = $poApprover ? trim($poApprover->fname . ' ' . $poApprover->lname) : 'Unknown';
                        $roApproverName = $roApprover ? trim($roApprover->fname . ' ' . $roApprover->lname) : 'Unknown';
                        $uploadedTime = $doc && $doc->uploaded_at ? $doc->uploaded_at->copy()->setTimezone(config('app.timezone')) : null;
                        $poValidatedAt = $doc && $doc->approved_at_dilg_po ? $doc->approved_at_dilg_po->copy()->setTimezone(config('app.timezone')) : null;
                        $roValidatedAt = $doc && $doc->approved_at_dilg_ro ? $doc->approved_at_dilg_ro->copy()->setTimezone(config('app.timezone')) : null;
                        $returnedAt = $doc && $doc->status === 'returned' && $doc->approved_at ? $doc->approved_at->copy()->setTimezone(config('app.timezone')) : null;
                        $returnedByName = $roApproverName !== 'Unknown' ? $roApproverName : $poApproverName;
                        $returnedByLevel = $doc && $doc->approved_by_dilg_ro ? 'DILG Regional Office' : ($doc && $doc->approved_by_dilg_po ? 'DILG Provincial Office' : null);
                        $returnedRemarks = trim((string) ($doc->approval_remarks ?? '')) ?: null;
                        $timelineEvents = [];

                        if ($uploadedTime) {
                            $timelineEvents[] = [
                                'message' => 'Uploaded at: ' . $uploadedTime->format('M d, Y h:i A') . ' by ' . $uploaderName,
                                'color' => '#6b7280',
                            ];
                        }

                        if ($poValidatedAt) {
                            $timelineEvents[] = [
                                'message' => 'DILG Provincial Validated at: ' . $poValidatedAt->format('M d, Y h:i A') . ' by ' . $poApproverName,
                                'color' => '#059669',
                            ];
                        }

                        if ($roValidatedAt) {
                            $timelineEvents[] = [
                                'message' => 'DILG Regional Validated at: ' . $roValidatedAt->format('M d, Y h:i A') . ' by ' . $roApproverName,
                                'color' => '#0891b2',
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
                                'message' => 'Returned at: ' . ($returnedAt ? $returnedAt->format('M d, Y h:i A') : '-') . ' by ' . $returnedByName . $returnSuffix,
                                'color' => '#dc2626',
                            ];
                        }
                    @endphp
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                        <button
                            type="button"
                            class="swa-annex-f-accordion-toggle"
                            data-target="swa-annex-f-{{ $monthCode }}"
                            aria-expanded="{{ $isExpandedByDefault ? 'true' : 'false' }}"
                            style="width: 100%; padding: 14px 16px; background-color: #002C76; color: white; border: none; text-align: left; cursor: pointer; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;"
                        >
                            <span>{{ $label }} - SWA- Annex F</span>
                            <span style="display: inline-flex; align-items: center; gap: 10px;">
                                <span style="display: inline-block; padding: 4px 10px; background-color: {{ $statusColor }}; color: white; border: 1px solid rgba(255,255,255,0.25); border-radius: 20px; font-size: 10px; font-weight: 600;">
                                    {{ $statusLabel }}
                                </span>
                                <i class="fas fa-chevron-down" style="transition: transform 0.3s; transform: {{ $isExpandedByDefault ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                            </span>
                        </button>
                        <div id="swa-annex-f-{{ $monthCode }}" style="display: {{ $isExpandedByDefault ? 'block' : 'none' }}; padding: 16px; background-color: #ffffff;">
                            <form method="POST" action="{{ route('reports.monthly.swa-annex-f.upload', $officeName) }}" enctype="multipart/form-data" style="border: 1px dashed #cbd5f5; padding: 16px; border-radius: 8px; background-color: #f9fafb;">
                                @csrf
                                <input type="hidden" name="year" value="{{ $reportingYear }}">
                                <input type="hidden" name="month" value="{{ $monthCode }}">
                                <label style="display: block; color: #374151; font-weight: 600; font-size: 13px; margin: 0 0 8px 0;">
                                    {{ $label }} Upload
                                </label>
                                <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
                                    @forelse ($timelineEvents as $timelineEvent)
                                        <div style="display: block; font-size: 11px; color: {{ $timelineEvent['color'] }}; {{ $loop->first ? '' : 'margin-top: 4px;' }}">
                                            {{ $timelineEvent['message'] }}
                                        </div>
                                    @empty
                                        <div>No submission activity yet.</div>
                                    @endforelse
                                </div>
                                @if ($doc && $doc->file_path)
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                                        <a href="{{ route('reports.monthly.swa-annex-f.document', [$officeName, $doc->id]) }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; color: #002C76; font-size: 12px; text-decoration: none;">
                                            <i class="fas fa-file"></i>&nbsp;View current file
                                        </a>
                                        @if (Auth::user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('reports.monthly.swa-annex-f.delete-document', ['office' => $officeName, 'docId' => $doc->id]) }}" onsubmit="return confirm('Delete this uploaded document? This action cannot be undone.');" style="display: inline;">
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
                                <input
                                    id="{{ $inputId }}"
                                    type="file"
                                    name="document"
                                    accept=".pdf,application/pdf"
                                    required
                                    @disabled($disableUploadInput)
                                    class="ops-upload-input"
                                    style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; margin-bottom: 8px; background-color: {{ $disableUploadInput ? '#f3f4f6' : '#ffffff' }}; cursor: {{ $disableUploadInput ? 'not-allowed' : 'auto' }};"
                                    onchange="showSwaAnnexFSaveButton(this, '{{ $buttonId }}', '{{ $filenameId }}')"
                                >
                                <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                    Remarks: Only PDF files are allowed (maximum 15MB).
                                </div>
                                @if ($disableUploadInput)
                                    <div style="margin-bottom: 8px; font-size: 11px; color: #6b7280;">
                                        @if ($isRegionalOfficeUserForUpload)
                                            Regional Office cannot upload files. Choose file is disabled.
                                        @else
                                            File already uploaded for this month. Choose file is disabled.
                                        @endif
                                    </div>
                                @endif
                                @if ($showApprovalButtons)
                                    <div style="display: flex; gap: 6px; margin-top: 8px; margin-bottom: 8px; justify-content: flex-start; align-items: center;">
                                        <button type="button" onclick="openSwaAnnexFApprovalModal({{ $doc->id }}, 'approve')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                            <i class="fas fa-check"></i>
                                            <span>Approve</span>
                                        </button>
                                        @if (!$hideReturnButton)
                                            <button type="button" onclick="openSwaAnnexFApprovalModal({{ $doc->id }}, 'return')" style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 11px; line-height: 1;">
                                                <i class="fas fa-undo"></i>
                                                <span>Return</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                                <div id="{{ $filenameId }}" class="ops-upload-filename" style="display: none; margin-bottom: 8px; font-size: 12px; color: #6b7280;"></div>
                                <button
                                    type="submit"
                                    id="{{ $buttonId }}"
                                    class="ops-upload-submit"
                                    style="width: 25%; padding: 8px 12px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; opacity: 0; pointer-events: none; transition: all 0.3s ease; display: block; margin-left: 0; margin-right: auto;"
                                >
                                    Upload
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div id="swaAnnexFApprovalModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-width: 420px; width: 90%;">
                <h3 id="swaAnnexFApprovalTitle" style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Approve Document</h3>
                <form id="swaAnnexFApprovalForm" method="POST">
                    @csrf
                    <input type="hidden" name="action" id="swaAnnexFApprovalAction">
                    <textarea id="swaAnnexFApprovalRemarks" name="remarks" placeholder="Enter remarks (required for return)..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                        <button type="button" onclick="closeSwaAnnexFApprovalModal()" style="padding: 10px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Cancel</button>
                        <button type="submit" id="swaAnnexFApprovalSubmit" style="padding: 10px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Confirm</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.querySelectorAll('.swa-annex-f-accordion-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetId = button.getAttribute('data-target');
                    const panel = document.getElementById(targetId);
                    if (!panel) return;

                    const isOpen = panel.style.display === 'block';

                    if (!isOpen) {
                        document.querySelectorAll('.swa-annex-f-accordion-toggle').forEach(function (otherBtn) {
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

            function showSwaAnnexFSaveButton(fileInput, buttonId, filenameId) {
                const saveBtn = document.getElementById(buttonId);
                const filenameDiv = document.getElementById(filenameId);
                if (!saveBtn || !filenameDiv) return;

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
                    filenameDiv.textContent = 'Selected: ' + fileName;
                    filenameDiv.style.color = '#6b7280';
                    filenameDiv.style.display = 'block';
                    filenameDiv.classList.add('has-file');
                } else {
                    saveBtn.style.opacity = '0';
                    saveBtn.style.pointerEvents = 'none';
                    filenameDiv.style.display = 'none';
                    filenameDiv.classList.remove('has-file');
                }
            }

            function openSwaAnnexFApprovalModal(docId, action) {
                const modal = document.getElementById('swaAnnexFApprovalModal');
                const form = document.getElementById('swaAnnexFApprovalForm');
                const title = document.getElementById('swaAnnexFApprovalTitle');
                const actionInput = document.getElementById('swaAnnexFApprovalAction');
                const remarks = document.getElementById('swaAnnexFApprovalRemarks');
                const submitBtn = document.getElementById('swaAnnexFApprovalSubmit');

                form.action = '{{ url('/reports/monthly/swa-annex-f') }}/' + encodeURIComponent('{{ $officeName }}') + '/approve/' + docId;
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

            function closeSwaAnnexFApprovalModal() {
                document.getElementById('swaAnnexFApprovalModal').style.display = 'none';
            }

            window.addEventListener('click', function (event) {
                const modal = document.getElementById('swaAnnexFApprovalModal');
                if (event.target === modal) {
                    closeSwaAnnexFApprovalModal();
                }
            });
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

            .ops-detail-page .ops-upload-input:disabled {
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
        </style>
    </div>
@endsection

