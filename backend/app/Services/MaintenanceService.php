<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Town;
use App\Models\TownResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MaintenanceService
{
    public function __construct(private readonly TownBonusService $bonuses) {}

    public function runCycle(): int
    {
        $count = 0;

        foreach (Game::query()->where('status', GameStatus::Active)->get() as $game) {
            $this->processGame($game);
            $count++;
        }

        return $count;
    }

    public function processGame(Game $game): void
    {
        $hadTowns = $game->towns()->exists();

        foreach ($game->towns()->whereNull('destroyed_at')->get() as $town) {
            $this->processTown($game, $town);
        }

        if ($hadTowns && $game->towns()->whereNull('destroyed_at')->count() === 0) {
            $this->endSeason($game);
        }
    }

    private function processTown(Game $game, Town $town): void
    {
        $townResources = $town->townResources()->with('resource')->get()
            ->keyBy(fn (TownResource $townResource): string => $townResource->resource->key);

        $this->applyPerDayBonuses($town, $townResources);
        $this->applySoldierUpkeep($game, $town, $townResources);

        $town->loyalty = max(0, min(100, $town->loyalty));

        if ($town->loyalty <= 0 && $town->destroyed_at === null) {
            $town->destroyed_at = Carbon::now();
        }

        $town->save();
    }

    /**
     * @param  Collection<string, TownResource>  $townResources
     */
    private function applyPerDayBonuses(Town $town, Collection $townResources): void
    {
        foreach ($this->bonuses->forTown($town) as $key => $value) {
            if (! str_ends_with($key, '_per_day')) {
                continue;
            }

            if ($key === 'loyalty_per_day') {
                $town->loyalty = min(100, $town->loyalty + $value);

                continue;
            }

            $resourceKey = substr($key, 0, -strlen('_per_day'));
            $this->addResource($townResources, $resourceKey, $value);
        }
    }

    /**
     * @param  Collection<string, TownResource>  $townResources
     */
    private function applySoldierUpkeep(Game $game, Town $town, Collection $townResources): void
    {
        $soldiers = $this->amountOf($townResources, 'soldiers');

        if ($soldiers <= 0) {
            return;
        }

        /** @var array<string, int> $upkeep */
        $upkeep = $game->config['soldier_upkeep'] ?? ['gold' => 1, 'food' => 1];

        $needed = [];
        foreach ($upkeep as $resourceKey => $perSoldier) {
            $needed[$resourceKey] = $soldiers * (int) $perSoldier;
        }

        $canPay = true;
        foreach ($needed as $resourceKey => $amount) {
            if ($this->amountOf($townResources, $resourceKey) < $amount) {
                $canPay = false;
                break;
            }
        }

        foreach ($needed as $resourceKey => $amount) {
            $this->addResource($townResources, $resourceKey, -$amount);
        }

        if ($canPay) {
            return;
        }

        $rate = (float) ($game->config['desertion_rate'] ?? 0.2);
        $deserters = max(1, (int) floor($soldiers * $rate));
        $this->addResource($townResources, 'soldiers', -$deserters);

        $penalty = (int) ($game->config['upkeep_loyalty_penalty'] ?? 5);
        $town->loyalty -= $penalty;
    }

    /**
     * @param  Collection<string, TownResource>  $townResources
     */
    private function addResource(Collection $townResources, string $key, int $delta): void
    {
        if (! $townResources->has($key)) {
            return;
        }

        /** @var TownResource $townResource */
        $townResource = $townResources->get($key);

        $new = $townResource->amount + $delta;
        if ($delta > 0 && $townResource->capacity !== null) {
            $new = min($new, $townResource->capacity);
        }
        $new = max(0, $new);

        if ($new !== $townResource->amount) {
            $townResource->update(['amount' => $new]);
        }
    }

    /**
     * @param  Collection<string, TownResource>  $townResources
     */
    private function amountOf(Collection $townResources, string $key): int
    {
        return $townResources->has($key) ? $townResources->get($key)->amount : 0;
    }

    private function endSeason(Game $game): void
    {
        $rows = DB::table('action_logs')
            ->join('players', 'players.id', '=', 'action_logs.player_id')
            ->where('action_logs.game_id', $game->id)
            ->groupBy('action_logs.player_id', 'players.user_id')
            ->select('action_logs.player_id', 'players.user_id')
            ->selectRaw('SUM(action_logs.xp_gained) as xp, COUNT(*) as actions')
            ->orderByDesc('xp')
            ->get();

        $ranking = [];
        $rank = 1;

        foreach ($rows as $row) {
            $ranking[] = [
                'rank' => $rank++,
                'player_id' => (int) $row->player_id,
                'user_id' => (int) $row->user_id,
                'xp' => (int) $row->xp,
                'actions' => (int) $row->actions,
            ];
        }

        $game->forceFill([
            'status' => GameStatus::Ended,
            'ended_at' => Carbon::now(),
            'results' => [
                'reason' => 'all_towns_destroyed',
                'ended_at' => Carbon::now()->toIso8601String(),
                'ranking' => $ranking,
            ],
        ])->save();
    }
}
