<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblpmc_document_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lpmc_document_id');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('lpmc_document_id')
                ->references('id')
                ->on('tblpmc_documents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblpmc_document_files');
    }
};
