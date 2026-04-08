<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblswa_annex_f_documents', function (Blueprint $table) {
            $table->id();
            $table->string('office');
            $table->string('province');
            $table->string('doc_type');
            $table->unsignedSmallInteger('year');
            $table->enum('month', [
                'JAN',
                'FEB',
                'MAR',
                'APR',
                'MAY',
                'JUN',
                'JUL',
                'AUG',
                'SEP',
                'OCT',
                'NOV',
                'DEC',
            ]);
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('user_remarks')->nullable();
            $table->timestamps();

            $table->unique(
                ['office', 'doc_type', 'year', 'month'],
                'tblswa_annex_f_documents_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblswa_annex_f_documents');
    }
};
