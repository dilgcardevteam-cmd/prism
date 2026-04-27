<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblconfirmation_of_fund_receipt_documents', 'nadai_document_id')) {
            Schema::table('tblconfirmation_of_fund_receipt_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('nadai_document_id')->nullable()->after('id');
            });
        }

        $existingIndexes = collect(DB::select('SHOW INDEX FROM tblconfirmation_of_fund_receipt_documents'))
            ->pluck('Key_name')
            ->all();

        Schema::table('tblconfirmation_of_fund_receipt_documents', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('cfr_docs_nadai_document_idx', $existingIndexes, true)) {
                $table->index('nadai_document_id', 'cfr_docs_nadai_document_idx');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblconfirmation_of_fund_receipt_documents', 'nadai_document_id')) {
            $existingIndexes = collect(DB::select('SHOW INDEX FROM tblconfirmation_of_fund_receipt_documents'))
                ->pluck('Key_name')
                ->all();

            Schema::table('tblconfirmation_of_fund_receipt_documents', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('cfr_docs_nadai_document_idx', $existingIndexes, true)) {
                    $table->dropIndex('cfr_docs_nadai_document_idx');
                }

                $table->dropColumn('nadai_document_id');
            });
        }
    }
};
