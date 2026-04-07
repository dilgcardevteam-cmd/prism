<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FURWrittenNotice;

class CheckDatabaseTimestamps extends Command
{
    protected $signature = 'check:timestamps';
    protected $description = 'Check database timestamps for written notices';

    public function handle()
    {
        $records = FURWrittenNotice::all();
        
        if ($records->isEmpty()) {
            $this->info('No records found');
            return;
        }
        
        $this->info('=== Database Timestamps ===');
        
        foreach ($records as $record) {
            $this->line("\nProject: {$record->project_code}, Quarter: {$record->quarter}");
            $this->line("  created_at: " . ($record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("  updated_at: " . ($record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("  approved_at: " . ($record->approved_at ? $record->approved_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("  approved_at_dilg_po: " . ($record->approved_at_dilg_po ? $record->approved_at_dilg_po->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("  approved_at_dilg_ro: " . ($record->approved_at_dilg_ro ? $record->approved_at_dilg_ro->format('Y-m-d H:i:s') : 'NULL'));
        }
    }
}
