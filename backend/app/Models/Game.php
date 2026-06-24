<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property GameStatus $status
 * @property array<string, mixed>|null $config
 * @property array<string, mixed>|null $results
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
        'status',
        'config',
        'results',
        'started_at',
        'ended_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'config' => 'array',
            'results' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @return HasMany<Town, $this>
     */
    public function towns(): HasMany
    {
        return $this->hasMany(Town::class);
    }

    /**
     * @return HasMany<ActionLog, $this>
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }

    /**
     * @return HasMany<LawVote, $this>
     */
    public function lawVotes(): HasMany
    {
        return $this->hasMany(LawVote::class);
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
