<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            return;
        }

        DB::table('tickets')
            ->where('current_level', Ticket::LEVEL_REGIONAL)
            ->where('status', Ticket::STATUS_ESCALATED_TO_REGION)
            ->where('assigned_role', User::ROLE_REGIONAL)
            ->update([
                'assigned_to' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Previous assignees cannot be restored reliably.
    }
};
