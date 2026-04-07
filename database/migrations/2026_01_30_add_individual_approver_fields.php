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
        // Add separate approver fields to tbfur_written_notice for each document type
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->unsignedBigInteger('dbm_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('dbm_approved_by_dilg_ro')->nullable();
            $table->unsignedBigInteger('dilg_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('dilg_approved_by_dilg_ro')->nullable();
            $table->unsignedBigInteger('speaker_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('speaker_approved_by_dilg_ro')->nullable();
            $table->unsignedBigInteger('president_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('president_approved_by_dilg_ro')->nullable();
            $table->unsignedBigInteger('house_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('house_approved_by_dilg_ro')->nullable();
            $table->unsignedBigInteger('senate_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('senate_approved_by_dilg_ro')->nullable();
        });

        // Add separate approver fields to tbfur_fdp for posting
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->unsignedBigInteger('posting_approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('posting_approved_by_dilg_ro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove separate approver fields from tbfur_written_notice
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->dropColumn([
                'dbm_approved_by_dilg_po', 'dbm_approved_by_dilg_ro',
                'dilg_approved_by_dilg_po', 'dilg_approved_by_dilg_ro',
                'speaker_approved_by_dilg_po', 'speaker_approved_by_dilg_ro',
                'president_approved_by_dilg_po', 'president_approved_by_dilg_ro',
                'house_approved_by_dilg_po', 'house_approved_by_dilg_ro',
                'senate_approved_by_dilg_po', 'senate_approved_by_dilg_ro'
            ]);
        });

        // Remove separate approver fields from tbfur_fdp
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn(['posting_approved_by_dilg_po', 'posting_approved_by_dilg_ro']);
        });
    }
};
