<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pre_implementation_documents')) {
            return;
        }

        Schema::table('pre_implementation_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('pre_implementation_documents', 'project_proposal_path')) {
                $table->string('project_proposal_path')->nullable()->after('signed_lgu_letter_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'mwssmp_path')) {
                $table->string('mwssmp_path')->nullable()->after('moa_rural_electrification_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pre_implementation_documents')) {
            return;
        }

        Schema::table('pre_implementation_documents', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('pre_implementation_documents', 'project_proposal_path')) {
                $columnsToDrop[] = 'project_proposal_path';
            }

            if (Schema::hasColumn('pre_implementation_documents', 'mwssmp_path')) {
                $columnsToDrop[] = 'mwssmp_path';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
