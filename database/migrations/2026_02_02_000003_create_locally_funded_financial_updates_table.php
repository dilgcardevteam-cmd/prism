<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locally_funded_financial_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('obligation', 15, 2)->nullable();
            $table->decimal('disbursed_amount', 15, 2)->nullable();
            $table->decimal('reverted_amount', 15, 2)->nullable();
            $table->decimal('utilization_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')
                ->on('locally_funded_projects')
                ->onDelete('cascade');
            $table->index(['project_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locally_funded_financial_updates');
    }
};
