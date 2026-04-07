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
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_fdp', 'posting_approved_at_dilg_po')) {
                $table->datetime('posting_approved_at_dilg_po')->nullable()->after('posting_approved_at')->comment('DILG PO (Province Office) Level Approval Time for Posting Link');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn('posting_approved_at_dilg_po');
        });
    }
};
