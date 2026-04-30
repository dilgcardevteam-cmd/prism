<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quarterly_dilg_mc_2018_30_uploads')) {
            return;
        }

        Schema::create('quarterly_dilg_mc_2018_30_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->string('province');
            $table->unsignedSmallInteger('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('user_remarks')->nullable();
            $table->timestamps();

            $table->index(['office', 'year', 'quarter'], 'q_dilg_mc_2018_30_office_year_quarter_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_dilg_mc_2018_30_uploads');
    }
};
