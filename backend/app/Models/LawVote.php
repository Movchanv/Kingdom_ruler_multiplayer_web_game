<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LawVoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawVote extends Model
{
    protected $fillable = [
        'game_id',
        'country_id',
        'status',
        'starts_at',
        'ends_at',
        'winning_law_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => LawVoteStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Law, $this>
     */
    public function winningLaw(): BelongsTo
    {
        return $this->belongsTo(Law::class, 'winning_law_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<LawVoteOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(LawVoteOption::class);
    }

    /**
     * @return HasMany<LawVoteBallot, $this>
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(LawVoteBallot::class);
    }
}
