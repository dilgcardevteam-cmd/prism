@extends('layouts.dashboard')

@section('title', 'NADAI Management')
@section('page-title', 'NADAI Management')

@section('content')
<div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
    <div>
        <h1>NADAI Management - {{ $officeName }}</h1>
        <p>Notice of Authority to Debit Account Issued submissions for this LGU/PLGU.</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="{{ route('nadai-management.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        @if ($canUpload)
            <button type="button" onclick="openNadaiUploadModal()" style="display: inline-flex; padding: 10px 18px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-upload"></i> Upload NADAI
            </button>
        @endif
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
        <strong style="display: block; margin-bottom: 8px;">Please fix the following:</strong>
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
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $province }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">City/Municipality or PLGU</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $officeName }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Total NADAI Files</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $documents->count() }}</p>
        </div>
        <div>
            <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Upload Permission</label>
            <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">
                {{ $canUpload ? 'DILG Regional Office upload enabled' : 'View only' }}
            </p>
        </div>
    </div>
</div>

<div style="background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
        <div>
            <h2 style="color: #002C76; font-size: 18px; margin: 0; font-weight: 600;">Uploaded NADAI Documents</h2>
            <p style="margin: 4px 0 0; color: #6b7280; font-size: 13px;">Each record stores the project title, NADAI date, and uploaded document.</p>
        </div>
        @if (!$canUpload)
            <div style="font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 999px; padding: 8px 12px;">
                Only DILG Regional Office users can upload NADAI files.
            </div>
        @endif
    </div>

    <div class="report-table-scroll">
        <table style="width: 100%; border-collapse: collapse; min-width: 980px;">
            <thead>
                <tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Project Title</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">NADAI Date</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: 600; font-size: 14px;">Document</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Uploaded By</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Uploaded At</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: 600; font-size: 14px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    @php
                        $uploader = $document->uploaded_by ? ($usersById[$document->uploaded_by] ?? null) : null;
                        $uploaderName = $uploader ? trim(($uploader->fname ?? '') . ' ' . ($uploader->lname ?? '')) : 'Unknown';
                    @endphp
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #111827; font-size: 13px;">{{ $document->project_title }}</td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">{{ $document->nadai_date?->format('M d, Y') }}</td>
                        <td style="padding: 12px; color: #111827; font-size: 13px;">
                            <div style="font-weight: 600;">{{ $document->original_filename }}</div>
                            <div style="font-size: 11px; color: #6b7280;">PDF document</div>
                        </td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px;">{{ $uploaderName !== '' ? $uploaderName : 'Unknown' }}</td>
                        <td style="padding: 12px; text-align: center; color: #111827; font-size: 12px; white-space: nowrap;">
                            {{ $document->uploaded_at ? $document->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '—' }}
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: center;">
                                <a href="{{ route('nadai-management.document', ['office' => $officeName, 'docId' => $document->id]) }}" target="_blank" style="display: inline-flex; padding: 8px 12px; background-color: #2563eb; color: white; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; align-items: center; gap: 5px;">
                                    <i class="fas fa-file-pdf"></i> View
                                </a>
                                <a href="{{ route('nadai-management.download-document', ['office' => $officeName, 'docId' => $document->id]) }}" style="display: inline-flex; padding: 8px 12px; background-color: #0f766e; color: white; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; align-items: center; gap: 5px;">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                @if ($canDelete)
                                    <form method="POST" action="{{ route('nadai-management.delete-document', ['office' => $officeName, 'docId' => $document->id]) }}" onsubmit="return confirm('Delete this NADAI document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="display: inline-flex; padding: 8px 12px; background-color: #dc2626; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; align-items: center; gap: 5px;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #6b7280;">
                            <i class="fas fa-file-circle-xmark" style="font-size: 30px; margin-bottom: 8px; display: block;"></i>
                            No NADAI documents uploaded for this LGU/PLGU yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($canUpload)
    <div id="nadaiUploadModal" style="position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.55); z-index: 2000; padding: 20px;">
        <div style="width: min(100%, 560px); background: #fff; border-radius: 16px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.28); overflow: hidden;" onclick="event.stopPropagation()">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 22px; background: #002C76; color: #fff;">
                <div>
                    <div style="font-size: 17px; font-weight: 700;">Upload NADAI</div>
                    <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">Provide the project title, NADAI date, and PDF document.</div>
                </div>
                <button type="button" onclick="closeNadaiUploadModal()" style="border: none; background: transparent; color: rgba(255,255,255,0.8); font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form method="POST" action="{{ route('nadai-management.store', ['office' => $officeName]) }}" enctype="multipart/form-data" style="padding: 22px;">
                @csrf
                <div style="display: grid; gap: 14px;">
                    <div>
                        <label for="project_title" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Project Title</label>
                        <input id="project_title" type="text" name="project_title" value="{{ old('project_title') }}" required style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; color: #111827;">
                    </div>
                    <div>
                        <label for="nadai_date" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Date of NADAI</label>
                        <input id="nadai_date" type="date" name="nadai_date" value="{{ old('nadai_date') }}" required style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; color: #111827;">
                    </div>
                    <div>
                        <label for="document" style="display: block; margin-bottom: 6px; color: #374151; font-size: 12px; font-weight: 600;">Upload NADAI Document</label>
                        <input id="document" type="file" name="document" accept="application/pdf,.pdf" required class="dashboard-file-input" style="width: 100%;">
                        <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">Allowed format: PDF only, maximum 15MB.</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeNadaiUploadModal()" style="padding: 10px 18px; background: #e5e7eb; color: #111827; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 18px; background: #002C76; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Save NADAI
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openNadaiUploadModal() {
            const modal = document.getElementById('nadaiUploadModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeNadaiUploadModal() {
            const modal = document.getElementById('nadaiUploadModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('nadaiUploadModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeNadaiUploadModal();
            }
        });

        @if ($errors->any())
            openNadaiUploadModal();
        @endif
    </script>
@endif
@endsection
