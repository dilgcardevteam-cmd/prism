<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_utilization_approval_workflows')) {
            Schema::table('fund_utilization_approval_workflows', function (Blueprint $table) {
                if (!Schema::hasColumn('fund_utilization_approval_workflows', 'revision_number')) {
                    $table->unsignedInteger('revision_number')->default(1)->after('returned_from_level');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('fund_utilization_approval_workflows', function (Blueprint $table) {
            if (Schema::hasColumn('fund_utilization_approval_workflows', 'revision_number')) {
                $table->dropColumn('revision_number');
            }
        });
    }
};
