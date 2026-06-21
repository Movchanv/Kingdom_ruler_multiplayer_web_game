<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('presence-room.{roomId}', function (User $user, string $roomId): array {
    return ['id' => $user->id, 'name' => $user->name];
});
