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
        // Create tbfur_mov_uploads table
        if (!Schema::hasTable('tbfur_mov_uploads')) {
            Schema::create('tbfur_mov_uploads', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('mov_file_path')->nullable();
                $table->timestamps();

                $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
                $table->unique(['project_code', 'quarter']);
            });
        }

        // Create tbfur_written_notice table
        if (!Schema::hasTable('tbfur_written_notice')) {
            Schema::create('tbfur_written_notice', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('notice_screenshot_path')->nullable();
                $table->string('notice_pdf_path')->nullable();
                $table->string('secretary_dbm_path')->nullable();
                $table->string('secretary_dilg_path')->nullable();
                $table->string('speaker_house_path')->nullable();
                $table->string('president_senate_path')->nullable();
                $table->string('house_committee_path')->nullable();
                $table->string('senate_committee_path')->nullable();
                $table->timestamps();

                $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
                $table->unique(['project_code', 'quarter']);
            });
        }

        // Create tbfur_fdp table
        if (!Schema::hasTable('tbfur_fdp')) {
            Schema::create('tbfur_fdp', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('fdp_file_path')->nullable();
                $table->timestamps();

                $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
                $table->unique(['project_code', 'quarter']);
            });
        }

        // Create tbfur_admin_remarks table
        if (!Schema::hasTable('tbfur_admin_remarks')) {
            Schema::create('tbfur_admin_remarks', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->text('remarks');
                $table->unsignedBigInteger('admin_id');
                $table->timestamps();

                $table->foreign('project_code')->references('project_code')->on('tbfur')->onDelete('cascade');
                $table->foreign('admin_id')->references('idno')->on('tbusers');
                $table->unique(['project_code', 'quarter']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbfur_admin_remarks');
        Schema::dropIfExists('tbfur_fdp');
        Schema::dropIfExists('tbfur_written_notice');
        Schema::dropIfExists('tbfur_mov_uploads');
    }
};
