<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pisat_documents')) {
            return;
        }

        Schema::create('pisat_documents', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->index();
            $table->string('project_title');
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedInteger('uploaded_by');
            $table->string('office')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('idno')->on('tbusers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pisat_documents');
    }
};