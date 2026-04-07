<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FURMovUpload;
use App\Models\FURWrittenNotice;
use App\Models\FURFDP;
use App\Services\SecureTimestampService;

class VerifyUploadTimestamps extends Command
{
    protected $signature = 'verify:timestamps';
    protected $description = 'Verify integrity of all upload timestamps for security audit';

    public function handle()
    {
        $this->info('=== Upload Timestamp Integrity Audit ===');
        $this->line('');

        $this->auditModel('MOV Uploads', FURMovUpload::class);
        $this->auditModel('Written Notices', FURWrittenNotice::class);
        $this->auditModel('FDP Documents', FURFDP::class);

        $this->info('');
        $this->info('Audit complete. Check storage/logs/upload_timestamps.log for detailed upload history.');
    }

    private function auditModel(string $modelName, string $modelClass)
    {
        $this->line("<fg=cyan>Auditing: {$modelName}</>");
        $records = $modelClass::all();

        if ($records->isEmpty()) {
            $this->line("  No records found\n");
            return;
        }

        $issuesFound = 0;

        foreach ($records as $record) {
            $verification = SecureTimestampService::verifyTimestampIntegrity(
                $record->updated_at,
                $record->approved_at ?? null
            );

            if (!$verification['upload_timestamp_valid'] || !$verification['approval_sequence_valid']) {
                $issuesFound++;
                $this->error("  ❌ {$record->project_code} Q{$record->quarter}");
                foreach ($verification['integrity_checks'] as $check) {
                    $this->line("     - {$check}");
                }
            }
        }

        if ($issuesFound === 0) {
            $this->line("  ✓ All {$records->count()} records verified successfully\n");
        } else {
            $this->warn("  ⚠ {$issuesFound} issues found!\n");
        }
    }
}
