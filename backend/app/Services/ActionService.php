<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TownUpdated;
use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Player;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ActionService
{
    public function __construct(
        private readonly DailyActionTracker $tracker,
        private readonly ProgressionService $progression,
        private readonly PlayerLocationGuard $guard,
        private readonly TownBonusService $bonuses,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function perform(Player $player, Action $action): array
    {
        if (! $action->is_active || $action->effects === null) {
            throw ValidationException::withMessages([
                'action' => [__('This action is not available.')],
            ]);
        }

        $town = $this->guard->requireActiveTown($player);
        $limit = $this->dailyLimit($player);

        $townResources = $town->townResources()->with('resource')->get()
            ->keyBy(fn (TownResource $townResource): string => $townResource->resource->key);

        foreach (array_keys($action->effects) as $resourceKey) {
            if (! $townResources->has($resourceKey)) {
                throw ValidationException::withMessages([
                    'action' => [__('This town cannot produce the required resource.')],
                ]);
            }
        }

        $townBonuses = $this->bonuses->forTown($town);

        if (! $this->tracker->consume($player, $action->ap_cost, $limit)) {
            throw ValidationException::withMessages([
                'action' => [__('You have no action points left today.')],
            ]);
        }

        try {
            return DB::transaction(function () use ($player, $action, $town, $townResources, $townBonuses, $limit): array {
                $produced = $this->applyEffects($action, $townResources, $townBonuses);

                /** @var User $user */
                $user = $player->user()->first();
                $xpGained = $this->progression->award($user, $action->base_xp);

                ActionLog::create([
                    'game_id' => $player->game_id,
                    'player_id' => $player->id,
                    'town_id' => $town->id,
                    'action_id' => $action->id,
                    'payload' => ['effects' => $action->effects],
                    'result' => ['produced' => $produced, 'xp' => $xpGained],
                    'xp_gained' => $xpGained,
                ]);

                event(new TownUpdated($town->id));

                return [
                    'action' => $action->key,
                    'xp_gained' => $xpGained,
                    'total_xp' => $user->xp,
                    'title' => $user->title?->name,
                    'resources' => $produced,
                    'actions_remaining' => $this->tracker->remaining($player, $limit),
                ];
            });
        } catch (Throwable $e) {
            $this->tracker->consume($player, -$action->ap_cost, $limit);

            throw $e;
        }
    }

    /**
     * @param  Collection<string, TownResource>  $townResources
     * @param  array<string, int>  $townBonuses
     * @return array<int, array<string, mixed>>
     */
    private function applyEffects(Action $action, Collection $townResources, array $townBonuses): array
    {
        $produced = [];

        /** @var array<string, int> $effects */
        $effects = $action->effects;

        foreach ($effects as $resourceKey => $delta) {
            /** @var TownResource $townResource */
            $townResource = $townResources->get($resourceKey);

            if ($delta > 0) {
                $pct = $this->bonuses->productionBonusPct($townBonuses, $resourceKey);
                $delta = (int) floor($delta * (1 + $pct / 100));
            }

            $newAmount = $townResource->amount + $delta;

            if ($delta > 0 && $townResource->capacity !== null) {
                $newAmount = min($newAmount, $townResource->capacity);
            }

            $newAmount = max(0, $newAmount);
            $gained = $newAmount - $townResource->amount;

            $townResource->update(['amount' => $newAmount]);

            $produced[] = [
                'key' => $resourceKey,
                'name' => $townResource->resource->name,
                'gained' => $gained,
                'amount' => $newAmount,
                'capacity' => $townResource->capacity,
            ];
        }

        return $produced;
    }

    private function dailyLimit(Player $player): int
    {
        return (int) ($player->game()->first()?->config['daily_actions'] ?? 5);
    }
}
