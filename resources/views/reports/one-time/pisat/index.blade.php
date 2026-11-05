@extends('layouts.dashboard')

@section('title', 'PISAT Dashboard')
@section('page-title', 'PISAT Dashboard')

@section('content')
    <style>
        .pisat-page { max-width: 1540px; margin: 0 auto; }
        .pisat-hero { position:relative; overflow:hidden; padding:28px 30px; border-radius:18px; background:linear-gradient(120deg,#062b68 0%,#0b4b96 58%,#0f766e 100%); color:#fff; box-shadow:0 12px 30px rgba(15,61,112,.16); }
        .pisat-hero:after { content:""; position:absolute; width:220px; height:220px; right:8%; top:-110px; border:32px solid rgba(255,255,255,.08); border-radius:50%; }
        .pisat-hero h1, .pisat-hero p, .pisat-hero div { position:relative; z-index:1; }
        .pisat-hero h1 { color:#fff !important; font-size:26px !important; letter-spacing:-.02em; }
        .pisat-hero p { color:rgba(255,255,255,.78) !important; max-width:680px; }
        .pisat-hero-tip { display:inline-flex; align-items:center; gap:8px; margin-top:17px; padding:8px 12px; border:1px solid rgba(255,255,255,.2); border-radius:999px; background:rgba(255,255,255,.1); color:#fff; font-size:12px; font-weight:600; }
        .pisat-filter { padding:18px 20px !important; border:1px solid #dbe5f0 !important; border-radius:14px !important; box-shadow:0 5px 16px rgba(15,23,42,.04) !important; }
        .pisat-filter label { color:#64748b !important; font-size:10px !important; letter-spacing:.08em !important; }
        .pisat-filter input, .pisat-filter select { background:#fff !important; border-color:#cbd5e1 !important; transition:border-color .15s, box-shadow .15s; }
        .pisat-filter input:focus, .pisat-filter select:focus { outline:none; border-color:#2563eb !important; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .pisat-table-card { border:1px solid #dbe5f0 !important; border-radius:14px !important; box-shadow:0 5px 16px rgba(15,23,42,.04) !important; }
        .pisat-table-card thead tr { background:linear-gradient(180deg,#f8fbff,#f1f5f9) !important; }
        .pisat-table-card th { color:#64748b !important; font-size:10px !important; letter-spacing:.08em !important; }
        .pisat-table-card tbody tr:hover { background:#f8fbff !important; }
        .pisat-action { transition:transform .15s, box-shadow .15s; }
        .pisat-action:hover { transform:translateY(-1px); box-shadow:0 4px 10px rgba(15,23,42,.14); }
        .pisat-modal-backdrop { backdrop-filter:blur(4px); }
        .pisat-modal-card { border:1px solid rgba(255,255,255,.7); }
    </style>

    <div class="pisat-page">
    <div class="content-header pisat-hero" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="color:#0f172a;font-size:24px;font-weight:800;margin:0;">PISAT Completed Projects</h1>
            <p style="color:#4b5563;font-size:14px;margin:4px 0 0 0;">PDF repository for completed locally funded projects. Each project can contain up to five PISAT documents.</p>
        </div>
        <div style="font-size:13px;color:#475569;font-weight:600;">
            <div class="pisat-hero-tip"><i class="fas fa-cloud-upload-alt"></i> Secure project document repository</div>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-check-circle" style="font-size:16px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-exclamation-circle" style="font-size:16px;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session('info'))
        <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:14px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
            <i class="fas fa-info-circle" style="font-size:16px;"></i>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    <!-- Filtering panel -->
    <div class="pisat-filter" style="background:#ffffff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:24px;border:1px solid #e5e7eb;">
        <form method="GET" action="{{ route('reports.one-time.pisat') }}" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
            <div style="flex:1 1 240px;min-width:240px;">
                <label for="filter-search" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Search Project</label>
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:13px;color:#9ca3af;"></i>
                    <input type="text" id="filter-search" name="search" value="{{ request('search') }}" placeholder="Search by name or project code..." 
                           style="width:100%;height:40px;padding:0 12px 0 34px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                </div>
            </div>

            @if (!Auth::user()->isLguScopedUser())
                <div style="flex:1 1 180px;min-width:180px;">
                    <label for="filter-province" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Province</label>
                    <select id="filter-province" name="province" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                        <option value="">All Provinces</option>
                        @foreach($provinces as $prov)
                            <option value="{{ $prov }}" @selected(request('province') === $prov)>{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1 1 200px;min-width:200px;">
                    <label for="filter-city" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">City / Municipality</label>
                    <select id="filter-city" name="city_municipality" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                        <option value="">All Cities / Municipalities</option>
                        @foreach($officesByProvince as $prov => $cities)
                            <optgroup label="{{ $prov }}">
                                @foreach($cities as $city)
                                    <option value="{{ $city }}" @selected(request('city_municipality') === $city)>{{ $city }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="flex:1 1 160px;min-width:160px;">
                <label for="filter-status" style="display:block;margin-bottom:6px;color:#4b5563;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">PISAT Status</label>
                <select id="filter-status" name="status" style="width:100%;height:40px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background-color:#f9fafb;box-sizing:border-box;">
                    <option value="">All Statuses</option>
                    <option value="none" @selected(request('status') === 'none')>No Assessment</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="returned" @selected(request('status') === 'returned')>Returned</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" style="height:40px;padding:0 18px;background-color:#2563eb;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:background 0.2s;"
                        onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <a href="{{ route('reports.one-time.pisat') }}" style="height:40px;padding:0 18px;background-color:#4b5563;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;text-decoration:none;transition:background 0.2s;"
                   onmouseover="this.style.background='#374151'" onmouseout="this.style.background='#4b5563'">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Table list of completed LFP projects -->
    <div class="pisat-table-card" style="background:#ffffff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);border:1px solid #e5e7eb;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:1000px;text-align:left;">
                <thead>
                    <tr style="background-color:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;width:160px;">Project Code</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;width:340px;">Project Title</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">LGU / Location</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:80px;">Year</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:140px;">Documents</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:160px;">Repository</th>
                        <th style="padding:16px 20px;color:#475569;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;text-align:center;width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody style="divide-y: 1px solid #e2e8f0;">
                    @forelse ($projects as $project)
                        @php
                        @endphp
                        <tr style="border-bottom:1px solid #e2e8f0;transition:background 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding:14px 20px;color:#64748b;font-size:13px;font-family:Consolas, monospace;font-weight:600;">
                                {{ $project->project_code }}
                            </td>
                            <td style="padding:14px 20px;color:#0f172a;font-weight:600;font-size:13px;line-height:1.4;">
                                {{ $project->project_title }}
                            </td>
                            <td style="padding:14px 20px;color:#334155;font-size:13px;line-height:1.4;">
                                <strong>{{ $project->city_municipality }}</strong>, {{ $project->province }}
                                @if($project->barangay)
                                    <div style="font-size:11px;color:#64748b;margin-top:2px;">Brgy. {{ $project->barangay }}</div>
                                @endif
                            </td>
                            <td style="padding:14px 20px;text-align:center;color:#475569;font-size:13px;font-weight:500;">
                                {{ $project->funding_year ?: '—' }}
                            </td>
                            <td style="padding:14px 20px;text-align:center;">
                                <span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid #cbd5e1;background:#f8fafc;color:#475569;text-transform:uppercase;letter-spacing:0.04em;">
                                    {{ $project->document_count }}/5 PDFs
                                </span>
                            </td>
                            <td style="padding:14px 20px;text-align:center;font-size:13px;font-weight:700;color:#0f172a;">
                                Repository only
                            </td>
                            <td style="padding:14px 20px;text-align:center;white-space:nowrap;">
                                <div style="display:inline-flex;gap:8px;align-items:center;justify-content:center;">
                                    <button class="pisat-action" type="button" data-pisat-upload
                                            data-project-code="{{ $project->project_code }}"
                                            data-project-title="{{ $project->project_title }}"
                                            data-province="{{ $project->province }}"
                                            data-city="{{ $project->city_municipality }}"
                                            data-document-count="{{ $project->document_count }}"
                                            style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#2563eb;color:#ffffff;border:none;font-size:12px;font-weight:600;cursor:pointer;">
                                        <i class="fas fa-folder-plus"></i> Create PISAT
                                    </button>
                                    @if ((int) $project->document_count > 0)
                                        <button class="pisat-action" type="button" data-pisat-documents
                                                data-project-code="{{ $project->project_code }}"
                                                data-project-title="{{ $project->project_title }}"
                                                data-documents='@json($project->documents)'
                                                style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#0f766e;color:#ffffff;border:none;font-size:12px;font-weight:600;cursor:pointer;">
                                            <i class="fas fa-folder-open"></i> View Documents
                                        </button>
                                    @endif
                                    @if ((int) $project->document_count > 0)
                                        <span style="font-size:11px;color:#475569;">{{ $project->document_count }}/5 PDFs</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;background:#f8fafc;">
                                <i class="fas fa-folder-open" style="font-size:36px;margin-bottom:12px;color:#cbd5e1;display:block;"></i>
                                <span style="font-weight:600;color:#64748b;">No completed projects found</span>
                                <p style="margin:4px 0 0 0;font-size:12px;color:#94a3b8;">There are no completed locally funded projects matching your criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div style="padding:16px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;">
                {{ $projects->links() }}
            </div>
        @endif
    </div>

    <div id="pisat-documents-modal" class="pisat-modal-backdrop" hidden style="position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px;">
        <div class="pisat-modal-card" role="dialog" aria-modal="true" style="width:min(720px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:14px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px;">
                <div>
                    <h2 style="margin:0;color:#0f172a;font-size:20px;">PISAT Documents</h2>
                    <p id="pisat-documents-project" style="margin:5px 0 0;color:#64748b;font-size:13px;"></p>
                </div>
                <button type="button" data-pisat-documents-close aria-label="Close" style="border:0;background:transparent;color:#64748b;font-size:22px;cursor:pointer;">&times;</button>
            </div>
            <div id="pisat-documents-list" style="display:grid;gap:10px;"></div>
            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                <button type="button" data-pisat-documents-close style="padding:9px 16px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#475569;font-weight:600;cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

    <div id="pisat-preview-modal" class="pisat-modal-backdrop" hidden style="position:fixed;inset:0;z-index:1100;background:rgba(15,23,42,.7);align-items:center;justify-content:center;padding:20px;">
        <div class="pisat-modal-card" role="dialog" aria-modal="true" style="width:min(1000px,100%);height:min(90vh,800px);background:#fff;border-radius:14px;padding:14px;box-shadow:0 20px 50px rgba(0,0,0,.3);display:flex;flex-direction:column;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:0 4px 10px;">
                <strong id="pisat-preview-title" style="color:#0f172a;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></strong>
                <button type="button" data-pisat-preview-close aria-label="Close preview" style="border:0;background:transparent;color:#64748b;font-size:22px;cursor:pointer;">&times;</button>
            </div>
            <iframe id="pisat-preview-frame" title="PISAT document preview" style="width:100%;height:100%;border:1px solid #e2e8f0;border-radius:8px;"></iframe>
        </div>
    </div>

    <div id="pisat-upload-modal" hidden style="position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px;">
        <div role="dialog" aria-modal="true" style="width:min(560px,100%);background:#fff;border-radius:14px;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.25);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px;">
                <div>
                    <h2 style="margin:0;color:#0f172a;font-size:20px;">Upload PISAT Documents</h2>
                    <p id="pisat-upload-project" style="margin:5px 0 0;color:#64748b;font-size:13px;"></p>
                </div>
                <button type="button" data-pisat-close aria-label="Close" style="border:0;background:transparent;color:#64748b;font-size:22px;cursor:pointer;">&times;</button>
            </div>

            <form method="POST" action="{{ route('reports.one-time.pisat.documents.store') }}" enctype="multipart/form-data" id="pisat-upload-form">
                @csrf
                <input type="hidden" name="project_code" id="pisat-upload-project-code">
                <input type="hidden" name="project_title" id="pisat-upload-project-title">
                <input type="hidden" name="province" id="pisat-upload-province">
                <input type="hidden" name="city_municipality" id="pisat-upload-city">
                <label for="pisat-documents" style="display:block;color:#334155;font-size:13px;font-weight:700;margin-bottom:7px;">PDF documents (maximum 5 per project)</label>
                <input type="file" id="pisat-documents" name="documents[]" accept="application/pdf,.pdf" multiple required style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;">
                <p id="pisat-upload-help" style="margin:8px 0 18px;color:#64748b;font-size:12px;">Select one to five PDF files. Each file may be up to 15 MB.</p>
                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" data-pisat-close style="padding:9px 16px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#475569;font-weight:600;cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:9px 16px;border:0;border-radius:8px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;">Upload PDFs</button>
                </div>
            </form>
        </div>
    </div>

    </div>

    <script>
        (() => {
            const modal = document.getElementById('pisat-upload-modal');
            const form = document.getElementById('pisat-upload-form');
            const fileInput = document.getElementById('pisat-documents');
            const help = document.getElementById('pisat-upload-help');
            if (!modal || !form || !fileInput) return;
            const documentsModal = document.getElementById('pisat-documents-modal');
            const documentsList = document.getElementById('pisat-documents-list');
            const previewModal = document.getElementById('pisat-preview-modal');
            const previewFrame = document.getElementById('pisat-preview-frame');
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
            const closeDocumentsModal = () => {
                documentsModal.hidden = true;
                documentsModal.style.display = 'none';
            };
            const closePreviewModal = () => {
                previewModal.hidden = true;
                previewModal.style.display = 'none';
                previewFrame.src = 'about:blank';
            };
            document.querySelectorAll('[data-pisat-documents]').forEach((button) => {
                button.addEventListener('click', () => {
                    const documents = JSON.parse(button.dataset.documents || '[]');
                    document.getElementById('pisat-documents-project').textContent =
                        `${button.dataset.projectTitle} (${button.dataset.projectCode})`;
                    documentsList.innerHTML = documents.map((document) => `
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                            <div style="min-width:0;">
                                <div style="font-size:13px;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(document.file_name)}</div>
                                <div style="font-size:11px;color:#64748b;margin-top:3px;">Uploaded ${escapeHtml(document.uploaded_at || '—')}</div>
                            </div>
                            <div style="display:flex;gap:6px;flex-shrink:0;">
                                <button type="button" data-view-url="${escapeHtml(document.view_url)}" data-file-name="${escapeHtml(document.file_name)}" style="padding:6px 10px;border:1px solid #93c5fd;border-radius:6px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:600;cursor:pointer;"><i class="fas fa-eye"></i> View</button>
                                <a href="${document.download_url}" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#475569;font-size:12px;font-weight:600;text-decoration:none;"><i class="fas fa-download"></i></a>
                                <form method="POST" action="${document.delete_url}" onsubmit="return confirm('Delete this document? This cannot be undone.');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="padding:6px 10px;border:1px solid #fecaca;border-radius:6px;background:#fef2f2;color:#b91c1c;font-size:12px;font-weight:600;cursor:pointer;"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    `).join('');
                    documentsList.querySelectorAll('[data-view-url]').forEach((viewButton) => {
                        viewButton.addEventListener('click', () => {
                            document.getElementById('pisat-preview-title').textContent = viewButton.dataset.fileName;
                            previewFrame.src = viewButton.dataset.viewUrl;
                            previewModal.hidden = false;
                            previewModal.style.display = 'flex';
                        });
                    });
                    documentsModal.hidden = false;
                    documentsModal.style.display = 'flex';
                });
            });
            documentsModal.querySelectorAll('[data-pisat-documents-close]').forEach((button) => button.addEventListener('click', closeDocumentsModal));
            previewModal.querySelector('[data-pisat-preview-close]').addEventListener('click', closePreviewModal);
            const closeModal = () => {
                modal.hidden = true;
                modal.style.display = 'none';
                form.reset();
            };

            document.querySelectorAll('[data-pisat-upload]').forEach((button) => {
                button.addEventListener('click', () => {
                    const remaining = Math.max(0, 5 - Number(button.dataset.documentCount || 0));
                    document.getElementById('pisat-upload-project-code').value = button.dataset.projectCode;
                    document.getElementById('pisat-upload-project-title').value = button.dataset.projectTitle;
                    document.getElementById('pisat-upload-province').value = button.dataset.province;
                    document.getElementById('pisat-upload-city').value = button.dataset.city;
                    document.getElementById('pisat-upload-project').textContent = `${button.dataset.projectTitle} (${button.dataset.projectCode})`;
                    fileInput.max = String(remaining);
                    help.textContent = remaining
                        ? `Select one to ${remaining} PDF file${remaining === 1 ? '' : 's'}. Each file may be up to 15 MB.`
                        : 'This project already has the maximum of 5 PDF documents.';
                    modal.hidden = false;
                    modal.style.display = 'flex';
                });
            });

            modal.querySelectorAll('[data-pisat-close]').forEach((button) => button.addEventListener('click', closeModal));
            fileInput.addEventListener('change', () => {
                const max = Number(fileInput.max || 5);
                fileInput.setCustomValidity(fileInput.files.length > max ? `Select no more than ${max} PDF file(s).` : '');
            });
        })();
    </script>
@endsection
