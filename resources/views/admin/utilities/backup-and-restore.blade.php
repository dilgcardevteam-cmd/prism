@extends('layouts.dashboard')

@section('title', 'Backup and Restore')
@section('page-title', 'Backup and Restore')

@section('content')
    @php
        $schedulerState = [
            'is_enabled' => old('is_enabled', $automationSetting?->is_enabled),
            'frequency' => old('frequency', $automationSetting?->frequency ?? 'daily'),
            'weekly_day' => old('weekly_day', $automationSetting?->weekly_day),
            'run_time' => old('run_time', $automationSetting?->run_time ? \Illuminate\Support\Str::of($automationSetting->run_time)->substr(0, 5) : '18:00'),
            'recipient_emails' => old('recipient_emails', $automationSetting?->recipient_emails ? implode(', ', $automationSetting->recipient_emails) : ''),
            'retention_days' => old('retention_days', $automationSetting?->retention_days),
            'encrypt_backup' => old('encrypt_backup', $automationSetting?->encrypt_backup),
        ];

        $schedulerFieldErrors = ['is_enabled', 'frequency', 'weekly_day', 'run_time', 'recipient_emails', 'retention_days', 'encrypt_backup', 'encryption_password'];
        $activeUtilityTab = 'backupRestorePanel';
        foreach ($schedulerFieldErrors as $schedulerFieldError) {
            if ($errors->has($schedulerFieldError)) {
                $activeUtilityTab = 'schedulerPanel';
                break;
            }
        }

        if (request()->query('tab') === 'scheduler') {
            $activeUtilityTab = 'schedulerPanel';
        }
    @endphp

    <div class="content-header">
        <h1>Backup and Restore</h1>
        <p>Download manual SQL backups, restore the database, and configure automated backup delivery.</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <strong style="display: block; margin-bottom: 8px;">Please resolve the following issue(s):</strong>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="project-tabs" role="tablist" aria-label="Utility sections" style="margin-top: 28px;">
        <button
            type="button"
            class="project-tab {{ $activeUtilityTab === 'backupRestorePanel' ? 'is-active' : '' }}"
            data-utility-tab-target="backupRestorePanel"
            role="tab"
            aria-controls="backupRestorePanel"
            aria-selected="{{ $activeUtilityTab === 'backupRestorePanel' ? 'true' : 'false' }}"
        >
            Backup &amp; Restore
        </button>
        <button
            type="button"
            class="project-tab {{ $activeUtilityTab === 'schedulerPanel' ? 'is-active' : '' }}"
            data-utility-tab-target="schedulerPanel"
            role="tab"
            aria-controls="schedulerPanel"
            aria-selected="{{ $activeUtilityTab === 'schedulerPanel' ? 'true' : 'false' }}"
        >
            Scheduler
        </button>
    </div>

    <section id="backupRestorePanel" class="project-tab-panel {{ $activeUtilityTab === 'backupRestorePanel' ? 'is-active' : '' }}" role="tabpanel" style="margin-bottom: 20px; padding: 0; border: none; background: transparent;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <section style="background: white; padding: 28px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: #002C76; font-size: 18px;">Generate SQL Backup</h2>
                        <p style="margin: 4px 0 0; color: #6b7280; font-size: 13px;">Downloads a full dump of the active database.</p>
                    </div>
                </div>

                <div style="background: #dbeafe; border: 1px solid #1d4ed8; border-radius: 8px; padding: 14px; margin-bottom: 18px; color: #002C76; font-size: 13px; line-height: 1.6;">
                    Manual download creates an immediate SQL backup using the current database connection.
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 18px; color: #334155; font-size: 13px; line-height: 1.7;">
                    <div><strong>Database:</strong> {{ $databaseName }}</div>
                    <div><strong>Host:</strong> {{ $databaseHost }}</div>
                    <div><strong>Next Scheduled Run:</strong> {{ $nextScheduledRun ?? 'Scheduler disabled' }}</div>
                </div>

                <form method="GET" action="{{ route('utilities.backup-and-restore.download') }}">
                    <button type="submit" style="width: 100%; padding: 12px 18px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-download"></i>
                        <span>Download SQL Backup</span>
                    </button>
                </form>
            </section>

            <section style="background: white; padding: 28px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <div style="width: 46px; height: 46px; border-radius: 12px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; color: #002C76; font-size: 18px;">Restore Database</h2>
                        <p style="margin: 4px 0 0; color: #6b7280; font-size: 13px;">Imports a `.sql` backup into the active database.</p>
                    </div>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; margin-bottom: 18px; color: #991b1b; font-size: 13px; line-height: 1.6;">
                    Restoring a backup can overwrite existing tables and data. Use a verified SQL backup before continuing.
                </div>

                <form id="restoreDatabaseForm" method="POST" action="{{ route('utilities.backup-and-restore.restore') }}" enctype="multipart/form-data">
                    @csrf
                    <div style="margin-bottom: 14px;">
                        <label for="backup_file" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">SQL Backup File</label>
                        <input id="backup_file" class="dashboard-file-input" name="backup_file" type="file" accept=".sql" required>
                        <div id="backupFileValidationMessage" style="display: none; margin-top: 8px; color: #b91c1c; font-size: 12px; font-weight: 600;">
                            Please select a `.sql` backup file to proceed.
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; padding: 12px 18px; background-color: #b91c1c; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-rotate-left"></i>
                        <span>Restore Database</span>
                    </button>
                </form>
            </section>
        </div>
    </section>

    <section id="schedulerPanel" class="project-tab-panel {{ $activeUtilityTab === 'schedulerPanel' ? 'is-active' : '' }}" role="tabpanel" style="margin-bottom: 20px; padding: 0; border: none; background: transparent;">
        <section style="background: white; padding: 28px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <div class="scheduler-header-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 18px; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0; color: #002C76; font-size: 20px;">Automated Backup Scheduler</h2>
                <p style="margin: 6px 0 0; color: #6b7280; font-size: 13px;">Configure daily or weekly automatic backups, email recipients, retention cleanup, and password protection.</p>
            </div>
            <div style="padding: 10px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; background: {{ $automationSetting?->is_enabled ? '#dcfce7' : '#e5e7eb' }}; color: {{ $automationSetting?->is_enabled ? '#166534' : '#374151' }};">
                {{ $automationSetting?->is_enabled ? 'Scheduler Enabled' : 'Scheduler Disabled' }}
            </div>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 14px; margin-bottom: 18px; color: #92400e; font-size: 13px; line-height: 1.6;">
            Automated backups require the server to run Laravel scheduler every minute using `php artisan schedule:run`. On Windows/XAMPP, configure Windows Task Scheduler for this command.
        </div>

        <form class="scheduler-form-grid" method="POST" action="{{ route('utilities.backup-and-restore.schedule') }}" style="display: grid; grid-template-columns: repeat(2, minmax(260px, 1fr)); gap: 16px;">
            @csrf
            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; padding: 14px; border: 1px solid #dbeafe; background: #eff6ff; border-radius: 8px;">
                <input id="is_enabled" name="is_enabled" type="checkbox" value="1" @checked($schedulerState['is_enabled']) style="width: 18px; height: 18px;">
                <label for="is_enabled" style="color: #1e3a8a; font-weight: 600; font-size: 14px;">Enable automated backup emails</label>
            </div>

            <div>
                <label for="frequency" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Frequency</label>
                <select id="frequency" name="frequency" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff;">
                    <option value="daily" @selected($schedulerState['frequency'] === 'daily')>Daily</option>
                    <option value="weekly" @selected($schedulerState['frequency'] === 'weekly')>Weekly</option>
                </select>
            </div>

            <div id="weeklyDayContainer" style="{{ $schedulerState['frequency'] === 'weekly' ? '' : 'display: none;' }}">
                <label for="weekly_day" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Weekly Day</label>
                <select id="weekly_day" name="weekly_day" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff;">
                    <option value="">Select day</option>
                    @foreach ($dayOptions as $dayValue => $dayLabel)
                        <option value="{{ $dayValue }}" @selected((string) $schedulerState['weekly_day'] === (string) $dayValue)>{{ $dayLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="run_time" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Run Time</label>
                <input id="run_time" name="run_time" type="time" value="{{ $schedulerState['run_time'] }}" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff;">
            </div>

            <div>
                <label for="retention_days" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Retention Days</label>
                <input id="retention_days" name="retention_days" type="number" min="1" max="365" value="{{ $schedulerState['retention_days'] }}" placeholder="Optional" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff;">
                <div style="margin-top: 6px; font-size: 12px; color: #6b7280;">Older automated backups will be deleted when this limit is set.</div>
            </div>

            <div style="grid-column: 1 / -1;">
                <label for="recipient_emails" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Recipient Emails</label>
                <textarea id="recipient_emails" name="recipient_emails" rows="3" placeholder="name@example.com, admin@example.com" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff; resize: vertical;">{{ $schedulerState['recipient_emails'] }}</textarea>
                <div style="margin-top: 6px; font-size: 12px; color: #6b7280;">Separate multiple addresses with commas, spaces, or new lines.</div>
            </div>

            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; padding: 14px; border: 1px solid #fee2e2; background: #fff1f2; border-radius: 8px;">
                <input id="encrypt_backup" name="encrypt_backup" type="checkbox" value="1" @checked($schedulerState['encrypt_backup']) style="width: 18px; height: 18px;">
                <label for="encrypt_backup" style="color: #991b1b; font-weight: 600; font-size: 14px;">Password-protect emailed backup attachments</label>
            </div>

            <div id="encryptionPasswordContainer" style="{{ $schedulerState['encrypt_backup'] ? '' : 'display: none;' }}">
                <label for="encryption_password" style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 13px;">Encryption Password</label>
                <input id="encryption_password" name="encryption_password" type="password" placeholder="{{ $automationSetting?->encrypt_backup ? 'Leave blank to keep current password' : 'Enter at least 8 characters' }}" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; color: #374151; background: #fff;">
                <div style="margin-top: 6px; font-size: 12px; color: #6b7280;">Encrypted files are attached as `.enc` and require this password for decryption.</div>
            </div>

            <div class="scheduler-form-actions" style="grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                <button form="testBackupNowForm" type="submit" style="padding: 12px 20px; background-color: #0f766e; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Test Backup Now</span>
                </button>
                <button type="submit" style="padding: 12px 20px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fas fa-clock"></i>
                    <span>Save Scheduler</span>
                </button>
            </div>
        </form>

        <form id="testBackupNowForm" method="POST" action="{{ route('utilities.backup-and-restore.test-now') }}" onsubmit="return confirm('Send a test backup email now using the current scheduler settings?');">
            @csrf
        </form>

        @if ($automationSetting)
            <div class="scheduler-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 18px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Last Run</div>
                    <div style="font-size: 14px; color: #0f172a;">{{ $automationSetting->last_run_at?->format('F j, Y g:i A') ?? 'Not yet run' }}</div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Last Status</div>
                    <div style="font-size: 14px; color: {{ $automationSetting->last_status === 'success' ? '#166534' : ($automationSetting->last_status === 'failed' ? '#991b1b' : '#0f172a') }};">
                        {{ $automationSetting->last_status ? ucfirst($automationSetting->last_status) : 'No status yet' }}
                    </div>
                </div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">Next Run</div>
                    <div style="font-size: 14px; color: #0f172a;">{{ $nextScheduledRun ?? 'Scheduler disabled' }}</div>
                </div>
            </div>

            @if ($automationSetting->last_error)
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; margin-top: 14px; color: #991b1b; font-size: 13px; line-height: 1.6;">
                    <strong>Last scheduler error:</strong> {{ $automationSetting->last_error }}
                </div>
            @endif
            @endif
        </section>

        <section style="background: white; padding: 28px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
            <h2 style="margin: 0 0 18px; color: #002C76; font-size: 20px;">Recent Backup Activity</h2>

            <div class="scheduler-history-table-wrap" style="overflow-x: auto;">
                <table class="scheduler-history-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 12px; text-align: left; color: #475569;">Started</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">Type</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">Status</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">File</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">Recipients</th>
                            <th style="padding: 12px; text-align: left; color: #475569;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentBackupRuns as $run)
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; color: #334155;">{{ $run->started_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                <td style="padding: 12px; color: #334155;">{{ ucfirst($run->backup_type) }}</td>
                                <td style="padding: 12px;">
                                    <span style="padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 12px; background: {{ $run->status === 'success' ? '#dcfce7' : ($run->status === 'failed' ? '#fee2e2' : '#e0f2fe') }}; color: {{ $run->status === 'success' ? '#166534' : ($run->status === 'failed' ? '#991b1b' : '#075985') }};">
                                        {{ ucfirst($run->status) }}
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #334155;">{{ $run->filename ?? '-' }}</td>
                                <td style="padding: 12px; color: #334155;">{{ $run->mailed_to ? implode(', ', $run->mailed_to) : '-' }}</td>
                                <td style="padding: 12px; color: #64748b;">
                                    @if ($run->error_message)
                                        {{ $run->error_message }}
                                    @else
                                        {{ $run->was_encrypted ? 'Encrypted' : 'Plain SQL' }}
                                        @if ($run->retention_deleted_count)
                                            | Cleaned {{ $run->retention_deleted_count }} old backup(s)
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">No backup activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <style>
        .project-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .project-tab {
            padding: 10px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .project-tab:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .project-tab.is-active {
            background: #002C76;
            border-color: #002C76;
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(0, 44, 118, 0.18);
        }

        .project-tab-panel {
            display: none;
        }

        .project-tab-panel.is-active {
            display: block;
        }

        .scheduler-form-grid > div,
        .scheduler-status-grid > div {
            min-width: 0;
        }

        @keyframes backup-file-shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-6px); }
            80% { transform: translateX(6px); }
        }

        .backup-file-input-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.18);
            animation: backup-file-shake 0.35s ease-in-out;
        }

        @media (max-width: 768px) {
            .project-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                width: 100%;
                padding-bottom: 4px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .project-tabs::-webkit-scrollbar {
                display: none;
            }

            .project-tab {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .scheduler-header-row {
                flex-direction: column;
                align-items: stretch !important;
            }

            .scheduler-form-grid {
                grid-template-columns: 1fr !important;
            }

            .scheduler-form-grid input,
            .scheduler-form-grid select,
            .scheduler-form-grid textarea {
                font-size: 16px !important;
            }

            .scheduler-form-actions {
                justify-content: stretch !important;
            }

            .scheduler-form-actions button {
                width: 100%;
                justify-content: center;
            }

            .scheduler-status-grid {
                grid-template-columns: 1fr !important;
            }

            .scheduler-history-table {
                min-width: 720px;
            }
        }

        @media (max-width: 480px) {
            .project-tab {
                padding: 8px 12px;
                font-size: 12px;
            }

            .scheduler-history-table {
                min-width: 640px;
            }
        }
    </style>

    <script>
        (function attachRestoreFormValidation() {
            const restoreForm = document.getElementById('restoreDatabaseForm');
            const backupFileInput = document.getElementById('backup_file');
            const validationMessage = document.getElementById('backupFileValidationMessage');
            const frequencySelect = document.getElementById('frequency');
            const weeklyDayContainer = document.getElementById('weeklyDayContainer');
            const encryptCheckbox = document.getElementById('encrypt_backup');
            const encryptionPasswordContainer = document.getElementById('encryptionPasswordContainer');
            const utilityTabs = Array.from(document.querySelectorAll('[data-utility-tab-target]'));
            const utilityPanels = Array.from(document.querySelectorAll('.project-tab-panel'));

            function activateUtilityTab(panelId) {
                utilityTabs.forEach((tab) => {
                    const isActive = tab.dataset.utilityTabTarget === panelId;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                utilityPanels.forEach((panel) => {
                    panel.classList.toggle('is-active', panel.id === panelId);
                });
            }

            utilityTabs.forEach((tab) => {
                tab.addEventListener('click', function () {
                    activateUtilityTab(tab.dataset.utilityTabTarget);
                });
            });

            if (frequencySelect && weeklyDayContainer) {
                frequencySelect.addEventListener('change', function () {
                    weeklyDayContainer.style.display = frequencySelect.value === 'weekly' ? '' : 'none';
                });
            }

            if (encryptCheckbox && encryptionPasswordContainer) {
                encryptCheckbox.addEventListener('change', function () {
                    encryptionPasswordContainer.style.display = encryptCheckbox.checked ? '' : 'none';
                });
            }

            if (!restoreForm || !backupFileInput || !validationMessage) {
                return;
            }

            function showValidationError(message) {
                validationMessage.textContent = message;
                validationMessage.style.display = 'block';
                backupFileInput.classList.remove('backup-file-input-error');
                void backupFileInput.offsetWidth;
                backupFileInput.classList.add('backup-file-input-error');
            }

            function clearValidationError() {
                validationMessage.style.display = 'none';
                backupFileInput.classList.remove('backup-file-input-error');
            }

            restoreForm.addEventListener('submit', function (event) {
                const selectedFile = backupFileInput.files && backupFileInput.files[0] ? backupFileInput.files[0] : null;
                if (!selectedFile) {
                    event.preventDefault();
                    showValidationError('Please select a `.sql` backup file to proceed.');
                    backupFileInput.focus();
                    return;
                }

                if (!selectedFile.name.toLowerCase().endsWith('.sql')) {
                    event.preventDefault();
                    showValidationError('Only `.sql` backup files are allowed.');
                    backupFileInput.focus();
                    return;
                }

                clearValidationError();

                if (!window.confirm('Restoring a backup may overwrite the current database. Continue?')) {
                    event.preventDefault();
                }
            });

            backupFileInput.addEventListener('change', function () {
                const selectedFile = backupFileInput.files && backupFileInput.files[0] ? backupFileInput.files[0] : null;
                if (selectedFile && selectedFile.name.toLowerCase().endsWith('.sql')) {
                    clearValidationError();
                }
            });
        })();
    </script>
@endsection
