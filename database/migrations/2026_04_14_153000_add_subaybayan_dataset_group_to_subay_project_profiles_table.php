<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('subay_project_profiles', 'subaybayan_dataset_group')) {
                $table->string('subaybayan_dataset_group')->nullable()->after('sglgif_overall');
                $table->index('subaybayan_dataset_group', 'subay_project_profiles_dataset_group_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('subay_project_profiles', 'subaybayan_dataset_group')) {
                $table->dropIndex('subay_project_profiles_dataset_group_idx');
                $table->dropColumn('subaybayan_dataset_group');
            }
        });
    }
};
