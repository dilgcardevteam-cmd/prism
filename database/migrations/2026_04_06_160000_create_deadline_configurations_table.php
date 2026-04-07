<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deadline_configurations')) {
            Schema::create('deadline_configurations', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('funding_year')->unique();
                $table->date('pcr_submission_deadline')->nullable();
                $table->date('rssa_report_deadline')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_configurations');
    }
};
