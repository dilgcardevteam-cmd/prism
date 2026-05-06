<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $recipientIds,
        public int $threadId,
        public int $userId,
        public bool $typing,
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
        return 'message.typing';
    }

    /**
     * @return array<string, int|bool>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->threadId,
            'user_id' => $this->userId,
            'typing' => $this->typing,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
