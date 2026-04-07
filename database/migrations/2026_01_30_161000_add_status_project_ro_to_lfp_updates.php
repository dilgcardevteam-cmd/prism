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
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->string('status_project_ro')->nullable()->after('status_project_fou');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->dropColumn('status_project_ro');
        });
    }
};
