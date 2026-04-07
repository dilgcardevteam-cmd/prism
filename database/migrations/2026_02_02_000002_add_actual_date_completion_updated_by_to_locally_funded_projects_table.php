<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('locally_funded_projects', 'actual_date_completion_updated_by')) {
            Schema::table('locally_funded_projects', function (Blueprint $table) {
                $table->unsignedBigInteger('actual_date_completion_updated_by')->nullable()->after('actual_date_completion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locally_funded_projects', 'actual_date_completion_updated_by')) {
            Schema::table('locally_funded_projects', function (Blueprint $table) {
                $table->dropColumn('actual_date_completion_updated_by');
            });
        }
    }
};
