<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('locally_funded_projects')
            || !Schema::hasColumn('locally_funded_projects', 'contractor')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->longText('contractor')->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('locally_funded_projects')
            || !Schema::hasColumn('locally_funded_projects', 'contractor')) {
            return;
        }

        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->string('contractor')->change();
        });
    }
};
