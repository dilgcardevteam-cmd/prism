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
        if (Schema::hasTable('tbfur_batch_documents')) {
            return;
        }

        Schema::create('tbfur_batch_documents', function (Blueprint $table) {
            $table->id();
            $table->string('project_code');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->string('batch_document_file_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'returned'])->default('pending');
            $table->text('approval_remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('user_remarks')->nullable();
            $table->unsignedBigInteger('encoder_id')->nullable();
            $table->timestamp('batch_document_uploaded_at')->nullable();
            $table->unsignedBigInteger('batch_document_encoder_id')->nullable();
            $table->timestamps();

            $table->foreign('project_code')
                ->references('project_code')
                ->on('tbfur')
                ->onDelete('cascade');
            $table->unique(['project_code', 'quarter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbfur_batch_documents');
    }
};
