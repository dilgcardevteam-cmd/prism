<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lgu_reportorial_deadlines') && !Schema::hasColumn('lgu_reportorial_deadlines', 'deadline_time')) {
            Schema::table('lgu_reportorial_deadlines', function (Blueprint $table) {
                $table->time('deadline_time')->nullable()->after('deadline_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lgu_reportorial_deadlines') && Schema::hasColumn('lgu_reportorial_deadlines', 'deadline_time')) {
            Schema::table('lgu_reportorial_deadlines', function (Blueprint $table) {
                $table->dropColumn('deadline_time');
            });
        }
    }
};
