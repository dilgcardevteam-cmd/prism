<?php

namespace App\Console\Commands;

use App\Modules\Users\Models\UserImpersonationAuditLog;
use Illuminate\Console\Command;

class ExpireUserImpersonationSessions extends Command
{
    protected $signature = 'impersonation:expire-sessions';

    protected $description = 'Mark expired administrator impersonation sessions in the audit log.';

    public function handle(): int
    {
        UserImpersonationAuditLog::query()
            ->where('status', UserImpersonationAuditLog::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => UserImpersonationAuditLog::STATUS_EXPIRED,
                'ended_at' => now(),
            ]);

        return self::SUCCESS;
    }
}
