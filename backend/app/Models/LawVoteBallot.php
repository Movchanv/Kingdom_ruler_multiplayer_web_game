<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawVoteBallot extends Model
{
    protected $fillable = [
        'law_vote_id',
        'player_id',
        'law_vote_option_id',
    ];

    /**
     * @return BelongsTo<LawVote, $this>
     */
    public function lawVote(): BelongsTo
    {
        return $this->belongsTo(LawVote::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<LawVoteOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(LawVoteOption::class, 'law_vote_option_id');
    }
}
