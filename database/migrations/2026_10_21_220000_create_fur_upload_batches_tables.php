<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbfur_upload_batches')) {
            Schema::create('tbfur_upload_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_number')->unique();
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->unsignedInteger('uploaded_by')->nullable();
                $table->unsignedInteger('project_count')->default(0);
                $table->unsignedInteger('item_count')->default(0);
                $table->enum('status', ['submitted', 'partially_validated', 'validated', 'failed'])->default('submitted');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('uploaded_by')->references('idno')->on('tbusers')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('tbfur_upload_batch_projects')) {
            Schema::create('tbfur_upload_batch_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('tbfur_upload_batches')->cascadeOnDelete();
                $table->string('project_code');
                $table->longText('items_json')->nullable();
                $table->enum('status', ['pending', 'included', 'excluded', 'needs_correction'])->default('pending');
                $table->unsignedInteger('selected_by')->nullable();
                $table->timestamp('selected_at')->nullable();
                $table->enum('distribution_status', ['pending', 'distributed', 'failed'])->default('pending');
                $table->timestamps();

                $table->foreign('project_code')->references('project_code')->on('tbfur')->cascadeOnDelete();
                $table->foreign('selected_by')->references('idno')->on('tbusers')->nullOnDelete();
                $table->unique(['batch_id', 'project_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbfur_upload_batch_projects');
        Schema::dropIfExists('tbfur_upload_batches');
    }
};
