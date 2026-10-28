<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subay_project_profiles')
            && Schema::hasColumn('subay_project_profiles', 'created_at')
            && !Schema::hasIndex('subay_project_profiles', 'subay_profiles_created_at_idx')) {
            Schema::table('subay_project_profiles', function (Blueprint $table): void {
                $table->index('created_at', 'subay_profiles_created_at_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subay_project_profiles')
            && Schema::hasIndex('subay_project_profiles', 'subay_profiles_created_at_idx')) {
            Schema::table('subay_project_profiles', function (Blueprint $table): void {
                $table->dropIndex('subay_profiles_created_at_idx');
            });
        }
    }
};
