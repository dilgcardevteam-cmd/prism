<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageThreadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $recipientIds,
        public int $threadId,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return collect($this->recipientIds)
            ->map(fn ($recipientId) => (int) $recipientId)
            ->filter(fn ($recipientId) => $recipientId > 0)
            ->unique()
            ->map(fn ($recipientId) => new PrivateChannel('users.'.$recipientId.'.messages'))
            ->values()
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'message.thread.updated';
    }

    /**
     * @return array<string, int|string>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
