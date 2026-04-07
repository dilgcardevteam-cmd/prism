<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblpmc_documents', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->string('province');
            $table->string('doc_type');
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4'])->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['office', 'doc_type', 'year', 'quarter'], 'tblpmc_docs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblpmc_documents');
    }
};
