<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('subay_project_profiles', 'reverted_amount')) {
                $table->text('reverted_amount')->nullable();
            }

            if (!Schema::hasColumn('subay_project_profiles', 'link_to_proof_of_reversion')) {
                $table->text('link_to_proof_of_reversion')->nullable();
            }

            if (!Schema::hasColumn('subay_project_profiles', 'reversion_date_added')) {
                $table->text('reversion_date_added')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return;
        }

        Schema::table('subay_project_profiles', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'reverted_amount',
                'link_to_proof_of_reversion',
                'reversion_date_added',
            ] as $column) {
                if (Schema::hasColumn('subay_project_profiles', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
