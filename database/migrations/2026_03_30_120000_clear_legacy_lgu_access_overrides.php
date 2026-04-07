<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbusers')) {
            return;
        }

        // Older self-registered LGU accounts were created with crud:none,
        // which blocks the role-based permission matrix entirely.
        DB::table('tbusers')
            ->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [User::ROLE_LGU])
            ->whereRaw('LOWER(TRIM(COALESCE(access, ""))) = ?', [User::ACCESS_SCOPE_NONE])
            ->update([
                'access' => null,
            ]);
    }

    public function down(): void
    {
        // Irreversible on purpose: we cannot safely distinguish legacy
        // registration defaults from intentional no-access overrides.
    }
};
