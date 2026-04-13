<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('annual_rpmes_form4_uploads')) {
            return;
        }

        Schema::create('annual_rpmes_form4_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('project_code');
            $table->enum('quarter', ['Annual']);
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

            $table->unique(['project_code', 'quarter'], 'annual_rpmes_form4_uploads_project_quarter_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_rpmes_form4_uploads');
    }
};
