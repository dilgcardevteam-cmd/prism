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
            $table->decimal('accomplishment_pct_ro', 5, 2)->nullable()->after('accomplishment_pct');
            $table->unsignedInteger('accomplishment_pct_ro_updated_by')->nullable()->after('accomplishment_pct_ro');
            $table->timestamp('accomplishment_pct_ro_updated_at')->nullable()->after('accomplishment_pct_ro_updated_by');

            $table->decimal('slippage_ro', 5, 2)->nullable()->after('slippage');
            $table->unsignedInteger('slippage_ro_updated_by')->nullable()->after('slippage_ro');
            $table->timestamp('slippage_ro_updated_at')->nullable()->after('slippage_ro_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locally_funded_physical_updates', function (Blueprint $table) {
            $table->dropColumn([
                'accomplishment_pct_ro',
                'accomplishment_pct_ro_updated_by',
                'accomplishment_pct_ro_updated_at',
                'slippage_ro',
                'slippage_ro_updated_by',
                'slippage_ro_updated_at',
            ]);
        });
    }
};
