<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_messages')) {
            return;
        }

        if (!Schema::hasTable('message_threads')) {
            Schema::create('message_threads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->boolean('is_group')->default(false);
                $table->string('name', 150)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('is_group');
            });
        }

        if (!Schema::hasTable('message_thread_members')) {
            Schema::create('message_thread_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('thread_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['thread_id', 'user_id']);
                $table->index('user_id');
            });
        }

        if (!Schema::hasColumn('user_messages', 'thread_id')) {
            Schema::table('user_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('thread_id')->nullable()->after('id');
                $table->index(['thread_id', 'recipient_id']);
            });
        }

        $pairs = DB::table('user_messages')
            ->selectRaw('LEAST(sender_id, recipient_id) as user_a, GREATEST(sender_id, recipient_id) as user_b')
            ->whereNotNull('sender_id')
            ->whereNotNull('recipient_id')
            ->groupBy('user_a', 'user_b')
            ->get();

        foreach ($pairs as $pair) {
            $userA = (int) ($pair->user_a ?? 0);
            $userB = (int) ($pair->user_b ?? 0);
            if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
                continue;
            }

            $threadId = DB::table('message_threads')->insertGetId([
                'is_group' => false,
                'name' => null,
                'created_by' => $userA,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('message_thread_members')->insert([
                [
                    'thread_id' => $threadId,
                    'user_id' => $userA,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'thread_id' => $threadId,
                    'user_id' => $userB,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            DB::table('user_messages')
                ->whereRaw('LEAST(sender_id, recipient_id) = ? AND GREATEST(sender_id, recipient_id) = ?', [$userA, $userB])
                ->update([
                    'thread_id' => $threadId,
                    'updated_at' => now(),
                ]);
        }

        DB::statement(
            'INSERT INTO user_messages (thread_id, sender_id, recipient_id, message, read_at, created_at, updated_at)
             SELECT um.thread_id, um.sender_id, um.sender_id, um.message, COALESCE(um.read_at, um.created_at), um.created_at, um.updated_at
             FROM user_messages um
             WHERE um.thread_id IS NOT NULL AND um.recipient_id <> um.sender_id'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_messages', 'thread_id')) {
            Schema::table('user_messages', function (Blueprint $table) {
                $table->dropIndex(['thread_id', 'recipient_id']);
                $table->dropColumn('thread_id');
            });
        }

        Schema::dropIfExists('message_thread_members');
        Schema::dropIfExists('message_threads');
    }
};
