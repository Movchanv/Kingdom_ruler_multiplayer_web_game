<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
