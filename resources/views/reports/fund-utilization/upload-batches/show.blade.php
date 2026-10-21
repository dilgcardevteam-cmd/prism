@extends('layouts.dashboard')

@section('title', $batch->batch_number . ' - Batch Review')
@section('page-title', 'Fund Utilization Batch Review')

@section('styles')
<style>
    :root {
        --fur-primary: #1d4ed8;
        --fur-primary-dark: #1e3a8a;
        --fur-primary-light: #eff6ff;
        --fur-success: #059669;
        --fur-success-light: #ecfdf5;
        --fur-warning: #d97706;
        --fur-warning-light: #fffbeb;
        --fur-danger: #dc2626;
        --fur-danger-light: #fef2f2;
        --fur-slate-50: #f8fafc;
        --fur-slate-100: #f1f5f9;
        --fur-slate-200: #e2e8f0;
        --fur-slate-300: #cbd5e1;
        --fur-slate-500: #64748b;
        --fur-slate-700: #334155;
        --fur-slate-900: #0f172a;
    }

    .fur-batch-container {
        color: var(--fur-slate-900);
        margin: 0 auto;
        max-width: 1540px;
        padding-bottom: 40px;
    }

    /* Top Breadcrumb & Navigation */
    .fur-nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .fur-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 10px;
        color: var(--fur-slate-700);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .fur-back-btn:hover {
        background: var(--fur-slate-50);
        border-color: var(--fur-primary);
        color: var(--fur-primary);
        transform: translateX(-2px);
    }

    .fur-nav-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Hero Banner Card */
    .fur-hero-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #eff6ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 18px;
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
        padding: 24px 28px;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
    }

    .fur-hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #2563eb, #3b82f6, #60a5fa, #10b981);
    }

    .fur-hero-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .fur-hero-badge-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        background: #dbeafe;
        border-radius: 9999px;
        color: var(--fur-primary-dark);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .fur-hero-badge-wrap .pulse-dot {
        width: 7px;
        height: 7px;
        background: #2563eb;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
    }

    .fur-hero-title {
        font-size: 28px;
        font-weight: 800;
        color: #0b1f52;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .fur-quarter-tag {
        font-size: 13px;
        font-weight: 700;
        background: #1e3a8a;
        color: #ffffff;
        padding: 4px 12px;
        border-radius: 8px;
        letter-spacing: 0.03em;
        vertical-align: middle;
    }

    .fur-hero-desc {
        color: var(--fur-slate-500);
        font-size: 13.5px;
        margin: 0;
        max-width: 780px;
        line-height: 1.5;
    }

    .fur-batch-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 12.5px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .fur-batch-status-pill.submitted {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .fur-batch-status-pill.approved {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .fur-batch-status-pill.returned {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Hero Metadata & Stats Grid */
    .fur-hero-meta-grid {
        display: grid;
        grid-template-columns: minmax(280px, 1.3fr) repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        padding-top: 18px;
        border-top: 1px solid rgba(226, 232, 240, 0.8);
    }

    .fur-uploader-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .fur-uploader-avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 15px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        flex-shrink: 0;
    }

    .fur-uploader-info h4 {
        margin: 0;
        font-size: 13.5px;
        font-weight: 700;
        color: #0b1f52;
    }

    .fur-uploader-info p {
        margin: 2px 0 0 0;
        font-size: 12px;
        color: var(--fur-slate-500);
    }

    .fur-stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .fur-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .fur-stat-icon.blue { background: #eff6ff; color: #2563eb; }
    .fur-stat-icon.green { background: #ecfdf5; color: #059669; }
    .fur-stat-icon.amber { background: #fffbeb; color: #d97706; }
    .fur-stat-icon.purple { background: #faf5ff; color: #7c3aed; }
    .fur-stat-icon.red { background: #fef2f2; color: #dc2626; }

    .fur-stat-content h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }

    .fur-stat-content span {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--fur-slate-500);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    /* Alerts */
    .fur-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-radius: 12px;
        font-size: 13px;
        margin-bottom: 20px;
        animation: fadeIn 0.3s ease;
    }

    .fur-alert.success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .fur-alert.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    /* Main Workspace Layout */
    .fur-workspace {
        display: grid;
        grid-template-columns: 330px 1fr;
        gap: 22px;
        align-items: start;
    }

    /* Left Sidebar: Document Categories */
    .fur-sidebar {
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        padding: 16px;
        position: sticky;
        top: 20px;
    }

    .fur-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding: 0 4px;
    }

    .fur-sidebar-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--fur-slate-700);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .fur-filter-switch {
        display: flex;
        background: var(--fur-slate-100);
        border-radius: 8px;
        padding: 3px;
        margin-bottom: 12px;
        gap: 2px;
    }

    .fur-filter-btn {
        flex: 1;
        padding: 5px 8px;
        font-size: 11px;
        font-weight: 700;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: var(--fur-slate-500);
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }

    .fur-filter-btn.active {
        background: #ffffff;
        color: var(--fur-primary);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    .fur-doc-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        max-height: calc(100vh - 240px);
        overflow-y: auto;
        padding-right: 2px;
    }

    .fur-doc-list::-webkit-scrollbar {
        width: 5px;
    }
    .fur-doc-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .fur-doc-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .fur-doc-tab {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 12px;
        cursor: pointer;
        text-align: left;
        width: 100%;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .fur-doc-tab:hover {
        background: var(--fur-slate-50);
        border-color: #93c5fd;
        transform: translateY(-1px);
    }

    .fur-doc-tab.active {
        background: #eff6ff;
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
    }

    .fur-doc-tab.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 10px;
        bottom: 10px;
        width: 4px;
        background: #2563eb;
        border-radius: 0 4px 4px 0;
    }

    .fur-doc-tab-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: var(--fur-slate-100);
        color: var(--fur-slate-500);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .fur-doc-tab.has-files .fur-doc-tab-icon {
        background: #dcfce7;
        color: #15803d;
    }

    .fur-doc-tab.active .fur-doc-tab-icon {
        background: #2563eb;
        color: #ffffff;
    }

    .fur-doc-tab-content {
        flex: 1;
        min-width: 0;
    }

    .fur-doc-tab-title {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--fur-slate-700);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .fur-doc-tab.active .fur-doc-tab-title {
        color: #1e3a8a;
    }

    .fur-doc-tab-sub {
        font-size: 11px;
        color: var(--fur-slate-500);
        margin-top: 1px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sidebar-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
    }

    .sidebar-status-tag.status-success {
        color: #059669;
        font-weight: 600;
    }

    .sidebar-status-tag.status-danger {
        color: #dc2626;
        font-weight: 600;
    }

    .sidebar-status-tag.status-primary {
        color: #1d4ed8;
        font-weight: 600;
    }

    .sidebar-status-tag.status-warning {
        color: #d97706;
        font-weight: 600;
    }

    .sidebar-status-tag.status-muted {
        color: var(--fur-slate-500);
    }

    .fur-doc-pill-count {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 9999px;
        background: var(--fur-slate-100);
        color: var(--fur-slate-500);
        flex-shrink: 0;
    }

    .fur-doc-tab.has-files .fur-doc-pill-count {
        background: #dcfce7;
        color: #166534;
    }

    .fur-doc-tab.active .fur-doc-pill-count {
        background: #dbeafe;
        color: #1d4ed8;
    }

    /* Right Main Panel: Document Review Workspace */
    .fur-review-panel {
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        padding: 24px 26px;
    }

    .fur-panel-section {
        display: none;
    }

    .fur-panel-section.active {
        display: block;
        animation: fadeIn 0.25s ease;
    }

    /* Panel Header Banner */
    .fur-doc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--fur-slate-200);
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .fur-doc-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .fur-doc-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1d4ed8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1);
        flex-shrink: 0;
    }

    .fur-doc-header-text h2 {
        font-size: 20px;
        font-weight: 800;
        color: #0b1f52;
        margin: 0 0 4px 0;
    }

    .fur-doc-header-text p {
        margin: 0;
        font-size: 13px;
        color: var(--fur-slate-500);
    }

    .fur-doc-metrics-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fur-metric-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .fur-metric-badge.files-ok {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .fur-metric-badge.files-none {
        background: #f8fafc;
        border: 1px solid var(--fur-slate-200);
        color: var(--fur-slate-500);
    }

    .fur-metric-badge.ro {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }

    .fur-metric-badge.po {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
    }

    .fur-metric-badge.approved {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
    }

    .fur-metric-badge.returned {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    /* Helper Instruction Strip */
    .fur-instruction-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .fur-instruction-banner i {
        color: #2563eb;
        font-size: 16px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .fur-instruction-banner-text {
        font-size: 12.5px;
        color: #1e3a8a;
        line-height: 1.5;
    }

    /* Toolbar for Checklist (Select All, Filter, Count) */
    .fur-list-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
        padding: 10px 14px;
        background: var(--fur-slate-50);
        border: 1px solid var(--fur-slate-200);
        border-radius: 12px;
        flex-wrap: wrap;
    }

    .fur-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .fur-select-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        border: 1px solid var(--fur-slate-300);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        color: var(--fur-slate-700);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .fur-select-all-btn:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }

    .fur-selection-count {
        font-size: 12px;
        font-weight: 600;
        color: var(--fur-slate-500);
    }

    .fur-search-box {
        position: relative;
        min-width: 240px;
    }

    .fur-search-box input {
        width: 100%;
        padding: 7px 12px 7px 32px;
        font-size: 12px;
        border: 1px solid var(--fur-slate-200);
        border-radius: 8px;
        background: #ffffff;
        color: var(--fur-slate-900);
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .fur-search-box input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .fur-search-box i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--fur-slate-500);
        font-size: 12px;
    }

    /* Project Review Cards List */
    .fur-project-cards-wrap {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 24px;
    }

    .fur-project-card {
        display: grid;
        grid-template-columns: 36px 1fr auto;
        gap: 14px;
        align-items: center;
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 14px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .fur-project-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        transform: translateY(-1px);
    }

    .fur-project-card.is-selected {
        border-color: #86efac;
        background: #f0fdf4;
    }

    .fur-project-card.is-selected:hover {
        border-color: #4ade80;
    }

    .fur-card-checkbox-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fur-custom-checkbox {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        cursor: pointer;
        accent-color: #2563eb;
    }

    .fur-card-main {
        min-width: 0;
    }

    .fur-card-tags {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .fur-code-pill {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11.5px;
        font-weight: 700;
        color: #1e3a8a;
        background: #dbeafe;
        padding: 2px 8px;
        border-radius: 6px;
    }

    .fur-meta-pill {
        font-size: 11px;
        font-weight: 600;
        color: var(--fur-slate-500);
        background: var(--fur-slate-100);
        padding: 2px 8px;
        border-radius: 6px;
    }

    .fur-card-title {
        font-size: 14px;
        font-weight: 700;
        color: #0b1f52;
        margin: 0 0 8px 0;
        line-height: 1.4;
        word-break: break-word;
    }

    /* File Attachment Box inside card */
    .fur-card-file-box {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        background: var(--fur-slate-50);
        border: 1px solid var(--fur-slate-200);
        max-width: 100%;
    }

    .fur-card-file-box.has-file {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .fur-card-file-box.missing {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .fur-file-name {
        max-width: 320px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
    }

    .fur-review-token-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        padding: 3px 8px;
        background: #f1f5f9;
        border: 1px solid var(--fur-slate-200);
        border-radius: 6px;
        font-family: ui-monospace, SFMono-Regular, monospace;
        font-size: 10.5px;
        font-weight: 700;
        color: var(--fur-slate-700);
    }

    .fur-review-token-badge.approved {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .fur-review-token-badge.forwarded {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }

    .fur-review-token-badge.returned {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    /* FUR Validation Pills & Level Indicators */
    .fur-val-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }

    .fur-val-pill.pending-ro {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }

    .fur-val-pill.pending-po {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #b45309;
    }

    .fur-val-pill.approved {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
    }

    .fur-val-pill.returned {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    .fur-val-pill.missing {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
    }

    .fur-val-level-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 2px 8px;
        border-radius: 6px;
    }

    /* Validation Status Left Border on Project Cards */
    .fur-project-card.val-status-pending-ro {
        border-left: 4px solid #3b82f6 !important;
    }

    .fur-project-card.val-status-pending-po {
        border-left: 4px solid #f59e0b !important;
    }

    .fur-project-card.val-status-approved {
        border-left: 4px solid #10b981 !important;
    }

    .fur-project-card.val-status-returned {
        border-left: 4px solid #ef4444 !important;
    }

    .fur-project-card.val-status-missing {
        border-left: 4px solid #cbd5e1 !important;
    }

    /* Official Validation Stamps & Metadata Box */
    .fur-val-stamps-box {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 18px;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px dashed var(--fur-slate-200);
    }

    .fur-val-stamp {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        line-height: 1.4;
    }

    .fur-val-stamp.po-stamp {
        color: #047857;
    }

    .fur-val-stamp.po-stamp i {
        color: #059669;
    }

    .fur-val-stamp.ro-stamp {
        color: #0284c7;
    }

    .fur-val-stamp.ro-stamp i {
        color: #0284c7;
    }

    .fur-val-stamp.upload-stamp {
        color: #64748b;
    }

    .fur-val-stamp.upload-stamp i {
        color: #94a3b8;
    }

    /* Return Remarks Box */
    .fur-return-remarks-box {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 8px;
        padding: 8px 12px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        border-left: 3px solid #e11d48;
        border-radius: 8px;
        font-size: 12px;
        color: #9f1239;
        line-height: 1.45;
    }

    .fur-return-remarks-box i {
        margin-top: 2px;
        color: #e11d48;
        flex-shrink: 0;
    }

    /* Actions column on right of project card */
    .fur-card-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        flex-shrink: 0;
    }

    .fur-btn-group {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .fur-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid var(--fur-slate-200);
        background: #ffffff;
        color: var(--fur-slate-700);
    }

    .fur-btn-action:hover {
        background: var(--fur-slate-50);
        border-color: var(--fur-slate-300);
        color: var(--fur-slate-900);
        transform: translateY(-1px);
    }

    .fur-btn-action.view {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .fur-btn-action.view:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .fur-btn-action.return-quick {
        border-color: #fecaca;
        color: #b91c1c;
        background: #ffffff;
    }

    .fur-btn-action.return-quick:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    /* Empty state if 0 files for category */
    .fur-doc-empty-state {
        text-align: center;
        padding: 44px 20px;
        background: var(--fur-slate-50);
        border: 2px dashed var(--fur-slate-200);
        border-radius: 14px;
        color: var(--fur-slate-500);
        margin-bottom: 24px;
    }

    .fur-doc-empty-state i {
        font-size: 36px;
        color: #94a3b8;
        margin-bottom: 12px;
    }

    .fur-doc-empty-state h4 {
        font-size: 15px;
        font-weight: 700;
        color: var(--fur-slate-700);
        margin: 0 0 6px 0;
    }

    .fur-doc-empty-state p {
        font-size: 12.5px;
        margin: 0 auto;
        max-width: 440px;
    }

    /* Review Action & Submission Box */
    .fur-review-box {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--fur-slate-200);
        border-radius: 16px;
        padding: 20px 22px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
    }

    .fur-review-box-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .fur-review-box-title {
        font-size: 13.5px;
        font-weight: 800;
        color: #0b1f52;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fur-quick-remarks-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .fur-quick-chip {
        font-size: 11px;
        font-weight: 600;
        background: #ffffff;
        border: 1px solid var(--fur-slate-300);
        border-radius: 9999px;
        padding: 3px 10px;
        color: var(--fur-slate-700);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .fur-quick-chip:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }

    .fur-textarea-remarks {
        width: 100%;
        min-height: 80px;
        padding: 12px 14px;
        border: 1px solid var(--fur-slate-300);
        border-radius: 10px;
        font-family: inherit;
        font-size: 13px;
        color: var(--fur-slate-900);
        background: #ffffff;
        outline: none;
        resize: vertical;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        margin-bottom: 16px;
    }

    .fur-textarea-remarks:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .fur-review-submit-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .fur-btn-submit {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 20px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        border: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .fur-btn-submit.approve {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #ffffff;
    }

    .fur-btn-submit.approve:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(5, 150, 105, 0.25);
    }

    .fur-btn-submit.return {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        color: #ffffff;
    }

    .fur-btn-submit.return:hover {
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(220, 38, 38, 0.25);
    }

    /* History Timeline Modal */
    .fur-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
    }

    .fur-modal-overlay.open {
        display: flex;
        animation: fadeIn 0.2s ease;
    }

    .fur-modal-dialog {
        background: #ffffff;
        border-radius: 18px;
        width: min(720px, 100%);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
        animation: scaleUp 0.2s ease;
    }

    .fur-modal-header {
        background: linear-gradient(135deg, #0b1f52 0%, #1e3a8a 100%);
        color: #ffffff;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .fur-modal-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fur-modal-close-btn {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease;
    }

    .fur-modal-close-btn:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .fur-modal-body {
        padding: 24px;
        overflow-y: auto;
    }

    /* Timeline styling in modal */
    .fur-timeline {
        position: relative;
        padding-left: 28px;
    }

    .fur-timeline::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: var(--fur-slate-200);
    }

    .fur-timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .fur-timeline-item:last-child {
        margin-bottom: 0;
    }

    .fur-timeline-dot {
        position: absolute;
        left: -28px;
        top: 2px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #ffffff;
        border: 3px solid #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .fur-timeline-dot.approved { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
    .fur-timeline-dot.returned { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15); }

    .fur-timeline-card {
        background: var(--fur-slate-50);
        border: 1px solid var(--fur-slate-200);
        border-radius: 12px;
        padding: 12px 16px;
    }

    .fur-timeline-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        flex-wrap: wrap;
        gap: 6px;
    }

    .fur-timeline-actor {
        font-size: 13px;
        font-weight: 700;
        color: #0b1f52;
    }

    .fur-timeline-time {
        font-size: 11.5px;
        color: var(--fur-slate-500);
    }

    .fur-timeline-action-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .fur-timeline-remarks {
        font-size: 12.5px;
        color: var(--fur-slate-700);
        margin: 0;
        line-height: 1.5;
        background: #ffffff;
        border: 1px solid var(--fur-slate-200);
        border-radius: 8px;
        padding: 8px 12px;
    }

    /* Keyframes */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleUp {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Responsive */
    @media (max-width: 980px) {
        .fur-workspace {
            grid-template-columns: 1fr;
        }

        .fur-sidebar {
            position: static;
        }

        .fur-doc-list {
            max-height: none;
        }

        .fur-hero-meta-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 680px) {
        .fur-project-card {
            grid-template-columns: 28px 1fr;
        }

        .fur-card-actions {
            grid-column: 2;
            align-items: flex-start;
            margin-top: 8px;
        }

        .fur-btn-group {
            justify-content: flex-start;
        }

        .fur-hero-card {
            padding: 18px;
        }

        .fur-hero-title {
            font-size: 22px;
        }
    }
</style>
@endsection

@section('content')
<div class="fur-batch-container">
    {{-- Navigation Breadcrumbs --}}
    <nav class="fur-nav-bar" aria-label="Breadcrumb">
        <a class="fur-back-btn" href="{{ route('fund-utilization.upload-batches.index') }}">
            <i class="fas fa-arrow-left"></i>
            <span>All Upload Batches</span>
        </a>
        <div class="fur-nav-actions">
            <a class="fur-back-btn" href="{{ route('fund-utilization.index') }}">
                <i class="fas fa-table-list"></i>
                <span>Fund Utilization Reports</span>
            </a>
        </div>
    </nav>

    {{-- Hero Summary Card --}}
    <section class="fur-hero-card">
        <div class="fur-hero-header">
            <div>
                <div class="fur-hero-badge-wrap">
                    <span class="pulse-dot"></span>
                    <i class="fas fa-layer-group"></i>
                    <span>FUR Validator Batch Workspace</span>
                </div>
                <h1 class="fur-hero-title">
                    <span>{{ $batch->batch_number }}</span>
                    <span class="fur-quarter-tag">Quarter {{ $batch->quarter }}</span>
                </h1>
                <p class="fur-hero-desc">
                    Comprehensive multi-project batch verification workspace. Review submitted documents by category, approve verified items, or tag deficient projects for revision.
                </p>
            </div>
            <div>
                @php
                    $statusClass = match (strtolower((string) $batch->status)) {
                        'approved' => 'approved',
                        'returned' => 'returned',
                        default => 'submitted',
                    };
                @endphp
                <span class="fur-batch-status-pill {{ $statusClass }}">
                    @if($statusClass === 'approved')
                        <i class="fas fa-circle-check"></i>
                    @elseif($statusClass === 'returned')
                        <i class="fas fa-rotate-left"></i>
                    @else
                        <i class="fas fa-clock"></i>
                    @endif
                    <span>{{ str_replace('_', ' ', ucfirst($batch->status)) }}</span>
                </span>
            </div>
        </div>

        {{-- Meta & Stat Badges --}}
        <div class="fur-hero-meta-grid">
            <div class="fur-uploader-card">
                <div class="fur-uploader-avatar">
                    @php
                        $uploaderName = $batch->uploader ? $batch->uploader->fullName() : 'Provincial Office';
                        $initials = collect(explode(' ', $uploaderName))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                    @endphp
                    {{ $initials ?: 'PO' }}
                </div>
                <div class="fur-uploader-info">
                    <h4>{{ $uploaderName }}</h4>
                    <p>
                        <i class="fas fa-building me-1"></i> {{ $batch->uploader?->agency ?: 'DILG' }} {{ $batch->uploader?->province ? '· ' . $batch->uploader->province : '' }}
                        <span class="mx-1">·</span>
                        <i class="fas fa-clock me-1"></i> {{ optional($batch->submitted_at)->format('M d, Y h:i A') ?: 'Recent' }}
                    </p>
                </div>
            </div>

            <div class="fur-stat-card">
                <div class="fur-stat-icon blue">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="fur-stat-content">
                    <h5>{{ $stats['total_projects'] }}</h5>
                    <span>Projects In Batch</span>
                </div>
            </div>

            <div class="fur-stat-card">
                <div class="fur-stat-icon green">
                    <i class="fas fa-file-circle-check"></i>
                </div>
                <div class="fur-stat-content">
                    <h5>{{ $stats['total_files_uploaded'] }}</h5>
                    <span>Files Uploaded</span>
                </div>
            </div>

            @if(($stats['total_pending_ro'] ?? 0) > 0)
                <div class="fur-stat-card">
                    <div class="fur-stat-icon blue">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div class="fur-stat-content">
                        <h5>{{ $stats['total_pending_ro'] }}</h5>
                        <span>Pending Regional (L2)</span>
                    </div>
                </div>
            @endif

            @if(($stats['total_pending_po'] ?? 0) > 0)
                <div class="fur-stat-card">
                    <div class="fur-stat-icon amber">
                        <i class="fas fa-map-location-dot"></i>
                    </div>
                    <div class="fur-stat-content">
                        <h5>{{ $stats['total_pending_po'] }}</h5>
                        <span>Pending Provincial (L1)</span>
                    </div>
                </div>
            @endif

            @if(($stats['total_approved'] ?? 0) > 0)
                <div class="fur-stat-card">
                    <div class="fur-stat-icon green">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="fur-stat-content">
                        <h5>{{ $stats['total_approved'] }}</h5>
                        <span>Validated & Approved</span>
                    </div>
                </div>
            @endif

            @if(($stats['total_returned'] ?? 0) > 0)
                <div class="fur-stat-card">
                    <div class="fur-stat-icon red">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                    <div class="fur-stat-content">
                        <h5>{{ $stats['total_returned'] }}</h5>
                        <span>Returned For Revision</span>
                    </div>
                </div>
            @endif

            <div class="fur-stat-card">
                <div class="fur-stat-icon purple">
                    <i class="fas fa-list-check"></i>
                </div>
                <div class="fur-stat-content">
                    <h5>{{ $stats['active_groups_count'] }}</h5>
                    <span>Active Categories</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="fur-alert success" role="alert">
            <i class="fas fa-circle-check fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="fur-alert error" role="alert">
            <i class="fas fa-triangle-exclamation fs-5"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    {{-- Main Two-Column Workspace --}}
    <div class="fur-workspace">
        {{-- Left Sidebar: Document Categories --}}
        <aside class="fur-sidebar" aria-label="Document Categories">
            <div class="fur-sidebar-header">
                <h3 class="fur-sidebar-title">
                    <i class="fas fa-layer-group text-primary"></i>
                    <span>Document Requirements</span>
                </h3>
                <span class="badge bg-light text-secondary rounded-pill" style="font-size: 11px;">
                    {{ count($documentGroups) }}
                </span>
            </div>

            {{-- Quick Filter: All vs Only Uploaded --}}
            <div class="fur-filter-switch" role="tablist">
                <button type="button" class="fur-filter-btn active" data-filter="all">All Items</button>
                <button type="button" class="fur-filter-btn" data-filter="uploads">With Files ({{ $stats['active_groups_count'] }})</button>
            </div>

            {{-- Document Tabs List --}}
            <div class="fur-doc-list" role="tablist">
                @foreach($documentGroups as $index => $group)
                    @php
                        $hasFiles = $group['available_count'] > 0;
                        $sb = $group['sidebar_status'] ?? [
                            'label' => $hasFiles ? 'Review Needed' : 'No Uploads',
                            'class' => $hasFiles ? 'primary' : 'muted',
                            'icon' => $hasFiles ? 'fa-clock' : 'fa-minus',
                        ];
                    @endphp
                    <button type="button"
                            class="fur-doc-tab {{ $index === 0 ? 'active' : '' }} {{ $hasFiles ? 'has-files' : 'no-files' }}"
                            data-doc-type="{{ $group['type'] }}"
                            data-has-files="{{ $hasFiles ? 'true' : 'false' }}"
                            role="tab"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        <div class="fur-doc-tab-icon">
                            <i class="fas {{ $group['icon'] }}"></i>
                        </div>
                        <div class="fur-doc-tab-content">
                            <span class="fur-doc-tab-title">{{ $group['label'] }}</span>
                            <span class="fur-doc-tab-sub">
                                <span class="sidebar-status-tag status-{{ $sb['class'] }}">
                                    <i class="fas {{ $sb['icon'] }}"></i> {{ $sb['label'] }}
                                </span>
                            </span>
                        </div>
                        <span class="fur-doc-pill-count">
                            {{ $group['available_count'] }}/{{ $batch->projects->count() }}
                        </span>
                    </button>
                @endforeach
            </div>
        </aside>

        {{-- Right Pane: Document Review Workspace --}}
        <main class="fur-review-panel">
            @foreach($documentGroups as $index => $group)
                <div class="fur-panel-section {{ $index === 0 ? 'active' : '' }}"
                     id="panel-{{ $group['type'] }}"
                     data-panel-type="{{ $group['type'] }}"
                     role="tabpanel">

                    {{-- Document Category Header Banner --}}
                    <div class="fur-doc-header">
                        <div class="fur-doc-title-group">
                            <div class="fur-doc-header-icon">
                                <i class="fas {{ $group['icon'] }}"></i>
                            </div>
                            <div class="fur-doc-header-text">
                                <h2>{{ $group['label'] }}</h2>
                                <p>{{ $group['description'] }}</p>
                            </div>
                        </div>

                        <div class="fur-doc-metrics-wrap">
                            @if($group['available_count'] > 0)
                                <span class="fur-metric-badge files-ok" title="Total Projects with Uploads">
                                    <i class="fas fa-file-check"></i>
                                    <span>{{ $group['available_count'] }} of {{ $batch->projects->count() }} Uploaded</span>
                                </span>

                                @if(($group['pending_ro_count'] ?? 0) > 0)
                                    <span class="fur-metric-badge ro" title="Pending DILG Regional Validation (Level 2)">
                                        <i class="fas fa-building-columns"></i>
                                        <span>{{ $group['pending_ro_count'] }} Pending Regional</span>
                                    </span>
                                @endif

                                @if(($group['pending_po_count'] ?? 0) > 0)
                                    <span class="fur-metric-badge po" title="Pending DILG Provincial Validation (Level 1)">
                                        <i class="fas fa-map-location-dot"></i>
                                        <span>{{ $group['pending_po_count'] }} Pending Provincial</span>
                                    </span>
                                @endif

                                @if(($group['approved_count'] ?? 0) > 0)
                                    <span class="fur-metric-badge approved" title="Validated & Approved Documents">
                                        <i class="fas fa-circle-check"></i>
                                        <span>{{ $group['approved_count'] }} Validated & Approved</span>
                                    </span>
                                @endif

                                @if(($group['returned_count'] ?? 0) > 0)
                                    <span class="fur-metric-badge returned" title="Returned for Revision">
                                        <i class="fas fa-rotate-left"></i>
                                        <span>{{ $group['returned_count'] }} Returned</span>
                                    </span>
                                @endif
                            @else
                                <span class="fur-metric-badge files-none">
                                    <i class="fas fa-circle-info"></i>
                                    <span>No Files Attached In Batch</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Guidance / Explanation Alert --}}
                    <div class="fur-instruction-banner">
                        <i class="fas fa-shield-halved"></i>
                        <div class="fur-instruction-banner-text">
                            <strong>Validation Instructions:</strong> Checked projects will be recorded as <strong>Approved</strong> (or forwarded to Regional Office). Projects that are left unchecked will be recorded as <strong>Returned</strong> for revision. When returning any project, descriptive validator remarks are mandatory.
                        </div>
                    </div>

                    {{-- Form for Reviewing This Document Type --}}
                    <form method="POST"
                          action="{{ route('fund-utilization.upload-batches.documents.review', [$batch->id, $group['type']]) }}"
                          class="fur-review-form"
                          id="form-{{ $group['type'] }}">
                        @csrf

                        {{-- Interactive List Toolbar (Select All / Deselect All / Search) --}}
                        <div class="fur-list-toolbar">
                            <div class="fur-toolbar-left">
                                <button type="button" class="fur-select-all-btn btn-toggle-all" data-target="form-{{ $group['type'] }}">
                                    <i class="fas fa-check-double"></i>
                                    <span>Select All ({{ $batch->projects->count() }})</span>
                                </button>
                                <span class="fur-selection-count selection-counter">
                                    <span class="count-checked">0</span> of {{ $batch->projects->count() }} selected
                                </span>
                            </div>

                            <div class="fur-search-box">
                                <i class="fas fa-search"></i>
                                <input type="text"
                                       class="project-filter-input"
                                       placeholder="Filter project code or title..."
                                       aria-label="Filter projects in list">
                            </div>
                        </div>

                        {{-- Project Review Cards List --}}
                        <div class="fur-project-cards-wrap">
                            @foreach($group['projects'] as $entry)
                                @php
                                    $project = $entry['project'];
                                    $report = $project->report_details;
                                    $review = $entry['review'];
                                    $hasItem = $entry['item']['available'];
                                    $val = $entry['item']['validation'] ?? null;
                                    $isPreChecked = ($review?->status === 'approved' || $review?->status === 'forwarded' || (!$review && $hasItem));
                                @endphp
                                <div class="fur-project-card val-status-{{ $val['badge_class'] ?? 'missing' }} {{ $isPreChecked ? 'is-selected' : '' }}"
                                     data-project-card
                                     data-project-code="{{ strtolower($project->project_code) }}"
                                     data-project-title="{{ strtolower($report?->project_title ?? '') }}">

                                    {{-- Checkbox Toggle --}}
                                    <div class="fur-card-checkbox-wrap">
                                        <input type="checkbox"
                                               name="project_codes[]"
                                               value="{{ $project->project_code }}"
                                               class="fur-custom-checkbox project-checkbox"
                                               {{ $isPreChecked ? 'checked' : '' }}
                                               aria-label="Select {{ $project->project_code }}">
                                    </div>

                                    {{-- Project Core Details --}}
                                    <div class="fur-card-main">
                                        <div class="fur-card-tags">
                                            <span class="fur-code-pill">
                                                <i class="fas fa-barcode me-1"></i> {{ $project->project_code }}
                                            </span>
                                            @if($report?->funding_year && $report->funding_year !== 'N/A')
                                                <span class="fur-meta-pill">
                                                    <i class="fas fa-calendar-alt me-1"></i> {{ $report->funding_year }}
                                                </span>
                                            @endif
                                            @if($report?->province && $report->province !== 'N/A')
                                                <span class="fur-meta-pill">
                                                    <i class="fas fa-map-marker-alt me-1"></i> {{ $report->province }}
                                                    @if($report->implementing_unit && $report->implementing_unit !== 'N/A')
                                                        · {{ $report->implementing_unit }}
                                                    @endif
                                                </span>
                                            @endif
                                            @if($report?->fund_source)
                                                <span class="fur-meta-pill text-primary" style="background:#eff6ff;">
                                                    <i class="fas fa-coins me-1"></i> {{ $report->fund_source }}
                                                </span>
                                            @endif

                                            {{-- Official FUR Validation Status Pill --}}
                                            @if($val)
                                                <span class="fur-val-pill {{ $val['badge_class'] }}" title="Workflow Status: {{ $val['workflow_status'] ?? $val['status_label'] }}">
                                                    <i class="fas {{ $val['icon'] }}"></i>
                                                    <span>{{ $val['status_label'] }}</span>
                                                </span>

                                                @if($val['has_file'] && $val['current_level'])
                                                    <span class="fur-val-level-pill" title="Current Approval Level">
                                                        <i class="fas fa-layer-group"></i> {{ $val['current_level_label'] }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>

                                        <h4 class="fur-card-title">
                                            {{ $report?->project_title ?: 'Project Title Unavailable' }}
                                        </h4>

                                        {{-- File Attachment Representation --}}
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            @if($hasItem)
                                                <div class="fur-card-file-box has-file">
                                                    @if($group['type'] === 'posting-link')
                                                        <i class="fas fa-link text-primary"></i>
                                                        <a href="{{ $entry['item']['file_name'] }}" target="_blank" rel="noopener" class="fur-file-name text-decoration-none text-primary">
                                                            {{ $entry['item']['file_name'] }}
                                                        </a>
                                                    @else
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                        <span class="fur-file-name" title="{{ $entry['item']['file_name'] }}">
                                                            {{ $entry['item']['file_name'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="fur-card-file-box missing">
                                                    <i class="fas fa-triangle-exclamation"></i>
                                                    <span>No document uploaded for Quarter {{ $batch->quarter }}</span>
                                                </div>
                                            @endif

                                            {{-- Validation Audit Badge if already reviewed in this batch --}}
                                            @if($review)
                                                @php
                                                    $reviewClass = match($review->status) {
                                                        'approved' => 'approved',
                                                        'forwarded' => 'forwarded',
                                                        'returned' => 'returned',
                                                        default => '',
                                                    };
                                                @endphp
                                                <span class="fur-review-token-badge {{ $reviewClass }}" title="Validation Token: {{ $review->validation_token }}">
                                                    <i class="fas fa-certificate"></i>
                                                    <span>Batch Review: {{ strtoupper($review->status) }}</span>
                                                    <span>· Level {{ $review->validation_level }}</span>
                                                    <span>· {{ substr($review->validation_token, 0, 16) }}...</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Official FUR Validation Stamps & Encoder Metadata --}}
                                        @if($val && $val['has_file'])
                                            <div class="fur-val-stamps-box">
                                                @if($val['po_approved_at'])
                                                    <div class="fur-val-stamp po-stamp">
                                                        <i class="fas fa-circle-check"></i>
                                                        <span>DILG Provincial Validated at: <strong>{{ $val['po_approved_at'] }}</strong>@if($val['po_approver_name']) by <strong>{{ $val['po_approver_name'] }}</strong>@endif</span>
                                                    </div>
                                                @endif

                                                @if($val['ro_approved_at'])
                                                    <div class="fur-val-stamp ro-stamp">
                                                        <i class="fas fa-check-double"></i>
                                                        <span>DILG Regional Validated at: <strong>{{ $val['ro_approved_at'] }}</strong>@if($val['ro_approver_name']) by <strong>{{ $val['ro_approver_name'] }}</strong>@endif</span>
                                                    </div>
                                                @endif

                                                @if($val['uploaded_at'])
                                                    <div class="fur-val-stamp upload-stamp">
                                                        <i class="fas fa-cloud-arrow-up"></i>
                                                        <span>Uploaded at: {{ $val['uploaded_at'] }}@if($val['uploader_name']) by {{ $val['uploader_name'] }}@endif</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Remarks if previously returned in FUR or Batch Review --}}
                                        @if($val && $val['status'] === 'returned' && $val['remarks'])
                                            <div class="fur-return-remarks-box">
                                                <i class="fas fa-comment-dots"></i>
                                                <div>
                                                    <strong>Return Remarks:</strong> {{ $val['remarks'] }}
                                                </div>
                                            </div>
                                        @elseif($review && $review->remarks && $review->status === 'returned')
                                            <div class="fur-return-remarks-box">
                                                <i class="fas fa-comment-dots"></i>
                                                <div>
                                                    <strong>Batch Review Remarks:</strong> {{ $review->remarks }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Quick Action Buttons on Right --}}
                                    <div class="fur-card-actions">
                                        <div class="fur-btn-group">
                                            @if($entry['view_url'])
                                                <a class="fur-btn-action view"
                                                   href="{{ $entry['view_url'] }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   title="View uploaded document in new tab">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                            @endif

                                            <button type="button"
                                                    class="fur-btn-action"
                                                    data-history-url="{{ route('fund-utilization.upload-batches.documents.history', [$batch->id, $group['type'], $project->project_code]) }}"
                                                    data-project-code="{{ $project->project_code }}"
                                                    data-doc-label="{{ $group['label'] }}"
                                                    title="View audit trail and workflow logs">
                                                <i class="fas fa-history"></i>
                                                <span>History</span>
                                            </button>

                                            <button type="button"
                                                    class="fur-btn-action return-quick"
                                                    data-return-project="{{ $project->project_code }}"
                                                    title="Flag just this project as returned for correction">
                                                <i class="fas fa-rotate-left"></i>
                                                <span>Return</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Validator Submission & Remarks Area --}}
                        <div class="fur-review-box">
                            <div class="fur-review-box-header">
                                <h4 class="fur-review-box-title">
                                    <i class="fas fa-pen-to-square text-primary"></i>
                                    <span>Validator Remarks & Decision</span>
                                </h4>
                                <span class="text-muted" style="font-size: 11.5px;">
                                    Remarks required if returning any project for revision
                                </span>
                            </div>

                            {{-- Quick Pre-canned Remarks Chips --}}
                            <div class="fur-quick-remarks-wrap" aria-label="Quick remarks templates">
                                <span class="text-secondary me-1" style="font-size: 11px; font-weight: 700;">Quick templates:</span>
                                <button type="button" class="fur-quick-chip" data-insert="Complete and compliant as submitted. Verified for Quarter {{ $batch->quarter }}.">+ Verified & Compliant</button>
                                <button type="button" class="fur-quick-chip" data-insert="Returned: Incomplete milestone attachments and proof of implementation.">+ Incomplete Attachments</button>
                                <button type="button" class="fur-quick-chip" data-insert="Returned: Official signatures or certifications are missing from transmittal.">+ Missing Signatures</button>
                                <button type="button" class="fur-quick-chip" data-insert="Returned: Discrepancy between reported disbursement amount and financial schedule.">+ Amount Discrepancy</button>
                                <button type="button" class="fur-quick-chip" data-insert="Returned: Please upload clear, geotagged high-resolution photos.">+ Geotagged Photos</button>
                            </div>

                            <textarea class="fur-textarea-remarks"
                                      name="remarks"
                                      placeholder="Enter validator remarks, observations, or specific revision guidelines for the LGU / provincial office..."
                                      rows="3">{{ old('remarks') }}</textarea>

                            <div class="fur-review-submit-bar">
                                <button type="submit"
                                        name="action"
                                        value="return"
                                        class="fur-btn-submit return"
                                        onclick="return confirmReturnAction(this);">
                                    <i class="fas fa-rotate-left"></i>
                                    <span>Return for Correction</span>
                                </button>

                                <button type="submit"
                                        name="action"
                                        value="approve"
                                        class="fur-btn-submit approve">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Approve Selected Projects</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach
        </main>
    </div>
</div>

{{-- Modern Audit History Modal --}}
<div id="furHistoryModal" class="fur-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="furHistoryTitle">
    <div class="fur-modal-dialog">
        <div class="fur-modal-header">
            <div>
                <h3 class="fur-modal-title" id="furHistoryTitle">
                    <i class="fas fa-timeline"></i>
                    <span>Document Audit Trail</span>
                </h3>
                <div id="furHistorySub" style="font-size: 12px; color: #bfdbfe; margin-top: 3px;">Loading...</div>
            </div>
            <button type="button" id="furHistoryClose" class="fur-modal-close-btn" aria-label="Close modal">&times;</button>
        </div>
        <div id="furHistoryBody" class="fur-modal-body">
            <div class="text-center py-4 text-muted">
                <i class="fas fa-circle-notch fa-spin fa-2x mb-2 text-primary"></i>
                <div>Loading audit history...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Document Tab Navigation
    const tabs = document.querySelectorAll('[data-doc-type]');
    const panels = document.querySelectorAll('[data-panel-type]');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const docType = tab.dataset.docType;

            tabs.forEach(t => {
                const isActive = t === tab;
                t.classList.toggle('active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panels.forEach(p => {
                const isActive = p.dataset.panelType === docType;
                p.classList.toggle('active', isActive);
            });
        });
    });

    // 2. Sidebar Filter (All vs Uploads only)
    const filterButtons = document.querySelectorAll('.fur-filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.dataset.filter;
            tabs.forEach(tab => {
                if (filter === 'uploads') {
                    tab.style.display = tab.dataset.hasFiles === 'true' ? 'flex' : 'none';
                } else {
                    tab.style.display = 'flex';
                }
            });
        });
    });

    // 3. Selection Counter & Card Highlighting per panel
    function updatePanelSelection(panel) {
        const checkboxes = panel.querySelectorAll('.project-checkbox');
        const checked = panel.querySelectorAll('.project-checkbox:checked');
        const countDisplay = panel.querySelector('.count-checked');
        const toggleBtn = panel.querySelector('.btn-toggle-all span');

        if (countDisplay) {
            countDisplay.textContent = checked.length;
        }

        checkboxes.forEach(cb => {
            const card = cb.closest('[data-project-card]');
            if (card) {
                card.classList.toggle('is-selected', cb.checked);
            }
        });

        if (toggleBtn) {
            if (checked.length === checkboxes.length && checkboxes.length > 0) {
                toggleBtn.textContent = 'Deselect All (' + checkboxes.length + ')';
            } else {
                toggleBtn.textContent = 'Select All (' + checkboxes.length + ')';
            }
        }
    }

    // Initialize counts on each panel
    panels.forEach(panel => {
        updatePanelSelection(panel);

        panel.querySelectorAll('.project-checkbox').forEach(cb => {
            cb.addEventListener('change', () => updatePanelSelection(panel));
        });

        // Toggle All button
        const toggleAll = panel.querySelector('.btn-toggle-all');
        if (toggleAll) {
            toggleAll.addEventListener('click', () => {
                const checkboxes = panel.querySelectorAll('.project-checkbox');
                const allChecked = panel.querySelectorAll('.project-checkbox:checked').length === checkboxes.length;
                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                });
                updatePanelSelection(panel);
            });
        }

        // Live project search within panel
        const searchInput = panel.querySelector('.project-filter-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase().trim();
                const cards = panel.querySelectorAll('[data-project-card]');
                cards.forEach(card => {
                    const code = card.dataset.projectCode || '';
                    const title = card.dataset.projectTitle || '';
                    const match = code.includes(query) || title.includes(query);
                    card.style.display = match ? 'grid' : 'none';
                });
            });
        }

        // Quick remarks chips
        panel.querySelectorAll('.fur-quick-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const textarea = panel.querySelector('.fur-textarea-remarks');
                if (textarea) {
                    const text = chip.dataset.insert;
                    if (textarea.value.trim() === '') {
                        textarea.value = text;
                    } else if (!textarea.value.includes(text)) {
                        textarea.value += ' ' + text;
                    }
                    textarea.focus();
                }
            });
        });
    });

    // 4. Single Project Return Button
    document.querySelectorAll('[data-return-project]').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const projectCode = button.dataset.returnProject;
            const form = button.closest('form');
            if (!form) return;

            const textarea = form.querySelector('.fur-textarea-remarks');
            if (!textarea || textarea.value.trim() === '') {
                const promptReason = prompt('Enter reason for returning project ' + projectCode + ' for revision:');
                if (promptReason === null || promptReason.trim() === '') {
                    alert('Remarks are required when returning a project.');
                    if (textarea) textarea.focus();
                    return;
                }
                if (textarea) textarea.value = promptReason.trim();
            }

            // Uncheck this project
            const checkbox = form.querySelector(`input[name="project_codes[]"][value="${CSS.escape(projectCode)}"]`);
            if (checkbox) checkbox.checked = false;

            let target = form.querySelector('input[name="return_project_code"]');
            if (!target) {
                target = document.createElement('input');
                target.type = 'hidden';
                target.name = 'return_project_code';
                form.appendChild(target);
            }
            target.value = projectCode;

            const returnSubmit = form.querySelector('button[name="action"][value="return"]');
            if (returnSubmit) {
                returnSubmit.click();
            } else {
                form.submit();
            }
        });
    });

    // 5. History Modal AJAX Loader
    const modal = document.getElementById('furHistoryModal');
    const modalTitle = document.getElementById('furHistoryTitle');
    const modalSub = document.getElementById('furHistorySub');
    const modalBody = document.getElementById('furHistoryBody');
    const modalClose = document.getElementById('furHistoryClose');

    document.querySelectorAll('[data-history-url]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const url = btn.dataset.historyUrl;
            const projectCode = btn.dataset.projectCode || '';
            const docLabel = btn.dataset.docLabel || 'Document';

            modal.classList.add('open');
            modalSub.textContent = `${projectCode} · ${docLabel}`;
            modalBody.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-circle-notch fa-spin fa-2x mb-3 text-primary"></i>
                    <div>Fetching audit logs and review timestamps...</div>
                </div>
            `;

            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('Unable to retrieve audit history.');
                const data = await res.json();

                let html = '<div class="fur-timeline">';

                // If batch review exists
                if (data.review) {
                    const r = data.review;
                    const rStatus = (r.status || 'reviewed').toLowerCase();
                    html += `
                        <div class="fur-timeline-item">
                            <div class="fur-timeline-dot ${rStatus}"></div>
                            <div class="fur-timeline-card">
                                <div class="fur-timeline-head">
                                    <span class="fur-timeline-actor">
                                        <i class="fas fa-user-shield text-primary me-1"></i> ${r.validator_name || 'Batch Validator'}
                                    </span>
                                    <span class="fur-timeline-time">${r.validated_at || 'Recently'}</span>
                                </div>
                                <span class="badge ${rStatus === 'approved' ? 'bg-success' : (rStatus === 'returned' ? 'bg-danger' : 'bg-primary')} fur-timeline-action-badge">
                                    Batch Review: ${r.status.toUpperCase()} (Level ${r.validation_level})
                                </span>
                                <div class="font-monospace small text-muted mb-2" style="font-size:11px;">Token: ${r.validation_token}</div>
                                <div class="fur-timeline-remarks">${r.remarks || 'No remarks provided.'}</div>
                            </div>
                        </div>
                    `;
                }

                // Workflow logs
                const logs = data.workflow?.logs || [];
                if (logs.length > 0) {
                    logs.forEach(log => {
                        const actionLower = (log.action || '').toLowerCase();
                        let dotClass = '';
                        let badgeColor = 'bg-secondary';
                        if (actionLower.includes('approve')) { dotClass = 'approved'; badgeColor = 'bg-success'; }
                        else if (actionLower.includes('return')) { dotClass = 'returned'; badgeColor = 'bg-danger'; }
                        else if (actionLower.includes('upload')) { badgeColor = 'bg-primary'; }

                        html += `
                            <div class="fur-timeline-item">
                                <div class="fur-timeline-dot ${dotClass}"></div>
                                <div class="fur-timeline-card">
                                    <div class="fur-timeline-head">
                                        <span class="fur-timeline-actor">
                                            <i class="fas fa-user-check me-1"></i> ${log.actor || 'System Actor'}
                                        </span>
                                        <span class="fur-timeline-time">${log.created_at || '—'}</span>
                                    </div>
                                    <span class="badge ${badgeColor} fur-timeline-action-badge">${log.action || 'Workflow Action'}</span>
                                    <div class="fur-timeline-remarks">${log.remarks || 'No remarks entered.'}</div>
                                </div>
                            </div>
                        `;
                    });
                } else if (!data.review) {
                    html += `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-clipboard-question fa-2x mb-2 text-secondary"></i>
                            <div>No previous workflow reviews or action logs found for this document.</div>
                        </div>
                    `;
                }

                html += '</div>';
                modalBody.innerHTML = html;
            } catch (err) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="fas fa-triangle-exclamation me-1"></i> ${err.message}
                    </div>
                `;
            }
        });
    });

    modalClose.addEventListener('click', () => modal.classList.remove('open'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('open'); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.classList.remove('open'); });
});

function confirmReturnAction(button) {
    const form = button.closest('form');
    const textarea = form ? form.querySelector('.fur-textarea-remarks') : null;
    if (textarea && textarea.value.trim() === '') {
        alert('Please enter validator remarks explaining the required corrections before returning.');
        textarea.focus();
        return false;
    }
    return confirm('Are you sure you want to return the unselected / flagged projects for correction?');
}
</script>
@endsection
