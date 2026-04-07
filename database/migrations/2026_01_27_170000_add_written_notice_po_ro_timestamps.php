<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $documents = ['dbm', 'dilg', 'speaker', 'president', 'house', 'senate'];

            foreach ($documents as $doc) {
                $poColumn = "{$doc}_approved_at_dilg_po";
                $roColumn = "{$doc}_approved_at_dilg_ro";

                if (!Schema::hasColumn('tbfur_written_notice', $poColumn)) {
                    $table->timestamp($poColumn)->nullable()->after("{$doc}_approved_at");
                }

                if (!Schema::hasColumn('tbfur_written_notice', $roColumn)) {
                    $table->timestamp($roColumn)->nullable()->after($poColumn);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $columns = [
                'dbm_approved_at_dilg_po',
                'dbm_approved_at_dilg_ro',
                'dilg_approved_at_dilg_po',
                'dilg_approved_at_dilg_ro',
                'speaker_approved_at_dilg_po',
                'speaker_approved_at_dilg_ro',
                'president_approved_at_dilg_po',
                'president_approved_at_dilg_ro',
                'house_approved_at_dilg_po',
                'house_approved_at_dilg_ro',
                'senate_approved_at_dilg_po',
                'senate_approved_at_dilg_ro',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tbfur_written_notice', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
