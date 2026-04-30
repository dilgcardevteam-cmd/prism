@extends('layouts.dashboard')

@section('title', 'DILG MC No. 2018-19 Encoding Form')
@section('page-title', 'DILG MC No. 2018-19 Encoding Form')

@section('content')
    @php
        $quarterLabels = ['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'];
        $quarterWindows = [
            'Q1' => 'January - March',
            'Q2' => 'April - June',
            'Q3' => 'July - September',
            'Q4' => 'October - December',
        ];
        $tableColumns = [
            ['key' => 'lgu_name', 'label' => 'Name of LGU with Road/Public Works', 'width' => '220px'],
            ['key' => 'project_title', 'label' => 'Project Title', 'width' => '240px'],
            ['key' => 'timeline_exceeded', 'label' => 'The project exceeded the timeline based on the POW (Yes/No)', 'width' => '180px'],
            ['key' => 'target_completion_date', 'label' => 'Target Date of Completion', 'width' => '170px'],
            ['key' => 'catch_up_mandated', 'label' => 'The LGU mandated the contractor/s to catch-up within 30 days with the agreed project schedule (if there are delays) Yes/No/NA', 'width' => '190px'],
            ['key' => 'revised_target_completion_date', 'label' => 'Revised Target Date of Completion', 'width' => '170px'],
            ['key' => 'project_status', 'label' => 'Status of Project', 'width' => '180px'],
            ['key' => 'remarks', 'label' => 'Remarks', 'width' => '240px'],
        ];
        $columnWidthStorageKey = 'dilg-mc-2018-19-encoding-column-widths';
    @endphp

    <div class="content-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
        <div>
            <h1>{{ $office }} - {{ $quarterLabel }} Encoding Form</h1>
            <p>Encode the quarterly template based on the uploaded DILG MC No. 2018-19 workbook format.</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.show', ['office' => $office, 'year' => $reportingYear]) }}"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                <i class="fas fa-eye"></i>
                <span>Back to Upload History</span>
            </a>
            <a href="{{ route('reports.quarterly.dilg-mc-2018-19', ['year' => $reportingYear]) }}"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#6b7280;color:#fff;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to List</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;padding:14px 16px;border-radius:10px;margin-bottom:18px;">
            {{ session('success') }}
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
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;margin-bottom:4px;">Quarter Scope</div>
                <div style="font-size:15px;font-weight:600;color:#111827;">{{ $quarterLabel }} <span style="font-size:12px;color:#6b7280;font-weight:500;">({{ $quarterWindows[$quarter] ?? '' }})</span></div>
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

        <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
            @foreach ($quarterLabels as $quarterCode => $label)
                <a href="{{ route('reports.quarterly.dilg-mc-2018-19.edit', ['office' => $office, 'quarter' => $quarterCode, 'year' => $reportingYear]) }}"
                    style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;text-decoration:none;font-size:12px;font-weight:700;border:1px solid {{ $quarterCode === $quarter ? '#1d4ed8' : '#d1d5db' }};background:{{ $quarterCode === $quarter ? '#dbeafe' : '#fff' }};color:{{ $quarterCode === $quarter ? '#1d4ed8' : '#374151' }};">
                    <span>{{ $label }}</span>
                    <span style="font-size:11px;opacity:.85;">{{ $quarterWindows[$quarterCode] ?? '' }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('reports.quarterly.dilg-mc-2018-19.save-encoding', ['office' => $office, 'quarter' => $quarter, 'year' => $reportingYear]) }}">
        @csrf
        <input type="hidden" name="year" value="{{ $reportingYear }}">

        <div style="background:#fff;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">
            <div style="padding:18px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px;font-weight:700;color:#111827;">{{ $quarterLabel }} Template</div>
                    <div style="font-size:13px;color:#6b7280;margin-top:4px;">The column layout follows the Excel template you provided.</div>
                    @if ($encoding && $encoding->last_saved_at)
                        <div style="font-size:12px;color:#047857;margin-top:6px;">
                            Last saved: {{ $encoding->last_saved_at->setTimezone(config('app.timezone'))->format('M d, Y h:i A') }}
                        </div>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" id="add-encoding-row"
                        style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-plus"></i>
                        <span>Add Row</span>
                    </button>
                    <button type="submit" class="encoding-save-btn"
                        style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-save"></i>
                        <span>Save Encoding</span>
                    </button>
                    <details class="encoding-export-dropdown" style="position:relative;">
                        <summary class="encoding-export-trigger" style="list-style:none;display:inline-flex;align-items:center;gap:8px;padding:10px 16px;color:#fff;border:1px solid rgba(255,255,255,.16);border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-file-export"></i>
                            <span>Export</span>
                            <i class="fas fa-chevron-down" style="font-size:11px;"></i>
                        </summary>
                        <div style="position:absolute;right:0;top:calc(100% + 8px);min-width:160px;background:#fff;border:1px solid #fed7aa;border-radius:12px;box-shadow:0 12px 28px rgba(15,23,42,.16);padding:8px;z-index:20;">
                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.export-encoding', ['office' => $office, 'quarter' => $quarter, 'year' => $reportingYear, 'format' => 'excel']) }}"
                                style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;color:#9a3412;text-decoration:none;font-size:12px;font-weight:700;">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </a>
                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.export-encoding', ['office' => $office, 'quarter' => $quarter, 'year' => $reportingYear, 'format' => 'pdf']) }}"
                                style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;color:#9a3412;text-decoration:none;font-size:12px;font-weight:700;">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </a>
                            <a href="{{ route('reports.quarterly.dilg-mc-2018-19.export-encoding', ['office' => $office, 'quarter' => $quarter, 'year' => $reportingYear, 'format' => 'csv']) }}"
                                style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;color:#9a3412;text-decoration:none;font-size:12px;font-weight:700;">
                                <i class="fas fa-file-csv"></i>
                                <span>CSV</span>
                            </a>
                        </div>
                    </details>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table id="dilg-mc-2018-19-encoding-table" style="width:100%;border-collapse:collapse;min-width:1500px;table-layout:fixed;">
                    <colgroup>
                        <col style="width:56px;min-width:56px;">
                        @foreach ($tableColumns as $column)
                            <col data-column-key="{{ $column['key'] }}" style="width:{{ $column['width'] }};min-width:140px;">
                        @endforeach
                        <col style="width:72px;min-width:72px;">
                    </colgroup>
                    <thead>
                        <tr style="background:#002c76;color:#fff;">
                            <th style="padding:14px 12px;width:48px;text-align:center;font-size:11px;font-weight:700;border-right:1px solid rgba(255,255,255,.18);">#</th>
                            @foreach ($tableColumns as $column)
                                <th data-resizable-column="{{ $column['key'] }}" style="position:relative;padding:14px 18px 14px 12px;text-align:center;font-size:11px;font-weight:700;line-height:1.45;border-right:1px solid rgba(255,255,255,.18);vertical-align:middle;">
                                    <span style="display:block;text-align:center;">{{ $column['label'] }}</span>
                                    <span class="encoding-column-resizer" title="Drag to resize column"></span>
                                </th>
                            @endforeach
                            <th style="padding:14px 12px;width:72px;text-align:center;font-size:11px;font-weight:700;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="encoding-rows-body">
                        @foreach ($formRows as $rowIndex => $row)
                            <tr class="encoding-row">
                                <td style="padding:10px 8px;border:1px solid #e5e7eb;background:#f8fafc;text-align:center;font-size:12px;font-weight:700;color:#475569;">
                                    <span class="encoding-row-number">{{ $rowIndex + 1 }}</span>
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;background:#f8fbff;">
                                    <input type="text" name="rows[{{ $rowIndex }}][lgu_name]" value="{{ old("rows.$rowIndex.lgu_name", $row['lgu_name'] ?? $office) }}" readonly
                                        style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#eef2ff;color:#334155;font-size:12px;font-weight:600;">
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <textarea name="rows[{{ $rowIndex }}][project_title]" rows="2"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;resize:vertical;">{{ old("rows.$rowIndex.project_title", $row['project_title'] ?? '') }}</textarea>
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <select name="rows[{{ $rowIndex }}][timeline_exceeded]"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                                        <option value="">Select</option>
                                        @foreach (['Yes', 'No'] as $option)
                                            <option value="{{ $option }}" @selected(old("rows.$rowIndex.timeline_exceeded", $row['timeline_exceeded'] ?? '') === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <input type="date" name="rows[{{ $rowIndex }}][target_completion_date]" value="{{ old("rows.$rowIndex.target_completion_date", $row['target_completion_date'] ?? '') }}"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <select name="rows[{{ $rowIndex }}][catch_up_mandated]"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                                        <option value="">Select</option>
                                        @foreach (['Yes', 'No', 'NA'] as $option)
                                            <option value="{{ $option }}" @selected(old("rows.$rowIndex.catch_up_mandated", $row['catch_up_mandated'] ?? '') === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <input type="date" name="rows[{{ $rowIndex }}][revised_target_completion_date]" value="{{ old("rows.$rowIndex.revised_target_completion_date", $row['revised_target_completion_date'] ?? '') }}"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <input type="text" name="rows[{{ $rowIndex }}][project_status]" value="{{ old("rows.$rowIndex.project_status", $row['project_status'] ?? '') }}"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;">
                                    <textarea name="rows[{{ $rowIndex }}][remarks]" rows="2"
                                        style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;resize:vertical;">{{ old("rows.$rowIndex.remarks", $row['remarks'] ?? '') }}</textarea>
                                </td>
                                <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;background:#f8fafc;">
                                    <button type="button" class="remove-encoding-row"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:none;border-radius:10px;background:#fee2e2;color:#b91c1c;cursor:pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <template id="encoding-row-template">
        <tr class="encoding-row">
            <td style="padding:10px 8px;border:1px solid #e5e7eb;background:#f8fafc;text-align:center;font-size:12px;font-weight:700;color:#475569;">
                <span class="encoding-row-number"></span>
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;background:#f8fbff;">
                <input type="text" data-field="lgu_name" readonly
                    style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#eef2ff;color:#334155;font-size:12px;font-weight:600;">
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <textarea data-field="project_title" rows="2"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;resize:vertical;"></textarea>
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <select data-field="timeline_exceeded"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                    <option value="">Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <input type="date" data-field="target_completion_date"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <select data-field="catch_up_mandated"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
                    <option value="">Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                    <option value="NA">NA</option>
                </select>
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <input type="date" data-field="revised_target_completion_date"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <input type="text" data-field="project_status"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;">
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;">
                <textarea data-field="remarks" rows="2"
                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:12px;resize:vertical;"></textarea>
            </td>
            <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;background:#f8fafc;">
                <button type="button" class="remove-encoding-row"
                    style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:none;border-radius:10px;background:#fee2e2;color:#b91c1c;cursor:pointer;">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rowsBody = document.getElementById('encoding-rows-body');
            const template = document.getElementById('encoding-row-template');
            const addRowButton = document.getElementById('add-encoding-row');
            const officeName = @json($office);
            const table = document.getElementById('dilg-mc-2018-19-encoding-table');
            const widthStorageKey = @json($columnWidthStorageKey);
            const minColumnWidth = 140;

            function renumberRows() {
                Array.from(rowsBody.querySelectorAll('.encoding-row')).forEach(function (row, index) {
                    row.querySelector('.encoding-row-number').textContent = String(index + 1);
                    row.querySelectorAll('[name]').forEach(function (field) {
                        const fieldName = field.getAttribute('name');
                        const updatedName = fieldName.replace(/rows\[\d+\]/, 'rows[' + index + ']');
                        field.setAttribute('name', updatedName);
                    });
                });
            }

            function bindRemoveButtons(scope) {
                scope.querySelectorAll('.remove-encoding-row').forEach(function (button) {
                    if (button.dataset.bound === '1') {
                        return;
                    }

                    button.dataset.bound = '1';
                    button.addEventListener('click', function () {
                        const allRows = rowsBody.querySelectorAll('.encoding-row');
                        if (allRows.length <= 1) {
                            const fields = rowsBody.querySelectorAll('input:not([readonly]), textarea, select');
                            fields.forEach(function (field) {
                                field.value = '';
                            });
                            return;
                        }

                        button.closest('.encoding-row')?.remove();
                        renumberRows();
                    });
                });
            }

            function readStoredWidths() {
                try {
                    const raw = window.localStorage.getItem(widthStorageKey);
                    return raw ? JSON.parse(raw) : {};
                } catch (error) {
                    return {};
                }
            }

            function writeStoredWidths(widths) {
                try {
                    window.localStorage.setItem(widthStorageKey, JSON.stringify(widths));
                } catch (error) {
                }
            }

            function getColumnElement(columnKey) {
                return table?.querySelector('col[data-column-key="' + columnKey + '"]') ?? null;
            }

            function applyColumnWidth(columnKey, width) {
                const column = getColumnElement(columnKey);
                if (!column) {
                    return;
                }

                const safeWidth = Math.max(minColumnWidth, Math.round(width));
                column.style.width = safeWidth + 'px';
                column.style.minWidth = safeWidth + 'px';
            }

            function applyStoredWidths() {
                const storedWidths = readStoredWidths();
                Object.entries(storedWidths).forEach(function ([columnKey, width]) {
                    if (Number.isFinite(Number(width))) {
                        applyColumnWidth(columnKey, Number(width));
                    }
                });
            }

            function initializeColumnResizing() {
                if (!table) {
                    return;
                }

                applyStoredWidths();

                table.querySelectorAll('th[data-resizable-column]').forEach(function (headerCell) {
                    const handle = headerCell.querySelector('.encoding-column-resizer');
                    const columnKey = headerCell.dataset.resizableColumn;
                    if (!handle || !columnKey || handle.dataset.bound === '1') {
                        return;
                    }

                    handle.dataset.bound = '1';
                    handle.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        const column = getColumnElement(columnKey);
                        if (!column) {
                            return;
                        }

                        const startX = event.clientX;
                        const startWidth = column.getBoundingClientRect().width;

                        function onMouseMove(moveEvent) {
                            const nextWidth = startWidth + (moveEvent.clientX - startX);
                            applyColumnWidth(columnKey, nextWidth);
                        }

                        function onMouseUp(upEvent) {
                            document.removeEventListener('mousemove', onMouseMove);
                            document.removeEventListener('mouseup', onMouseUp);

                            const finalWidth = startWidth + (upEvent.clientX - startX);
                            const storedWidths = readStoredWidths();
                            storedWidths[columnKey] = Math.max(minColumnWidth, Math.round(finalWidth));
                            writeStoredWidths(storedWidths);
                            document.body.style.userSelect = '';
                            document.body.style.cursor = '';
                        }

                        document.body.style.userSelect = 'none';
                        document.body.style.cursor = 'col-resize';
                        document.addEventListener('mousemove', onMouseMove);
                        document.addEventListener('mouseup', onMouseUp);
                    });
                });
            }

            addRowButton?.addEventListener('click', function () {
                const clone = template.content.firstElementChild.cloneNode(true);
                const nextIndex = rowsBody.querySelectorAll('.encoding-row').length;

                clone.querySelectorAll('[data-field]').forEach(function (field) {
                    const key = field.dataset.field;
                    field.setAttribute('name', 'rows[' + nextIndex + '][' + key + ']');
                    if (key === 'lgu_name') {
                        field.value = officeName;
                    } else {
                        field.value = '';
                    }
                });

                rowsBody.appendChild(clone);
                bindRemoveButtons(clone);
                renumberRows();
            });

            bindRemoveButtons(document);
            renumberRows();
            initializeColumnResizing();
        });
    </script>

    <style>
        .encoding-column-resizer {
            position: absolute;
            top: 0;
            right: -4px;
            width: 10px;
            height: 100%;
            cursor: col-resize;
            user-select: none;
        }

        .encoding-column-resizer::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 4px;
            transform: translateY(-50%);
            width: 2px;
            height: 24px;
            border-radius: 999px;
            background: rgba(255,255,255,.5);
        }

        .encoding-column-resizer:hover::after {
            background: rgba(255,255,255,.9);
        }

        .encoding-export-dropdown summary::-webkit-details-marker {
            display: none;
        }

        .encoding-save-btn {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            box-shadow: 0 10px 18px rgba(5, 150, 105, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .encoding-save-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(5, 150, 105, 0.32);
            filter: brightness(1.03);
        }

        .encoding-export-trigger {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
            box-shadow: 0 10px 18px rgba(249, 115, 22, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .encoding-export-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(249, 115, 22, 0.32);
            filter: brightness(1.03);
        }

        .encoding-export-dropdown[open] summary {
            filter: brightness(1.04);
        }

        .encoding-export-dropdown a:hover {
            background: #fff7ed;
        }
    </style>
@endsection
