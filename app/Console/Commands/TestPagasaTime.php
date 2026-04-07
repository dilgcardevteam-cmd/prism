<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PagasaTimeService;
use Carbon\Carbon;

class TestPagasaTime extends Command
{
    protected $signature = 'test:pagasa';
    protected $description = 'Test PAGASA time service';

    public function handle()
    {
        $this->info('=== Testing PAGASA Time Service ===');
        
        $systemTime = Carbon::now('Asia/Manila');
        $this->line("System Time (Asia/Manila): " . $systemTime->format('Y-m-d H:i:s'));
        
        try {
            $pagasaTime = PagasaTimeService::getCurrentTime();
            $this->line("PAGASA Time: " . $pagasaTime->format('Y-m-d H:i:s'));
            $this->line("PAGASA Timezone: " . $pagasaTime->timezone->getName());
            $this->line("Success: PAGASA time retrieved!");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
