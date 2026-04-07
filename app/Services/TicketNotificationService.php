<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Support\NotificationUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketNotificationService
{
    public function __construct(
        protected TicketRoutingService $routingService,
    ) {
    }

    public function notifyProvincialQueue(Ticket $ticket, User $actor): void
    {
        $recipients = $this->routingService
            ->provincialRecipientsForProvince($ticket->province_scope)
            ->reject(fn (User $recipient) => (int) $recipient->getKey() === (int) $actor->getKey())
            ->values();

        $message = sprintf(
            'New ticket %s from %s was submitted to the provincial queue for %s.',
            $ticket->ticket_number,
            $this->resolveActorName($actor),
            $ticket->province_scope ?: 'the assigned province'
        );

        $this->insertNotifications(
            recipients: $recipients,
            sender: $actor,
            message: $message,
            url: route('ticketing.show', $ticket, false),
            documentType: 'ticketing-system',
        );
    }

    public function notifyRegionalQueue(Ticket $ticket, User $actor): void
    {
        $recipients = $this->routingService
            ->regionalRecipientsForTicket($ticket)
            ->reject(fn (User $recipient) => (int) $recipient->getKey() === (int) $actor->getKey())
            ->values();

        $message = sprintf(
            'Ticket %s was escalated by %s to the regional queue for %s.',
            $ticket->ticket_number,
            $this->resolveActorName($actor),
            $ticket->region_scope ?: 'the assigned region'
        );

        $this->insertNotifications(
            recipients: $recipients,
            sender: $actor,
            message: $message,
            url: route('ticketing.show', $ticket, false),
            documentType: 'ticketing-system',
        );
    }

    protected function insertNotifications(
        Collection $recipients,
        User $sender,
        string $message,
        string $url,
        string $documentType,
    ): void {
        if ($recipients->isEmpty() || !Schema::hasTable('tbnotifications')) {
            return;
        }

        $url = NotificationUrl::normalizeForStorage($url);
        $senderId = (int) $sender->getKey();
        $senderName = $this->resolveActorName($sender);
        $now = now();
        $notificationMessage = Str::limit(trim($message), 500, '...');
        $hasSenderUserIdColumn = Schema::hasColumn('tbnotifications', 'sender_user_id');
        $hasSenderNameColumn = Schema::hasColumn('tbnotifications', 'sender_name');

        $rows = $recipients->map(function (User $recipient) use (
            $senderId,
            $senderName,
            $notificationMessage,
            $url,
            $documentType,
            $now,
            $hasSenderUserIdColumn,
            $hasSenderNameColumn,
        ): array {
            $row = [
                'user_id' => (int) $recipient->getKey(),
                'message' => $notificationMessage,
                'url' => $url,
                'document_type' => $documentType,
                'quarter' => null,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasSenderUserIdColumn) {
                $row['sender_user_id'] = $senderId;
            }

            if ($hasSenderNameColumn) {
                $row['sender_name'] = $senderName;
            }

            return $row;
        })->all();

        try {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('tbnotifications')->insert($chunk);
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to create ticketing notifications.', [
                'document_type' => $documentType,
                'recipient_count' => count($rows),
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function resolveActorName(User $actor): string
    {
        return $actor->fullName() !== ''
            ? $actor->fullName()
            : trim((string) ($actor->username ?? 'System User'));
    }
}
