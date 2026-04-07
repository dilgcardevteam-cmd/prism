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
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('subay_project_profiles', 'sglgif_level')) {
                $table->text('sglgif_level')->nullable();
            }

            if (!Schema::hasColumn('subay_project_profiles', 'sglgif_financial')) {
                $table->text('sglgif_financial')->nullable();
            }

            if (!Schema::hasColumn('subay_project_profiles', 'sglgif_attachment')) {
                $table->text('sglgif_attachment')->nullable();
            }

            if (!Schema::hasColumn('subay_project_profiles', 'sglgif_overall')) {
                $table->text('sglgif_overall')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['sglgif_level', 'sglgif_financial', 'sglgif_attachment', 'sglgif_overall'] as $column) {
                if (Schema::hasColumn('subay_project_profiles', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
