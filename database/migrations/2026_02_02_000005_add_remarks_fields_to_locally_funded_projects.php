<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->longText('physical_remarks')->nullable();
            $table->timestamp('physical_remarks_updated_at')->nullable();
            $table->unsignedBigInteger('physical_remarks_updated_by')->nullable();
            $table->unsignedBigInteger('physical_remarks_encoded_by')->nullable();

            $table->longText('financial_remarks')->nullable();
            $table->timestamp('financial_remarks_updated_at')->nullable();
            $table->unsignedBigInteger('financial_remarks_updated_by')->nullable();
            $table->unsignedBigInteger('financial_remarks_encoded_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('locally_funded_projects', function (Blueprint $table) {
            $table->dropColumn([
                'physical_remarks',
                'physical_remarks_updated_at',
                'physical_remarks_updated_by',
                'physical_remarks_encoded_by',
                'financial_remarks',
                'financial_remarks_updated_at',
                'financial_remarks_updated_by',
                'financial_remarks_encoded_by',
            ]);
        });
    }
};
