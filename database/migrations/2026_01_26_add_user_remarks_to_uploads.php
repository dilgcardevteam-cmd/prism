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
        // Add user_remarks to MOV uploads
        if (Schema::hasTable('tbfur_mov_uploads')) {
            Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_mov_uploads', 'user_remarks')) {
                    $table->text('user_remarks')->nullable()->after('approval_remarks');
                }
            });
        }

        // Add user_remarks to Written Notice uploads
        if (Schema::hasTable('tbfur_written_notice')) {
            Schema::table('tbfur_written_notice', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_written_notice', 'user_remarks')) {
                    $table->text('user_remarks')->nullable()->after('approval_remarks');
                }
            });
        }

        // Add user_remarks to FDP uploads
        if (Schema::hasTable('tbfur_fdp')) {
            Schema::table('tbfur_fdp', function (Blueprint $table) {
                if (!Schema::hasColumn('tbfur_fdp', 'user_remarks')) {
                    $table->text('user_remarks')->nullable()->after('approval_remarks');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbfur_mov_uploads', function (Blueprint $table) {
            $table->dropColumn('user_remarks');
        });

        Schema::table('tbfur_written_notice', function (Blueprint $table) {
            $table->dropColumn('user_remarks');
        });

        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn('user_remarks');
        });
    }
};
