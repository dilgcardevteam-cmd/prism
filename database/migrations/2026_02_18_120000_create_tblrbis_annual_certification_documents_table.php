<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblrbis_annual_certification_documents', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->string('province');
            $table->string('document_name');
            $table->unsignedSmallInteger('document_year')->nullable();
            $table->text('remarks')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('status')->default('uploaded');
            $table->timestamps();

            $table->index('office');
            $table->index(['province', 'office']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblrbis_annual_certification_documents');
    }
};
