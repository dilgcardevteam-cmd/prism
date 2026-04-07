<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblpmc_documents', function (Blueprint $table) {
            $table->string('status')->nullable()->after('file_path');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->timestamp('approved_at_dilg_po')->nullable()->after('approved_at');
            $table->timestamp('approved_at_dilg_ro')->nullable()->after('approved_at_dilg_po');
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable()->after('approved_at_dilg_ro');
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable()->after('approved_by_dilg_po');
            $table->text('approval_remarks')->nullable()->after('approved_by_dilg_ro');
            $table->text('user_remarks')->nullable()->after('approval_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('tblpmc_documents', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'approved_at',
                'approved_at_dilg_po',
                'approved_at_dilg_ro',
                'approved_by_dilg_po',
                'approved_by_dilg_ro',
                'approval_remarks',
                'user_remarks',
            ]);
        });
    }
};
