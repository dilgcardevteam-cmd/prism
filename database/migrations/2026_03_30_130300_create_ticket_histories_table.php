<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_histories')) {
            return;
        }

        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('action', 80);
            $table->string('description', 500);
            $table->string('from_status', 60)->nullable();
            $table->string('to_status', 60)->nullable();
            $table->string('from_level', 40)->nullable();
            $table->string('to_level', 40)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('actor_id')->references('idno')->on('tbusers')->nullOnDelete();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_histories');
    }
};
