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
        if (!Schema::hasTable('locally_funded_projects')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('locally_funded_projects', 'office')) {
                $table->string('office')->nullable()->after('province');
            }
            if (!Schema::hasColumn('locally_funded_projects', 'region')) {
                $table->string('region')->nullable()->after('office');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('locally_funded_projects')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            if (Schema::hasColumn('locally_funded_projects', 'office')) {
                $table->dropColumn('office');
            }
            if (Schema::hasColumn('locally_funded_projects', 'region')) {
                $table->dropColumn('region');
            }
        });
    }
};
