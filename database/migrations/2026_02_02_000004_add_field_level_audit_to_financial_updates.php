<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locally_funded_financial_updates', function (Blueprint $table) {
            $table->timestamp('obligation_updated_at')->nullable();
            $table->unsignedBigInteger('obligation_updated_by')->nullable();
            $table->timestamp('disbursed_amount_updated_at')->nullable();
            $table->unsignedBigInteger('disbursed_amount_updated_by')->nullable();
            $table->timestamp('reverted_amount_updated_at')->nullable();
            $table->unsignedBigInteger('reverted_amount_updated_by')->nullable();
            $table->timestamp('utilization_rate_updated_at')->nullable();
            $table->unsignedBigInteger('utilization_rate_updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('locally_funded_financial_updates', function (Blueprint $table) {
            $table->dropColumn([
                'obligation_updated_at',
                'obligation_updated_by',
                'disbursed_amount_updated_at',
                'disbursed_amount_updated_by',
                'reverted_amount_updated_at',
                'reverted_amount_updated_by',
                'utilization_rate_updated_at',
                'utilization_rate_updated_by',
            ]);
        });
    }
};
