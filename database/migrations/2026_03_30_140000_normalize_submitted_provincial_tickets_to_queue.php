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
            ->where('current_level', Ticket::LEVEL_PROVINCIAL)
            ->where('status', Ticket::STATUS_SUBMITTED)
            ->where('assigned_role', User::ROLE_PROVINCIAL)
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
