<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quarterly_rpmes_form2_uploads')) {
            Schema::create('quarterly_rpmes_form2_uploads', function (Blueprint $table) {
                $table->id();
                $table->string('project_code');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->string('file_path')->nullable();
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['project_code', 'quarter'],
                    'quarterly_rpmes_form2_uploads_project_quarter_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quarterly_rpmes_form2_uploads');
    }
};
