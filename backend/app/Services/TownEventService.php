<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TownEventStatus;
use App\Events\TownUpdated;
use App\Models\Event;
use App\Models\Town;
use App\Models\TownEvent;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TownEventService
{
    private const DEFAULT_DELAY_MIN = 30;

    private const DEFAULT_DELAY_MAX = 180;

    public function schedule(
        Event $event,
        Town $town,
        float $intensity = 1.0,
        ?User $triggeredBy = null,
        ?int $delayMinutes = null,
    ): TownEvent {
        $delay = $delayMinutes ?? $this->drawDelay($event);

        $townEvent = TownEvent::create([
            'game_id' => $town->game_id,
            'town_id' => $town->id,
            'event_id' => $event->id,
            'status' => TownEventStatus::Pending,
            'resolves_at' => Carbon::now()->addMinutes($delay),
            'requirement' => $this->scale($event->requirement ?? [], $intensity),
            'success_effects' => $this->scale($event->success_effects ?? [], $intensity),
            'failure_effects' => $this->scale($event->failure_effects ?? $event->effects ?? [], $intensity),
            'intensity' => round($intensity, 2),
            'triggered_by' => $triggeredBy?->id,
        ]);

        event(new TownUpdated($town->id));

        return $townEvent;
    }

    /**
     * Resout toutes les menaces arrivees a echeance.
     *
     * @return int nombre d'evenements resolus
     */
    public function resolveDue(): int
    {
        $due = TownEvent::query()
            ->pending()
            ->where('resolves_at', '<=', Carbon::now())
            ->with('town')
            ->get();

        $resolved = 0;

        foreach ($due as $townEvent) {
            if ($townEvent->town === null || $townEvent->town->destroyed_at !== null) {
                $townEvent->forceFill([
                    'status' => TownEventStatus::Failed,
                    'resolved_at' => Carbon::now(),
                    'outcome' => [],
                ])->save();

                continue;
            }

            $this->resolve($townEvent);
            $resolved++;
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(TownEvent $townEvent): array
    {
        $town = $townEvent->town;

        if ($town === null) {
            throw new RuntimeException("Town event {$townEvent->id} has no town.");
        }

        $met = $this->requirementIsMet($townEvent, $town);
        $effects = $met ? ($townEvent->success_effects ?? []) : ($townEvent->failure_effects ?? []);

        $applied = DB::transaction(function () use ($townEvent, $town, $met, $effects): array {
            $result = $this->applyEffects($effects, $town);

            $townEvent->forceFill([
                'status' => $met ? TownEventStatus::Succeeded : TownEventStatus::Failed,
                'resolved_at' => Carbon::now(),
                'outcome' => $result,
            ])->save();

            return $result;
        });

        event(new TownUpdated($town->id));

        return [
            'town_event_id' => $townEvent->id,
            'town_id' => $town->id,
            'succeeded' => $met,
            'effects' => $applied,
        ];
    }

    public function requirementIsMet(TownEvent $townEvent, Town $town): bool
    {
        $requirement = $townEvent->requirement ?? [];

        if ($requirement === []) {
            return true;
        }

        $amounts = $this->currentAmounts($town);

        foreach ($requirement as $key => $needed) {
            if (($amounts[$key] ?? 0) < (int) $needed) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, int>
     */
    private function currentAmounts(Town $town): array
    {
        $amounts = ['loyalty' => (int) $town->loyalty];

        foreach ($town->townResources()->with('resource')->get() as $townResource) {
            $amounts[$townResource->resource->key] = (int) $townResource->amount;
        }

        return $amounts;
    }

    /**
     * @param  array<string, int>  $effects
     * @return array<int, array<string, mixed>>
     */
    private function applyEffects(array $effects, Town $town): array
    {
        if ($effects === []) {
            return [];
        }

        $townResources = $town->townResources()->with('resource')->get()
            ->keyBy(fn (TownResource $townResource): string => $townResource->resource->key);

        $applied = [];

        foreach ($effects as $key => $delta) {
            $delta = (int) $delta;

            if ($key === 'loyalty') {
                $new = max(0, min(100, $town->loyalty + $delta));
                $applied[] = ['key' => 'loyalty', 'delta' => $new - $town->loyalty, 'value' => $new];
                $town->loyalty = $new;

                continue;
            }

            if (! $townResources->has($key)) {
                continue;
            }

            /** @var TownResource $townResource */
            $townResource = $townResources->get($key);

            $new = $townResource->amount + $delta;
            if ($delta > 0 && $townResource->capacity !== null) {
                $new = min($new, $townResource->capacity);
            }
            $new = max(0, $new);

            $applied[] = ['key' => $key, 'delta' => $new - $townResource->amount, 'amount' => $new];
            $townResource->update(['amount' => $new]);
        }

        if ($town->isDirty('loyalty')) {
            $town->save();
        }

        return $applied;
    }

    /**
     * @param  array<string, int>  $values
     * @return array<string, int>
     */
    private function scale(array $values, float $intensity): array
    {
        $scaled = [];

        foreach ($values as $key => $value) {
            $scaled[$key] = (int) round($value * $intensity);
        }

        return $scaled;
    }

    private function drawDelay(Event $event): int
    {
        $min = $event->delay_min_minutes ?? self::DEFAULT_DELAY_MIN;
        $max = $event->delay_max_minutes ?? self::DEFAULT_DELAY_MAX;

        if ($max < $min) {
            $max = $min;
        }

        return random_int($min, $max);
    }
}
