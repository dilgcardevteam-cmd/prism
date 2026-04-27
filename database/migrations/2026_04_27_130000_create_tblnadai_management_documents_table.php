<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblnadai_management_documents')) {
            Schema::create('tblnadai_management_documents', function (Blueprint $table) {
                $table->id();
                $table->string('office');
                $table->string('province');
                $table->string('project_title');
                $table->date('nadai_date');
                $table->string('original_filename');
                $table->string('file_path');
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();
            });
        }

        $existingIndexes = collect(DB::select('SHOW INDEX FROM tblnadai_management_documents'))
            ->pluck('Key_name')
            ->all();

        Schema::table('tblnadai_management_documents', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('nadai_docs_office_idx', $existingIndexes, true)) {
                $table->index('office', 'nadai_docs_office_idx');
            }

            if (!in_array('nadai_docs_province_office_idx', $existingIndexes, true)) {
                $table->index(['province', 'office'], 'nadai_docs_province_office_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblnadai_management_documents');
    }
};
