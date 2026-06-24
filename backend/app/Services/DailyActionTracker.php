<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class DailyActionTracker
{
    public function used(Player $player): int
    {
        return (int) Cache::get($this->key($player), 0);
    }

    public function remaining(Player $player, int $limit): int
    {
        return max(0, $limit - $this->used($player));
    }
    
    public function consume(Player $player, int $cost, int $limit): bool
    {
        $key = $this->key($player);

        if (Cache::get($key) === null) {
            Cache::put($key, 0, $this->secondsUntilMidnight());
        }

        $total = (int) Cache::increment($key, $cost);

        if ($total > $limit) {
            Cache::decrement($key, $cost);

            return false;
        }

        return true;
    }

    private function key(Player $player): string
    {
        return sprintf('actions:%d:%s', $player->id, Carbon::now()->toDateString());
    }

    private function secondsUntilMidnight(): int
    {
        return (int) Carbon::now()->diffInSeconds(Carbon::tomorrow(), true);
    }
}
