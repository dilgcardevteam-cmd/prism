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
            if (!Schema::hasColumn('pre_implementation_documents', 'program_of_works_path')) {
                $table->string('program_of_works_path')->nullable()->after('moa_rural_electrification_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'design_and_engineering_documents_path')) {
                $table->string('design_and_engineering_documents_path')->nullable()->after('program_of_works_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'variation_orders_path')) {
                $table->string('variation_orders_path')->nullable()->after('design_and_engineering_documents_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'suspensions_path')) {
                $table->string('suspensions_path')->nullable()->after('variation_orders_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'work_resumptions_path')) {
                $table->string('work_resumptions_path')->nullable()->after('suspensions_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'time_extensions_path')) {
                $table->string('time_extensions_path')->nullable()->after('work_resumptions_path');
            }

            if (!Schema::hasColumn('pre_implementation_documents', 'cancellation_termination_path')) {
                $table->string('cancellation_termination_path')->nullable()->after('time_extensions_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pre_implementation_documents')) {
            return;
        }

        Schema::table('pre_implementation_documents', function (Blueprint $table) {
            $columns = [
                'program_of_works_path',
                'design_and_engineering_documents_path',
                'variation_orders_path',
                'suspensions_path',
                'work_resumptions_path',
                'time_extensions_path',
                'cancellation_termination_path',
            ];

            $existingColumns = array_values(array_filter($columns, function (string $column): bool {
                return Schema::hasColumn('pre_implementation_documents', $column);
            }));

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
