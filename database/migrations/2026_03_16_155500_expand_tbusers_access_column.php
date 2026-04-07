<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tbusers MODIFY access TEXT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tbusers MODIFY access VARCHAR(255) NOT NULL DEFAULT 'none'");
    }
};
