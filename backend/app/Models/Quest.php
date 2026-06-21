<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'objective_type',
        'objective_target',
        'action_id',
        'xp_reward',
        'rewards',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'objective_target' => 'integer',
            'xp_reward' => 'integer',
            'rewards' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Action, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class);
    }

    /**
     * @return HasMany<PlayerQuest, $this>
     */
    public function playerQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_quests')
            ->withPivot('status', 'progress', 'completed_at')
            ->withTimestamps();
    }
}
