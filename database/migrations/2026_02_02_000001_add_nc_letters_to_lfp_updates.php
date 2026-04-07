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
            $table->string('nc_letters')->nullable()->after('risk_aging');
            $table->unsignedInteger('nc_letters_updated_by')->nullable()->after('nc_letters');
            $table->timestamp('nc_letters_updated_at')->nullable()->after('nc_letters_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->dropColumn([
                'nc_letters',
                'nc_letters_updated_by',
                'nc_letters_updated_at',
            ]);
        });
    }
};
