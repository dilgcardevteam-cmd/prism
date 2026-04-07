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
        Schema::create('project_at_risks', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->nullable();
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->text('barangays')->nullable();
            $table->year('funding_year')->nullable();
            $table->string('name_of_program')->nullable();
            $table->text('project_title')->nullable();
            $table->decimal('national_subsidy', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->decimal('target', 8, 2)->nullable();
            $table->decimal('actual', 8, 2)->nullable();
            $table->decimal('slippage', 8, 2)->nullable();
            $table->date('date_of_accomplishment')->nullable();
            $table->date('date_of_extraction')->nullable();
            $table->integer('aging')->nullable();
            $table->string('risk_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_at_risks');
    }
};
