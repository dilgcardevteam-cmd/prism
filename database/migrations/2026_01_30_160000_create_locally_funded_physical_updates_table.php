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
        Schema::create('locally_funded_physical_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status_project_fou')->nullable();
            $table->decimal('accomplishment_pct', 5, 2)->nullable();
            $table->decimal('slippage', 5, 2)->nullable();
            $table->string('risk_aging')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'year', 'month'], 'lfp_physical_project_year_month_unique');
            $table->foreign('project_id')->references('id')->on('locally_funded_projects')->onDelete('cascade');
            $table->foreign('updated_by')->references('idno')->on('tbusers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locally_funded_physical_updates');
    }
};
