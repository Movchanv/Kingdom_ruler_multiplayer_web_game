<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Events\TownUpdated;
use App\Models\Event;
use App\Models\Town;
use App\Models\TownResource;
use Illuminate\Support\Collection;

final class EventService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function applyToTown(Event $event, Town $town): array
    {
        /** @var array<string, int> $effects */
        $effects = $event->effects ?? [];

        $townResources = $town->townResources()->with('resource')->get()
            ->keyBy(fn (TownResource $townResource): string => $townResource->resource->key);

        $applied = [];

        foreach ($effects as $key => $delta) {
            if ($key === 'loyalty') {
                $new = max(0, min(100, $town->loyalty + $delta));
                $applied[] = ['key' => 'loyalty', 'delta' => $new - $town->loyalty, 'value' => $new];
                $town->loyalty = $new;

                continue;
            }

            if ($townResources->has($key)) {
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
        }

        if ($town->isDirty('loyalty')) {
            $town->save();
        }

        if ($applied !== []) {
            event(new TownUpdated($town->id));
        }

        return $applied;
    }

    public function draw(EventType $type, ?EventDifficulty $difficulty = null): ?Event
    {
        $query = Event::query()
            ->where('type', $type)
            ->where('is_active', true);

        if ($difficulty !== null) {
            $query->where('difficulty', $difficulty);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            return null;
        }

        return $this->weightedRandom($events);
    }

    /**
     * @param  Collection<int, Event>  $events
     */
    private function weightedRandom(Collection $events): Event
    {
        $total = (int) $events->sum(fn (Event $event): int => max(1, $event->weight ?? 1));
        $roll = random_int(1, $total);
        $cursor = 0;

        foreach ($events as $event) {
            $cursor += max(1, $event->weight ?? 1);

            if ($roll <= $cursor) {
                return $event;
            }
        }

        /** @var Event $last */
        $last = $events->last();

        return $last;
    }
}
