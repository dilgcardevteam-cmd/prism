<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fund_utilization_batch_approvals')) {
            Schema::create('fund_utilization_batch_approvals', function (Blueprint $table) {
                $table->id();
                $table->string('batch_code', 64)->unique();
                $table->string('quarter', 8);
                $table->unsignedSmallInteger('year')->default(2026);
                $table->unsignedBigInteger('uploader_id')->nullable()->index();
                $table->string('uploader_role', 32)->nullable();
                $table->string('uploader_province', 64)->nullable()->index();
                $table->string('uploader_office', 128)->nullable();
                $table->json('project_codes');
                $table->unsignedInteger('project_count')->default(0);
                $table->json('documents_manifest')->nullable();
                $table->unsignedTinyInteger('current_approval_level')->nullable()->default(1)->index();
                $table->unsignedTinyInteger('last_approved_level')->default(0);
                $table->unsignedTinyInteger('returned_from_level')->nullable();
                $table->unsignedBigInteger('current_approver_id')->nullable()->index();
                $table->string('current_approver_role', 32)->nullable();
                $table->string('status', 64)->default('Pending Level 1 - Provincial Officer')->index();
                $table->text('approval_remarks')->nullable();
                $table->unsignedInteger('revision_number')->default(1);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['year', 'quarter']);
                $table->index(['uploader_province', 'status']);
            });
        }

        if (!Schema::hasTable('fund_utilization_batch_approval_logs')) {
            Schema::create('fund_utilization_batch_approval_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('batch_id')->index();
                $table->string('batch_code', 64)->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('action', 64);
                $table->unsignedTinyInteger('approval_level')->nullable();
                $table->string('previous_status', 64)->nullable();
                $table->string('new_status', 64)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        $documentTables = [
            'tbfur_mov_uploads',
            'tbfur_written_notice',
            'tbfur_fdp',
            'tbfur_batch_documents',
        ];

        foreach ($documentTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'batch_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('batch_code', 64)->nullable()->after('quarter')->index();
                });
            }
        }
    }

    public function down(): void
    {
        $documentTables = [
            'tbfur_mov_uploads',
            'tbfur_written_notice',
            'tbfur_fdp',
            'tbfur_batch_documents',
        ];

        foreach ($documentTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'batch_code')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('batch_code');
                });
            }
        }

        Schema::dropIfExists('fund_utilization_batch_approval_logs');
        Schema::dropIfExists('fund_utilization_batch_approvals');
    }
};
