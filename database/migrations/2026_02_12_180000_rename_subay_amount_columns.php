<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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

        if (Schema::hasColumn('subay_project_profiles', 'amount') && !Schema::hasColumn('subay_project_profiles', 'obligation')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `amount` `obligation` TEXT NULL');
        }

        if (Schema::hasColumn('subay_project_profiles', 'amount_2') && !Schema::hasColumn('subay_project_profiles', 'disbursement')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `amount_2` `disbursement` TEXT NULL');
        }

        if (Schema::hasColumn('subay_project_profiles', 'amount_3') && !Schema::hasColumn('subay_project_profiles', 'liquidations')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `amount_3` `liquidations` TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        if (Schema::hasColumn('subay_project_profiles', 'obligation') && !Schema::hasColumn('subay_project_profiles', 'amount')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `obligation` `amount` TEXT NULL');
        }

        if (Schema::hasColumn('subay_project_profiles', 'disbursement') && !Schema::hasColumn('subay_project_profiles', 'amount_2')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `disbursement` `amount_2` TEXT NULL');
        }

        if (Schema::hasColumn('subay_project_profiles', 'liquidations') && !Schema::hasColumn('subay_project_profiles', 'amount_3')) {
            DB::statement('ALTER TABLE `subay_project_profiles` CHANGE COLUMN `liquidations` `amount_3` TEXT NULL');
        }
    }
};

