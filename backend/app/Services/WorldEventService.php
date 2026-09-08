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

        $actionsSince = $this->actionsSinceLastEvent($game);
        $pressure = $this->pressure($game);
        $chances = $this->chances($pressure);
        $difficulty = $this->drawDifficulty($chances);
        $intensity = $this->intensityFor($pressure);

        $event = $this->events->draw(EventType::World, $difficulty)
            ?? $this->events->draw(EventType::World);

        if ($event === null) {
            return null;
        }

        $applied = DB::transaction(function () use ($event, $town, $game, $intensity): array {
            $result = $this->events->applyToTown($event, $town, $intensity);
            $game->forceFill(['last_world_event_at' => Carbon::now()])->save();

            return $result;
        });

        return [
            'game_id' => $game->id,
            'town_id' => $town->id,
            'event' => $event->name,
            'difficulty' => $difficulty->value,
            'pressure' => round($pressure, 2),
            'intensity' => round($intensity, 2),
            'chances' => array_map(static fn (float $w): float => round($w, 3), $chances),
            'actions_since_last_event' => $actionsSince,
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

    private const AGE_WEIGHT = 0.35;

    private const ACTIVITY_WEIGHT = 0.65;

    private const MAX_INTENSITY = 2.0;

    private const MIN_TIER_CHANCE = 0.05;

    /**
     * @return array<string, float>
     */
    private function chances(float $pressure): array
    {
        $p = max(0.0, min(1.0, $pressure));
        $scale = 1 - 3 * self::MIN_TIER_CHANCE;

        return [
            EventDifficulty::Easy->value => $scale * (1 - $p) ** 2 + self::MIN_TIER_CHANCE,
            EventDifficulty::Medium->value => $scale * 2 * $p * (1 - $p) + self::MIN_TIER_CHANCE,
            EventDifficulty::Hard->value => $scale * $p ** 2 + self::MIN_TIER_CHANCE,
        ];
    }

    /**
     * @param  array<string, float>  $chances
     */
    private function drawDifficulty(array $chances): EventDifficulty
    {
        $roll = random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
        $cumulative = 0.0;

        foreach ($chances as $value => $weight) {
            $cumulative += $weight;

            if ($roll <= $cumulative) {
                return EventDifficulty::from($value);
            }
        }

        return EventDifficulty::Hard;
    }

    private function intensityFor(float $pressure): float
    {
        $p = max(0.0, min(1.0, $pressure));

        return 1.0 + (self::MAX_INTENSITY - 1.0) * $p;
    }

    private function pressure(Game $game): float
    {
        $age = $this->ageScore($this->ageInDays($game));
        $activity = $this->activityScore($game);

        return self::AGE_WEIGHT * $age + self::ACTIVITY_WEIGHT * $activity;
    }

    private function ageScore(int $days): float
    {
        return min($days / 5, 1.0);
    }

    private function activityScore(Game $game): float
    {
        $dailyActions = (int) ($game->config['daily_actions'] ?? 5);
        $players = $game->players()->count();
        $intervalHours = $this->intervalHoursForAge($this->ageInDays($game));

        $nominal = $players * $dailyActions * $intervalHours / 24;
        $sustained = max(1.0, $nominal * 1.5);

        return min($this->actionsSinceLastEvent($game) / $sustained, 1.0);
    }

    private function actionsSinceLastEvent(Game $game): int
    {
        $since = $game->last_world_event_at ?? $game->started_at;

        return $game->actionLogs()
            ->when($since !== null, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();
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
