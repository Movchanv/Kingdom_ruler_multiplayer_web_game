<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\LawVoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CastBallotRequest;
use App\Models\LawVote;
use App\Models\LawVoteOption;
use App\Models\Player;
use App\Models\User;
use App\Services\GameService;
use App\Services\LawVoteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class VoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GameService $game,
        private readonly LawVoteService $votes,
    ) {}

    public function current(Request $request): JsonResponse
    {
        $player = $this->requirePlayer($request);

        $vote = LawVote::query()
            ->where('game_id', $player->game_id)
            ->where('country_id', $player->country_id)
            ->where('status', LawVoteStatus::Open)
            ->latest('starts_at')
            ->first();

        if ($vote === null) {
            return $this->success(null, __('No vote is open right now.'));
        }

        return $this->success($this->present($vote, $player));
    }

    public function ballot(CastBallotRequest $request, LawVote $vote): JsonResponse
    {
        $player = $this->requirePlayer($request);

        $option = LawVoteOption::query()->findOrFail($request->integer('option_id'));

        $this->votes->castBallot($player, $vote, $option);

        return $this->success($this->present($vote->refresh(), $player), __('Vote recorded.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(LawVote $vote, Player $player): array
    {
        $vote->loadMissing('options.law');
        $tally = $this->votes->tally($vote);

        $myBallot = $vote->ballots()->where('player_id', $player->id)->value('law_vote_option_id');

        return [
            'id' => $vote->id,
            'status' => $vote->status->value,
            'ends_at' => $vote->ends_at?->toIso8601String(),
            'my_ballot' => $myBallot,
            'total_ballots' => array_sum($tally),
            'options' => $vote->options->map(fn (LawVoteOption $option): array => [
                'id' => $option->id,
                'law' => [
                    'key' => $option->law->key,
                    'name' => $option->law->name,
                    'bonus' => $option->law->bonus ?? [],
                ],
                'votes' => $tally[$option->id] ?? 0,
            ])->all(),
        ];
    }

    private function requirePlayer(Request $request): Player
    {
        /** @var User $user */
        $user = $request->user();

        $player = $this->game->activePlayer($user);

        if ($player === null) {
            throw ValidationException::withMessages([
                'player' => [__('You have not joined a country yet.')],
            ]);
        }

        return $player;
    }
}
