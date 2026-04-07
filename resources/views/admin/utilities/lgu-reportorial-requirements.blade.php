@extends('layouts.dashboard')

@section('title', 'LGU Reportorial Requirements')
@section('page-title', 'LGU Reportorial Requirements')

@section('content')
    <div class="content-header">
        <h1>LGU Reportorial Requirements</h1>
        <p>Manage LGU-specific reportorial requirement settings and future workflow controls.</p>
    </div>

    <section class="reportorial-shell reportorial-shell--lgu">
        <div class="reportorial-header">
            <div class="reportorial-icon" aria-hidden="true">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h2>LGU Configuration Workspace</h2>
                <p>
                    Review LGU reportorial requirements by reporting timeline so annual, quarterly, and monthly
                    requirement groups are easier to manage from one place.
                </p>
            </div>
        </div>

        <div class="reportorial-timeline-grid">
            @foreach ($timelineCards as $timelineCard)
                <article class="reportorial-timeline-card">
                    <div class="reportorial-timeline-card__header">
                        <span class="reportorial-timeline-card__badge">{{ $timelineCard['badge'] }}</span>
                        <i class="{{ $timelineCard['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <h3>{{ $timelineCard['title'] }}</h3>
                    <p>{{ $timelineCard['description'] }}</p>
                    <div class="reportorial-timeline-card__items">
                        @forelse ($timelineCard['items'] as $item)
                            @php
                                $opensDeadlineModal = !empty($item['route']);
                            @endphp
                            @if (!empty($item['route']))
                                <a
                                    href="{{ $item['route'] }}"
                                    class="reportorial-timeline-card__item-link"
                                    data-confirm-skip="true"
                                    @if ($opensDeadlineModal)
                                        data-deadline-modal-trigger="true"
                                        data-deadline-aspect="{{ $item['aspect'] }}"
                                        data-deadline-label="{{ $item['label'] }}"
                                        data-deadline-timeline="{{ $timelineCard['badge'] }}"
                                        data-deadline-draft-period="{{ $item['saved_period'] ?? '' }}"
                                        data-deadline-draft-year="{{ $item['saved_year'] ?? '' }}"
                                        data-deadline-draft-date="{{ $item['saved_date'] ?? '' }}"
                                        data-deadline-draft-time="{{ $item['saved_time'] ?? '' }}"
                                    @endif
                                >
                                    <div class="reportorial-timeline-card__item-main">
                                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                        <div class="reportorial-timeline-card__item-copy">
                                            <span class="reportorial-timeline-card__item-title">{{ $item['label'] }}</span>
                                            @if ($opensDeadlineModal)
                                                <span class="reportorial-timeline-card__item-status" data-deadline-status-for="{{ $item['aspect'] }}">
                                                    {{ $item['saved_status_text'] !== '' ? $item['saved_status_text'] : 'Click to set deadline' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <i class="fas fa-arrow-right reportorial-timeline-card__item-arrow" aria-hidden="true"></i>
                                </a>
                            @else
                                <div class="reportorial-timeline-card__item">
                                    <div class="reportorial-timeline-card__item-main">
                                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                        <div class="reportorial-timeline-card__item-copy">
                                            <span class="reportorial-timeline-card__item-title">{{ $item['label'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="reportorial-timeline-card__item reportorial-timeline-card__item--empty">
                                No submenu items are currently configured for this timeline.
                            </div>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>

        <a href="{{ route('utilities.deadlines-configuration.index') }}" class="reportorial-back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Deadlines Configuration</span>
        </a>
    </section>

    <div class="deadline-modal" id="deadlineDraftModal" hidden>
        <div class="deadline-modal__backdrop" data-deadline-close></div>
        <div class="deadline-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="deadlineDraftModalTitle">
            <div class="deadline-modal__header">
                <div>
                    <span class="deadline-modal__eyebrow">Deadline Configuration</span>
                    <h2 id="deadlineDraftModalTitle">Set Deadline</h2>
                    <p id="deadlineDraftModalDescription">Configure and save a deadline for this requirement.</p>
                </div>
                <button type="button" class="deadline-modal__close" data-deadline-close aria-label="Close deadline modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form class="deadline-modal__form" id="deadlineDraftForm">
                <div class="deadline-modal__field-grid">
                    <div class="deadline-modal__field deadline-modal__field--full">
                        <label for="deadlineDraftRequirement">Requirement</label>
                        <input type="text" id="deadlineDraftRequirement" readonly>
                    </div>
                    <div class="deadline-modal__field">
                        <label for="deadlineDraftYear">Reporting Year</label>
                        <select id="deadlineDraftYear" required>
                            @for ($year = now()->year + 3; $year >= 2020; $year--)
                                <option value="{{ $year }}" @selected($year === (int) now()->year)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="deadline-modal__field">
                        <label for="deadlineDraftDate">Deadline Date</label>
                        <input type="date" id="deadlineDraftDate" required>
                    </div>
                    <div class="deadline-modal__field deadline-modal__field--timeline">
                        <label for="deadlineDraftTimeline">Reporting Period</label>
                        <select id="deadlineDraftTimeline" required></select>
                    </div>
                    <div class="deadline-modal__field">
                        <label for="deadlineDraftTime">Deadline Time</label>
                        <input type="time" id="deadlineDraftTime" required>
                    </div>
                </div>

                <div class="deadline-modal__history">
                    <div class="deadline-modal__history-header">
                        <h3>Saved Deadlines</h3>
                        <span id="deadlineDraftHistoryYearLabel">CY {{ now()->year }}</span>
                    </div>
                    <div class="deadline-modal__history-table-wrap">
                        <table class="deadline-modal__history-table">
                            <thead>
                                <tr>
                                    <th>Reporting Period</th>
                                    <th>Deadline Date &amp; Time</th>
                                    <th>Set By</th>
                                </tr>
                            </thead>
                            <tbody id="deadlineDraftHistoryBody">
                                <tr>
                                    <td colspan="3">Select a reporting year to view saved deadlines.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="deadline-modal__footer">
                    <a href="#" class="deadline-modal__link" id="deadlineDraftOpenRoute">
                        <i class="fas fa-up-right-from-square"></i>
                        <span>Open Report Page</span>
                    </a>
                    <div class="deadline-modal__actions">
                        <button type="button" class="deadline-modal__button deadline-modal__button--secondary" data-deadline-close>Cancel</button>
                        <button type="submit" class="deadline-modal__button deadline-modal__button--primary" data-confirm-skip="true">Save Deadline</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .reportorial-shell {
            background: #ffffff;
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .reportorial-shell--lgu {
            background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
            border-color: #93b7f3;
        }

        .reportorial-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 18px;
        }

        .reportorial-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #dbeafe;
            color: #002c76;
            flex: 0 0 auto;
        }

        .reportorial-header h2 {
            margin: 0;
            color: #002c76;
            font-size: 20px;
        }

        .reportorial-header p {
            margin: 6px 0 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.7;
        }

        .reportorial-timeline-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .reportorial-timeline-card {
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.08);
        }

        .reportorial-timeline-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            color: #1e3a8a;
        }

        .reportorial-timeline-card__badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .reportorial-timeline-card h3 {
            margin: 0 0 8px;
            color: #1e3a8a;
            font-size: 15px;
        }

        .reportorial-timeline-card p {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .reportorial-timeline-card__items {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .reportorial-timeline-card__item {
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #f8fbff;
            padding: 10px 12px;
            color: #334155;
            font-size: 12px;
            line-height: 1.5;
        }

        .reportorial-timeline-card__item-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #f8fbff;
            padding: 10px 12px;
            color: #334155;
            text-decoration: none;
            transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        }

        .reportorial-timeline-card__item-link:hover {
            background: #eef4ff;
            border-color: #93c5fd;
            transform: translateY(-1px);
        }

        .reportorial-timeline-card__item-main {
            display: inline-flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
        }

        .reportorial-timeline-card__item-main i {
            color: #1d4ed8;
            width: 14px;
            text-align: center;
            flex: 0 0 auto;
        }

        .reportorial-timeline-card__item-main span {
            color: #334155;
            font-size: 12px;
            line-height: 1.5;
        }

        .reportorial-timeline-card__item-copy {
            display: grid;
            gap: 3px;
        }

        .reportorial-timeline-card__item-title {
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.5;
        }

        .reportorial-timeline-card__item-status {
            color: #1d4ed8;
            font-size: 11px;
            line-height: 1.4;
        }

        .reportorial-timeline-card__item-arrow {
            color: #94a3b8;
            font-size: 11px;
            flex: 0 0 auto;
        }

        .reportorial-timeline-card__item--empty {
            color: #64748b;
            font-style: italic;
            background: #f8fafc;
            border-style: dashed;
        }

        .reportorial-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #93b7f3;
            background: #ffffff;
            color: #002c76;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .reportorial-back-link:hover {
            background: #eef4ff;
        }

        .deadline-modal[hidden] {
            display: none;
        }

        .deadline-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
        }

        .deadline-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(2px);
        }

        .deadline-modal__dialog {
            position: relative;
            width: min(100%, 840px);
            max-height: calc(100vh - 48px);
            margin: 24px auto;
            background:
                linear-gradient(180deg, rgba(239, 246, 255, 0.92) 0%, rgba(255, 255, 255, 1) 24%),
                #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(191, 219, 254, 0.9);
            box-shadow: 0 36px 80px rgba(15, 23, 42, 0.32);
            padding: 28px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1;
        }

        .deadline-modal__dialog::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 6px;
            background: linear-gradient(90deg, #0a4cb3 0%, #2563eb 50%, #60a5fa 100%);
        }

        .deadline-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding-top: 4px;
        }

        .deadline-modal__eyebrow {
            display: inline-block;
            margin-bottom: 6px;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .deadline-modal__header h2 {
            margin: 0;
            color: #002c76;
            font-size: 22px;
            line-height: 1.25;
        }

        .deadline-modal__header p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .deadline-modal__close {
            width: 42px;
            height: 42px;
            border: 1px solid #c7ddff;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.85);
            color: #1d4ed8;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.12);
            transition: transform 0.16s ease, background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease;
        }

        .deadline-modal__close:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #0f4fb8;
            transform: translateY(-1px);
        }

        .deadline-modal__close:focus-visible,
        .deadline-modal__button:focus-visible,
        .deadline-modal__link:focus-visible,
        .deadline-modal__field input:focus-visible,
        .deadline-modal__field select:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
        }

        .deadline-modal__form {
            display: grid;
            gap: 18px;
            overflow: auto;
            padding-right: 2px;
        }

        .deadline-modal__field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 16px;
        }

        .deadline-modal__field {
            display: grid;
            gap: 6px;
        }

        .deadline-modal__field--full {
            grid-column: 1 / -1;
        }

        .deadline-modal__field--timeline {
            grid-column: auto;
        }

        .deadline-modal__field label {
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .deadline-modal__field input,
        .deadline-modal__field select {
            width: 100%;
            min-height: 46px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            color: #0f172a;
            padding: 10px 14px;
            font-size: 13px;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .deadline-modal__field input[readonly] {
            background: #f8fafc;
            color: #475569;
        }

        .deadline-modal__field select:disabled {
            background: #f8fafc;
            color: #475569;
        }

        .deadline-modal__history {
            display: grid;
            gap: 12px;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            background: linear-gradient(180deg, #f8fbff 0%, #f3f8ff 100%);
            padding: 16px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
        }

        .deadline-modal__history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .deadline-modal__history-header h3 {
            margin: 0;
            color: #002c76;
            font-size: 14px;
        }

        .deadline-modal__history-header span {
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }

        .deadline-modal__history-table-wrap {
            overflow-x: auto;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(219, 234, 254, 0.95);
        }

        .deadline-modal__history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            min-width: 520px;
        }

        .deadline-modal__history-table th,
        .deadline-modal__history-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #dbeafe;
            text-align: left;
            vertical-align: top;
        }

        .deadline-modal__history-table th {
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: #f8fbff;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .deadline-modal__history-table td {
            color: #475569;
        }

        .deadline-modal__history-meta {
            display: grid;
            gap: 2px;
            justify-items: start;
            text-align: left;
        }

        .deadline-modal__history-meta strong {
            color: inherit;
            font-size: 12px;
            font-weight: 700;
        }

        .deadline-modal__history-meta span {
            color: #64748b;
            font-size: 11px;
            font-weight: 500;
        }

        .deadline-modal__history-table tbody tr:last-child td {
            border-bottom: none;
        }

        .deadline-modal__history-table tbody tr[data-period] {
            cursor: pointer;
            transition: background-color 0.16s ease;
        }

        .deadline-modal__history-table tbody tr[data-period]:hover td,
        .deadline-modal__history-table tbody tr[data-period]:focus-visible td {
            background: #eff6ff;
        }

        .deadline-modal__history-table tbody tr.is-active td {
            background: #dbeafe;
            color: #1e3a8a;
            font-weight: 700;
        }

        .deadline-modal__history-table td[data-empty="true"] {
            color: #94a3b8;
            font-style: italic;
        }

        .deadline-modal__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding-top: 2px;
        }

        .deadline-modal__actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .deadline-modal__button,
        .deadline-modal__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            border-radius: 12px;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease, border-color 0.16s ease;
        }

        .deadline-modal__button {
            cursor: pointer;
            border: 1px solid transparent;
        }

        .deadline-modal__button--secondary {
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            border-color: #cbd5e1;
            color: #334155;
        }

        .deadline-modal__button--primary {
            background: linear-gradient(180deg, #0a4cb3 0%, #002c76 100%);
            border-color: #002c76;
            color: #ffffff;
            box-shadow: 0 14px 24px rgba(0, 44, 118, 0.22);
        }

        .deadline-modal__button:hover,
        .deadline-modal__link:hover {
            transform: translateY(-1px);
        }

        .deadline-modal__link {
            border: 1px solid #bfdbfe;
            background: rgba(255, 255, 255, 0.9);
            color: #1d4ed8;
        }

        .deadline-modal__button--primary:hover {
            box-shadow: 0 16px 28px rgba(0, 44, 118, 0.28);
        }

        .deadline-modal__button--secondary:hover,
        .deadline-modal__link:hover {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .deadline-page-toast {
            position: fixed;
            right: 20px;
            top: 20px;
            z-index: 1300;
            min-width: 260px;
            max-width: min(420px, calc(100vw - 32px));
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
            opacity: 0;
            transform: translateY(12px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .deadline-page-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .deadline-page-toast--error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        @media (max-width: 640px) {
            .reportorial-shell {
                padding: 16px;
            }

            .reportorial-header {
                flex-direction: column;
            }

            .deadline-modal__dialog {
                width: calc(100% - 16px);
                margin: 8px auto;
                max-height: calc(100vh - 16px);
                padding: 18px;
                border-radius: 18px;
            }

            .deadline-modal__field-grid {
                grid-template-columns: 1fr;
            }

            .deadline-modal__field--timeline {
                grid-column: auto;
            }

            .deadline-modal__history-table {
                min-width: 0;
            }

            .deadline-modal__footer,
            .deadline-modal__actions {
                width: 100%;
            }

            .deadline-modal__actions {
                justify-content: stretch;
            }

            .deadline-modal__button,
            .deadline-modal__link {
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .deadline-modal__dialog {
                width: min(100%, 720px);
            }
        }
    </style>

    <script>
        (() => {
            const modal = document.getElementById('deadlineDraftModal');
            const form = document.getElementById('deadlineDraftForm');
            const requirementInput = document.getElementById('deadlineDraftRequirement');
            const timelineInput = document.getElementById('deadlineDraftTimeline');
            const yearInput = document.getElementById('deadlineDraftYear');
            const dateInput = document.getElementById('deadlineDraftDate');
            const timeInput = document.getElementById('deadlineDraftTime');
            const description = document.getElementById('deadlineDraftModalDescription');
            const openRouteLink = document.getElementById('deadlineDraftOpenRoute');
            const triggers = document.querySelectorAll('[data-deadline-modal-trigger="true"]');
            const historyYearLabel = document.getElementById('deadlineDraftHistoryYearLabel');
            const historyBody = document.getElementById('deadlineDraftHistoryBody');
            const submitButton = form ? form.querySelector('button[type="submit"]') : null;
            const initialSubmitButtonText = submitButton ? submitButton.textContent.trim() : 'Save Deadline';
            const defaultYear = yearInput ? yearInput.value : '';
            const saveUrl = @json($deadlineSaveUrl);
            const savedDeadlines = @json($savedDeadlines);
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            const periodOptions = {
                quarterly: [
                    { value: 'Q1', label: 'Q1' },
                    { value: 'Q2', label: 'Q2' },
                    { value: 'Q3', label: 'Q3' },
                    { value: 'Q4', label: 'Q4' },
                ],
                annual: [
                    { value: 'Annual', label: 'Annual' },
                ],
                monthly: [
                    { value: 'January', label: 'January' },
                    { value: 'February', label: 'February' },
                    { value: 'March', label: 'March' },
                    { value: 'April', label: 'April' },
                    { value: 'May', label: 'May' },
                    { value: 'June', label: 'June' },
                    { value: 'July', label: 'July' },
                    { value: 'August', label: 'August' },
                    { value: 'September', label: 'September' },
                    { value: 'October', label: 'October' },
                    { value: 'November', label: 'November' },
                    { value: 'December', label: 'December' },
                ],
            };

            if (!modal || !form || !timeInput || triggers.length === 0) {
                return;
            }

            let activeTrigger = null;
            let fallbackToastTimeout = null;
            const currentMonthLabel = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(new Date());

            const setSavingState = (isSaving) => {
                if (!submitButton) {
                    return;
                }

                submitButton.disabled = isSaving;
                submitButton.textContent = isSaving ? 'Saving...' : initialSubmitButtonText;
            };

            const getActiveAspect = () => {
                if (!activeTrigger) {
                    return '';
                }

                return (activeTrigger.dataset.deadlineAspect || '').trim().toLowerCase();
            };

            const getLatestSavedEntryFromTrigger = (trigger) => {
                if (!trigger) {
                    return null;
                }

                const reportingPeriod = (trigger.dataset.deadlineDraftPeriod || '').trim();
                const deadlineDate = (trigger.dataset.deadlineDraftDate || '').trim();
                const deadlineTime = (trigger.dataset.deadlineDraftTime || '').trim();
                const reportingYear = Number.parseInt(trigger.dataset.deadlineDraftYear || '', 10);

                if (!reportingPeriod || !deadlineDate || !Number.isInteger(reportingYear)) {
                    return null;
                }

                return {
                    reporting_period: reportingPeriod,
                    reporting_year: reportingYear,
                    deadline_date: deadlineDate,
                    deadline_time: deadlineTime,
                };
            };

            const getSavedDeadlineEntry = (aspect, reportingYear, reportingPeriod) => {
                if (!aspect || !reportingYear || !reportingPeriod) {
                    return null;
                }

                const aspectDeadlines = savedDeadlines[aspect];
                if (!aspectDeadlines) {
                    return null;
                }

                const yearDeadlines = aspectDeadlines[String(reportingYear)];
                if (!yearDeadlines) {
                    return null;
                }

                return yearDeadlines[reportingPeriod] || null;
            };

            const selectHistoryPeriod = (reportingPeriod, shouldFocusDate = false) => {
                if (!reportingPeriod) {
                    return;
                }

                const optionExists = Array.from(timelineInput.options).some((option) => option.value === reportingPeriod);
                if (!optionExists) {
                    return;
                }

                timelineInput.value = reportingPeriod;
                syncDeadlineInputsFromSavedDeadline();
                renderHistoryTable();

                if (shouldFocusDate) {
                    dateInput.focus();
                }
            };

            const renderHistoryTable = () => {
                if (!historyBody) {
                    return;
                }

                historyBody.innerHTML = '';

                if (!activeTrigger) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 3;
                    cell.textContent = 'Select a requirement to view saved deadlines.';
                    row.appendChild(cell);
                    historyBody.appendChild(row);
                    return;
                }

                const selectedYear = yearInput.value || '';
                const timeline = activeTrigger.dataset.deadlineTimeline || '';
                const options = getPeriodOptions(timeline);
                const aspect = getActiveAspect();

                if (historyYearLabel) {
                    historyYearLabel.textContent = selectedYear !== '' ? 'CY ' + selectedYear : '';
                }

                if (options.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 3;
                    cell.textContent = 'No reporting periods available for this requirement.';
                    row.appendChild(cell);
                    historyBody.appendChild(row);
                    return;
                }

                options.forEach((optionData) => {
                    const savedEntry = getSavedDeadlineEntry(aspect, selectedYear, optionData.value);
                    const row = document.createElement('tr');
                    row.dataset.period = optionData.value;
                    row.tabIndex = 0;
                    row.title = 'Click to edit ' + optionData.label;
                    if (timelineInput.value === optionData.value) {
                        row.classList.add('is-active');
                    }

                    const periodCell = document.createElement('td');
                    periodCell.textContent = optionData.label;

                    const dateCell = document.createElement('td');
                    if (savedEntry && savedEntry.deadline_date) {
                        dateCell.textContent = savedEntry.deadline_display || formatDeadlineDisplay(savedEntry.deadline_date, savedEntry.deadline_time || '');
                    } else {
                        dateCell.textContent = 'Not set';
                        dateCell.dataset.empty = 'true';
                    }

                    const setByCell = document.createElement('td');
                    if (savedEntry && (savedEntry.updated_by_name || savedEntry.updated_at_display)) {
                        const meta = document.createElement('div');
                        meta.className = 'deadline-modal__history-meta';

                        const name = document.createElement('strong');
                        name.textContent = savedEntry.updated_by_name || '—';

                        const timestamp = document.createElement('span');
                        timestamp.textContent = savedEntry.updated_at_display || '—';

                        meta.appendChild(name);
                        meta.appendChild(timestamp);
                        setByCell.appendChild(meta);
                    } else {
                        setByCell.textContent = '—';
                        setByCell.dataset.empty = 'true';
                    }

                    row.appendChild(periodCell);
                    row.appendChild(dateCell);
                    row.appendChild(setByCell);
                    historyBody.appendChild(row);
                });
            };

            const getPeriodOptions = (timeline) => {
                const normalizedTimeline = (timeline || '').trim().toLowerCase();
                const options = periodOptions[normalizedTimeline];

                if (Array.isArray(options) && options.length > 0) {
                    return options;
                }

                const fallbackLabel = (timeline || '').trim();
                return fallbackLabel !== ''
                    ? [{ value: fallbackLabel, label: fallbackLabel }]
                    : [];
            };

            const getSelectedPeriodLabel = () => {
                const selectedOption = timelineInput.options[timelineInput.selectedIndex] || null;
                return selectedOption ? selectedOption.textContent || selectedOption.value : '';
            };

            const syncTimelineOptions = (trigger, preferredPeriod = '') => {
                const timeline = trigger.dataset.deadlineTimeline || '';
                const normalizedTimeline = (timeline || '').trim().toLowerCase();
                const options = getPeriodOptions(timeline);
                const savedPeriod = preferredPeriod || trigger.dataset.deadlineDraftPeriod || '';

                timelineInput.innerHTML = '';

                options.forEach((optionData) => {
                    const option = document.createElement('option');
                    option.value = optionData.value;
                    option.textContent = optionData.label;
                    timelineInput.appendChild(option);
                });

                const fallbackValue = normalizedTimeline === 'monthly'
                    ? (options.find((option) => option.value === currentMonthLabel)?.value || (options[0] ? options[0].value : ''))
                    : (options[0] ? options[0].value : '');
                const hasSavedValue = savedPeriod !== '' && options.some((option) => option.value === savedPeriod);

                timelineInput.value = hasSavedValue ? savedPeriod : fallbackValue;
                timelineInput.disabled = options.length <= 1;
            };

            const syncDeadlineInputsFromSavedDeadline = () => {
                const aspect = getActiveAspect();
                if (aspect === '') {
                    return;
                }

                const savedEntry = getSavedDeadlineEntry(aspect, yearInput.value, timelineInput.value);
                dateInput.value = savedEntry && savedEntry.deadline_date ? savedEntry.deadline_date : '';
                timeInput.value = savedEntry && savedEntry.deadline_time ? savedEntry.deadline_time : '';
            };

            const updateSavedDeadlineCache = (record) => {
                const aspect = (record.aspect || '').trim().toLowerCase();
                if (aspect === '') {
                    return;
                }

                const reportingYear = String(record.reporting_year || '');
                const reportingPeriod = (record.reporting_period || '').trim();
                if (reportingYear === '' || reportingPeriod === '') {
                    return;
                }

                if (!savedDeadlines[aspect]) {
                    savedDeadlines[aspect] = {};
                }

                if (!savedDeadlines[aspect][reportingYear]) {
                    savedDeadlines[aspect][reportingYear] = {};
                }

                savedDeadlines[aspect][reportingYear][reportingPeriod] = record;
            };

            const closeModal = () => {
                modal.hidden = true;
                document.body.style.overflow = '';
                activeTrigger = null;
                setSavingState(false);
            };

            const openModal = (trigger) => {
                activeTrigger = trigger;
                const latestSavedEntry = getLatestSavedEntryFromTrigger(trigger);

                requirementInput.value = trigger.dataset.deadlineLabel || '';
                yearInput.value = latestSavedEntry ? String(latestSavedEntry.reporting_year) : defaultYear;
                syncTimelineOptions(trigger, latestSavedEntry ? latestSavedEntry.reporting_period : '');
                syncDeadlineInputsFromSavedDeadline();
                renderHistoryTable();
                description.textContent = 'Configure and save a deadline for ' + (trigger.dataset.deadlineLabel || 'this requirement') + '.';
                openRouteLink.href = trigger.getAttribute('href') || '#';
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            };

            const formatDate = (value) => {
                const date = new Date(value + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            };

            const formatTime = (value) => {
                const normalizedValue = String(value || '').trim();
                if (normalizedValue === '') {
                    return '';
                }

                const isoTime = normalizedValue.length === 5 ? normalizedValue + ':00' : normalizedValue;
                const time = new Date('1970-01-01T' + isoTime);
                if (Number.isNaN(time.getTime())) {
                    return normalizedValue;
                }

                return time.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                });
            };

            const formatDeadlineDisplay = (dateValue, timeValue = '') => {
                const formattedDate = formatDate(dateValue);
                const formattedTime = formatTime(timeValue);

                return formattedTime !== ''
                    ? formattedDate + ' ' + formattedTime
                    : formattedDate;
            };

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    openModal(trigger);
                });
            });

            yearInput.addEventListener('change', () => {
                if (!activeTrigger) {
                    return;
                }

                syncDeadlineInputsFromSavedDeadline();
                renderHistoryTable();
            });

            timelineInput.addEventListener('change', () => {
                if (!activeTrigger) {
                    return;
                }

                syncDeadlineInputsFromSavedDeadline();
                renderHistoryTable();
            });

            historyBody.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-period]');
                if (!row) {
                    return;
                }

                selectHistoryPeriod(row.dataset.period || '', true);
            });

            historyBody.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                const row = event.target.closest('tr[data-period]');
                if (!row) {
                    return;
                }

                event.preventDefault();
                selectHistoryPeriod(row.dataset.period || '', true);
            });

            modal.querySelectorAll('[data-deadline-close]').forEach((element) => {
                element.addEventListener('click', () => closeModal());
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });

            const confirmDeadlineSave = (message) => {
                return new Promise((resolve) => {
                    if (typeof window.openConfirmationModal === 'function') {
                        window.openConfirmationModal(message, () => resolve(true), () => resolve(false));
                        return;
                    }

                    resolve(window.confirm(message));
                });
            };

            const showDeadlineToast = (message, type = 'success') => {
                const normalizedMessage = typeof message === 'string' && message.trim() !== ''
                    ? message.trim()
                    : 'Deadline saved successfully.';

                if (window.AppUI && typeof window.AppUI.toast === 'function') {
                    window.AppUI.toast(normalizedMessage, type, 3200);
                    return;
                }

                let toast = document.getElementById('deadlinePageToast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'deadlinePageToast';
                    toast.className = 'deadline-page-toast';
                    toast.setAttribute('role', 'status');
                    toast.setAttribute('aria-live', 'polite');
                    document.body.appendChild(toast);
                }

                toast.textContent = normalizedMessage;
                toast.classList.toggle('deadline-page-toast--error', type === 'error');
                toast.classList.add('is-visible');

                if (fallbackToastTimeout !== null) {
                    window.clearTimeout(fallbackToastTimeout);
                }

                fallbackToastTimeout = window.setTimeout(() => {
                    toast.classList.remove('is-visible');
                }, 3200);
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!activeTrigger) {
                    closeModal();
                    return;
                }

                if (!dateInput.value) {
                    dateInput.focus();
                    return;
                }

                if (!timeInput.value) {
                    timeInput.focus();
                    return;
                }

                const aspect = activeTrigger.dataset.deadlineAspect || '';
                if (!saveUrl || !csrfToken || aspect === '') {
                    showDeadlineToast('Unable to save deadline because the save endpoint is not available.', 'error');
                    return;
                }

                const payload = {
                    aspect: aspect,
                    reporting_year: yearInput.value,
                    reporting_period: timelineInput.value,
                    deadline_date: dateInput.value,
                    deadline_time: timeInput.value,
                };
                const existingEntry = getSavedDeadlineEntry(aspect, payload.reporting_year, payload.reporting_period);
                const actionLabel = existingEntry ? 'update' : 'save';
                const confirmationMessage = 'Do you want to ' + actionLabel + ' the deadline for '
                    + requirementInput.value + ' (' + getSelectedPeriodLabel() + ', CY ' + payload.reporting_year + ')?';
                const confirmed = await confirmDeadlineSave(confirmationMessage);
                if (!confirmed) {
                    return;
                }

                setSavingState(true);

                try {
                    const response = await fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const firstError = data.errors
                            ? Object.values(data.errors).flat().find((message) => typeof message === 'string' && message.trim() !== '')
                            : '';

                        throw new Error(firstError || data.message || 'Unable to save deadline.');
                    }

                    const savedRecord = data.record || payload;
                    const status = document.querySelector('[data-deadline-status-for="' + aspect + '"]');
                    const statusText = typeof data.status_text === 'string' && data.status_text.trim() !== ''
                        ? data.status_text
                        : 'Saved deadline: ' + formatDeadlineDisplay(savedRecord.deadline_date, savedRecord.deadline_time || timeInput.value) + ' (' + getSelectedPeriodLabel() + ', CY ' + savedRecord.reporting_year + ')';

                    updateSavedDeadlineCache(savedRecord);
                    activeTrigger.dataset.deadlineDraftPeriod = savedRecord.reporting_period || timelineInput.value;
                    activeTrigger.dataset.deadlineDraftYear = String(savedRecord.reporting_year || yearInput.value);
                    activeTrigger.dataset.deadlineDraftDate = savedRecord.deadline_date || dateInput.value;
                    activeTrigger.dataset.deadlineDraftTime = savedRecord.deadline_time || timeInput.value;
                    selectHistoryPeriod(savedRecord.reporting_period || timelineInput.value);
                    renderHistoryTable();
                    setSavingState(false);

                    if (status) {
                        status.textContent = statusText;
                    }

                    showDeadlineToast(typeof data.message === 'string' ? data.message : 'Deadline saved successfully.', 'success');
                } catch (error) {
                    setSavingState(false);
                    showDeadlineToast(error instanceof Error ? error.message : 'Unable to save deadline.', 'error');
                }
            });
        })();
    </script>
@endsection
