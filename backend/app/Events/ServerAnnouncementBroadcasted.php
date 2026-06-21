<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ServerAnnouncementBroadcasted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly string $message) {}

    /**
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('lobby'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'server.announcement';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'at' => now()->toIso8601String(),
        ];
    }
}
