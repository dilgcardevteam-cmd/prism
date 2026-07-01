<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbfur_batch_documents')) {
            return;
        }

        Schema::table('tbfur_batch_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_batch_documents', 'batch_document_files_json')) {
                $table->longText('batch_document_files_json')->nullable()->after('batch_document_file_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbfur_batch_documents')) {
            return;
        }

        Schema::table('tbfur_batch_documents', function (Blueprint $table) {
            if (Schema::hasColumn('tbfur_batch_documents', 'batch_document_files_json')) {
                $table->dropColumn('batch_document_files_json');
            }
        });
    }
};
