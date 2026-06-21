<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawVoteOption extends Model
{
    protected $fillable = [
        'law_vote_id',
        'law_id',
    ];

    /**
     * @return BelongsTo<LawVote, $this>
     */
    public function lawVote(): BelongsTo
    {
        return $this->belongsTo(LawVote::class);
    }

    /**
     * @return BelongsTo<Law, $this>
     */
    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    /**
     * @return HasMany<LawVoteBallot, $this>
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(LawVoteBallot::class);
    }
}
