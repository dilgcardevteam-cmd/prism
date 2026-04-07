<?php

namespace App\Console\Commands;

use App\Mail\AutomatedDatabaseBackupMail;
use App\Models\BackupAutomationSetting;
use App\Models\DatabaseBackupRun;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class RunScheduledDatabaseBackups extends Command
{
    protected $signature = 'backup:run-scheduled';

    protected $description = 'Run the configured automated database backup schedule';

    public function handle(DatabaseBackupService $backupService): int
    {
        $lock = Cache::lock('backup:run-scheduled', 3600);

        if (! $lock->get()) {
            return self::SUCCESS;
        }

        try {
            $setting = BackupAutomationSetting::query()->first();

            if (! $setting || ! $setting->is_enabled) {
                return self::SUCCESS;
            }

            $recipients = array_values(array_filter($setting->recipient_emails ?? []));
            if ($recipients === []) {
                $setting->update([
                    'last_status' => 'failed',
                    'last_error' => 'No recipient email addresses are configured for automated backups.',
                ]);

                return self::FAILURE;
            }

            if (! $this->isDue($setting)) {
                return self::SUCCESS;
            }

            try {
                $backup = $backupService->createBackup([
                    'type' => 'automated',
                    'directory' => 'app/backups/automated',
                    'prefix' => $backupService->databaseConnection()['database'] . '-scheduled-backup',
                    'encrypt' => $setting->encrypt_backup,
                    'encryption_password' => $setting->encrypt_backup ? $setting->encryption_password : null,
                    'setting_id' => $setting->id,
                    'mailed_to' => $recipients,
                ]);

                Mail::to($recipients)->send(new AutomatedDatabaseBackupMail(
                    databaseName: $backupService->databaseConnection()['database'],
                    filePath: $backup['absolute_path'],
                    fileName: $backup['filename'],
                    wasEncrypted: $backup['was_encrypted'],
                    retentionDays: $setting->retention_days,
                ));

                $deletedCount = $backupService->pruneOldBackups($setting->retention_days);

                DatabaseBackupRun::query()
                    ->where('stored_path', $backup['stored_path'])
                    ->latest('id')
                    ->first()?->update([
                        'retention_deleted_count' => $deletedCount,
                    ]);

                $setting->update([
                    'last_run_at' => now(),
                    'last_status' => 'success',
                    'last_error' => null,
                    'last_backup_path' => $backup['stored_path'],
                ]);

                $this->info('Automated database backup sent successfully.');

                return self::SUCCESS;
            } catch (\Throwable $exception) {
                $setting->update([
                    'last_run_at' => now(),
                    'last_status' => 'failed',
                    'last_error' => $exception->getMessage(),
                ]);

                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        } finally {
            $lock->release();
        }
    }

    private function isDue(BackupAutomationSetting $setting): bool
    {
        $now = now();
        $target = $now->copy()->setTimeFromTimeString($setting->run_time);
        $lastAutomatedRun = DatabaseBackupRun::query()
            ->where('backup_automation_setting_id', $setting->id)
            ->where('backup_type', 'automated')
            ->where(function ($query) {
                $query->whereNull('filename')
                    ->orWhere('filename', 'not like', '%-test-backup-%');
            })
            ->where('status', 'success')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        if ($setting->frequency === 'weekly') {
            if ($setting->weekly_day === null || $now->dayOfWeek !== (int) $setting->weekly_day) {
                return false;
            }
        }

        if ($now->lt($target)) {
            return false;
        }

        if (! $lastAutomatedRun || ! $lastAutomatedRun->completed_at) {
            return true;
        }

        if ($setting->frequency === 'weekly') {
            return $lastAutomatedRun->completed_at->lt($target->copy()->startOfDay());
        }

        return ! $lastAutomatedRun->completed_at->isSameDay($now);
    }
}
