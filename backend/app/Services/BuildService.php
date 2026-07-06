<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TownUpdated;
use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Player;
use App\Models\TownBuilding;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BuildService
{
    public function __construct(
        private readonly DailyActionTracker $tracker,
        private readonly ProgressionService $progression,
        private readonly PlayerLocationGuard $guard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function contribute(Player $player, TownBuilding $townBuilding): array
    {
        $town = $this->guard->requireActiveTown($player);

        if ($townBuilding->town_id !== $town->id) {
            throw ValidationException::withMessages([
                'building' => [__('This building is not in your current town.')],
            ]);
        }

        $building = $townBuilding->building()->first();

        if ($building === null || $townBuilding->level >= $building->max_level) {
            throw ValidationException::withMessages([
                'building' => [__('This building is already at its maximum level.')],
            ]);
        }

        $nextLevel = $townBuilding->level + 1;
        $levelRow = $building->levels()->where('level', $nextLevel)->first();

        /** @var array<string, int> $cost */
        $cost = $levelRow->cost ?? [];

        $action = Action::query()->where('key', 'build')->first();

        if ($action === null || ! $action->is_active) {
            throw ValidationException::withMessages([
                'building' => [__('Construction is not available right now.')],
            ]);
        }

        $game = $player->game()->first();
        $limit = (int) ($game?->config['daily_actions'] ?? 5);
        $buildStep = (int) ($game?->config['build_step'] ?? 20);

        $townResources = $town->townResources()->with('resource')->get()
            ->keyBy(fn (TownResource $townResource): string => $townResource->resource->key);

        /** @var array<string, int> $contributions */
        $contributions = $townBuilding->contributions ?? [];

        $plan = $this->planContribution($cost, $contributions, $townResources, $buildStep);

        if (array_sum($plan) === 0) {
            throw ValidationException::withMessages([
                'building' => [__('The town lacks the resources to make progress here.')],
            ]);
        }

        if (! $this->tracker->consume($player, $action->ap_cost, $limit)) {
            throw ValidationException::withMessages([
                'building' => [__('You have no action points left today.')],
            ]);
        }

        try {
            return DB::transaction(function () use (
                $player, $action, $town, $townBuilding, $building, $levelRow,
                $nextLevel, $cost, $contributions, $townResources, $plan, $limit,
            ): array {
                foreach ($plan as $resourceKey => $take) {
                    /** @var TownResource $townResource */
                    $townResource = $townResources->get($resourceKey);
                    $townResource->update(['amount' => $townResource->amount - $take]);

                    $contributions[$resourceKey] = ($contributions[$resourceKey] ?? 0) + $take;
                }

                $leveledUp = $this->isComplete($cost, $contributions);
                $bonus = null;

                if ($leveledUp) {
                    $bonus = $levelRow?->bonus;
                    $townBuilding->level = $nextLevel;
                    $townBuilding->contributions = [];
                } else {
                    $townBuilding->contributions = $contributions;
                }

                $townBuilding->save();

                /** @var User $user */
                $user = $player->user()->first();
                $xpGained = $this->progression->award($user, $action->base_xp);

                ActionLog::create([
                    'game_id' => $player->game_id,
                    'player_id' => $player->id,
                    'town_id' => $town->id,
                    'action_id' => $action->id,
                    'payload' => ['town_building_id' => $townBuilding->id, 'contributed' => $plan],
                    'result' => ['level' => $townBuilding->level, 'leveled_up' => $leveledUp],
                    'xp_gained' => $xpGained,
                ]);

                event(new TownUpdated($town->id));

                return [
                    'building' => $building->key,
                    'level' => $townBuilding->level,
                    'leveled_up' => $leveledUp,
                    'spent' => $plan,
                    'contributions' => $townBuilding->contributions,
                    'remaining_cost' => $leveledUp ? null : $this->remainingCost($cost, $contributions),
                    'bonus' => $bonus,
                    'xp_gained' => $xpGained,
                    'total_xp' => $user->xp,
                    'title' => $user->title?->name,
                    'actions_remaining' => $this->tracker->remaining($player, $limit),
                ];
            });
        } catch (Throwable $e) {
            $this->tracker->consume($player, -$action->ap_cost, $limit);

            throw $e;
        }
    }

    /**
     * @param  array<string, int>  $cost
     * @param  array<string, int>  $contributions
     * @param  Collection<string, TownResource>  $townResources
     * @return array<string, int>
     */
    private function planContribution(array $cost, array $contributions, $townResources, int $buildStep): array
    {
        $plan = [];

        foreach ($cost as $resourceKey => $required) {
            $needed = $required - ($contributions[$resourceKey] ?? 0);

            if ($needed <= 0) {
                continue;
            }

            $available = $townResources->has($resourceKey)
                ? $townResources->get($resourceKey)->amount
                : 0;
            $take = min($buildStep, $needed, $available);

            if ($take > 0) {
                $plan[$resourceKey] = $take;
            }
        }

        return $plan;
    }

    /**
     * @param  array<string, int>  $cost
     * @param  array<string, int>  $contributions
     */
    private function isComplete(array $cost, array $contributions): bool
    {
        foreach ($cost as $resourceKey => $required) {
            if (($contributions[$resourceKey] ?? 0) < $required) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $cost
     * @param  array<string, int>  $contributions
     * @return array<string, int>
     */
    private function remainingCost(array $cost, array $contributions): array
    {
        $remaining = [];

        foreach ($cost as $resourceKey => $required) {
            $left = $required - ($contributions[$resourceKey] ?? 0);

            if ($left > 0) {
                $remaining[$resourceKey] = $left;
            }
        }

        return $remaining;
    }
}
