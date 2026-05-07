<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblnadai_management_documents')) {
            return;
        }

        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('tblnadai_management_documents', 'municipality')) {
                $table->string('municipality')->nullable()->after('province');
            }

            if (!Schema::hasColumn('tblnadai_management_documents', 'barangay')) {
                $table->string('barangay')->nullable()->after('municipality');
            }

            if (!Schema::hasColumn('tblnadai_management_documents', 'funding_year')) {
                $table->string('funding_year', 16)->nullable()->after('barangay');
            }

            if (!Schema::hasColumn('tblnadai_management_documents', 'program')) {
                $table->string('program')->nullable()->after('funding_year');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tblnadai_management_documents')) {
            return;
        }

        Schema::table('tblnadai_management_documents', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['municipality', 'barangay', 'funding_year', 'program'] as $column) {
                if (Schema::hasColumn('tblnadai_management_documents', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
