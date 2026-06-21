<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'is_military',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_military' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TownResource, $this>
     */
    public function townResources(): HasMany
    {
        return $this->hasMany(TownResource::class);
    }

    /**
     * @return BelongsToMany<Town, $this>
     */
    public function towns(): BelongsToMany
    {
        return $this->belongsToMany(Town::class, 'town_resources')
            ->withPivot('amount', 'capacity')
            ->withTimestamps();
    }
}
