<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AdventureService
{
    public function __construct(
        private readonly DailyActionTracker $tracker,
        private readonly ProgressionService $progression,
        private readonly PlayerLocationGuard $guard,
        private readonly EventService $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function embark(Player $player): array
    {
        $town = $this->guard->requireActiveTown($player);

        $action = Action::query()->where('key', 'adventure')->first();

        if ($action === null || ! $action->is_active) {
            throw ValidationException::withMessages([
                'action' => [__('This action is not available.')],
            ]);
        }

        $event = $this->events->draw(EventType::Adventure);

        if ($event === null) {
            throw ValidationException::withMessages([
                'action' => [__('No adventure is available right now.')],
            ]);
        }

        $limit = (int) ($player->game()->first()?->config['daily_actions'] ?? 5);

        if (! $this->tracker->consume($player, $action->ap_cost, $limit)) {
            throw ValidationException::withMessages([
                'action' => [__('You have no action points left today.')],
            ]);
        }

        try {
            return DB::transaction(function () use ($player, $action, $town, $event, $limit): array {
                $applied = $this->events->applyToTown($event, $town);

                $freeActions = (int) ($event->effects['free_action'] ?? 0);
                if ($freeActions > 0) {
                    $this->tracker->consume($player, -$freeActions, $limit);
                }

                /** @var User $user */
                $user = $player->user()->first();
                $xpGained = $this->progression->award($user, $action->base_xp);

                ActionLog::create([
                    'game_id' => $player->game_id,
                    'player_id' => $player->id,
                    'town_id' => $town->id,
                    'action_id' => $action->id,
                    'event_id' => $event->id,
                    'payload' => ['event' => $event->name],
                    'result' => ['applied' => $applied, 'free_actions' => $freeActions],
                    'xp_gained' => $xpGained,
                ]);

                return [
                    'event' => [
                        'id' => $event->id,
                        'name' => $event->name,
                        'description' => $event->description,
                        'image' => $event->image,
                        'difficulty' => $event->difficulty?->value,
                    ],
                    'effects' => $applied,
                    'free_actions' => $freeActions,
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
}
