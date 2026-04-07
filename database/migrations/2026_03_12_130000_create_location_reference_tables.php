<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_regions', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 60)->nullable()->index();
            $table->string('region_name', 150)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('location_provinces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->string('province_code', 60)->nullable()->index();
            $table->string('province_name', 150)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('location_city_municipalities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->string('citymun_code', 60)->nullable()->index();
            $table->string('citymun_name', 150)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_city_municipalities');
        Schema::dropIfExists('location_provinces');
        Schema::dropIfExists('location_regions');
    }
};
