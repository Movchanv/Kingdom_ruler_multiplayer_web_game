<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Game\TriggerEventRequest;
use App\Models\Event;
use App\Models\Town;
use App\Services\TownEventService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AdminEventController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TownEventService $townEvents) {}

    public function index(): JsonResponse
    {
        $events = Event::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Event $event): array => $this->present($event))
            ->all();

        return $this->success($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create([
            'type' => $request->enum('type', EventType::class),
            'difficulty' => $request->enum('difficulty', EventDifficulty::class),
            'name' => (string) $request->string('name'),
            'description' => $request->input('description'),
            'icon' => $request->input('icon'),
            'effects' => $this->intMap($request->array('effects')),
            'requirement' => $this->intMap($request->array('requirement')),
            'success_effects' => $this->intMap($request->array('success_effects')),
            'failure_effects' => $this->intMap($request->array('failure_effects')),
            'delay_min_minutes' => $request->filled('delay_min_minutes') ? $request->integer('delay_min_minutes') : null,
            'delay_max_minutes' => $request->filled('delay_max_minutes') ? $request->integer('delay_max_minutes') : null,
            'weight' => $request->integer('weight') ?: null,
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ]);

        return $this->success($this->present($event), __('Event created.'), 201);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, int>|null
     */
    private function intMap(array $values): ?array
    {
        if ($values === []) {
            return null;
        }

        return array_map(static fn ($value): int => (int) $value, $values);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Event $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type->value,
            'difficulty' => $event->difficulty?->value,
            'name' => $event->name,
            'description' => $event->description,
            'icon' => $event->icon,
            'effects' => $event->effects ?? [],
            'requirement' => $event->requirement ?? [],
            'success_effects' => $event->success_effects ?? [],
            'failure_effects' => $event->failure_effects ?? [],
            'delay_min_minutes' => $event->delay_min_minutes,
            'delay_max_minutes' => $event->delay_max_minutes,
            'weight' => $event->weight,
        ];
    }

    public function trigger(TriggerEventRequest $request, Event $event): JsonResponse
    {
        $town = Town::query()->findOrFail($request->integer('town_id'));

        $townEvent = $this->townEvents->schedule(
            $event,
            $town,
            triggeredBy: $request->user(),
            delayMinutes: $request->filled('delay_minutes') ? $request->integer('delay_minutes') : null,
        );

        return $this->success([
            'event' => $event->name,
            'town_id' => $town->id,
            'town_event_id' => $townEvent->id,
            'resolves_at' => $townEvent->resolves_at->toIso8601String(),
            'requirement' => $townEvent->requirement ?? [],
        ], __('Event scheduled.'));
    }
}
