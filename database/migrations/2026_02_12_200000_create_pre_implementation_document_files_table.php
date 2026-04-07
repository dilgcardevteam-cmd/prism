<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pre_implementation_document_files')) {
            return;
        }

        Schema::create('pre_implementation_document_files', function (Blueprint $table) {
            $table->id();
            $table->string('project_code');
            $table->string('document_type', 120);
            $table->string('file_path')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('user_remarks')->nullable();
            $table->timestamps();

            $table->unique(['project_code', 'document_type'], 'pre_impl_doc_files_unique');
            $table->index(['project_code', 'status'], 'pre_impl_doc_files_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_implementation_document_files');
    }
};

