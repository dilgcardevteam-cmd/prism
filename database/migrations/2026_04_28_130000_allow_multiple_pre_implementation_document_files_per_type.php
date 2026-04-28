<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pre_implementation_document_files')) {
            return;
        }

        Schema::table('pre_implementation_document_files', function (Blueprint $table) {
            $table->dropUnique('pre_impl_doc_files_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pre_implementation_document_files')) {
            return;
        }

        $duplicateExists = DB::table('pre_implementation_document_files')
            ->select('project_code', 'document_type', DB::raw('COUNT(*) as total'))
            ->groupBy('project_code', 'document_type')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateExists) {
            return;
        }

        Schema::table('pre_implementation_document_files', function (Blueprint $table) {
            $table->unique(['project_code', 'document_type'], 'pre_impl_doc_files_unique');
        });
    }
};
