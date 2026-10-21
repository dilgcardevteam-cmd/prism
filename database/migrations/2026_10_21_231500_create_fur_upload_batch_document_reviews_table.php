<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbfur_upload_batch_document_reviews')) {
            return;
        }

        Schema::create('tbfur_upload_batch_document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_project_id')->constrained('tbfur_upload_batch_projects')->cascadeOnDelete();
            $table->string('document_type');
            $table->enum('status', ['pending', 'approved', 'returned', 'forwarded'])->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('validated_by')->nullable();
            $table->unsignedTinyInteger('validation_level')->default(1);
            $table->string('validation_token', 80)->unique();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('validated_by')->references('idno')->on('tbusers')->nullOnDelete();
            $table->unique(['batch_project_id', 'document_type'], 'fur_batch_doc_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbfur_upload_batch_document_reviews');
    }
};
