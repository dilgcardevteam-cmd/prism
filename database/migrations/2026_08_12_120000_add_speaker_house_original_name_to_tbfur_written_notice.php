<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbfur_written_notice') && !Schema::hasColumn('tbfur_written_notice', 'speaker_house_original_name')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->string('speaker_house_original_name')->nullable()->after('speaker_house_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbfur_written_notice') && Schema::hasColumn('tbfur_written_notice', 'speaker_house_original_name')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                $table->dropColumn('speaker_house_original_name');
            });
        }
    }
};