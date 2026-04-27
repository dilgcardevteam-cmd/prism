<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblconfirmation_of_fund_receipt_documents')) {
            Schema::create('tblconfirmation_of_fund_receipt_documents', function (Blueprint $table) {
                $table->id();
                $table->string('office');
                $table->string('province');
                $table->string('project_title');
                $table->date('confirmation_date');
                $table->string('original_filename');
                $table->string('file_path');
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->unsignedBigInteger('accepted_by')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        $existingIndexes = collect(DB::select('SHOW INDEX FROM tblconfirmation_of_fund_receipt_documents'))
            ->pluck('Key_name')
            ->all();

        Schema::table('tblconfirmation_of_fund_receipt_documents', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('cfr_docs_office_idx', $existingIndexes, true)) {
                $table->index('office', 'cfr_docs_office_idx');
            }

            if (!in_array('cfr_docs_province_office_idx', $existingIndexes, true)) {
                $table->index(['province', 'office'], 'cfr_docs_province_office_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblconfirmation_of_fund_receipt_documents');
    }
};
