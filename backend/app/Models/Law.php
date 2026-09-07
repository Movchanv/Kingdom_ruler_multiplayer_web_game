<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, int>|null $bonus
 */
class Law extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'bonus',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bonus' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LawVoteOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(LawVoteOption::class);
    }

    /**
     * @return HasMany<TownLaw, $this>
     */
    public function townLaws(): HasMany
    {
        return $this->hasMany(TownLaw::class);
    }
}
