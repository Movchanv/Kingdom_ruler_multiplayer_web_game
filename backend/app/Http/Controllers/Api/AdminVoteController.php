<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\OpenVoteRequest;
use App\Models\Country;
use App\Models\Game;
use App\Models\LawVoteOption;
use App\Models\User;
use App\Services\LawVoteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AdminVoteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LawVoteService $votes) {}

    public function open(OpenVoteRequest $request): JsonResponse
    {
        $game = Game::query()->findOrFail($request->integer('game_id'));
        $country = Country::query()->findOrFail($request->integer('country_id'));

        /** @var User $user */
        $user = $request->user();

        /** @var array<int, int> $lawIds */
        $lawIds = $request->validated('law_ids');
        $hours = (int) ($request->integer('hours') ?: ($game->config['vote_hours'] ?? 12));

        $vote = $this->votes->open($game, $country, $lawIds, $hours, $user->id);

        return $this->success([
            'id' => $vote->id,
            'ends_at' => $vote->ends_at?->toIso8601String(),
            'options' => $vote->options->map(fn (LawVoteOption $option): array => [
                'id' => $option->id,
                'law' => $option->law->name,
            ])->all(),
        ], __('Vote opened.'), 201);
    }
}
