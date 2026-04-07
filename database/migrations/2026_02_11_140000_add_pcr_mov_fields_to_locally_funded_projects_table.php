<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('locally_funded_projects', 'pcr_mov_file_path')) {
                $table->string('pcr_mov_file_path')->nullable()->after('pcr_date_submitted_to_po');
            }
            if (!Schema::hasColumn('locally_funded_projects', 'pcr_mov_uploaded_at')) {
                $table->timestamp('pcr_mov_uploaded_at')->nullable()->after('pcr_mov_file_path');
            }
            if (!Schema::hasColumn('locally_funded_projects', 'pcr_mov_uploaded_by')) {
                $table->unsignedBigInteger('pcr_mov_uploaded_by')->nullable()->after('pcr_mov_uploaded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $columns = [
                'pcr_mov_file_path',
                'pcr_mov_uploaded_at',
                'pcr_mov_uploaded_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('locally_funded_projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
