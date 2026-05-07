@extends('layouts.dashboard')

@section('title', 'NADAI Management')
@section('page-title', 'NADAI Management')

@section('content')
@php
    $programDisplayMap = [
        'SBDP' => 'Support to the Barangay Development Program',
        'FALGU' => 'Financial Assistance to Local Government Unit',
    ];
    $selectedUploadProvince = old('province', $uploadFormOptions['default_province'] ?? $province);
    $selectedUploadMunicipality = old('municipality', $uploadFormOptions['default_municipality'] ?? '');
    $selectedUploadBarangay = old('barangay', $uploadFormOptions['default_barangay'] ?? '');
    $selectedFundingYear = old('funding_year', $uploadFormOptions['default_funding_year'] ?? '');
    $selectedProgram = old('program', $uploadFormOptions['default_program'] ?? '');
    $initialMunicipalityOptions = $uploadFormOptions['province_municipality_map'][$selectedUploadProvince] ?? [];
    $initialBarangayOptions = $uploadFormOptions['municipality_barangay_map'][$selectedUploadMunicipality] ?? [];
@endphp

<style>
    .nadai-page {
        color: #0f172a;
    }

    .nadai-shell {
        display: grid;
        gap: 22px;
    }

    .nadai-hero {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        padding: 30px;
        background:
            radial-gradient(circle at top right, rgba(125, 211, 252, 0.22), transparent 32%),
            linear-gradient(135deg, #0b1f52 0%, #12398d 52%, #1d4ed8 100%);
        box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18);
    }

    .nadai-hero::after {
        content: '';
        position: absolute;
        inset: auto -80px -80px auto;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(10px);
    }

    .nadai-hero-grid {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        flex-wrap: wrap;
    }

    .nadai-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.86);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .nadai-title {
        margin: 16px 0 10px;
        color: #fff;
        font-size: clamp(28px, 4vw, 38px);
        line-height: 1.08;
        font-weight: 800;
    }

    .nadai-title-icon {
        margin-right: 10px;
        color: rgba(191, 219, 254, 0.95);
        font-size: 0.8em;
        vertical-align: middle;
    }

    .nadai-hero-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .nadai-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .nadai-hero-actions {
        display: flex;
        justify-content: flex-end;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 0;
        margin-left: auto;
    }

    .nadai-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 18px;
        border: 1px solid transparent;
        border-radius: 14px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease, border-color 0.16s ease;
    }

    .nadai-btn:hover {
        transform: translateY(-1px);
    }

    .nadai-btn-primary {
        background: #fff;
        color: #12398d;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    }

    .nadai-btn-primary:hover {
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
    }

    .nadai-btn-muted {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .nadai-btn-muted:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    .nadai-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 18px;
        border: 1px solid transparent;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .nadai-alert-success {
        background: linear-gradient(180deg, #ecfdf5 0%, #dcfce7 100%);
        border-color: #86efac;
        color: #166534;
    }

    .nadai-alert-error {
        background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
        border-color: #fda4af;
        color: #9f1239;
    }

    .nadai-alert-icon {
        flex: 0 0 20px;
        font-size: 18px;
        line-height: 1.2;
    }

    .nadai-alert-title {
        margin: 0 0 6px;
        font-size: 13px;
        font-weight: 800;
    }

    .nadai-alert-copy {
        margin: 0;
        font-size: 13px;
        line-height: 1.65;
    }

    .nadai-alert-list {
        margin: 0;
        padding-left: 18px;
        font-size: 13px;
        line-height: 1.65;
    }

    .nadai-records {
        overflow: hidden;
        border-radius: 26px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 20px 44px rgba(15, 23, 42, 0.07);
    }

    .nadai-records-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 14px;
        padding: 24px 26px 18px;
        border-bottom: 1px solid #e2e8f0;
    }

    .nadai-section-label {
        margin: 0 0 8px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .nadai-section-title {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        line-height: 1.2;
        font-weight: 800;
    }

    .nadai-section-copy {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.7;
    }

    .nadai-records-note {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: 12px;
        font-weight: 700;
    }

    .nadai-table-wrap {
        overflow-x: auto;
        padding: 14px 14px 16px;
        background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    }

    .nadai-table {
        width: 100%;
        min-width: 1080px;
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .nadai-table thead th {
        padding: 6px 14px 10px;
        background: transparent;
        color: #1e3a8a;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border-bottom: none;
    }

    .nadai-table tbody tr {
        transition: transform 0.16s ease;
    }

    .nadai-table tbody tr:hover {
        transform: translateY(-1px);
    }

    .nadai-table tbody td {
        padding: 18px 14px;
        border-top: 1px solid #dbe7f5;
        border-bottom: 1px solid #dbe7f5;
        background: rgba(255, 255, 255, 0.96);
        color: #0f172a;
        font-size: 13px;
        vertical-align: top;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .nadai-table tbody td:first-child {
        border-left: 1px solid #dbe7f5;
        border-top-left-radius: 18px;
        border-bottom-left-radius: 18px;
    }

    .nadai-table tbody td:last-child {
        border-right: 1px solid #dbe7f5;
        border-top-right-radius: 18px;
        border-bottom-right-radius: 18px;
    }

    .nadai-table tbody tr:hover td {
        background: #f8fbff;
    }

    .nadai-project {
        display: grid;
        gap: 7px;
    }

    .nadai-col-project {
        width: 22%;
    }

    .nadai-col-document {
        width: 19%;
    }

    .nadai-col-uploader {
        width: 13%;
    }

    .nadai-col-uploaded-at {
        width: 13%;
    }

    .nadai-col-actions {
        width: 176px;
    }

    .nadai-project-title {
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.45;
        word-break: break-word;
    }

    .nadai-project-meta {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .nadai-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .nadai-chip-muted {
        background: #e2e8f0;
        color: #475569;
    }

    .nadai-file {
        display: grid;
        gap: 6px;
    }

    .nadai-file-name {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.45;
        word-break: break-word;
    }

    .nadai-file-meta {
        margin: 0;
        color: #64748b;
        font-size: 12px;
    }

    .nadai-user {
        display: inline-grid;
        justify-items: center;
        gap: 8px;
        text-align: center;
    }

    .nadai-user-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
    }

    .nadai-date {
        font-weight: 700;
        line-height: 1.5;
        white-space: nowrap;
    }

    .nadai-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .nadai-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: none;
        border-radius: 12px;
        color: #fff;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);
        transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }

    .nadai-icon-btn:hover {
        transform: translateY(-1px);
        filter: brightness(1.04);
        box-shadow: 0 12px 22px rgba(15, 23, 42, 0.16);
    }

    .nadai-icon-btn-view {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .nadai-icon-btn-download {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
    }

    .nadai-icon-btn-edit {
        background: linear-gradient(135deg, #d97706 0%, #ea580c 100%);
    }

    .nadai-icon-btn-delete {
        background: linear-gradient(135deg, #dc2626 0%, #e11d48 100%);
    }

    .nadai-empty {
        padding: 54px 24px;
        text-align: center;
        color: #64748b;
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
    }

    .nadai-empty i {
        display: block;
        margin-bottom: 14px;
        color: #94a3b8;
        font-size: 36px;
    }

    .nadai-empty-title {
        margin: 0 0 6px;
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }

    .nadai-empty-copy {
        margin: 0;
        font-size: 13px;
        line-height: 1.7;
    }

    .nadai-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 22px;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(5px);
        z-index: 2000;
    }

    .nadai-modal-panel {
        width: min(100%, 760px);
        max-height: calc(100vh - 44px);
        overflow: auto;
        border-radius: 28px;
        background: #fff;
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.28);
    }

    .nadai-modal-panel-preview {
        width: min(100%, 980px);
        overflow: hidden;
    }

    .nadai-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 24px 26px 18px;
        border-bottom: 1px solid #e2e8f0;
        background:
            radial-gradient(circle at top right, rgba(191, 219, 254, 0.62), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);
    }

    .nadai-modal-head-edit {
        background:
            radial-gradient(circle at top right, rgba(96, 165, 250, 0.28), transparent 30%),
            linear-gradient(135deg, #002c76 0%, #0b3c9c 100%);
        border-bottom-color: rgba(255, 255, 255, 0.12);
    }

    .nadai-modal-head-edit .nadai-modal-label,
    .nadai-modal-head-edit .nadai-modal-title,
    .nadai-modal-head-edit .nadai-modal-copy {
        color: #fff;
    }

    .nadai-modal-head-edit .nadai-modal-label {
        color: rgba(219, 234, 254, 0.86);
    }

    .nadai-modal-head-edit .nadai-modal-copy {
        color: rgba(255, 255, 255, 0.82);
    }

    .nadai-modal-head-edit .nadai-modal-close {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }

    .nadai-modal-label {
        margin: 0 0 8px;
        color: #2563eb;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .nadai-modal-title {
        margin: 0;
        color: #0f172a;
        font-size: 24px;
        line-height: 1.2;
        font-weight: 800;
    }

    .nadai-modal-copy {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.7;
    }

    .nadai-modal-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.06);
        color: #334155;
        font-size: 20px;
        cursor: pointer;
    }

    .nadai-form {
        padding: 24px 26px 26px;
    }

    .nadai-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .nadai-form-field {
        display: grid;
        gap: 7px;
    }

    .nadai-form-field-full {
        grid-column: 1 / -1;
    }

    .nadai-form-label {
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .nadai-form-input,
    .nadai-form-select {
        width: 100%;
        min-height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #fff;
        color: #0f172a;
        font-size: 13px;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .nadai-form-input:focus,
    .nadai-form-select:focus {
        outline: none;
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18);
    }

    .nadai-form-helper {
        margin: 0;
        color: #64748b;
        font-size: 11px;
        line-height: 1.6;
    }

    .nadai-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .nadai-preview-shell {
        height: min(72vh, 820px);
        background: #e2e8f0;
    }

    .nadai-preview-frame {
        width: 100%;
        height: 100%;
        border: none;
        background: #fff;
    }

    .nadai-btn-secondary {
        background: #e2e8f0;
        color: #0f172a;
    }

    .nadai-btn-secondary:hover {
        background: #cbd5e1;
    }

    .nadai-btn-solid {
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(29, 78, 216, 0.24);
    }

    .nadai-btn-solid:hover {
        box-shadow: 0 18px 32px rgba(29, 78, 216, 0.3);
    }

    @media (max-width: 1180px) {
    @media (max-width: 980px) {
        .nadai-hero-actions {
            justify-content: flex-start;
            margin-left: 0;
        }

        .nadai-records-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 720px) {
        .nadai-hero {
            padding: 24px 20px;
            border-radius: 22px;
        }

        .nadai-form-grid {
            grid-template-columns: 1fr;
        }

        .nadai-records-head,
        .nadai-form,
        .nadai-modal-head {
            padding-left: 18px;
            padding-right: 18px;
        }

        .nadai-modal-actions {
            flex-direction: column-reverse;
        }

        .nadai-modal-actions .nadai-btn {
            width: 100%;
        }
    }
</style>

<div class="nadai-page">
    <div class="nadai-shell">
        <section class="nadai-hero">
            <div class="nadai-hero-grid">
                <div>
                    <div class="nadai-eyebrow">
                        <i class="fas fa-landmark"></i>
                        NADAI Records
                    </div>
                    <h1 class="nadai-title"><i class="fas fa-map-pin nadai-title-icon"></i>{{ $province }}, {{ $officeName }}</h1>
                    <div class="nadai-hero-pills">
                        <span class="nadai-pill">
                            <i class="fas fa-folder-open"></i>
                            {{ $documents->count() }} {{ \Illuminate\Support\Str::plural('document', $documents->count()) }}
                        </span>
                    </div>
                </div>
                <div class="nadai-hero-actions">
                    <a href="{{ route('nadai-management.index') }}" class="nadai-btn nadai-btn-muted">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                    @if ($canUpload)
                        <button type="button" onclick="openNadaiUploadModal()" class="nadai-btn nadai-btn-primary">
                            <i class="fas fa-upload"></i>
                            Upload NADAI
                        </button>
                    @endif
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="nadai-alert nadai-alert-success">
                <div class="nadai-alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <p class="nadai-alert-title">Action completed</p>
                    <p class="nadai-alert-copy">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="nadai-alert nadai-alert-error">
                <div class="nadai-alert-icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <p class="nadai-alert-title">Please review the submitted fields</p>
                    <ul class="nadai-alert-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="nadai-records">
            <div class="nadai-records-head">
                <div>
                    <p class="nadai-section-label">Document Library</p>
                    <h2 class="nadai-section-title">Uploaded NADAI Documents</h2>
                    <p class="nadai-section-copy">
                        Each record stores the project title, funding year, program, NADAI date, and uploaded document for this office.
                    </p>
                </div>
                @if (!$canUpload)
                    <div class="nadai-records-note">
                        <i class="fas fa-lock"></i>
                        Only DILG Regional Office users can upload or edit NADAI files.
                    </div>
                @endif
            </div>

            <div class="nadai-table-wrap">
                <table class="nadai-table">
                    <colgroup>
                        <col class="nadai-col-project">
                        <col>
                        <col>
                        <col>
                        <col class="nadai-col-document">
                        <col class="nadai-col-uploader">
                        <col class="nadai-col-uploaded-at">
                        <col class="nadai-col-actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="nadai-col-project" style="text-align: left;">Project Title</th>
                            <th style="text-align: center;">Funding Year</th>
                            <th style="text-align: left;">Program</th>
                            <th style="text-align: center;">NADAI Date</th>
                            <th class="nadai-col-document" style="text-align: left;">Document</th>
                            <th class="nadai-col-uploader" style="text-align: center;">Uploaded By</th>
                            <th class="nadai-col-uploaded-at" style="text-align: center;">Uploaded At</th>
                            <th class="nadai-col-actions" style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            @php
                                $uploader = $document->uploaded_by ? ($usersById[$document->uploaded_by] ?? null) : null;
                                $uploaderName = $uploader ? trim(($uploader->fname ?? '') . ' ' . ($uploader->lname ?? '')) : 'Unknown';
                                $uploaderInitials = $uploader
                                    ? strtoupper(substr(trim((string) ($uploader->fname ?? 'U')), 0, 1) . substr(trim((string) ($uploader->lname ?? 'N')), 0, 1))
                                    : 'UN';
                                $programValue = trim((string) ($document->program ?? ''));
                            @endphp
                            <tr>
                                <td class="nadai-col-project">
                                    <div class="nadai-project">
                                        <p class="nadai-project-title">{{ $document->project_title }}</p>
                                        <p class="nadai-project-meta">{{ $document->municipality ?: $officeName }}{{ $document->barangay ? ', ' . $document->barangay : '' }}</p>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="nadai-chip nadai-chip-muted">{{ $document->funding_year ?: '-' }}</span>
                                </td>
                                <td>
                                    @if ($programValue !== '')
                                        <span class="nadai-chip">{{ $programDisplayMap[$programValue] ?? $programValue }}</span>
                                    @else
                                        <span class="nadai-chip nadai-chip-muted">Not set</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="nadai-date">{{ $document->nadai_date?->format('M d, Y') ?: '-' }}</span>
                                </td>
                                <td class="nadai-col-document">
                                    <div class="nadai-file">
                                        <p class="nadai-file-name">{{ $document->original_filename }}</p>
                                        <p class="nadai-file-meta">PDF document</p>
                                    </div>
                                </td>
                                <td class="nadai-col-uploader" style="text-align: center;">
                                    <span class="nadai-user">
                                        <span class="nadai-user-avatar">{{ $uploaderInitials }}</span>
                                        <span>{{ $uploaderName !== '' ? $uploaderName : 'Unknown' }}</span>
                                    </span>
                                </td>
                                <td class="nadai-col-uploaded-at" style="text-align: center;">
                                    <span class="nadai-date">
                                        {{ $document->uploaded_at ? $document->uploaded_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') : '-' }}
                                    </span>
                                </td>
                                <td class="nadai-col-actions" style="text-align: center;">
                                    <div class="nadai-actions">
                                        <button
                                            type="button"
                                            onclick="openNadaiDocumentModal(@js(route('nadai-management.document', ['office' => $officeName, 'docId' => $document->id])), @js($document->original_filename))"
                                            title="View document"
                                            aria-label="View document"
                                            class="nadai-icon-btn nadai-icon-btn-view"
                                        >
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <a
                                            href="{{ route('nadai-management.download-document', ['office' => $officeName, 'docId' => $document->id]) }}"
                                            title="Download document"
                                            aria-label="Download document"
                                            class="nadai-icon-btn nadai-icon-btn-download"
                                        >
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @if ($canUpload)
                                            <button
                                                type="button"
                                                onclick="openNadaiEditModal({{ $document->id }})"
                                                title="Edit document"
                                                aria-label="Edit document"
                                                class="nadai-icon-btn nadai-icon-btn-edit"
                                            >
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('nadai-management.delete-document', ['office' => $officeName, 'docId' => $document->id]) }}" onsubmit="return confirm('Delete this NADAI document?');" style="display: inline-flex;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Delete document" aria-label="Delete document" class="nadai-icon-btn nadai-icon-btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="nadai-empty">
                                        <i class="fas fa-file-circle-xmark"></i>
                                        <p class="nadai-empty-title">No NADAI documents uploaded yet</p>
                                        <p class="nadai-empty-copy">Once documents are added for this office, they will appear here with quick actions for viewing, downloading, editing, and deletion.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<div id="nadaiDocumentModal" class="nadai-modal" style="z-index: 2200;">
        <div class="nadai-modal-panel nadai-modal-panel-preview" onclick="event.stopPropagation()">
            <div class="nadai-modal-head">
                <div>
                    <p class="nadai-modal-label">Document Preview</p>
                    <h3 id="nadaiDocumentModalTitle" class="nadai-modal-title">NADAI Document</h3>
                    <p class="nadai-modal-copy">Review the PDF without leaving this page.</p>
                </div>
                <button type="button" onclick="closeNadaiDocumentModal()" class="nadai-modal-close">&times;</button>
            </div>
            <div class="nadai-preview-shell">
                <iframe id="nadaiDocumentFrame" title="NADAI document preview" class="nadai-preview-frame" src=""></iframe>
            </div>
        </div>
    </div>

@if ($canUpload)
    <div id="nadaiUploadModal" class="nadai-modal">
        <div class="nadai-modal-panel" onclick="event.stopPropagation()">
            <div class="nadai-modal-head">
                <div>
                    <p class="nadai-modal-label">New Record</p>
                    <h3 class="nadai-modal-title">Upload NADAI</h3>
                    <p class="nadai-modal-copy">Provide the project title, location, funding year, program, NADAI date, and PDF document.</p>
                </div>
                <button type="button" onclick="closeNadaiUploadModal()" class="nadai-modal-close">&times;</button>
            </div>
            <form method="POST" action="{{ route('nadai-management.store', ['office' => $officeName]) }}" enctype="multipart/form-data" class="nadai-form">
                @csrf
                <div class="nadai-form-grid">
                    <div class="nadai-form-field">
                        <label for="nadai_province" class="nadai-form-label">Province</label>
                        <select id="nadai_province" name="province" required class="nadai-form-select">
                            <option value="">Select province</option>
                            @foreach (($uploadFormOptions['provinces'] ?? []) as $provinceOption)
                                <option value="{{ $provinceOption }}" @selected($selectedUploadProvince === $provinceOption)>{{ $provinceOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nadai-form-field">
                        <label for="nadai_municipality" class="nadai-form-label">Municipality</label>
                        <select id="nadai_municipality" name="municipality" required class="nadai-form-select">
                            <option value="">Select municipality</option>
                            @foreach ($initialMunicipalityOptions as $municipalityOption)
                                <option value="{{ $municipalityOption }}" @selected($selectedUploadMunicipality === $municipalityOption)>{{ $municipalityOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nadai-form-field">
                        <label for="nadai_barangay" class="nadai-form-label">Barangay</label>
                        <select id="nadai_barangay" name="barangay" required class="nadai-form-select">
                            <option value="">Select barangay</option>
                            @foreach ($initialBarangayOptions as $barangayOption)
                                <option value="{{ $barangayOption }}" @selected($selectedUploadBarangay === $barangayOption)>{{ $barangayOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nadai-form-field">
                        <label for="nadai_funding_year" class="nadai-form-label">Funding Year</label>
                        <select id="nadai_funding_year" name="funding_year" required class="nadai-form-select">
                            <option value="">Select funding year</option>
                            @foreach (($uploadFormOptions['funding_years'] ?? []) as $fundingYearOption)
                                <option value="{{ $fundingYearOption }}" @selected($selectedFundingYear === $fundingYearOption)>{{ $fundingYearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nadai-form-field">
                        <label for="nadai_program" class="nadai-form-label">Program</label>
                        <select id="nadai_program" name="program" required class="nadai-form-select">
                            <option value="">Select program</option>
                            @foreach (($uploadFormOptions['programs'] ?? []) as $programOption)
                                <option value="{{ $programOption }}" @selected($selectedProgram === $programOption)>{{ $programDisplayMap[$programOption] ?? $programOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="nadai-form-field">
                        <label for="nadai_date" class="nadai-form-label">Date of NADAI</label>
                        <input id="nadai_date" type="date" name="nadai_date" value="{{ old('nadai_date') }}" required class="nadai-form-input">
                    </div>
                    <div class="nadai-form-field nadai-form-field-full">
                        <label for="project_title" class="nadai-form-label">Project Title</label>
                        <input id="project_title" type="text" name="project_title" value="{{ old('project_title') }}" required class="nadai-form-input">
                    </div>
                    <div class="nadai-form-field nadai-form-field-full">
                        <label for="document" class="nadai-form-label">Upload NADAI Document</label>
                        <input id="document" type="file" name="document" accept="application/pdf,.pdf" required class="nadai-form-input">
                        <p class="nadai-form-helper">Allowed format: PDF only, maximum 15MB.</p>
                    </div>
                </div>
                <div class="nadai-modal-actions">
                    <button type="button" onclick="closeNadaiUploadModal()" class="nadai-btn nadai-btn-secondary">Cancel</button>
                    <button type="submit" class="nadai-btn nadai-btn-solid">Save NADAI</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($documents as $document)
        @php
            $editMunicipalityOptions = $uploadFormOptions['province_municipality_map'][$document->province] ?? ($uploadFormOptions['municipalities'] ?? []);
            $editBarangayOptions = $uploadFormOptions['municipality_barangay_map'][$document->municipality] ?? [];
        @endphp
        <div id="nadaiEditModal{{ $document->id }}" class="nadai-modal" style="z-index: 2100;">
            <div class="nadai-modal-panel" onclick="event.stopPropagation()">
                <div class="nadai-modal-head nadai-modal-head-edit">
                    <div>
                        <p class="nadai-modal-label">Update Record</p>
                        <h3 class="nadai-modal-title">Edit NADAI</h3>
                        <p class="nadai-modal-copy">Update the project details and replace the PDF if needed.</p>
                    </div>
                    <button type="button" onclick="closeNadaiEditModal({{ $document->id }})" class="nadai-modal-close">&times;</button>
                </div>
                <form method="POST" action="{{ route('nadai-management.update-document', ['office' => $officeName, 'docId' => $document->id]) }}" enctype="multipart/form-data" class="nadai-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="edit_document_id" value="{{ $document->id }}">
                    <div class="nadai-form-grid">
                        <div class="nadai-form-field">
                            <label for="edit_nadai_province_{{ $document->id }}" class="nadai-form-label">Province</label>
                            <select id="edit_nadai_province_{{ $document->id }}" name="province" required class="nadai-form-select">
                                <option value="">Select province</option>
                                @foreach (($uploadFormOptions['provinces'] ?? []) as $provinceOption)
                                    <option value="{{ $provinceOption }}" @selected((string) old('edit_document_id') === (string) $document->id ? old('province', $document->province) === $provinceOption : $document->province === $provinceOption)>{{ $provinceOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="nadai-form-field">
                            <label for="edit_nadai_municipality_{{ $document->id }}" class="nadai-form-label">Municipality</label>
                            <select id="edit_nadai_municipality_{{ $document->id }}" name="municipality" required class="nadai-form-select">
                                <option value="">Select municipality</option>
                                @foreach ($editMunicipalityOptions as $municipalityOption)
                                    <option value="{{ $municipalityOption }}" @selected((string) old('edit_document_id') === (string) $document->id ? old('municipality', $document->municipality) === $municipalityOption : $document->municipality === $municipalityOption)>{{ $municipalityOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="nadai-form-field">
                            <label for="edit_nadai_barangay_{{ $document->id }}" class="nadai-form-label">Barangay</label>
                            <select id="edit_nadai_barangay_{{ $document->id }}" name="barangay" required class="nadai-form-select">
                                <option value="">Select barangay</option>
                                @foreach ($editBarangayOptions as $barangayOption)
                                    <option value="{{ $barangayOption }}" @selected((string) old('edit_document_id') === (string) $document->id ? old('barangay', $document->barangay) === $barangayOption : $document->barangay === $barangayOption)>{{ $barangayOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="nadai-form-field">
                            <label for="edit_nadai_funding_year_{{ $document->id }}" class="nadai-form-label">Funding Year</label>
                            <select id="edit_nadai_funding_year_{{ $document->id }}" name="funding_year" required class="nadai-form-select">
                                <option value="">Select funding year</option>
                                @foreach (($uploadFormOptions['funding_years'] ?? []) as $fundingYearOption)
                                    <option value="{{ $fundingYearOption }}" @selected((string) old('edit_document_id') === (string) $document->id ? old('funding_year', $document->funding_year) === $fundingYearOption : $document->funding_year === $fundingYearOption)>{{ $fundingYearOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="nadai-form-field">
                            <label for="edit_nadai_program_{{ $document->id }}" class="nadai-form-label">Program</label>
                            <select id="edit_nadai_program_{{ $document->id }}" name="program" required class="nadai-form-select">
                                <option value="">Select program</option>
                                @foreach (($uploadFormOptions['programs'] ?? []) as $programOption)
                                    <option value="{{ $programOption }}" @selected((string) old('edit_document_id') === (string) $document->id ? old('program', $document->program) === $programOption : $document->program === $programOption)>{{ $programDisplayMap[$programOption] ?? $programOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="nadai-form-field">
                            <label for="edit_nadai_date_{{ $document->id }}" class="nadai-form-label">Date of NADAI</label>
                            <input id="edit_nadai_date_{{ $document->id }}" type="date" name="nadai_date" value="{{ (string) old('edit_document_id') === (string) $document->id ? old('nadai_date', optional($document->nadai_date)->format('Y-m-d')) : optional($document->nadai_date)->format('Y-m-d') }}" required class="nadai-form-input">
                        </div>
                        <div class="nadai-form-field nadai-form-field-full">
                            <label for="edit_project_title_{{ $document->id }}" class="nadai-form-label">Project Title</label>
                            <input id="edit_project_title_{{ $document->id }}" type="text" name="project_title" value="{{ (string) old('edit_document_id') === (string) $document->id ? old('project_title', $document->project_title) : $document->project_title }}" required class="nadai-form-input">
                        </div>
                        <div class="nadai-form-field nadai-form-field-full">
                            <label for="edit_document_{{ $document->id }}" class="nadai-form-label">Replace NADAI Document</label>
                            <input id="edit_document_{{ $document->id }}" type="file" name="document" accept="application/pdf,.pdf" class="nadai-form-input">
                            <p class="nadai-form-helper">Leave blank to keep the current PDF. Allowed format: PDF only, maximum 15MB.</p>
                        </div>
                    </div>
                    <div class="nadai-modal-actions">
                        <button type="button" onclick="closeNadaiEditModal({{ $document->id }})" class="nadai-btn nadai-btn-secondary">Cancel</button>
                        <button type="submit" class="nadai-btn nadai-btn-solid">Update NADAI</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

@endif

<script>
        const NADAI_UPLOAD_FORM_OPTIONS = {
            provinceMunicipalityMap: @json($uploadFormOptions['province_municipality_map'] ?? []),
            municipalityBarangayMap: @json($uploadFormOptions['municipality_barangay_map'] ?? []),
        };

        function populateSelectOptions(selectElement, values, placeholder, selectedValue) {
            if (!selectElement) {
                return;
            }

            const normalizedSelectedValue = String(selectedValue || '').trim();
            selectElement.innerHTML = '';

            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            selectElement.appendChild(placeholderOption);

            (values || []).forEach((value) => {
                const optionValue = String(value || '').trim();
                if (!optionValue) {
                    return;
                }

                const optionElement = document.createElement('option');
                optionElement.value = optionValue;
                optionElement.textContent = optionValue;
                optionElement.selected = optionValue === normalizedSelectedValue;
                selectElement.appendChild(optionElement);
            });
        }

        function rebuildNadaiBarangayOptions(municipalitySelectId, barangaySelectId, preservedMunicipality, preservedBarangay) {
            const municipalitySelect = document.getElementById(municipalitySelectId);
            const barangaySelect = document.getElementById(barangaySelectId);
            const selectedMunicipality = String(preservedMunicipality || municipalitySelect?.value || '').trim();
            const barangayOptions = NADAI_UPLOAD_FORM_OPTIONS.municipalityBarangayMap?.[selectedMunicipality] || [];
            const nextBarangay = barangayOptions.includes(preservedBarangay)
                ? preservedBarangay
                : '';

            populateSelectOptions(
                barangaySelect,
                barangayOptions,
                barangayOptions.length ? 'Select barangay' : 'No barangays available',
                nextBarangay
            );
        }

        function rebuildNadaiMunicipalityOptions(provinceSelectId, municipalitySelectId, barangaySelectId, preservedMunicipality, preservedBarangay) {
            const provinceSelect = document.getElementById(provinceSelectId);
            const municipalitySelect = document.getElementById(municipalitySelectId);
            const selectedProvince = String(provinceSelect?.value || '').trim();
            const municipalityOptions = NADAI_UPLOAD_FORM_OPTIONS.provinceMunicipalityMap?.[selectedProvince] || [];
            const nextMunicipality = municipalityOptions.includes(preservedMunicipality)
                ? preservedMunicipality
                : '';

            populateSelectOptions(
                municipalitySelect,
                municipalityOptions,
                municipalityOptions.length ? 'Select municipality' : 'No municipalities available',
                nextMunicipality
            );

            rebuildNadaiBarangayOptions(municipalitySelectId, barangaySelectId, nextMunicipality, preservedBarangay);
        }

        function initNadaiLocationForm(config) {
            const provinceSelect = document.getElementById(config.provinceId);
            const municipalitySelect = document.getElementById(config.municipalityId);
            if (!provinceSelect || !municipalitySelect) {
                return;
            }

            provinceSelect.addEventListener('change', function () {
                rebuildNadaiMunicipalityOptions(config.provinceId, config.municipalityId, config.barangayId, '', '');
            });

            municipalitySelect.addEventListener('change', function () {
                rebuildNadaiBarangayOptions(config.municipalityId, config.barangayId, this.value, '');
            });

            rebuildNadaiMunicipalityOptions(
                config.provinceId,
                config.municipalityId,
                config.barangayId,
                config.initialMunicipality,
                config.initialBarangay
            );
        }

        function openNadaiUploadModal() {
            const modal = document.getElementById('nadaiUploadModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openNadaiDocumentModal(documentUrl, documentName) {
            const modal = document.getElementById('nadaiDocumentModal');
            const frame = document.getElementById('nadaiDocumentFrame');
            const title = document.getElementById('nadaiDocumentModalTitle');
            if (!modal || !frame || !title) {
                return;
            }

            title.textContent = documentName || 'NADAI Document';
            frame.src = documentUrl;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeNadaiDocumentModal() {
            const modal = document.getElementById('nadaiDocumentModal');
            const frame = document.getElementById('nadaiDocumentFrame');
            if (!modal || !frame) {
                return;
            }

            modal.style.display = 'none';
            frame.src = '';
            document.body.style.overflow = '';
        }

        function closeNadaiUploadModal() {
            const modal = document.getElementById('nadaiUploadModal');
            if (!modal) {
                return;
            }

            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function openNadaiEditModal(documentId) {
            const modal = document.getElementById(`nadaiEditModal${documentId}`);
            if (!modal) {
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeNadaiEditModal(documentId) {
            const modal = document.getElementById(`nadaiEditModal${documentId}`);
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

        document.getElementById('nadaiDocumentModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeNadaiDocumentModal();
            }
        });

        document.querySelectorAll('[id^="nadaiEditModal"]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === this) {
                    const documentId = this.id.replace('nadaiEditModal', '');
                    closeNadaiEditModal(documentId);
                }
            });
        });

        @if ($canUpload)
            initNadaiLocationForm({
                provinceId: 'nadai_province',
                municipalityId: 'nadai_municipality',
                barangayId: 'nadai_barangay',
                initialMunicipality: @json($selectedUploadMunicipality),
                initialBarangay: @json($selectedUploadBarangay),
            });

            @foreach ($documents as $document)
                initNadaiLocationForm({
                    provinceId: 'edit_nadai_province_{{ $document->id }}',
                    municipalityId: 'edit_nadai_municipality_{{ $document->id }}',
                    barangayId: 'edit_nadai_barangay_{{ $document->id }}',
                    initialMunicipality: @json((string) old('edit_document_id') === (string) $document->id ? old('municipality', $document->municipality) : $document->municipality),
                    initialBarangay: @json((string) old('edit_document_id') === (string) $document->id ? old('barangay', $document->barangay) : $document->barangay),
                });
            @endforeach

            @if ($errors->any())
                @if (old('edit_document_id'))
                    openNadaiEditModal(@json((int) old('edit_document_id')));
                @else
                    openNadaiUploadModal();
                @endif
            @endif
        @endif
    </script>
@endsection
