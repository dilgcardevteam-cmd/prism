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
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('content');
                $table->string('type', 50)->default('general'); // general, urgent, advisory, update, maintenance, event
                $table->string('priority', 50)->default('normal'); // low, normal, high, urgent
                $table->string('target_audience', 50)->default('all'); // all, role, province
                $table->json('target_roles')->nullable(); // JSON array of role strings
                $table->json('target_provinces')->nullable(); // JSON array of province strings
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->string('attachment_disk', 50)->default('public');
                $table->string('action_url')->nullable();
                $table->string('action_text', 100)->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->string('status', 50)->default('published'); // draft, published, archived
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['status', 'published_at']);
                $table->index(['is_pinned', 'published_at']);
            });
        }

        if (!Schema::hasTable('announcement_reads')) {
            Schema::create('announcement_reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('announcement_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id'], 'announcement_user_read_unique');
                $table->foreign('announcement_id')
                    ->references('id')
                    ->on('announcements')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};

