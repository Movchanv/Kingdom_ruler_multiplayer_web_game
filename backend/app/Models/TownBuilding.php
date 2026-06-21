<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TownBuilding extends Model
{
    protected $fillable = [
        'town_id',
        'building_id',
        'level',
        'build_progress',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'build_progress' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Town, $this>
     */
    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
