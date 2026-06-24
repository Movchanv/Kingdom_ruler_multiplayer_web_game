<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\JoinGameRequest;
use App\Http\Requests\Game\PerformActionRequest;
use App\Models\Action;
use App\Models\Country;
use App\Models\Town;
use App\Models\User;
use App\Services\ActionService;
use App\Services\GameService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class GameController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GameService $game,
        private readonly ActionService $actions,
    ) {}

    public function countries(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($this->game->countries($user));
    }

    public function join(JoinGameRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $country = Country::query()->findOrFail($request->integer('country_id'));

        $player = $this->game->join($user, $country);

        return $this->success(
            ['player_id' => $player->id, 'current_town_id' => $player->current_town_id],
            __('You have joined :country.', ['country' => $country->name]),
            201,
        );
    }

    public function enterTown(Request $request, Town $town): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $player = $this->game->activePlayer($user);

        if ($player === null) {
            throw ValidationException::withMessages([
                'player' => [__('You have not joined a country yet.')],
            ]);
        }

        $this->game->enterTown($player, $town);

        return $this->success(
            ['current_town_id' => $town->id],
            __('You are now in :town.', ['town' => $town->name]),
        );
    }

    public function state(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($this->game->state($user));
    }

    public function performAction(PerformActionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $player = $this->game->activePlayer($user);

        if ($player === null) {
            throw ValidationException::withMessages([
                'player' => [__('You have not joined a country yet.')],
            ]);
        }

        $action = Action::query()->where('key', $request->string('action'))->firstOrFail();

        $result = $this->actions->perform($player, $action);

        return $this->success($result, __('Action performed.'));
    }
}
