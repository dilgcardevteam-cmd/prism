@extends('layouts.dashboard')

@section('title', 'Task Management')
@section('page-title', 'Task Management')

@section('styles')
<style>
    .task-mgmt-page {
        color: #0f172a;
    }

    .task-mgmt-shell {
        display: grid;
        gap: 24px;
    }

    .task-mgmt-hero {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        padding: 30px;
        background:
            radial-gradient(circle at top right, rgba(125, 211, 252, 0.22), transparent 34%),
            linear-gradient(135deg, #0b1f52 0%, #12398d 52%, #1d4ed8 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
    }

    .task-mgmt-hero::after {
        content: '';
        position: absolute;
        inset: auto -60px -60px auto;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        filter: blur(8px);
    }

    .task-mgmt-hero-content {
        position: relative;
        z-index: 1;
    }

    .task-mgmt-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.9);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .task-mgmt-title {
        margin: 12px 0 6px;
        color: #fff;
        font-size: clamp(24px, 3.5vw, 32px);
        line-height: 1.15;
        font-weight: 800;
    }

    .task-mgmt-description {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        max-width: 600px;
    }

    .task-pool-section {
        margin-bottom: 12px;
    }

    .task-pool-section-title {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin: 12px 0 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
    }

    /* Cards Grid */
    .menu-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .menu-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 160px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .menu-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px -8px rgba(15, 23, 42, 0.15);
        border-color: #cbd5e1;
    }

    .menu-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .menu-card-icon-wrapper {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .menu-card-icon-wrapper.primary {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .menu-card-icon-wrapper.danger {
        background: #fef2f2;
        color: #dc2626;
    }

    .menu-card-badge {
        font-size: 14px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 999px;
    }

    .menu-card-badge.danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .menu-card-title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.4;
    }

    .menu-card-footer {
        margin-top: 14px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .menu-card-footer.primary {
        color: #1d4ed8;
    }

    .menu-card-footer.danger {
        color: #dc2626;
    }

    /* Modal Styling */
    .task-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1040;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .task-modal {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: min(1400px, 96vw);
        max-height: 85vh;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: modalSlideUp 0.25s ease-out;
    }

    @keyframes modalSlideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .task-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }

    .task-modal-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .task-modal-title i {
        color: #1d4ed8;
    }

    .task-modal-close {
        border: none;
        background: none;
        font-size: 20px;
        color: #64748b;
        cursor: pointer;
        transition: color 0.15s ease;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .task-modal-close:hover {
        color: #0f172a;
    }

    /* Modal Filtering Bar */
    .task-modal-filter-bar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 24px;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-input-wrapper {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .filter-input-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
    }

    .modal-search-input {
        width: 100%;
        padding: 9px 12px 9px 36px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .modal-search-input:focus {
        border-color: #1d4ed8;
    }

    .modal-select-filter {
        width: auto;
        padding: 10px 30px 10px 36px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        background-color: #fff;
        cursor: pointer;
        min-width: 160px;
    }

    .modal-filter-field {
        position: relative;
        min-width: 170px;
    }

    .modal-filter-field > i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #2563eb;
        font-size: 13px;
        pointer-events: none;
        z-index: 1;
    }

    .modal-filter-field .modal-select-filter {
        width: 100%;
    }

    .modal-select-filter:focus {
        border-color: #1d4ed8;
    }

    .modal-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .modal-filter-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 9px 15px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .modal-filter-btn:hover {
        transform: translateY(-1px);
    }

    .modal-filter-btn.apply {
        background: #1d4ed8;
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.2);
    }

    .modal-filter-btn.reset {
        background: #fff;
        color: #475569;
        border-color: #cbd5e1;
    }

    .modal-filter-btn.reset:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    @media (max-width: 680px) {
        .modal-filter-field,
        .modal-filter-actions {
            width: 100%;
        }

        .modal-filter-actions .modal-filter-btn {
            flex: 1;
        }
    }

    .task-modal-body {
        overflow-y: auto;
        flex: 1;
        padding: 0;
    }

    /* Table inside modal */
    .table-container {
        overflow-x: auto;
    }

    .task-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .task-table th {
        background: #fff;
        padding: 12px 24px;
        text-align: center;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .task-table td {
        padding: 14px 24px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .task-uploader-cell {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
        text-align: center;
        min-width: 150px;
    }

    .task-uploader-name {
        color: #0f172a;
        font-size: 12px;
        font-weight: 750;
        line-height: 1.3;
    }

    .task-uploader-province {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.2;
    }

    .task-uploader-province i {
        color: #2563eb;
        font-size: 10px;
    }

    .task-table tr:last-child td {
        border-bottom: none;
    }

    .task-table tr:hover {
        background: #f8fafc;
    }

    .proj-title-cell {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
        max-width: 320px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .task-table-fund-utilization {
        table-layout: fixed;
        min-width: 0;
        font-size: 11px;
    }

    .task-table-fund-utilization th,
    .task-table-fund-utilization td {
        padding: 9px 7px;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .task-table-fund-utilization th {
        font-size: 9px;
        letter-spacing: 0.01em;
    }

    .task-table-fund-utilization th:nth-child(1),
    .task-table-fund-utilization td:nth-child(1) {
        width: 12%;
    }

    .task-table-fund-utilization th:nth-child(2),
    .task-table-fund-utilization td:nth-child(2) {
        width: 16%;
    }

    .task-table-fund-utilization th:nth-child(3),
    .task-table-fund-utilization td:nth-child(3),
    .task-table-fund-utilization th:nth-child(4),
    .task-table-fund-utilization td:nth-child(4) {
        width: 10%;
    }

    .task-table-fund-utilization th:nth-child(5),
    .task-table-fund-utilization td:nth-child(5) {
        width: 7%;
    }

    .task-table-fund-utilization th:nth-child(6),
    .task-table-fund-utilization td:nth-child(6) {
        width: 15%;
    }

    .task-table-fund-utilization th:nth-child(7),
    .task-table-fund-utilization td:nth-child(7) {
        width: 12%;
    }

    .task-table-fund-utilization th:nth-child(8),
    .task-table-fund-utilization td:nth-child(8) {
        width: 10%;
    }

    .task-table-fund-utilization th:nth-child(9),
    .task-table-fund-utilization td:nth-child(9) {
        width: 8%;
    }

    .task-table-fund-utilization .proj-title-cell {
        max-width: none;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
    }

    /* ==========================================================================
       FUND UTILIZATION REPORT - BATCH UPLOAD APPROVALS POOL (CAR REGIONAL)
       ========================================================================== */
    .task-modal-batch-wide {
        max-width: 1280px !important;
        width: 96vw !important;
        border-radius: 20px;
        box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.9);
        overflow: hidden;
    }

    .task-modal-header-batch {
        background: linear-gradient(135deg, #091e42 0%, #0f3460 45%, #0284c7 100%) !important;
        color: #ffffff !important;
        padding: 20px 28px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .batch-header-main {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .batch-header-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.28);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        flex-shrink: 0;
    }

    .batch-header-titles {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .batch-header-pill-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .batch-modal-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.01em;
        line-height: 1.25;
    }

    .batch-scope-badge-header {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(56, 189, 248, 0.22);
        border: 1px solid rgba(125, 211, 252, 0.45);
        color: #e0f2fe;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .batch-scope-badge-header i {
        color: #7dd3fc;
        font-size: 10px;
    }

    .batch-modal-subtitle {
        margin: 0;
        font-size: 12px;
        color: rgba(241, 245, 249, 0.85);
        line-height: 1.4;
    }

    .task-modal-close-batch {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .task-modal-close-batch:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
    }

    /* KPI Summary Strip */
    .batch-pool-kpi-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 24px;
    }

    .batch-kpi-pill {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .batch-kpi-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
    }

    .batch-kpi-pill-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .batch-kpi-pill-icon.icon-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .batch-kpi-pill-icon.icon-emerald { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .batch-kpi-pill-icon.icon-amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .batch-kpi-pill-icon.icon-violet { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }

    .batch-kpi-pill-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .batch-kpi-pill-num {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }

    .batch-kpi-pill-lbl {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Modal Filter Bar Search */
    .modal-filter-search-wrap {
        position: relative;
        flex: 1;
        min-width: 220px;
        max-width: 360px;
    }

    .modal-filter-search-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 13px;
        pointer-events: none;
    }

    .modal-filter-search-wrap .modal-search-input {
        width: 100%;
        padding: 9px 12px 9px 36px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        outline: none;
        background: #ffffff;
        color: #0f172a;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .modal-filter-search-wrap .modal-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* Table Layout & Proportions */
    .task-table-fund-utilization-batch {
        table-layout: auto !important;
        min-width: 1140px;
        border-collapse: collapse;
        font-size: 12px;
    }

    .task-table-fund-utilization-batch thead th {
        background: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 12px 14px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .task-table-fund-utilization-batch tbody td {
        padding: 13px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    .task-table-fund-utilization-batch tbody tr {
        transition: background-color 0.15s ease;
    }

    .task-table-fund-utilization-batch tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Column Widths & Minimums */
    .task-table-fund-utilization-batch th:nth-child(1),
    .task-table-fund-utilization-batch td:nth-child(1) { width: 15%; min-width: 160px; }
    .task-table-fund-utilization-batch th:nth-child(2),
    .task-table-fund-utilization-batch td:nth-child(2) { width: 9%; min-width: 95px; }
    .task-table-fund-utilization-batch th:nth-child(3),
    .task-table-fund-utilization-batch td:nth-child(3) { width: 11%; min-width: 110px; }
    .task-table-fund-utilization-batch th:nth-child(4),
    .task-table-fund-utilization-batch td:nth-child(4) { width: 22%; min-width: 200px; }
    .task-table-fund-utilization-batch th:nth-child(5),
    .task-table-fund-utilization-batch td:nth-child(5) { width: 15%; min-width: 150px; }
    .task-table-fund-utilization-batch th:nth-child(6),
    .task-table-fund-utilization-batch td:nth-child(6) { width: 11%; min-width: 115px; }
    .task-table-fund-utilization-batch th:nth-child(7),
    .task-table-fund-utilization-batch td:nth-child(7) { width: 9%; min-width: 95px; }
    .task-table-fund-utilization-batch th:nth-child(8),
    .task-table-fund-utilization-batch td:nth-child(8) { width: 12%; min-width: 150px; text-align: center; }

    /* Badge Components */
    .batch-code-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        font-weight: 700;
        padding: 5px 9px;
        border-radius: 7px;
        letter-spacing: 0.03em;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .batch-code-pill i {
        color: #0284c7;
        font-size: 12px;
    }

    /* Quarter Tag */
    .batch-quarter-tag {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 11px;
        line-height: 1.15;
        border-width: 1px;
        border-style: solid;
        min-width: 62px;
    }

    .batch-quarter-tag .q-label {
        letter-spacing: 0.05em;
    }

    .batch-quarter-tag .q-year {
        font-size: 9px;
        font-weight: 700;
        opacity: 0.85;
    }

    .batch-quarter-tag.q1 { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
    .batch-quarter-tag.q2 { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .batch-quarter-tag.q3 { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    .batch-quarter-tag.q4 { background: #faf5ff; border-color: #e9d5ff; color: #7e22ce; }

    /* Scope Chip */
    .batch-scope-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 11px;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 9999px;
        white-space: nowrap;
    }

    .batch-scope-chip i {
        color: #10b981;
        font-size: 11px;
    }

    .batch-scope-chip strong {
        color: #0f172a;
        font-weight: 800;
    }

    /* Summary cell */
    .batch-doc-summary {
        max-width: 260px;
    }

    .batch-summary-text {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .batch-returned-remarks {
        font-size: 12px;
        font-style: italic;
        color: #b91c1c;
        font-weight: 600;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Uploader Wrap */
    .batch-uploader-wrap {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .batch-uploader-avatar {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0284c7;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bae6fd;
        flex-shrink: 0;
    }

    .batch-uploader-avatar.avatar-returned {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }

    .batch-uploader-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .batch-uploader-name {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 130px;
    }

    .batch-uploader-sub {
        font-size: 10px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
    }

    .batch-uploader-sub i {
        font-size: 9px;
        color: #94a3b8;
    }

    /* Date Cell */
    .batch-date-cell {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .batch-date-day {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
    }

    .batch-date-time {
        font-size: 10px;
        color: #64748b;
        white-space: nowrap;
    }

    /* Status Pill */
    .batch-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .status-indicator-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .status-pending .status-indicator-dot {
        background: #f59e0b;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
    }

    .status-approved {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .status-approved .status-indicator-dot {
        background: #10b981;
    }

    .status-returned {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .status-returned .status-indicator-dot {
        background: #ef4444;
    }

    /* Action Buttons */
    .batch-action-validate-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        color: #ffffff !important;
        font-size: 11px;
        font-weight: 700;
        padding: 8px 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .batch-action-validate-btn:hover {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
        box-shadow: 0 4px 12px rgba(29, 78, 216, 0.35);
        transform: translateY(-1px);
    }

    .batch-action-validate-btn:active {
        transform: translateY(0);
    }

    .batch-action-view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #ffffff;
        color: #dc2626 !important;
        border: 1px solid #f87171;
        font-size: 11px;
        font-weight: 700;
        padding: 7px 12px;
        border-radius: 8px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }

    .batch-action-view-btn:hover {
        background: #fee2e2;
        border-color: #ef4444;
        color: #b91c1c !important;
    }

    @media (max-width: 900px) {
        .batch-pool-kpi-bar {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 520px) {
        .batch-pool-kpi-bar {
            grid-template-columns: 1fr;
        }
    }

    .task-table-fund-utilization .task-uploader-cell {
        min-width: 0;
        gap: 4px;
    }

    .task-table-fund-utilization .task-message-cell {
        max-width: none;
    }

    .task-table-fund-utilization .task-action-btn {
        padding: 6px 7px;
        gap: 4px;
        font-size: 10px;
        line-height: 1.2;
        justify-content: center;
        text-align: center;
    }

    .proj-meta-cell {
        font-size: 11px;
        color: #64748b;
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .task-message-cell {
        color: #334155;
        line-height: 1.4;
        max-width: 350px;
    }

    .task-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #0b1f52 0%, #1d4ed8 100%);
        color: #fff;
        padding: 8px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 4px 6px -1px rgba(29, 78, 216, 0.2);
    }

    .task-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px -1px rgba(29, 78, 216, 0.35);
        color: #fff;
    }

    .task-action-btn.btn-danger {
        background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
    }

    .task-action-btn.btn-danger:hover {
        box-shadow: 0 6px 12px -1px rgba(220, 38, 38, 0.35);
    }

    .empty-state {
        padding: 60px 40px;
        text-align: center;
        color: #64748b;
        background: #fff;
    }

    .empty-icon {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .empty-title {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .empty-desc {
        font-size: 13px;
        max-width: 360px;
        margin: 0 auto;
        line-height: 1.6;
    }
</style>
@endsection

@section('content')
@php
    $user = Auth::user();
    $approvalLevel = $approvalLevel ?? ($user ? $user->approvalLevel() : null);
    $hasValidatorRole = $approvalLevel !== null;

    // Module icon mapper based on label/key
    $moduleIcons = [
        'fund-utilization' => 'fas fa-coins',
        'fund-utilization-batch' => 'fas fa-layer-group',
        'pre-implementation' => 'fas fa-file-signature',
        'road-maintenance-status' => 'fas fa-road',
        'rbis-annual-certification' => 'fas fa-award',
        'local-project-monitoring-committee' => 'fas fa-users-cog',
        'lgsf-pcr' => 'fas fa-file-invoice',
        'sglgif-pcr' => 'fas fa-file-invoice-dollar',
        'annual-maintenance-work-program' => 'fas fa-calendar-alt',
        'pd-no-pbbm-2025-1572-1573' => 'fas fa-file-invoice',
        'monitoring-evaluation-lfp' => 'fas fa-file-lines',
        'monitoring-evaluation-rlip-lime' => 'fas fa-chart-line',
        'swa-annex-f' => 'fas fa-file-invoice',
    ];
@endphp

<div class="task-mgmt-page">
    <div class="task-mgmt-shell">
        <!-- Hero Header -->
        <section class="task-mgmt-hero">
            <div class="task-mgmt-hero-content">
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px;">
                    <div class="task-mgmt-eyebrow">
                        <i class="fas fa-list-check"></i>
                        Workflow Tasks
                    </div>
                    @if(isset($userLevelLabel) && $userLevelLabel !== '')
                        <div class="task-mgmt-eyebrow" style="background: rgba(255, 255, 255, 0.22); color: #fff;">
                            <i class="fas fa-user-shield"></i>
                            Level: {{ $userLevelLabel }}
                        </div>
                    @endif
                    @if(isset($userJurisdictionLabel) && $userJurisdictionLabel !== '')
                        <div class="task-mgmt-eyebrow" style="background: rgba(56, 189, 248, 0.25); color: #f0f9ff; border: 1px solid rgba(125, 211, 252, 0.3);">
                            <i class="fas fa-location-dot"></i>
                            {{ $userJurisdictionLabel }}
                        </div>
                    @endif
                </div>
                <h1 class="task-mgmt-title">Task Management Hub</h1>
                <p class="task-mgmt-description">
                    Track accomplishments, review pending uploads, and action documents awaiting validation or returned for revision. Click a program card to open its task list.
                </p>
            </div>
        </section>

        <!-- Pool Section 1: APPROVALS POOL (Only visible to validators based on approval level office / level) -->
        @if($hasValidatorRole)
            <div class="task-pool-section">
                <h2 class="task-pool-section-title">
                    <i class="fas fa-inbox"></i>
                    @if($approvalLevel === 'regional')
                        <span>Centralized Regional Approvals Pool — {{ $user->region ?: 'CAR' }}</span>
                    @elseif($approvalLevel === 'provincial')
                        <span>Provincial Approvals Pool — {{ $user->province ?: 'Provincial' }}</span>
                    @elseif($approvalLevel === 'cmlgoo')
                        <span>C/MLGOO Approvals Pool — {{ $user->office ?: 'Municipal' }}@if($user->province), {{ $user->province }}@endif</span>
                    @endif
                </h2>
                
                @if($pendingByModule->isNotEmpty())
                    <div class="menu-cards-grid">
                        @foreach($pendingByModule as $moduleLabel => $tasks)
                            @php
                                $firstTask = $tasks->first();
                                $moduleKey = $firstTask['module_key'] ?? 'default';
                                $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
                                $slug = 'modal-pending-' . Str::slug($moduleLabel);
                            @endphp
                            <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                                <div class="menu-card-top">
                                    <div class="menu-card-icon-wrapper primary">
                                        <i class="{{ $iconClass }}"></i>
                                    </div>
                                    <span class="menu-card-badge danger">{{ $tasks->count() }}</span>
                                </div>
                                <div>
                                    <h3 class="menu-card-title">{{ $moduleLabel }}</h3>
                                    <div class="menu-card-footer primary">
                                        Open Pool <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                        <div class="empty-icon"><i class="fas fa-circle-check" style="color: #10b981;"></i></div>
                        <div class="empty-title">All Caught Up!</div>
                        <p class="empty-desc">
                            @if($approvalLevel === 'cmlgoo')
                                There are no document submissions awaiting C/MLGOO validation for {{ $user->office ?: 'your assigned municipality' }}.
                            @elseif($approvalLevel === 'provincial')
                                There are no document submissions awaiting provincial validation for {{ $user->province ?: 'your province' }}.
                            @elseif($approvalLevel === 'regional')
                                There are no document submissions awaiting regional validation in the regional pool.
                            @else
                                There are no document submissions waiting for your approval in this pool.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Pool Section 3: UNASSIGNED TICKETS POOL (Visible to validators/agents) -->
        @if(($user->isSuperAdmin() || $user->isRegionalUser() || $user->isProvincialUser()))
            <div class="task-pool-section" style="margin-top: 14px;">
                <h2 class="task-pool-section-title">
                    <i class="fas fa-ticket"></i>
                    @if($user->isRegionalUser())
                        <span>Unassigned Tickets Pool — Regional (CAR)</span>
                    @elseif($user->isProvincialUser())
                        <span>Unassigned Tickets Pool — {{ $user->province ?: 'Provincial' }}</span>
                    @else
                        <span>Unassigned Tickets Pool</span>
                    @endif
                </h2>
                
                @if($ticketsByCategory->isNotEmpty())
                    <div class="menu-cards-grid">
                        @foreach($ticketsByCategory as $categoryName => $categoryTickets)
                            @php
                                $slug = 'modal-ticket-' . Str::slug($categoryName);
                            @endphp
                            <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                                <div class="menu-card-top">
                                    <div class="menu-card-icon-wrapper info" style="background: #e0f2fe; color: #0284c7;">
                                        <i class="fas fa-ticket"></i>
                                    </div>
                                    <span class="menu-card-badge info" style="background: #0284c7; color: #fff;">{{ $categoryTickets->count() }}</span>
                                </div>
                                <div>
                                    <h3 class="menu-card-title">{{ $categoryName }}</h3>
                                    <div class="menu-card-footer info" style="color: #0284c7;">
                                        Open Pool <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                        <div class="empty-icon"><i class="fas fa-circle-check" style="color: #10b981;"></i></div>
                        <div class="empty-title">All Caught Up!</div>
                        <p class="empty-desc">
                            @if($user->isProvincialUser())
                                There are no unassigned tickets waiting in the {{ $user->province }} queue.
                            @elseif($user->isRegionalUser())
                                There are no unassigned tickets waiting in the regional queue.
                            @else
                                There are no unassigned tickets waiting in your queue.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Pool Section 2: RETURNED DOCUMENTS (Visible only to users based on their office / level) -->
        @if(!$user->isRegionalUser() && !$user->isSuperAdmin())
            <div class="task-pool-section" style="margin-top: 14px;">
                <h2 class="task-pool-section-title">
                    <i class="fas fa-rotate-left"></i>
                    @if($user->isLguUser())
                        <span>Returned Documents Awaiting Action — {{ $user->office ?: 'My LGU' }}</span>
                    @elseif($user->isMlgooUser())
                        <span>Returned Documents Awaiting Action — {{ $user->office ?: 'My Jurisdiction' }}</span>
                    @elseif($user->isProvincialUser())
                        <span>Returned Documents Awaiting Action — {{ $user->province ?: 'Provincial Jurisdiction' }}</span>
                    @endif
                </h2>
                
                @if($returnedByModule->isNotEmpty())
                    <div class="menu-cards-grid">
                        @foreach($returnedByModule as $moduleLabel => $tasks)
                            @php
                                $firstTask = $tasks->first();
                                $moduleKey = $firstTask['module_key'] ?? 'default';
                                $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
                                $slug = 'modal-returned-' . Str::slug($moduleLabel);
                            @endphp
                            <div class="menu-card" onclick="openTaskModal('{{ $slug }}')">
                                <div class="menu-card-top">
                                    <div class="menu-card-icon-wrapper danger">
                                        <i class="{{ $iconClass }}"></i>
                                    </div>
                                    <span class="menu-card-badge danger">{{ $tasks->count() }}</span>
                                </div>
                                <div>
                                    <h3 class="menu-card-title">{{ $moduleLabel }}</h3>
                                    <div class="menu-card-footer danger">
                                        Act & Resubmit <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                        <div class="empty-icon"><i class="fas fa-thumbs-up" style="color: #3b82f6;"></i></div>
                        <div class="empty-title">No Returned Documents</div>
                        <p class="empty-desc">
                            @if($user->isLguUser() || $user->isMlgooUser())
                                Great job! There are no returned documents requiring revision for {{ $user->office ?: 'your assigned LGU' }}.
                            @elseif($user->isProvincialUser())
                                Great job! There are no returned documents requiring revision in {{ $user->province ?: 'your province' }}.
                            @else
                                Great job! You have no document submissions that are returned or pending revisions.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>

<!-- MODAL DIALOGS CONTAINER -->
<!-- A. Approvals Modals -->
@if($hasValidatorRole && $pendingByModule->isNotEmpty())
    @foreach($pendingByModule as $moduleLabel => $tasks)
        @php
            $firstTask = $tasks->first();
            $moduleKey = $firstTask['module_key'] ?? 'default';
            $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
            $slug = 'modal-pending-' . Str::slug($moduleLabel);
            $provinces = $tasks->pluck('province')->filter()->unique()->sort();
            $cityMunicipalities = $tasks->pluck('city_municipality')->filter()->unique()->sort();
            $periods = $tasks->pluck('period')->filter()->unique()->sort();
            $years = $tasks->pluck('year')->filter()->unique()->sortDesc();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal{{ $moduleKey === 'fund-utilization-batch' ? ' task-modal-batch-wide' : '' }}">
                <!-- Modal Header -->
                @if($moduleKey === 'fund-utilization-batch')
                    <div class="task-modal-header task-modal-header-batch">
                        <div class="batch-header-main">
                            <div class="batch-header-icon-box">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="batch-header-titles">
                                <div class="batch-header-pill-group">
                                    <h3 class="batch-modal-title">
                                        Fund Utilization Report &bull; Batch Upload
                                    </h3>
                                    <span class="batch-scope-badge-header">
                                        <i class="fas fa-shield-halved"></i>
                                        @if($approvalLevel === 'regional')
                                            Regional Approvals Pool (CAR)
                                        @elseif($approvalLevel === 'provincial')
                                            Provincial Approvals Pool ({{ $user->province ?? 'CAR' }})
                                        @elseif($approvalLevel === 'cmlgoo')
                                            C/MLGOO Approvals Pool ({{ $user->office ?? 'LGU' }})
                                        @else
                                            Approvals Pool
                                        @endif
                                    </span>
                                </div>
                                <p class="batch-modal-subtitle">
                                    Executive validation and decision queue for quarterly multi-project FUR submissions across Cordillera Administrative Region.
                                </p>
                            </div>
                        </div>
                        <button type="button" class="task-modal-close task-modal-close-batch" onclick="closeTaskModal('{{ $slug }}')" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- 4-Card KPI Summary Strip -->
                    @php
                        $totalBatches = $tasks->count();
                        $totalProjects = $tasks->sum('project_count');
                        $pendingBatches = $tasks->filter(function($t) {
                            $st = strtolower($t['batch_status'] ?? $t['status'] ?? '');
                            return !str_contains($st, 'returned') && !str_contains($st, 'approved');
                        })->count();
                        $provCount = $provinces->count();
                    @endphp
                    <div class="batch-pool-kpi-bar">
                        <div class="batch-kpi-pill">
                            <div class="batch-kpi-pill-icon icon-blue"><i class="fas fa-layer-group"></i></div>
                            <div class="batch-kpi-pill-text">
                                <span class="batch-kpi-pill-num">{{ number_format($totalBatches) }}</span>
                                <span class="batch-kpi-pill-lbl">Batch Submissions</span>
                            </div>
                        </div>
                        <div class="batch-kpi-pill">
                            <div class="batch-kpi-pill-icon icon-emerald"><i class="fas fa-cubes"></i></div>
                            <div class="batch-kpi-pill-text">
                                <span class="batch-kpi-pill-num">{{ number_format($totalProjects) }}</span>
                                <span class="batch-kpi-pill-lbl">Projects Covered</span>
                            </div>
                        </div>
                        <div class="batch-kpi-pill">
                            <div class="batch-kpi-pill-icon icon-amber"><i class="fas fa-hourglass-half"></i></div>
                            <div class="batch-kpi-pill-text">
                                <span class="batch-kpi-pill-num">{{ number_format($pendingBatches) }}</span>
                                <span class="batch-kpi-pill-lbl">Awaiting Validation</span>
                            </div>
                        </div>
                        <div class="batch-kpi-pill">
                            <div class="batch-kpi-pill-icon icon-violet"><i class="fas fa-map-location-dot"></i></div>
                            <div class="batch-kpi-pill-text">
                                <span class="batch-kpi-pill-num">{{ number_format($provCount) }}</span>
                                <span class="batch-kpi-pill-lbl">Active Provinces</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="task-modal-header">
                        <h3 class="task-modal-title">
                            <i class="{{ $iconClass }}"></i>
                            @if($approvalLevel === 'cmlgoo')
                                <span>{{ $moduleLabel }} - C/MLGOO Approvals Pool ({{ $user->office }})</span>
                            @elseif($approvalLevel === 'provincial')
                                <span>{{ $moduleLabel }} - Provincial Approvals Pool ({{ $user->province }})</span>
                            @elseif($approvalLevel === 'regional')
                                <span>{{ $moduleLabel }} - Regional Approvals Pool (CAR)</span>
                            @else
                                <span>{{ $moduleLabel }} - Approvals Pool</span>
                            @endif
                        </h3>
                        <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar{{ $moduleKey === 'fund-utilization-batch' ? ' filter-bar-batch' : '' }}">
                    <div class="modal-filter-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" class="modal-search-input" placeholder="{{ $moduleKey === 'fund-utilization-batch' ? 'Search batch code, submitter, province...' : 'Search task details...' }}" oninput="filterModalRows('{{ $slug }}')">
                    </div>
                    @if($provinces->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="province" aria-label="Filter by province" onchange="filterModalRows('{{ $slug }}')">
                                <option value="">All Provinces</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($moduleKey === 'fund-utilization' && $cityMunicipalities->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-city" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="cityMunicipality" aria-label="Filter by city or municipality" onchange="filterModalRows('{{ $slug }}')">
                                <option value="">All Cities/Municipalities</option>
                                @foreach($cityMunicipalities as $cityMunicipality)
                                    <option value="{{ $cityMunicipality }}">{{ $cityMunicipality }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($periods->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-calendar-days" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="period" aria-label="Filter by period" onchange="filterModalRows('{{ $slug }}')">
                                <option value="">All Periods</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period }}">{{ $period }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($moduleKey !== 'fund-utilization' && $years->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="year" aria-label="Filter by year" onchange="filterModalRows('{{ $slug }}')">
                                <option value="">All Years</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="modal-filter-actions">
                        <button type="button" class="modal-filter-btn apply" onclick="filterModalRows('{{ $slug }}')">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="modal-filter-btn reset" onclick="resetModalFilters('{{ $slug }}')">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table{{ $moduleKey === 'fund-utilization' ? ' task-table-fund-utilization' : ($moduleKey === 'fund-utilization-batch' ? ' task-table-fund-utilization-batch' : '') }}">
                            <thead>
                                <tr>
                                    @if($moduleKey === 'fund-utilization-batch')
                                        <th>Batch Approval Code</th>
                                        <th>Quarter</th>
                                        <th>Target Scope</th>
                                        <th>Documents Summary</th>
                                        <th>Submitted By</th>
                                        <th>Date Received</th>
                                        <th>Status</th>
                                        <th style="text-align: center;">Action</th>
                                    @else
                                        @if($moduleKey === 'fund-utilization')
                                            <th>Project Code</th>
                                            <th>Project Title</th>
                                            <th>Province</th>
                                            <th>City/Municipality</th>
                                        @elseif($moduleKey === 'pre-implementation')
                                            <th>Project Code</th>
                                            <th>Province</th>
                                            <th>City/Municipality</th>
                                        @elseif($moduleKey === 'monitoring-evaluation-lfp')
                                            <th>Province</th>
                                        @elseif($moduleKey === 'local-project-monitoring-committee')
                                            <th>Province</th>
                                            <th>City/Municipality</th>
                                        @else
                                            <th>Office</th>
                                        @endif
                                        <th>Period</th>
                                        <th>Task Details</th>
                                        <th>Submitted By</th>
                                        <th>Date Received</th>
                                        <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                    <tr data-province="{{ $task['province'] ?? '' }}" data-city-municipality="{{ $task['city_municipality'] ?? '' }}" data-period="{{ $task['period'] ?? $task['quarter'] ?? '' }}" data-year="{{ $task['year'] ?? '' }}">
                                        @if($moduleKey === 'fund-utilization-batch')
                                            <td>
                                                <div class="batch-code-pill" title="Batch Approval Code: {{ $task['batch_code'] ?? $task['project_code'] }}">
                                                    <i class="fas fa-barcode"></i>
                                                    <span>{{ $task['batch_code'] ?? $task['project_code'] }}</span>
                                                </div>
                                            </td>
                                            @php
                                                $qRaw = strtolower($task['quarter'] ?? $task['period'] ?? 'q1');
                                                $qClass = str_contains($qRaw, '1') ? 'q1' : (str_contains($qRaw, '2') ? 'q2' : (str_contains($qRaw, '3') ? 'q3' : 'q4'));
                                            @endphp
                                            <td>
                                                <div class="batch-quarter-tag {{ $qClass }}">
                                                    <span class="q-label">{{ strtoupper($task['quarter'] ?? 'N/A') }}</span>
                                                    @if(!empty($task['year']))
                                                        <span class="q-year">{{ $task['year'] }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="batch-scope-chip" title="{{ $task['project_count'] ?? 0 }} projects covered in this submission">
                                                    <i class="fas fa-cubes"></i>
                                                    <strong>{{ $task['project_count'] ?? 0 }}</strong>
                                                    <span class="chip-label">Projects</span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="batch-doc-summary" title="{{ $task['message'] }}">
                                                    <div class="batch-summary-text">
                                                        <i class="fas fa-file-shield text-blue-500 mr-1"></i>
                                                        {{ $task['message'] }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $uploaderName = $task['sender_name'] ?: 'Unknown Submitter';
                                                    $initials = collect(explode(' ', $uploaderName))->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                                @endphp
                                                <div class="batch-uploader-wrap">
                                                    <div class="batch-uploader-avatar" title="{{ $uploaderName }}">
                                                        {{ $initials ?: 'U' }}
                                                    </div>
                                                    <div class="batch-uploader-info">
                                                        <span class="batch-uploader-name" title="{{ $uploaderName }}">{{ $uploaderName }}</span>
                                                        @if(!empty($task['uploader_province']) || !empty($task['province']))
                                                            <span class="batch-uploader-sub">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                                {{ $task['uploader_province'] ?? $task['province'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $dt = !empty($task['created_at']) ? \Illuminate\Support\Carbon::parse($task['created_at']) : null;
                                                @endphp
                                                @if($dt)
                                                    <div class="batch-date-cell">
                                                        <span class="batch-date-day">{{ $dt->format('M d, Y') }}</span>
                                                        <span class="batch-date-time">{{ $dt->format('h:i A') }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $st = $task['batch_status'] ?? $task['status'] ?? 'Pending';
                                                    $stLower = strtolower($st);
                                                    $isApproved = str_contains($stLower, 'approved');
                                                    $isReturned = str_contains($stLower, 'returned');
                                                    $statusKind = $isApproved ? 'approved' : ($isReturned ? 'returned' : 'pending');
                                                    $statusDisplay = ucwords(str_replace('_', ' ', $st));
                                                @endphp
                                                <span class="batch-status-pill status-{{ $statusKind }}">
                                                    <span class="status-indicator-dot"></span>
                                                    <span>{{ $statusDisplay }}</span>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="batch-action-validate-btn" onclick="openBatchApprovalModal('{{ $task['batch_code'] ?? $task['project_code'] }}')" title="Open review & validation drawer">
                                                    <i class="fas fa-clipboard-check"></i>
                                                    <span>Review &amp; Validate</span>
                                                </button>
                                            </td>
                                        @elseif($moduleKey === 'fund-utilization')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_code'] ?? '' }}">{{ $task['project_code'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_title'] ?? '' }}">{{ $task['project_title'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @elseif($moduleKey === 'pre-implementation')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_code'] ?? '' }}">{{ $task['project_code'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @elseif($moduleKey === 'monitoring-evaluation-lfp')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                        @elseif($moduleKey === 'local-project-monitoring-committee')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @else
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                                @if(!empty($task['province']))
                                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><i class="fas fa-map-marker-alt"></i> {{ $task['province'] }}</div>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <strong>{{ strtoupper($task['quarter'] ?? 'N/A') }}</strong>
                                        </td>
                                        <td>
                                            <div class="task-message-cell">
                                                <div style="font-weight: 700; color: #1e293b;">{{ $task['task_title'] ?? ($task['document_label'] ?? $task['message']) }}</div>
                                                @if(isset($task['task_title']))
                                                    <div style="margin-top: 4px; color: #64748b;">{{ $task['message'] }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $uploaderName = $task['sender_name'] ?: 'Unknown uploader';
                                            @endphp
                                            <div class="task-uploader-cell">
                                                <span>
                                                    <span class="task-uploader-name">{{ $uploaderName }}</span>
                                                    @if(!empty($task['uploader_province']))
                                                        <span class="task-uploader-province"><i class="fas fa-map-marker-alt"></i> {{ $task['uploader_province'] }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ !empty($task['created_at']) ? \Illuminate\Support\Carbon::parse($task['created_at'])->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            <a href="{{ $task['url'] }}" class="task-action-btn" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation(); window.open(this.href, '_blank', 'noopener,noreferrer'); return false;">
                                                <i class="fas fa-clipboard-check"></i>
                                                Review & Validate
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- B. Returned Modals -->
@if($returnedByModule->isNotEmpty())
    @foreach($returnedByModule as $moduleLabel => $tasks)
        @php
            $firstTask = $tasks->first();
            $moduleKey = $firstTask['module_key'] ?? 'default';
            $iconClass = $moduleIcons[$moduleKey] ?? 'fas fa-folder';
            $slug = 'modal-returned-' . Str::slug($moduleLabel);
            $provinces = $tasks->pluck('province')->filter()->unique()->sort();
            $cityMunicipalities = $tasks->pluck('city_municipality')->filter()->unique()->sort();
            $periods = $tasks->pluck('period')->filter()->unique()->sort();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal{{ $moduleKey === 'fund-utilization-batch' ? ' task-modal-batch-wide' : '' }}">
                <!-- Modal Header -->
                @if($moduleKey === 'fund-utilization-batch')
                    <div class="task-modal-header" style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 50%, #b91c1c 100%); color: #fff; padding: 20px 28px; border-bottom: 1px solid rgba(255,255,255,0.12);">
                        <div class="batch-header-main">
                            <div class="batch-header-icon-box" style="background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.3);">
                                <i class="fas fa-rotate-left"></i>
                            </div>
                            <div class="batch-header-titles">
                                <div class="batch-header-pill-group">
                                    <h3 class="batch-modal-title">Fund Utilization Report &bull; Batch Upload</h3>
                                    <span class="batch-scope-badge-header" style="background: rgba(254, 202, 202, 0.25); border-color: rgba(252, 165, 165, 0.4); color: #fee2e2;">
                                        Returned Submissions
                                    </span>
                                </div>
                                <p class="batch-modal-subtitle">Submissions returned for revision with feedback from Regional / Provincial Validators.</p>
                            </div>
                        </div>
                        <button type="button" class="task-modal-close task-modal-close-batch" onclick="closeTaskModal('{{ $slug }}')" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @else
                    <div class="task-modal-header">
                        <h3 class="task-modal-title">
                            <i class="{{ $iconClass }}" style="color: #dc2626;"></i>
                            <span>{{ $moduleLabel }} - Returned Documents</span>
                        </h3>
                        <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar">
                    <div class="filter-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" class="modal-search-input" placeholder="Search project or remarks..." oninput="filterModalRows('{{ $slug }}')">
                    </div>
                    @if($provinces->isNotEmpty())
                        <select class="modal-select-filter" data-filter="province" onchange="filterModalRows('{{ $slug }}')">
                            <option value="">All Provinces</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov }}">{{ $prov }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if($moduleKey === 'fund-utilization' && $cityMunicipalities->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-city" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="cityMunicipality" aria-label="Filter by city or municipality">
                                <option value="">All Cities/Municipalities</option>
                                @foreach($cityMunicipalities as $cityMunicipality)
                                    <option value="{{ $cityMunicipality }}">{{ $cityMunicipality }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($moduleKey === 'fund-utilization' && $periods->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-calendar-days" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="period" aria-label="Filter by period">
                                <option value="">All Periods</option>
                                @foreach($periods as $period)
                                    <option value="{{ $period }}">{{ $period }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table{{ $moduleKey === 'fund-utilization' ? ' task-table-fund-utilization' : ($moduleKey === 'fund-utilization-batch' ? ' task-table-fund-utilization-batch' : '') }}">
                            <thead>
                                <tr>
                                    @if($moduleKey === 'fund-utilization-batch')
                                        <th>Batch Approval Code</th>
                                        <th>Quarter</th>
                                        <th>Target Scope</th>
                                        <th>Instructions / Remarks</th>
                                        <th>Returned By</th>
                                        <th>Date Returned</th>
                                        <th>Status</th>
                                        <th style="text-align: center;">Action</th>
                                    @else
                                        @if($moduleKey === 'fund-utilization')
                                            <th>Project Code</th>
                                            <th>Project Title</th>
                                            <th>Province</th>
                                            <th>City/Municipality</th>
                                        @else
                                            <th>Project / Location</th>
                                        @endif
                                        <th>Period</th>
                                        <th>Instructions / Remarks</th>
                                        <th>Returned By</th>
                                        <th>Date Returned</th>
                                        <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tasks as $task)
                                    <tr data-province="{{ $task['province'] ?? '' }}" data-city-municipality="{{ $task['city_municipality'] ?? '' }}" data-period="{{ $task['period'] ?? $task['quarter'] ?? '' }}">
                                        @if($moduleKey === 'fund-utilization-batch')
                                            <td>
                                                <div class="batch-code-pill" title="Batch Approval Code: {{ $task['batch_code'] ?? $task['project_code'] }}">
                                                    <i class="fas fa-barcode"></i>
                                                    <span>{{ $task['batch_code'] ?? $task['project_code'] }}</span>
                                                </div>
                                            </td>
                                            @php
                                                $qRaw = strtolower($task['quarter'] ?? $task['period'] ?? 'q1');
                                                $qClass = str_contains($qRaw, '1') ? 'q1' : (str_contains($qRaw, '2') ? 'q2' : (str_contains($qRaw, '3') ? 'q3' : 'q4'));
                                            @endphp
                                            <td>
                                                <div class="batch-quarter-tag {{ $qClass }}">
                                                    <span class="q-label">{{ strtoupper($task['quarter'] ?? 'N/A') }}</span>
                                                    @if(!empty($task['year']))
                                                        <span class="q-year">{{ $task['year'] }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="batch-scope-chip" title="{{ $task['project_count'] ?? 0 }} projects covered">
                                                    <i class="fas fa-cubes"></i>
                                                    <strong>{{ $task['project_count'] ?? 0 }}</strong>
                                                    <span class="chip-label">Projects</span>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="batch-returned-remarks" title="{{ $task['approval_remarks'] ?? $task['message'] }}">
                                                    <i class="fas fa-comment-dots text-rose-500 mr-1"></i>
                                                    <span>{{ $task['approval_remarks'] ?? $task['message'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $returnerName = $task['returned_by_name'] ?? $task['sender_name'] ?? 'Validator';
                                                    $rInitials = collect(explode(' ', $returnerName))->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                                @endphp
                                                <div class="batch-uploader-wrap">
                                                    <div class="batch-uploader-avatar avatar-returned" title="{{ $returnerName }}">
                                                        {{ $rInitials ?: 'V' }}
                                                    </div>
                                                    <div class="batch-uploader-info">
                                                        <span class="batch-uploader-name" title="{{ $returnerName }}">{{ $returnerName }}</span>
                                                        @if(!empty($task['returned_by_role']))
                                                            <span class="batch-uploader-sub">{{ $task['returned_by_role'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $returnedDate = $task['returned_at'] ?? $task['created_at'] ?? null;
                                                    $dt = !empty($returnedDate) ? \Illuminate\Support\Carbon::parse($returnedDate) : null;
                                                @endphp
                                                @if($dt)
                                                    <div class="batch-date-cell">
                                                        <span class="batch-date-day">{{ $dt->format('M d, Y') }}</span>
                                                        <span class="batch-date-time">{{ $dt->format('h:i A') }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-slate-400">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="batch-status-pill status-returned">
                                                    <span class="status-indicator-dot"></span>
                                                    <span>Returned</span>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="batch-action-view-btn" onclick="openBatchApprovalModal('{{ $task['batch_code'] ?? $task['project_code'] }}')" title="View batch submission details">
                                                    <i class="fas fa-eye"></i>
                                                    <span>View Details</span>
                                                </button>
                                            </td>
                                        @elseif($moduleKey === 'fund-utilization')
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_code'] ?? '' }}">{{ $task['project_code'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_title'] ?? '' }}">{{ $task['project_title'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['province'] ?? '' }}">{{ $task['province'] ?: 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['city_municipality'] ?? '' }}">{{ $task['city_municipality'] ?: 'N/A' }}</div>
                                            </td>
                                        @else
                                            <td>
                                                <div class="proj-title-cell" title="{{ $task['project_code'] }}">{{ $task['project_code'] }}</div>
                                                <div class="proj-meta-cell">
                                                    @if(!empty($task['province']))
                                                        <span><i class="fas fa-map-marker-alt"></i> {{ $task['province'] }}</span>
                                                    @endif
                                                    @if(!empty($task['city_municipality']))
                                                        <span>&bull;</span>
                                                        <span>{{ $task['city_municipality'] }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                        <td>
                                            <strong>{{ strtoupper($task['quarter'] ?? 'N/A') }}</strong>
                                        </td>
                                        <td>
                                            <div class="task-message-cell" style="font-style: italic;">{{ $task['message'] }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">{{ $task['returned_by_name'] ?? $task['sender_name'] ?? 'Validator' }}</div>
                                            @if(!empty($task['returned_by_role']))
                                                <div style="font-size: 0.75rem; color: #64748b;">{{ $task['returned_by_role'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $returnedDate = $task['returned_at'] ?? $task['created_at'] ?? null;
                                            @endphp
                                            {{ !empty($returnedDate) ? \Illuminate\Support\Carbon::parse($returnedDate)->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td>
                                            <a href="{{ url($task['url'] ?? ('/notifications/' . $task['id'] . '/read')) }}" class="task-action-btn btn-danger" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation(); window.open(this.href, '_blank', 'noopener,noreferrer'); return false;">
                                                <i class="fas fa-edit"></i>
                                                Act & Resubmit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- C. Ticket Modals -->
@if(($user->isSuperAdmin() || $user->isRegionalUser() || $user->isProvincialUser()) && $ticketsByCategory->isNotEmpty())
    @foreach($ticketsByCategory as $categoryName => $categoryTickets)
        @php
            $slug = 'modal-ticket-' . Str::slug($categoryName);
            $priorities = $categoryTickets->pluck('priority')->filter()->unique()->sort();
            $provinces = $categoryTickets->pluck('province_scope')->filter()->unique()->sort();
        @endphp
        <div id="{{ $slug }}" class="task-modal-backdrop" onclick="closeTaskModalOnBackdrop(event, '{{ $slug }}')">
            <div class="task-modal" style="max-width: 900px; width: 90%;">
                <!-- Modal Header -->
                <div class="task-modal-header">
                    <h3 class="task-modal-title">
                        <i class="fas fa-ticket"></i>
                        <span>{{ $categoryName }} - Ticket Pool</span>
                    </h3>
                    <button class="task-modal-close" onclick="closeTaskModal('{{ $slug }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Bar inside Modal -->
                <div class="task-modal-filter-bar">
                    @if($provinces->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="province" aria-label="Filter by province">
                                <option value="">All Provinces</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($priorities->isNotEmpty())
                        <div class="modal-filter-field">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <select class="modal-select-filter" data-filter="priority" aria-label="Filter by priority">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority }}">{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="modal-filter-actions">
                        <button type="button" class="modal-filter-btn apply" onclick="filterModalRows('{{ $slug }}')">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="modal-filter-btn reset" onclick="resetModalFilters('{{ $slug }}')">
                            <i class="fas fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="task-modal-body">
                    <div class="table-container">
                        <table class="task-table">
                            <thead>
                                <tr>
                                    <th>Ticket No.</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Submitted By</th>
                                    <th>Date Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryTickets as $ticket)
                                    <tr data-province="{{ $ticket->province_scope ?? '' }}" data-priority="{{ $ticket->priority ?? '' }}">
                                        <td>
                                            <div class="proj-title-cell">
                                                <a href="{{ route('ticketing.show', $ticket) }}" style="font-weight: 700; color: #1e3a8a; text-decoration: underline;">
                                                    {{ $ticket->ticket_number }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="task-message-cell">
                                                <div style="font-weight: 700; color: #1e293b;">{{ $ticket->title }}</div>
                                                <div style="margin-top: 4px; color: #64748b; font-size: 12px;">{{ \Illuminate\Support\Str::limit($ticket->description, 80) }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="ticketing-badge" style="background: {{ $ticket->priority_color }}; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                                {{ $ticket->priority }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="task-uploader-cell">
                                                <span>
                                                    <span class="task-uploader-name">{{ $ticket->submitter?->fullName() ?? 'N/A' }}</span>
                                                    @if($ticket->province_scope)
                                                        <span class="task-uploader-province"><i class="fas fa-map-marker-alt"></i> {{ $ticket->province_scope }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ optional($ticket->date_submitted ?? $ticket->created_at)->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                @php
                                                    $acceptRoute = '';
                                                    if ($ticket->current_level === \App\Models\Ticket::LEVEL_PROVINCIAL) {
                                                        $acceptRoute = route('ticketing.province.accept', $ticket);
                                                    } elseif ($ticket->current_level === \App\Models\Ticket::LEVEL_REGIONAL) {
                                                        $acceptRoute = route('ticketing.region.accept', $ticket);
                                                    }
                                                @endphp
                                                @if($acceptRoute)
                                                    <form method="POST" action="{{ $acceptRoute }}" style="margin: 0;">
                                                        @csrf
                                                        <button type="submit" class="task-action-btn" style="background: #10b981; border: none; cursor: pointer; color: #fff;">
                                                            <i class="fas fa-hand"></i> Accept
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('ticketing.show', $ticket) }}" class="task-action-btn" style="background: #64748b; color: #fff;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- BATCH REVIEW & VALIDATION MODAL -->
<div id="modal-batch-review-validate" class="task-modal-backdrop" style="z-index: 1060; display: none;" onclick="closeBatchApprovalModalOnBackdrop(event)">
    <div class="task-modal" style="max-width: 1180px; width: 95vw; max-height: 90vh; border-radius: 20px; box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4); border: 1px solid rgba(226, 232, 240, 0.9); overflow: hidden; display: flex; flex-direction: column;">
        <!-- Header -->
        <div class="task-modal-header task-modal-header-batch" style="padding: 18px 26px !important;">
            <div class="batch-header-main">
                <div class="batch-header-icon-box">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="batch-header-pill-group">
                        <h3 class="batch-modal-title">
                            Batch Review &amp; Validation
                        </h3>
                        <span id="bm-header-code" class="batch-code-pill" style="background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.35); color: #fff;">
                            FUR-BATCH
                        </span>
                        <span class="batch-scope-badge-header">
                            <i class="fas fa-shield-halved"></i>
                            @if($approvalLevel === 'regional')
                                Regional Approvals Pool (CAR)
                            @elseif($approvalLevel === 'provincial')
                                Provincial Approvals Pool ({{ $user->province ?? 'CAR' }})
                            @elseif($approvalLevel === 'cmlgoo')
                                C/MLGOO Approvals Pool ({{ $user->office ?? 'LGU' }})
                            @else
                                Submission Details
                            @endif
                        </span>
                    </div>
                    <p class="batch-modal-subtitle">
                        Fund Utilization Report &bull; Comprehensive batch inspection, target projects scope verification, and approval decision.
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <span id="bm-header-status" style="padding: 6px 16px; border-radius: 999px; font-size: 11px; font-weight: 800; background: #fff; color: #1e3a8a; box-shadow: 0 2px 6px rgba(0,0,0,0.12); letter-spacing: 0.02em;">
                    Status
                </span>
                <button type="button" class="task-modal-close task-modal-close-batch" onclick="closeBatchApprovalModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="task-modal-body" style="padding: 24px; overflow-y: auto; background: #f8fafc; flex: 1;">
            <!-- Loading indicator -->
            <div id="bm-loading" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-circle-notch fa-spin" style="font-size: 34px; color: #2563eb;"></i>
                <div style="margin-top: 14px; font-size: 14px; font-weight: 600; color: #475569;">Loading batch details and target projects...</div>
            </div>

            <!-- Error container -->
            <div id="bm-error" style="display: none; padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px; color: #991b1b; margin-bottom: 20px;">
                <i class="fas fa-triangle-exclamation mr-2"></i> <span id="bm-error-msg">An error occurred while loading batch details.</span>
            </div>

            <!-- Content container -->
            <div id="bm-content" style="display: none;">
                <!-- 1. Metadata Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 20px;">
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><i class="fas fa-calendar-alt text-blue-500 mr-1"></i> Quarter &amp; Year</div>
                        <div id="bm-meta-quarter" style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 4px;">Q1 2026</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><i class="fas fa-user-circle text-indigo-500 mr-1"></i> Submitted By</div>
                        <div id="bm-meta-uploader" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Juan Dela Cruz</div>
                        <div id="bm-meta-uploader-sub" style="font-size: 11px; color: #64748b; margin-top: 2px;">LGU Submitter &bull; Province of Abra</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><i class="fas fa-clock text-amber-500 mr-1"></i> Date Submitted</div>
                        <div id="bm-meta-date" style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">Oct 22, 2026 03:00 PM</div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><i class="fas fa-cubes text-emerald-500 mr-1"></i> Target Scope</div>
                        <div id="bm-meta-count" style="font-size: 16px; font-weight: 800; color: #059669; margin-top: 4px;">12 Projects</div>
                    </div>
                </div>

                <!-- 2. Uploaded Documents Section -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-paperclip" style="color: #2563eb;"></i>
                            Uploaded Documents in Batch
                        </h4>
                        <span id="bm-docs-count" style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 999px;">0 Files</span>
                    </div>
                    <div id="bm-docs-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                        <!-- Dynamically injected doc cards -->
                    </div>
                </div>

                <!-- 3. Target Projects Section -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                        <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-folder-tree" style="color: #4f46e5;"></i>
                            Target Projects Receiving Batch Documents
                        </h4>
                        <div style="position: relative; width: 280px;">
                            <input type="text" id="bm-project-search" placeholder="Search project title or code..." onkeyup="filterBatchProjects()" style="width: 100%; padding: 8px 12px 8px 34px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; background: #fff;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #94a3b8;"></i>
                        </div>
                    </div>
                    <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                        <table class="table" style="width: 100%; margin: 0; font-size: 12px; border-collapse: collapse;" id="bm-projects-table">
                            <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2;">
                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 11px; text-transform: uppercase;">
                                    <th style="padding: 10px 12px;">Project Code</th>
                                    <th style="padding: 10px 12px;">Project Title</th>
                                    <th style="padding: 10px 12px;">Location</th>
                                    <th style="padding: 10px 12px;">Allocation</th>
                                    <th style="padding: 10px 12px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="bm-projects-body">
                                <!-- Dynamically injected rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Approval History & Timeline -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-history" style="color: #d97706;"></i>
                        Approval History &amp; Audit Trail
                    </h4>
                    <div id="bm-history-container" style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- Dynamically injected timeline items -->
                    </div>
                </div>

                <!-- 5. Decision & Actions Section -->
                <div id="bm-action-container" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div id="bm-action-form" style="display: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-pen-to-square" style="color: #059669;"></i>
                                Validator Action &amp; Remarks
                            </h4>
                            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 4px 10px;">
                                <i class="fas fa-shield-halved"></i> Regional Validation Authority (CAR)
                            </div>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label for="bm-remarks-input" style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                                Remarks / Revision Notes <span style="font-weight: 400; color: #64748b;">(Mandatory when returning for revision; optional for approval)</span>
                            </label>
                            <textarea id="bm-remarks-input" rows="3" placeholder="Enter findings, validation notes, or revision instructions..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; font-size: 13px; outline: none; font-family: inherit; resize: vertical;"></textarea>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-outline-danger" id="bm-btn-return" onclick="submitBatchDecision('return')" style="padding: 10px 20px; font-size: 13px; font-weight: 700; border-radius: 10px; border: 1px solid #ef4444; color: #dc2626; background: #fff; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;">
                                <i class="fas fa-rotate-left"></i> Return for Revision
                            </button>
                            <button type="button" class="btn btn-success" id="bm-btn-approve" onclick="submitBatchDecision('approve')" style="padding: 10px 24px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none; color: #fff; background: linear-gradient(135deg, #16a34a, #15803d); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3); transition: all 0.2s ease;">
                                <i class="fas fa-check-circle"></i> <span id="bm-btn-approve-label">Approve &amp; Forward</span>
                            </button>
                        </div>
                    </div>
                    <div id="bm-action-readonly" style="display: none; padding: 14px 18px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; color: #1e40af; font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i> You are viewing this batch in read-only mode. Current status: <strong id="bm-readonly-status">Approved</strong>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentBatchCode = '';
    let currentBatchData = null;

    function openBatchApprovalModal(batchCode) {
        currentBatchCode = batchCode;
        const modal = document.getElementById('modal-batch-review-validate');
        if (!modal) return;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Show loading, hide error & content
        document.getElementById('bm-loading').style.display = 'block';
        document.getElementById('bm-error').style.display = 'none';
        document.getElementById('bm-content').style.display = 'none';

        fetch(`/fund-utilization/batch-approvals/${encodeURIComponent(batchCode)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed to load batch.');
            currentBatchData = data;
            populateBatchApprovalModal(data);
        })
        .catch(err => {
            document.getElementById('bm-loading').style.display = 'none';
            document.getElementById('bm-error').style.display = 'block';
            document.getElementById('bm-error-msg').textContent = err.message || 'Error loading batch submission.';
        });
    }

    function closeBatchApprovalModal() {
        const modal = document.getElementById('modal-batch-review-validate');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function closeBatchApprovalModalOnBackdrop(event) {
        if (event.target === event.currentTarget) {
            closeBatchApprovalModal();
        }
    }

    function populateBatchApprovalModal(data) {
        const batch = data.batch;

        // Header info
        document.getElementById('bm-header-code').textContent = batch.batch_code;
        const statusEl = document.getElementById('bm-header-status');
        statusEl.textContent = batch.status;

        const stLower = (batch.status || '').toLowerCase();
        if (stLower.includes('approved')) {
            statusEl.style.background = '#dcfce7';
            statusEl.style.color = '#166534';
        } else if (stLower.includes('returned')) {
            statusEl.style.background = '#fee2e2';
            statusEl.style.color = '#991b1b';
        } else {
            statusEl.style.background = '#fef3c7';
            statusEl.style.color = '#92400e';
        }

        // Metadata grid
        document.getElementById('bm-meta-quarter').textContent = `${batch.quarter} ${batch.year || ''}`;
        document.getElementById('bm-meta-uploader').textContent = batch.uploader_name || 'Unknown Uploader';
        const roleLabel = (batch.uploader_role || 'UPLOADER').toUpperCase();
        const provLabel = batch.uploader_province || batch.uploader_office || 'CAR';
        document.getElementById('bm-meta-uploader-sub').textContent = `${roleLabel} • ${provLabel}`;
        document.getElementById('bm-meta-date').textContent = batch.submitted_at || 'N/A';
        document.getElementById('bm-meta-count').textContent = `${batch.project_count || (batch.projects ? batch.projects.length : 0)} Projects`;

        // Uploaded documents
        const docsContainer = document.getElementById('bm-docs-container');
        docsContainer.innerHTML = '';
        const docs = batch.documents || [];
        document.getElementById('bm-docs-count').textContent = `${docs.length} Document${docs.length === 1 ? '' : 's'}`;

        if (docs.length === 0) {
            docsContainer.innerHTML = '<div style="color: #64748b; font-size: 13px; font-style: italic; padding: 10px 0;">No document files attached in this batch.</div>';
        } else {
            docs.forEach(doc => {
                const card = document.createElement('div');
                card.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; gap: 10px;';
                card.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                            <i class="${doc.is_link ? 'fas fa-link' : 'fas fa-file-lines'}"></i>
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${doc.label}">${doc.label}</div>
                            <div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${doc.file_name}">${doc.file_name}</div>
                        </div>
                    </div>
                    <a href="${doc.url}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #2563eb; color: #fff; text-decoration: none; flex-shrink: 0;">
                        <i class="${doc.is_link ? 'fas fa-external-link-alt' : 'fas fa-eye'}"></i> ${doc.is_link ? 'Open' : 'View'}
                    </a>
                `;
                docsContainer.appendChild(card);
            });
        }

        // Target projects table
        const projectsBody = document.getElementById('bm-projects-body');
        projectsBody.innerHTML = '';
        const projects = batch.projects || [];

        if (projects.length === 0) {
            projectsBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 18px;">No projects found in this batch.</td></tr>';
        } else {
            projects.forEach(p => {
                const tr = document.createElement('tr');
                tr.style.cssText = 'border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;';
                tr.dataset.search = `${p.project_code} ${p.project_title} ${p.province} ${p.city_municipality}`.toLowerCase();
                const allocFormatted = Number(p.allocation || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });

                tr.innerHTML = `
                    <td style="padding: 10px 12px; font-weight: 700; color: #1e3a8a;">
                        <a href="/fund-utilization/${encodeURIComponent(p.project_code)}?quarter=${encodeURIComponent(batch.quarter)}" target="_blank" style="color: #1d4ed8; text-decoration: underline;" title="View FUR details">
                            ${p.project_code}
                        </a>
                    </td>
                    <td style="padding: 10px 12px; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${p.project_title}">
                        ${p.project_title}
                    </td>
                    <td style="padding: 10px 12px; color: #475569;">
                        ${p.city_municipality ? p.city_municipality + ', ' : ''}${p.province || 'CAR'}
                    </td>
                    <td style="padding: 10px 12px; font-weight: 600; color: #0f172a;">
                        ${allocFormatted}
                    </td>
                    <td style="padding: 10px 12px;">
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; background: #e0f2fe; color: #0369a1;">
                            ${p.status || 'Ongoing'}
                        </span>
                    </td>
                `;
                projectsBody.appendChild(tr);
            });
        }

        // History logs
        const historyContainer = document.getElementById('bm-history-container');
        historyContainer.innerHTML = '';
        const logs = batch.logs || [];

        if (logs.length === 0) {
            historyContainer.innerHTML = '<div style="color: #64748b; font-size: 13px; font-style: italic;">No audit events recorded yet.</div>';
        } else {
            logs.forEach(log => {
                const item = document.createElement('div');
                item.style.cssText = 'display: flex; gap: 12px; padding: 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0;';

                let iconClass = 'fa-check-circle text-emerald-600';
                if ((log.action || '').toLowerCase().includes('return')) {
                    iconClass = 'fa-rotate-left text-rose-600';
                } else if ((log.action || '').toLowerCase().includes('submit')) {
                    iconClass = 'fa-upload text-blue-600';
                } else if ((log.action || '').toLowerCase().includes('forward')) {
                    iconClass = 'fa-share text-indigo-600';
                }

                item.innerHTML = `
                    <div style="font-size: 18px; margin-top: 2px;">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                            <span style="font-weight: 700; color: #1e293b; font-size: 13px;">
                                ${log.action} &bull; <span style="font-weight: 600; color: #475569;">${log.actor_name}</span> ${log.actor_role ? '(' + log.actor_role + ')' : ''}
                            </span>
                            <span style="font-size: 11px; color: #94a3b8;">${log.created_at}</span>
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            Resulting Status: <strong style="color: #334155;">${log.status || 'N/A'}</strong>
                        </div>
                        ${log.remarks ? `
                            <div style="margin-top: 6px; padding: 6px 12px; background: #fff; border-left: 3px solid #cbd5e1; border-radius: 4px; font-size: 12px; color: #334155; font-style: italic;">
                                "${log.remarks}"
                            </div>
                        ` : ''}
                    </div>
                `;
                historyContainer.appendChild(item);
            });
        }

        // Action Decision Area
        const actionForm = document.getElementById('bm-action-form');
        const actionReadonly = document.getElementById('bm-action-readonly');
        const remarksInput = document.getElementById('bm-remarks-input');
        remarksInput.value = '';

        if (data.can_approve || data.can_return) {
            actionForm.style.display = 'block';
            actionReadonly.style.display = 'none';

            const approveBtn = document.getElementById('bm-btn-approve');
            const returnBtn = document.getElementById('bm-btn-return');
            const approveLabel = document.getElementById('bm-btn-approve-label');

            approveBtn.style.display = data.can_approve ? 'inline-flex' : 'none';
            returnBtn.style.display = data.can_return ? 'inline-flex' : 'none';
            approveLabel.textContent = data.approval_action_label || 'Approve';
        } else {
            actionForm.style.display = 'none';
            actionReadonly.style.display = 'block';
            document.getElementById('bm-readonly-status').textContent = batch.status;
        }

        // Show content, hide loading
        document.getElementById('bm-loading').style.display = 'none';
        document.getElementById('bm-content').style.display = 'block';
    }

    function filterBatchProjects() {
        const query = (document.getElementById('bm-project-search').value || '').trim().toLowerCase();
        const rows = document.querySelectorAll('#bm-projects-body tr');
        rows.forEach(r => {
            const searchData = r.dataset.search || '';
            r.style.display = searchData.includes(query) ? '' : 'none';
        });
    }

    function submitBatchDecision(action) {
        if (!currentBatchCode) return;

        const remarks = (document.getElementById('bm-remarks-input').value || '').trim();

        if (action === 'return' && !remarks) {
            alert('Remarks are required when returning a batch submission for revision.');
            document.getElementById('bm-remarks-input').focus();
            return;
        }

        const isApprove = action === 'approve';
        const confirmMsg = isApprove
            ? `Are you sure you want to approve batch submission ${currentBatchCode}?`
            : `Are you sure you want to return batch submission ${currentBatchCode} for revision?`;

        if (!confirm(confirmMsg)) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const endpoint = isApprove
            ? `/fund-utilization/batch-approvals/${encodeURIComponent(currentBatchCode)}/approve`
            : `/fund-utilization/batch-approvals/${encodeURIComponent(currentBatchCode)}/return`;

        const approveBtn = document.getElementById('bm-btn-approve');
        const returnBtn = document.getElementById('bm-btn-return');
        approveBtn.disabled = true;
        returnBtn.disabled = true;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ remarks: remarks })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                alert(result.message || 'Action executed successfully.');
                closeBatchApprovalModal();
            } else {
                alert(result.message || 'An error occurred during submission.');
                approveBtn.disabled = false;
                returnBtn.disabled = false;
            }
        })
        .catch(err => {
            alert(err.message || 'Network error occurred while submitting decision.');
            approveBtn.disabled = false;
            returnBtn.disabled = false;
        });
    }

    // Auto-open batch modal if ?open_batch=CODE is in query string
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const openBatch = urlParams.get('open_batch');
        if (openBatch) {
            openBatchApprovalModal(openBatch);
        }
    });
</script>
<script>
    function openTaskModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Focus on search input automatically
            const searchInput = modal.querySelector('.modal-search-input');
            if (searchInput) searchInput.focus();
        }
    }

    // Reset filters and inputs when closing the modal
    function closeTaskModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';

            resetModalFilters(id);
        }
    }

    function resetModalFilters(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const searchInput = modal.querySelector('.modal-search-input');
        if (searchInput) searchInput.value = '';
        modal.querySelectorAll('.modal-select-filter').forEach(select => select.value = '');
        modal.querySelectorAll('.task-table tbody tr').forEach(row => row.style.display = '');
    }

    function closeTaskModalOnBackdrop(event, id) {
        if (event.target === event.currentTarget) {
            closeTaskModal(id);
        }
    }

    function filterModalRows(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const searchInput = modal.querySelector('.modal-search-input');
        const searchVal = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
        const selectedFilters = {};

        modal.querySelectorAll('[data-filter]').forEach((control) => {
            const filterName = control.dataset.filter;
            if (filterName) {
                selectedFilters[filterName] = String(control.value || '').trim().toLowerCase();
            }
        });
        
        const rows = modal.querySelectorAll('.task-table tbody tr');
        rows.forEach(row => {
            const rowText = (row.innerText || '').toLowerCase();
            const matchesSearch = !searchVal || rowText.includes(searchVal);
            const matchesFilters = Object.entries(selectedFilters).every(([filterName, filterValue]) => {
                if (filterValue === '') return true;

                return String(row.dataset[filterName] || '').trim().toLowerCase() === filterValue;
            });
            
            if (matchesSearch && matchesFilters) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.task-modal-backdrop[style*="display: flex"]');
            openModals.forEach(modal => {
                closeTaskModal(modal.id);
            });
        }
    });
</script>
@endsection
