<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TownUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly int $townId) {}

    /**
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('town.'.$this->townId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'town.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'town_id' => $this->townId,
            'at' => now()->toIso8601String(),
        ];
    }
}
