<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quarterly_dilg_mc_2018_30_encodings')) {
            return;
        }

        Schema::create('quarterly_dilg_mc_2018_30_encodings', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->string('province')->nullable();
            $table->unsignedSmallInteger('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->json('rows')->nullable();
            $table->unsignedBigInteger('last_saved_by')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();

            $table->unique(['office', 'year', 'quarter'], 'q_dilg_mc_2018_30_encoding_unique');
            $table->index(['office', 'year'], 'q_dilg_mc_2018_30_encoding_office_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_dilg_mc_2018_30_encodings');
    }
};
