<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('locally_funded_projects')
            && Schema::hasColumn('locally_funded_projects', 'barangay')) {
            Schema::table('locally_funded_projects', function (Blueprint $table) {
                $table->longText('barangay')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('tbfur')
            && Schema::hasColumn('tbfur', 'barangay')) {
            Schema::table('tbfur', function (Blueprint $table) {
                $table->longText('barangay')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('locally_funded_projects')
            && Schema::hasColumn('locally_funded_projects', 'barangay')) {
            Schema::table('locally_funded_projects', function (Blueprint $table) {
                $table->string('barangay')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('tbfur')
            && Schema::hasColumn('tbfur', 'barangay')) {
            Schema::table('tbfur', function (Blueprint $table) {
                $table->string('barangay')->nullable()->change();
            });
        }
    }
};
