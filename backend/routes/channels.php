<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Town;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('country.{countryId}', function (User $user, int $countryId): bool {
    return $user->players()
        ->where('country_id', $countryId)
        ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Active))
        ->exists();
});

Broadcast::channel('town.{townId}', function (User $user, int $townId): bool {
    $gameId = Town::query()->whereKey($townId)->value('game_id');

    if ($gameId === null) {
        return false;
    }

    return $user->players()
        ->where('game_id', $gameId)
        ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Active))
        ->exists();
});

Broadcast::channel('presence-room.{roomId}', function (User $user, string $roomId): array {
    return ['id' => $user->id, 'name' => $user->username];
});
