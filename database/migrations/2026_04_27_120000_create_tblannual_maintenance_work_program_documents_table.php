<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblannual_maintenance_work_program_documents')) {
            Schema::create('tblannual_maintenance_work_program_documents', function (Blueprint $table) {
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
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('approved_at_dilg_po')->nullable();
                $table->timestamp('approved_at_dilg_ro')->nullable();
                $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
                $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
                $table->text('approval_remarks')->nullable();
                $table->text('user_remarks')->nullable();
                $table->timestamps();
            });
        }

        $existingIndexes = collect(DB::select('SHOW INDEX FROM tblannual_maintenance_work_program_documents'))
            ->pluck('Key_name')
            ->all();

        Schema::table('tblannual_maintenance_work_program_documents', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('awmp_docs_office_idx', $existingIndexes, true)) {
                $table->index('office', 'awmp_docs_office_idx');
            }

            if (!in_array('awmp_docs_province_office_idx', $existingIndexes, true)) {
                $table->index(['province', 'office'], 'awmp_docs_province_office_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblannual_maintenance_work_program_documents');
    }
};
