<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fund_utilization_approval_workflows')) {
            Schema::create('fund_utilization_approval_workflows', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('document_type', 80);
                $table->unsignedBigInteger('uploader_id')->nullable();
                $table->string('uploader_role', 80)->nullable();
                $table->unsignedSmallInteger('current_approval_level')->nullable();
                $table->unsignedSmallInteger('last_approved_level')->default(0);
                $table->unsignedSmallInteger('returned_from_level')->nullable();
                $table->unsignedBigInteger('current_approver_id')->nullable();
                $table->string('current_approver_role', 80)->nullable();
                $table->string('status', 120)->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['project_code', 'quarter', 'document_type'], 'fur_workflows_unique_submission');
                $table->index(['current_approver_id', 'status'], 'fur_workflows_approver_status_idx');
                $table->index(['project_code', 'quarter'], 'fur_workflows_project_quarter_idx');
            });
        }

        if (!Schema::hasTable('approval_logs')) {
            Schema::create('approval_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('submission_id');
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('document_type', 80);
                $table->unsignedBigInteger('approver_id')->nullable();
                $table->unsignedBigInteger('uploader_id')->nullable();
                $table->unsignedSmallInteger('approval_level')->nullable();
                $table->string('action', 40);
                $table->text('remarks')->nullable();
                $table->string('previous_status', 120)->nullable();
                $table->string('new_status', 120)->nullable();
                $table->unsignedInteger('revision_number')->default(1);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('submission_id', 'approval_logs_submission_fk')
                    ->references('id')
                    ->on('fund_utilization_approval_workflows')
                    ->cascadeOnDelete();

                $table->index(['project_code', 'quarter'], 'approval_logs_project_quarter_idx');
                $table->index(['document_type', 'action'], 'approval_logs_document_action_idx');
                $table->index(['submission_id', 'action'], 'approval_logs_submission_action_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('fund_utilization_approval_workflows');
    }
};
