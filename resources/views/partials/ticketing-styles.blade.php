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
</style>
