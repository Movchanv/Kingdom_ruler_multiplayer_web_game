<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class WorldEventService
{
    public function __construct(private readonly EventService $events) {}

    public function tick(): int
    {
        $fired = 0;

        foreach (Game::query()->where('status', GameStatus::Active)->get() as $game) {
            if ($this->fire($game) !== null) {
                $fired++;
            }
        }

        return $fired;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fire(Game $game): ?array
    {
        if (! $this->isDue($game)) {
            return null;
        }

        $town = $game->towns()->whereNull('destroyed_at')->inRandomOrder()->first();

        if ($town === null) {
            return null;
        }

        $difficulty = $this->difficultyForAge($this->ageInDays($game));

        $event = $this->events->draw(EventType::World, $difficulty)
            ?? $this->events->draw(EventType::World);

        if ($event === null) {
            return null;
        }

        $applied = DB::transaction(function () use ($event, $town, $game): array {
            $result = $this->events->applyToTown($event, $town);
            $game->forceFill(['last_world_event_at' => Carbon::now()])->save();

            return $result;
        });

        return [
            'game_id' => $game->id,
            'town_id' => $town->id,
            'event' => $event->name,
            'difficulty' => $difficulty->value,
            'effects' => $applied,
        ];
    }

    public function isDue(Game $game): bool
    {
        if ($game->last_world_event_at === null) {
            return true;
        }

        $interval = $this->intervalHoursForAge($this->ageInDays($game));

        return $game->last_world_event_at->lte(Carbon::now()->subHours($interval));
    }

    private function ageInDays(Game $game): int
    {
        if ($game->started_at === null) {
            return 0;
        }

        return (int) $game->started_at->diffInDays(Carbon::now());
    }

    private function difficultyForAge(int $days): EventDifficulty
    {
        return match (true) {
            $days < 2 => EventDifficulty::Easy,
            $days < 5 => EventDifficulty::Medium,
            default => EventDifficulty::Hard,
        };
    }

    private function intervalHoursForAge(int $days): int
    {
        return match (true) {
            $days < 2 => 12,
            $days < 5 => 8,
            default => 4,
        };
    }
}
