<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Town extends Model
{
    protected $fillable = [
        'game_id',
        'country_id',
        'player_id',
        'name',
        'population',
        'loyalty',
    ];

    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'loyalty' => 'integer',
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
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return HasMany<TownResource, $this>
     */
    public function townResources(): HasMany
    {
        return $this->hasMany(TownResource::class);
    }

    /**
     * @return BelongsToMany<\App\Models\Resource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'town_resources')
            ->withPivot('amount', 'capacity')
            ->withTimestamps();
    }
}
