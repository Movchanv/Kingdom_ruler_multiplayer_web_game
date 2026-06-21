<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TownLaw extends Model
{
    protected $fillable = [
        'town_id',
        'law_id',
        'law_vote_id',
        'applied_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * @return BelongsTo<Law, $this>
     */
    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    /**
     * @return BelongsTo<LawVote, $this>
     */
    public function lawVote(): BelongsTo
    {
        return $this->belongsTo(LawVote::class);
    }
}
