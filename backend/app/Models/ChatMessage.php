<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatMessageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ChatMessageType $type
 */
class ChatMessage extends Model
{
    use Prunable;

    protected $fillable = [
        'game_id',
        'country_id',
        'player_id',
        'type',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChatMessageType::class,
        ];
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subDays(7));
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
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
