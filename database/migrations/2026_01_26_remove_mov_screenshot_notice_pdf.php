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
        // Drop the MOV Screenshot and Written Notice PDF columns
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->dropColumn(['notice_screenshot_path', 'notice_pdf_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the columns if migration is rolled back
        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->string('notice_screenshot_path')->nullable();
            $table->string('notice_pdf_path')->nullable();
        });
    }
};
