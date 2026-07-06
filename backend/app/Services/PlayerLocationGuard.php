<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\Player;
use App\Models\Town;
use Illuminate\Validation\ValidationException;

final class PlayerLocationGuard
{
    public function requireActiveTown(Player $player): Town
    {
        $town = $player->currentTown()->first();

        if ($town === null) {
            throw ValidationException::withMessages([
                'town' => [__('You are not located in any town.')],
            ]);
        }

        if ($town->destroyed_at !== null) {
            throw ValidationException::withMessages([
                'town' => [__('This town has been destroyed.')],
            ]);
        }

        if ($player->game()->first()?->status !== GameStatus::Active) {
            throw ValidationException::withMessages([
                'town' => [__('This season has ended.')],
            ]);
        }

        return $town;
    }
}
