<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'country_id',
        'title_id',
        'display_name',
        'xp',
    ];

    protected function casts(): array
    {
        return [
            'xp' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * @return HasOne<Town, $this>
     */
    public function town(): HasOne
    {
        return $this->hasOne(Town::class);
    }

    /**
     * @return HasMany<ActionLog, $this>
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }

    /**
     * @return HasMany<PlayerQuest, $this>
     */
    public function playerQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    /**
     * @return BelongsToMany<Quest, $this>
     */
    public function quests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'player_quests')
            ->withPivot('status', 'progress', 'completed_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<LawVoteBallot, $this>
     */
    public function lawVoteBallots(): HasMany
    {
        return $this->hasMany(LawVoteBallot::class);
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
