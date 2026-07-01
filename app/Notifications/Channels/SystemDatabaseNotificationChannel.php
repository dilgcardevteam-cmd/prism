<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemDatabaseNotificationChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!Schema::hasTable('tbnotifications') || !method_exists($notification, 'toSystemDatabase')) {
            return;
        }

        $payload = $notification->toSystemDatabase($notifiable);
        if (!is_array($payload) || empty($payload['message'])) {
            return;
        }

        $row = [
            'user_id' => (int) $notifiable->getKey(),
            'message' => $payload['message'],
            'url' => $payload['url'] ?? null,
            'document_type' => $payload['document_type'] ?? 'fund-utilization',
            'quarter' => $payload['quarter'] ?? null,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('tbnotifications', 'sender_user_id')) {
            $row['sender_user_id'] = $payload['sender_user_id'] ?? null;
        }

        if (Schema::hasColumn('tbnotifications', 'sender_name')) {
            $row['sender_name'] = $payload['sender_name'] ?? null;
        }

        try {
            DB::table('tbnotifications')->insert($row);
        } catch (\Throwable $exception) {
            Log::warning('Failed to store Fund Utilization workflow notification.', [
                'user_id' => $notifiable->getKey(),
                'document_type' => $row['document_type'],
                'quarter' => $row['quarter'],
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
