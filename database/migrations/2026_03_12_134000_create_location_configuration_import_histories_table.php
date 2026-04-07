<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_configuration_import_histories')) {
            Schema::create('location_configuration_import_histories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('dataset_key', 64);
                $table->string('original_file_name');
                $table->string('stored_file_path');
                $table->unsignedBigInteger('file_size_bytes')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->timestamp('last_loaded_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('dataset_key');
                $table->index('imported_at');
                $table->index('created_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_configuration_import_histories');
    }
};
