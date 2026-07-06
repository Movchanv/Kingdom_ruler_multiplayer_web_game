<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ChatMessage $message,
        public readonly ?string $author = null,
    ) {}

    /**
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('country.'.$this->message->country_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'type' => $this->message->type->value,
            'body' => $this->message->body,
            'player_id' => $this->message->player_id,
            'author' => $this->author,
            'at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
