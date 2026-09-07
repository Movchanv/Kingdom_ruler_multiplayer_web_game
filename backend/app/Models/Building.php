<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'image',
        'max_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BuildingLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(BuildingLevel::class);
    }

    /**
     * @return HasMany<TownBuilding, $this>
     */
    public function townBuildings(): HasMany
    {
        return $this->hasMany(TownBuilding::class);
    }
}
