<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LawVoteStatus;
use App\Models\Country;
use App\Models\Game;
use App\Models\Law;
use App\Models\LawVote;
use App\Models\LawVoteBallot;
use App\Models\LawVoteOption;
use App\Models\Player;
use App\Models\Town;
use App\Models\TownLaw;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LawVoteService
{
    /**
     * @param  array<int, int>  $lawIds
     */
    public function open(Game $game, Country $country, array $lawIds, int $hours, ?int $creatorId = null): LawVote
    {
        return DB::transaction(function () use ($game, $country, $lawIds, $hours, $creatorId): LawVote {
            $vote = LawVote::create([
                'game_id' => $game->id,
                'country_id' => $country->id,
                'status' => LawVoteStatus::Open,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addHours($hours),
                'created_by' => $creatorId,
            ]);

            foreach (array_unique($lawIds) as $lawId) {
                LawVoteOption::create(['law_vote_id' => $vote->id, 'law_id' => $lawId]);
            }

            return $vote->load('options.law');
        });
    }

    public function castBallot(Player $player, LawVote $vote, LawVoteOption $option): LawVoteBallot
    {
        if ($vote->status !== LawVoteStatus::Open || ($vote->ends_at !== null && $vote->ends_at->isPast())) {
            throw ValidationException::withMessages([
                'vote' => [__('This vote is closed.')],
            ]);
        }

        if ($player->game_id !== $vote->game_id || $player->country_id !== $vote->country_id) {
            throw ValidationException::withMessages([
                'vote' => [__('This vote does not concern your country.')],
            ]);
        }

        if ($option->law_vote_id !== $vote->id) {
            throw ValidationException::withMessages([
                'option' => [__('This option does not belong to the vote.')],
            ]);
        }

        return LawVoteBallot::updateOrCreate(
            ['law_vote_id' => $vote->id, 'player_id' => $player->id],
            ['law_vote_option_id' => $option->id],
        );
    }

    /**
     * @return array<int, int>
     */
    public function tally(LawVote $vote): array
    {
        $rows = DB::table('law_vote_ballots')
            ->where('law_vote_id', $vote->id)
            ->select('law_vote_option_id', DB::raw('COUNT(*) as total'))
            ->groupBy('law_vote_option_id')
            ->get();

        $tally = [];

        foreach ($rows as $row) {
            $tally[(int) $row->law_vote_option_id] = (int) $row->total;
        }

        return $tally;
    }

    public function close(LawVote $vote): ?Law
    {
        if ($vote->status === LawVoteStatus::Closed) {
            return $vote->winningLaw()->first();
        }

        return DB::transaction(function () use ($vote): ?Law {
            $tally = $this->tally($vote);

            $winningOptionId = $this->winningOption($tally);

            $law = null;

            if ($winningOptionId !== null) {
                /** @var LawVoteOption $option */
                $option = $vote->options()->whereKey($winningOptionId)->first();
                $law = $option->law()->first();
            }

            $vote->forceFill([
                'status' => LawVoteStatus::Closed,
                'winning_law_id' => $law?->id,
            ])->save();

            if ($law !== null) {
                $this->applyLawToCountry($vote, $law);
            }

            return $law;
        });
    }

    public function closeDue(): int
    {
        $votes = LawVote::query()
            ->where('status', LawVoteStatus::Open)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now())
            ->get();

        foreach ($votes as $vote) {
            $this->close($vote);
        }

        return $votes->count();
    }

    /**
     * @param  array<int, int>  $tally
     */
    private function winningOption(array $tally): ?int
    {
        if ($tally === []) {
            return null;
        }

        $max = max($tally);

        $leaders = array_keys(array_filter($tally, fn (int $total): bool => $total === $max));
        sort($leaders);

        return $leaders[0];
    }

    private function applyLawToCountry(LawVote $vote, Law $law): void
    {
        $towns = Town::query()
            ->where('game_id', $vote->game_id)
            ->whereNull('destroyed_at')
            ->get();

        foreach ($towns as $town) {
            $alreadyActive = TownLaw::query()
                ->where('town_id', $town->id)
                ->where('law_id', $law->id)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now()))
                ->exists();

            if ($alreadyActive) {
                continue;
            }

            TownLaw::create([
                'town_id' => $town->id,
                'law_id' => $law->id,
                'law_vote_id' => $vote->id,
                'applied_at' => Carbon::now(),
                'expires_at' => null,
            ]);
        }
    }
}
