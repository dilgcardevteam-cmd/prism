<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('locally_funded_projects', 'financial_remarks')) {
                $table->longText('financial_remarks')->nullable()->after('physical_remarks_encoded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (Schema::hasColumn('locally_funded_projects', 'financial_remarks')) {
                $table->dropColumn('financial_remarks');
            }
        });
    }
};
