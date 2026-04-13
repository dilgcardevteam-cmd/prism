<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createUploadTable('quarterly_rpmes_form5_uploads', 'quarterly_rpmes_form5_uploads_project_quarter_unique');
        $this->createUploadTable('quarterly_rpmes_form6_uploads', 'quarterly_rpmes_form6_uploads_project_quarter_unique');
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_rpmes_form6_uploads');
        Schema::dropIfExists('quarterly_rpmes_form5_uploads');
    }

    private function createUploadTable(string $tableName, string $uniqueName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($uniqueName) {
            $table->id();
            $table->string('project_code');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('user_remarks')->nullable();
            $table->timestamps();

            $table->unique(['project_code', 'quarter'], $uniqueName);
        });
    }
};
