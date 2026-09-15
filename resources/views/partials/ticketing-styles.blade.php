<style>
    .ticketing-shell {
        display: grid;
        gap: 20px;
    }

    .ticketing-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(191, 219, 254, 0.9);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        padding: 22px;
    }

    .ticketing-card-title {
        color: #0f172a;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .ticketing-card-subtitle {
        color: #64748b;
        font-size: 13px;
        margin: 0;
    }

    .ticketing-grid {
        display: grid;
        gap: 18px;
    }

    .ticketing-grid--2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ticketing-grid--3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ticketing-grid--4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ticketing-summary-card {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 20px;
        color: #ffffff;
        min-height: 132px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 18px 30px rgba(15, 23, 42, 0.15);
    }

    .ticketing-summary-card::after {
        content: '';
        position: absolute;
        right: -24px;
        top: -24px;
        width: 120px;
        height: 120px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
    }

    .ticketing-summary-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .ticketing-summary-label {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.9;
    }

    .ticketing-summary-value {
        font-size: 34px;
        font-weight: 800;
        line-height: 1;
    }

    .ticketing-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        color: #ffffff;
        white-space: nowrap;
    }

    .ticketing-muted-badge {
        background: #e2e8f0;
        color: #334155;
    }

    .ticketing-toolbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .ticketing-toolbar-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ticketing-btn {
        border: none;
        border-radius: 12px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
    }

    .ticketing-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 16px rgba(15, 23, 42, 0.12);
    }

    .ticketing-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .ticketing-btn--primary {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        color: #ffffff;
    }

    .ticketing-btn--secondary {
        background: #e2e8f0;
        color: #0f172a;
    }

    .ticketing-btn--success {
        background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
        color: #ffffff;
    }

    .ticketing-btn--warning {
        background: linear-gradient(135deg, #b45309 0%, #d97706 100%);
        color: #ffffff;
    }

    .ticketing-btn--danger {
        background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        color: #ffffff;
    }

    .ticketing-btn--dark {
        background: linear-gradient(135deg, #334155 0%, #0f172a 100%);
        color: #ffffff;
    }

    .ticketing-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .ticketing-field label {
        display: block;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #475569;
    }

    .ticketing-field input,
    .ticketing-field textarea,
    .ticketing-field select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 14px;
        color: #0f172a;
        background: #ffffff;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .ticketing-field textarea {
        min-height: 120px;
        resize: vertical;
    }

    .ticketing-field input:focus,
    .ticketing-field textarea:focus,
    .ticketing-field select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .ticketing-table-wrap {
        overflow-x: auto;
    }

    .ticketing-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
    }

    .ticketing-table th,
    .ticketing-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
        text-align: left;
        color: #0f172a;
        font-size: 13px;
    }

    .ticketing-table th {
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f8fafc;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .ticketing-ticket-link {
        color: #1d4ed8;
        font-weight: 700;
        text-decoration: none;
    }

    .ticketing-ticket-link:hover {
        color: #1e3a8a;
    }

    .ticketing-kicker {
        color: #64748b;
        font-size: 12px;
        margin-top: 4px;
    }

    .ticketing-empty {
        text-align: center;
        padding: 28px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        color: #64748b;
        font-size: 14px;
    }

    .ticketing-meta-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .ticketing-meta-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 16px;
    }

    .ticketing-meta-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
        font-weight: 700;
    }

    .ticketing-meta-value {
        color: #0f172a;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.5;
    }

    .ticketing-attachment-list,
    .ticketing-comment-list,
    .ticketing-history-list {
        display: grid;
        gap: 12px;
    }

    .ticketing-attachment-item,
    .ticketing-comment-item,
    .ticketing-history-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        padding: 14px 16px;
    }

    .ticketing-history-item {
        border-left: 4px solid #2563eb;
    }

    .ticketing-comment-author,
    .ticketing-history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .ticketing-comment-author strong,
    .ticketing-history-head strong {
        color: #0f172a;
        font-size: 14px;
    }

    .ticketing-comment-time,
    .ticketing-history-time {
        color: #64748b;
        font-size: 12px;
    }

    .ticketing-comment-body,
    .ticketing-history-body {
        color: #1e293b;
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-line;
    }

    .ticketing-history-timeline {
        position: relative;
        display: grid;
        gap: 24px;
        padding: 4px 0 4px 0;
    }

    .ticketing-history-timeline::before {
        content: '';
        position: absolute;
        left: 82px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(180deg, rgba(203, 213, 225, 0.25) 0%, rgba(203, 213, 225, 0.95) 18%, rgba(203, 213, 225, 0.95) 82%, rgba(203, 213, 225, 0.25) 100%);
    }

    .ticketing-history-row {
        display: grid;
        grid-template-columns: 64px 34px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    .ticketing-history-date {
        position: relative;
        z-index: 1;
        display: grid;
        justify-items: end;
        gap: 0;
        padding-top: 6px;
    }

    .ticketing-history-day {
        color: #0f172a;
        font-size: 34px;
        font-weight: 800;
        line-height: 0.95;
    }

    .ticketing-history-month {
        color: #94a3b8;
        font-size: 22px;
        font-weight: 700;
        line-height: 1;
        margin-top: 2px;
        text-transform: uppercase;
    }

    .ticketing-history-clock {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin-top: 8px;
    }

    .ticketing-history-marker-wrap {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: center;
        padding-top: 10px;
    }

    .ticketing-history-marker {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #ffffff;
        border: 6px solid #14b8a6;
        box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.75);
    }

    .ticketing-history-card {
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid #eef2f7;
        border-radius: 18px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
        padding: 18px 20px;
        min-height: 150px;
    }

    .ticketing-history-title {
        color: #0f172a;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        margin: 0 0 16px;
    }

    .ticketing-history-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .ticketing-history-actor {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #94a3b8;
        font-size: 15px;
        font-weight: 700;
    }

    .ticketing-history-actor i {
        font-size: 17px;
    }

    .ticketing-history-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        padding: 8px 16px;
        color: #94a3b8;
        font-size: 13px;
        font-weight: 700;
        background: #ffffff;
    }

    .ticketing-history-summary {
        color: #475569;
        font-size: 13px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .ticketing-history-metadata {
        display: grid;
        gap: 6px;
    }

    .ticketing-history-metadata-item {
        color: #334155;
        font-size: 13px;
        line-height: 1.5;
    }

    .ticketing-history-card--positive.ticketing-history-card {
        box-shadow: 0 12px 28px rgba(20, 184, 166, 0.08);
    }

    .ticketing-history-card--positive.ticketing-history-marker,
    .ticketing-history-card--positive .ticketing-history-marker {
        border-color: #0ea5a4;
    }

    .ticketing-history-card--negative.ticketing-history-card {
        box-shadow: 0 12px 28px rgba(239, 68, 68, 0.08);
    }

    .ticketing-history-card--negative.ticketing-history-marker,
    .ticketing-history-card--negative .ticketing-history-marker {
        border-color: #ef4444;
    }

    .ticketing-history-card--neutral.ticketing-history-card {
        box-shadow: 0 12px 28px rgba(234, 179, 8, 0.08);
    }

    .ticketing-history-card--neutral.ticketing-history-marker,
    .ticketing-history-card--neutral .ticketing-history-marker {
        border-color: #eab308;
    }

    .ticketing-history-accordion {
        margin-top: 16px;
        border: 1px solid #dbe4f0;
        border-radius: 16px;
        background: #f8fbff;
        overflow: hidden;
    }

    .ticketing-history-accordion-toggle {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        cursor: pointer;
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
    }

    .ticketing-history-accordion-toggle::-webkit-details-marker {
        display: none;
    }

    .ticketing-history-accordion-toggle::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: #64748b;
        transition: transform 0.18s ease;
    }

    .ticketing-history-accordion[open] .ticketing-history-accordion-toggle::after {
        transform: rotate(180deg);
    }

    .ticketing-history-accordion-count {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        margin-left: auto;
        padding-right: 8px;
    }

    .ticketing-history-accordion-body {
        padding: 0 18px 18px;
    }

    .ticketing-history-timeline--nested {
        padding-top: 10px;
    }

    .ticketing-inline-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ticketing-dropdown {
        position: relative;
        display: inline-flex;
    }

    .ticketing-dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 240px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
        padding: 10px;
        display: none;
        z-index: 30;
    }

    .ticketing-dropdown-menu.is-open {
        display: block;
    }

    .ticketing-dropdown-menu form + form,
    .ticketing-dropdown-menu button + button {
        margin-top: 8px;
    }

    .ticketing-modal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.56);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 1200;
    }

    .ticketing-modal.is-open {
        display: flex;
    }

    .ticketing-modal-dialog {
        width: min(560px, 100%);
        background: #ffffff;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
    }

    .ticketing-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .ticketing-modal-close {
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 20px;
        cursor: pointer;
    }

    .ticketing-progress {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ticketing-progress-step {
        padding: 8px 12px;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .ticketing-progress-step.is-active {
        background: #2563eb;
        color: #ffffff;
    }

    .ticketing-progress-step.is-done {
        background: #15803d;
        color: #ffffff;
    }

    .ticketing-category-grid {
        display: grid;
        gap: 12px;
    }

    .ticketing-category-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 16px;
    }

    .ticketing-category-item form {
        display: grid;
        gap: 12px;
    }

    .ticketing-activity-item {
        display: grid;
        gap: 6px;
        padding: 14px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .ticketing-activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .ticketing-activity-item:first-child {
        padding-top: 0;
    }

    .ticketing-activity-title {
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
    }

    .ticketing-activity-meta {
        color: #64748b;
        font-size: 12px;
    }

    @media (max-width: 1200px) {
        .ticketing-grid--4,
        .ticketing-grid--3,
        .ticketing-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .ticketing-grid--2,
        .ticketing-grid--3,
        .ticketing-grid--4,
        .ticketing-filter-grid,
        .ticketing-meta-list {
            grid-template-columns: minmax(0, 1fr);
        }

        .ticketing-card {
            padding: 18px;
        }

        .ticketing-toolbar {
            flex-direction: column;
        }

        .ticketing-history-timeline::before {
            left: 16px;
        }

        .ticketing-history-row {
            grid-template-columns: 1fr;
            gap: 10px;
            padding-left: 42px;
        }

        .ticketing-history-date {
            justify-items: start;
            padding-top: 0;
        }

        .ticketing-history-day {
            font-size: 24px;
        }

        .ticketing-history-month {
            font-size: 16px;
        }

        .ticketing-history-marker-wrap {
            position: absolute;
            left: 5px;
            padding-top: 4px;
        }

        .ticketing-history-card {
            min-height: auto;
            padding: 16px;
        }

        .ticketing-history-accordion-toggle {
            align-items: flex-start;
            flex-direction: column;
        }

        .ticketing-history-accordion-count {
            margin-left: 0;
            padding-right: 0;
        }

        .ticketing-history-title {
            font-size: 22px;
        }

        .ticketing-history-meta {
            align-items: flex-start;
        }
    }

    /* Preview Modal additions */
    .ticketing-modal-preview {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
    }

    .ticketing-modal-preview-pdf {
        width: 100%;
        height: 70vh;
        border: none;
    }

    .ticketing-modal-preview-video {
        width: 100%;
        max-height: 70vh;
        outline: none;
    }

    .ticketing-modal-fallback {
        text-align: center;
        padding: 40px;
        width: 100%;
    }

    .ticketing-modal-fallback-icon {
        font-size: 48px;
        color: #94a3b8;
        margin-bottom: 16px;
    }

    /* Corporate helpdesk overrides: compact surfaces, restrained color, and scan-friendly controls. */
    .ticketing-shell {
        max-width: 1480px;
        gap: 16px;
    }

    .ticketing-card {
        background: #ffffff;
        border: 1px solid #d9e1ea;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        padding: 20px;
    }

    .ticketing-card-title {
        color: #172b4d;
        font-size: 17px;
        letter-spacing: 0;
    }

    .ticketing-card-subtitle {
        color: #61738a;
        line-height: 1.5;
    }

    .ticketing-summary-card {
        min-height: 116px;
        border-radius: 8px;
        padding: 18px;
        background: #172b4d !important;
        box-shadow: none;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .ticketing-summary-card:nth-child(2) { background: #0f766e !important; }
    .ticketing-summary-card:nth-child(3) { background: #2563a6 !important; }
    .ticketing-summary-card:nth-child(4) { background: #475569 !important; }
    .ticketing-summary-card:nth-child(5) { background: #9a6700 !important; }
    .ticketing-summary-card:nth-child(6) { background: #a33b3b !important; }

    .ticketing-summary-card::after {
        display: none;
    }

    .ticketing-summary-icon {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.14);
        font-size: 15px;
    }

    .ticketing-summary-label {
        font-size: 11px;
        letter-spacing: 0.06em;
    }

    .ticketing-summary-value {
        font-size: 30px;
    }

    .ticketing-btn {
        border-radius: 6px;
        padding: 10px 14px;
        transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .ticketing-btn:hover {
        transform: none;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.12);
    }

    .ticketing-btn--primary { background: #1f5f99; }
    .ticketing-btn--primary:hover { background: #174a78; }
    .ticketing-btn--secondary { background: #f3f6f9; color: #263b53; border: 1px solid #cbd5e1; }
    .ticketing-btn--success { background: #147d64; }
    .ticketing-btn--warning { background: #9a6700; }
    .ticketing-btn--danger { background: #a33b3b; }
    .ticketing-btn--dark { background: #27364a; }

    .ticketing-field input,
    .ticketing-field textarea,
    .ticketing-field select {
        border-radius: 6px;
        border-color: #b9c5d2;
        padding: 10px 12px;
        color: #172b4d;
    }

    .ticketing-field input:focus,
    .ticketing-field textarea:focus,
    .ticketing-field select:focus,
    .ticketing-btn:focus-visible,
    .ticketing-ticket-link:focus-visible,
    .ticketing-modal-close:focus-visible {
        outline: 3px solid rgba(31, 95, 153, 0.24);
        outline-offset: 2px;
        box-shadow: none;
    }

    .ticketing-table {
        min-width: 900px;
    }

    .ticketing-table th,
    .ticketing-table td {
        padding: 12px;
        border-bottom-color: #e6ebf0;
    }

    .ticketing-table th {
        color: #52657a;
        background: #f5f7fa;
        font-size: 11px;
    }

    .ticketing-table tbody tr:hover {
        background: #f8fafc;
    }

    .ticketing-ticket-link {
        color: #1f5f99;
    }

    .ticketing-badge,
    .ticketing-progress-step {
        border-radius: 5px;
        padding: 5px 8px;
        font-size: 11px;
    }

    .ticketing-progress {
        gap: 6px;
        padding-bottom: 2px;
        border-bottom: 1px solid #e6ebf0;
    }

    .ticketing-progress-step {
        border-radius: 4px 4px 0 0;
        background: transparent;
        color: #61738a;
        border-bottom: 2px solid transparent;
    }

    .ticketing-progress-step.is-active {
        background: #eef5fb;
        color: #1f5f99;
        border-bottom-color: #1f5f99;
    }

    .ticketing-progress-step.is-done {
        background: transparent;
        color: #147d64;
        border-bottom-color: #147d64;
    }

    .ticketing-meta-item,
    .ticketing-attachment-item,
    .ticketing-comment-item,
    .ticketing-history-item,
    .ticketing-category-item {
        border-radius: 6px;
        border-color: #d9e1ea;
    }

    .ticketing-meta-item {
        background: #f7f9fb;
        padding: 12px 14px;
    }

    .ticketing-meta-label,
    .ticketing-field label {
        color: #52657a;
        font-size: 11px;
    }

    .ticketing-meta-value {
        color: #172b4d;
    }

    .ticketing-empty {
        border-radius: 6px;
        background: #f7f9fb;
        padding: 24px;
    }

    .ticketing-modal {
        background: rgba(23, 43, 77, 0.48);
    }

    .ticketing-modal-dialog {
        border-radius: 8px;
        border: 1px solid #d9e1ea;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.2);
    }

    .ticketing-history-title {
        font-size: 20px;
        margin-bottom: 12px;
    }

    .ticketing-command-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        padding: 8px 2px 4px;
    }

    .ticketing-eyebrow {
        color: #61738a;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .ticketing-page-title {
        color: #172b4d;
        font-size: clamp(24px, 3vw, 34px);
        line-height: 1.1;
        margin: 0 0 6px;
        font-weight: 800;
    }

    .ticketing-page-subtitle {
        color: #61738a;
        margin: 0;
        font-size: 14px;
    }

    .ticketing-command-actions,
    .ticketing-quick-links {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ticketing-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
    }

    .ticketing-quick-links {
        border-top: 1px solid #d9e1ea;
        border-bottom: 1px solid #d9e1ea;
        padding: 8px 2px;
    }

    .ticketing-quick-links a {
        color: #52657a;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .ticketing-quick-links a:hover {
        background: #eef5fb;
        color: #1f5f99;
    }

    .ticketing-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.85fr);
        gap: 16px;
        align-items: start;
    }

    .ticketing-dashboard-primary {
        min-width: 0;
    }

    .ticketing-dashboard-rail {
        display: grid;
        gap: 16px;
        min-width: 0;
    }

    .ticketing-section-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
    }

    .ticketing-section-count {
        color: #61738a;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        padding-top: 4px;
    }

    .ticketing-process-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border: 1px solid #d9e1ea;
        border-radius: 8px;
        background: #ffffff;
        overflow: hidden;
    }

    .ticketing-process-strip > div {
        display: grid;
        grid-template-columns: 28px 1fr;
        column-gap: 9px;
        align-items: center;
        padding: 13px 16px;
        border-right: 1px solid #e6ebf0;
    }

    .ticketing-process-strip > div:last-child {
        border-right: none;
    }

    .ticketing-process-strip span {
        grid-row: span 2;
        display: inline-flex;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e8eef4;
        color: #52657a;
        font-size: 12px;
        font-weight: 800;
    }

    .ticketing-process-strip strong {
        color: #172b4d;
        font-size: 12px;
    }

    .ticketing-process-strip small {
        color: #61738a;
        font-size: 11px;
    }

    .ticketing-process-strip .is-active {
        background: #f2f7fb;
    }

    .ticketing-process-strip .is-active span {
        background: #1f5f99;
        color: #ffffff;
    }

    .ticketing-form-note {
        min-height: 42px;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 10px 12px;
        border: 1px solid #d9e1ea;
        border-radius: 6px;
        background: #f7f9fb;
        color: #52657a;
        font-size: 12px;
        line-height: 1.4;
    }

    .ticketing-form-note i {
        color: #1f5f99;
    }

    .ticketing-description-panel {
        border-left: 3px solid #1f5f99;
    }

    .ticketing-description-body {
        margin-top: 10px;
        color: #263b53;
        font-size: 14px;
        line-height: 1.7;
        white-space: pre-line;
    }

    .ticketing-chat-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .ticketing-chat-count {
        color: #61738a;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .ticketing-chat-count i {
        color: #1f5f99;
        margin-right: 4px;
    }

    .ticketing-chat-thread {
        display: grid;
        gap: 14px;
        max-height: 480px;
        overflow-y: auto;
        padding: 4px 4px 18px 0;
        margin-bottom: 16px;
        border-bottom: 1px solid #e6ebf0;
    }

    .ticketing-chat-message {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        max-width: 92%;
    }

    .ticketing-chat-message.is-own {
        flex-direction: row-reverse;
        justify-self: end;
    }

    .ticketing-chat-avatar {
        flex: 0 0 30px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #dceaf5;
        color: #1f5f99;
        font-size: 10px;
        font-weight: 800;
    }

    .ticketing-chat-message.is-own .ticketing-chat-avatar {
        background: #d7eee8;
        color: #147d64;
    }

    .ticketing-chat-content {
        min-width: 0;
    }

    .ticketing-chat-meta {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin: 0 0 4px 2px;
    }

    .ticketing-chat-meta strong {
        color: #172b4d;
        font-size: 12px;
    }

    .ticketing-chat-meta time {
        color: #8796a8;
        font-size: 10px;
    }

    .ticketing-chat-bubble {
        padding: 10px 12px;
        border: 1px solid #d9e1ea;
        border-radius: 4px 10px 10px 10px;
        background: #f5f8fb;
        color: #263b53;
        font-size: 13px;
        line-height: 1.55;
        white-space: pre-line;
    }

    .ticketing-chat-message.is-own .ticketing-chat-bubble {
        border-color: #c7e5dc;
        border-radius: 10px 4px 10px 10px;
        background: #eaf6f2;
    }

    .ticketing-chat-empty {
        display: grid;
        justify-items: center;
        gap: 5px;
        padding: 28px 16px;
        margin-bottom: 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        background: #f7f9fb;
        color: #61738a;
        text-align: center;
        font-size: 12px;
    }

    .ticketing-chat-empty i {
        margin-bottom: 4px;
        color: #1f5f99;
        font-size: 20px;
    }

    .ticketing-chat-empty strong {
        color: #263b53;
        font-size: 13px;
    }

    .ticketing-dashboard-table {
        min-width: 680px;
    }

    .ticketing-table-secondary {
        color: #61738a !important;
        white-space: nowrap;
    }

    .ticketing-icon-link {
        color: #1f5f99;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 5px;
        text-decoration: none;
    }

    .ticketing-icon-link:hover {
        background: #eef5fb;
    }

    .ticketing-compact-list {
        display: grid;
        border-top: 1px solid #e6ebf0;
    }

    .ticketing-compact-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 2px;
        border-bottom: 1px solid #e6ebf0;
        color: #172b4d;
        text-decoration: none;
    }

    .ticketing-compact-item:hover {
        color: #1f5f99;
    }

    .ticketing-compact-item strong,
    .ticketing-compact-item small {
        display: block;
    }

    .ticketing-compact-item strong {
        font-size: 12px;
    }

    .ticketing-compact-item small {
        color: #61738a;
        font-size: 12px;
        margin-top: 3px;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 900px) {
        .ticketing-dashboard-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 768px) {
        .ticketing-command-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .ticketing-card {
            padding: 16px;
        }

        .ticketing-chat-header {
            flex-direction: column;
        }

        .ticketing-chat-message {
            max-width: 100%;
        }

        .ticketing-process-strip {
            grid-template-columns: minmax(0, 1fr);
        }

        .ticketing-process-strip > div {
            border-right: none;
            border-bottom: 1px solid #e6ebf0;
        }

        .ticketing-process-strip > div:last-child {
            border-bottom: none;
        }

        .ticketing-summary-card {
            min-height: 104px;
        }
    }
</style>
