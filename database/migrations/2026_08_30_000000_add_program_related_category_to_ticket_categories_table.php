<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_categories')) {
            return;
        }

        // 1. Update the sort_order of "Others" to 9 (was 8)
        DB::table('ticket_categories')
            ->where('name', 'Others')
            ->update(['sort_order' => 9]);

        // 2. Insert or update "Program Related" with sort_order 8
        DB::table('ticket_categories')->updateOrInsert(
            ['name' => 'Program Related'],
            [
                'description' => 'Issues or concerns related to specific programs.',
                'sort_order' => 8,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('ticket_categories')) {
            return;
        }

        // 1. Delete "Program Related"
        DB::table('ticket_categories')
            ->where('name', 'Program Related')
            ->delete();

        // 2. Revert "Others" sort_order to 8
        DB::table('ticket_categories')
            ->where('name', 'Others')
            ->update(['sort_order' => 8]);
    }
};

