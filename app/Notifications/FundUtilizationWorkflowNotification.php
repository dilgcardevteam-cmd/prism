<?php

namespace App\Notifications;

use App\Notifications\Channels\SystemDatabaseNotificationChannel;
use App\Support\NotificationUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FundUtilizationWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $message,
        protected string $url,
        protected string $documentType,
        protected string $quarter,
        protected ?int $senderUserId = null,
        protected ?string $senderName = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [SystemDatabaseNotificationChannel::class];
    }

    public function toSystemDatabase(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'url' => NotificationUrl::normalizeForStorage($this->url),
            'document_type' => $this->documentType,
            'quarter' => $this->quarter,
            'sender_user_id' => $this->senderUserId,
            'sender_name' => $this->senderName,
        ];
    }
}
