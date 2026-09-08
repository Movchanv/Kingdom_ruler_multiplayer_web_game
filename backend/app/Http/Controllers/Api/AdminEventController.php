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
use App\Services\EventService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AdminEventController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EventService $events) {}

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
        /** @var array<string, int> $effects */
        $effects = $request->array('effects');

        $event = Event::create([
            'type' => $request->enum('type', EventType::class),
            'difficulty' => $request->enum('difficulty', EventDifficulty::class),
            'name' => (string) $request->string('name'),
            'description' => $request->input('description'),
            'effects' => array_map(static fn ($value): int => (int) $value, $effects),
            'weight' => $request->integer('weight') ?: null,
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ]);

        return $this->success($this->present($event), __('Event created.'), 201);
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
            'effects' => $event->effects ?? [],
            'weight' => $event->weight,
        ];
    }

    public function trigger(TriggerEventRequest $request, Event $event): JsonResponse
    {
        $town = Town::query()->findOrFail($request->integer('town_id'));

        $applied = $this->events->applyToTown($event, $town);

        return $this->success([
            'event' => $event->name,
            'town_id' => $town->id,
            'effects' => $applied,
        ], __('Event triggered.'));
    }
}
