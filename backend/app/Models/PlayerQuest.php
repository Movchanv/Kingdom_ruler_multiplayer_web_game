<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerQuest extends Model
{
    protected $fillable = [
        'player_id',
        'quest_id',
        'status',
        'progress',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuestStatus::class,
            'progress' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<Quest, $this>
     */
    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}
