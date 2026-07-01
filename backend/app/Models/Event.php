<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EventType $type
 * @property EventDifficulty|null $difficulty
 * @property array<string, int>|null $effects
 * @property int|null $weight
 */
class Event extends Model
{
    protected $fillable = [
        'type',
        'difficulty',
        'country_id',
        'town_id',
        'name',
        'description',
        'image',
        'effects',
        'weight',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'difficulty' => EventDifficulty::class,
            'effects' => 'array',
            'weight' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ActionLog, $this>
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }
}
