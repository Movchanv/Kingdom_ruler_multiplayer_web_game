<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TownEventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_id
 * @property int $town_id
 * @property int $event_id
 * @property TownEventStatus $status
 * @property Carbon $resolves_at
 * @property Carbon|null $resolved_at
 * @property array<string, int>|null $requirement
 * @property array<string, int>|null $success_effects
 * @property array<string, int>|null $failure_effects
 * @property float $intensity
 * @property array<int, array<string, mixed>>|null $outcome
 * @property int|null $triggered_by
 * @property Event $event
 * @property Town|null $town
 */
final class TownEvent extends Model
{
    protected $fillable = [
        'game_id',
        'town_id',
        'event_id',
        'status',
        'resolves_at',
        'resolved_at',
        'requirement',
        'success_effects',
        'failure_effects',
        'intensity',
        'outcome',
        'triggered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TownEventStatus::class,
            'resolves_at' => 'datetime',
            'resolved_at' => 'datetime',
            'requirement' => 'array',
            'success_effects' => 'array',
            'failure_effects' => 'array',
            'outcome' => 'array',
            'intensity' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @param  Builder<TownEvent>  $query
     * @return Builder<TownEvent>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TownEventStatus::Pending);
    }
}
