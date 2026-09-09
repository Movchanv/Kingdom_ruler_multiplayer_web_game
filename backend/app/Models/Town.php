<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TownFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $destroyed_at
 */
class Town extends Model
{
    /** @use HasFactory<TownFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'country_id',
        'name',
        'map_x',
        'map_y',
        'population',
        'loyalty',
        'destroyed_at',
    ];

    protected function casts(): array
    {
        return [
            'map_x' => 'integer',
            'map_y' => 'integer',
            'population' => 'integer',
            'loyalty' => 'integer',
            'destroyed_at' => 'datetime',
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
     * Players currently located in this town.
     *
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'current_town_id');
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

    /**
     * @return HasMany<TownBuilding, $this>
     */
    public function townBuildings(): HasMany
    {
        return $this->hasMany(TownBuilding::class);
    }

    /**
     * @return HasMany<TownLaw, $this>
     */
    public function townLaws(): HasMany
    {
        return $this->hasMany(TownLaw::class);
    }

    /**
     * @return HasMany<TownEvent, $this>
     */
    public function townEvents(): HasMany
    {
        return $this->hasMany(TownEvent::class);
    }
}
