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
        if (!Schema::hasTable('tbfur_fdp')) {
            return;
        }

        Schema::table('tbfur_fdp', function (Blueprint $table) {
            if (!Schema::hasColumn('tbfur_fdp', 'posting_link')) {
                $table->text('posting_link')->nullable()->after('fdp_file_path');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_status')) {
                $table->enum('posting_status', ['pending', 'approved', 'returned'])
                    ->default('pending')
                    ->after('posting_link');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_approved_by')) {
                $table->unsignedBigInteger('posting_approved_by')->nullable()->after('posting_status');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_approved_at')) {
                $table->timestamp('posting_approved_at')->nullable()->after('posting_approved_by');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_remarks')) {
                $table->text('posting_remarks')->nullable()->after('posting_approved_at');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_uploaded_at')) {
                $table->timestamp('posting_uploaded_at')->nullable()->after('posting_remarks');
            }
            if (!Schema::hasColumn('tbfur_fdp', 'posting_encoder_id')) {
                $table->unsignedBigInteger('posting_encoder_id')->nullable()->after('posting_uploaded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tbfur_fdp')) {
            return;
        }

        Schema::table('tbfur_fdp', function (Blueprint $table) {
            $table->dropColumn([
                'posting_link',
                'posting_status',
                'posting_approved_by',
                'posting_approved_at',
                'posting_remarks',
                'posting_uploaded_at',
                'posting_encoder_id',
            ]);
        });
    }
};

