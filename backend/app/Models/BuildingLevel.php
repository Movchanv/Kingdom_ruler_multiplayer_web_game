<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingLevel extends Model
{
    protected $fillable = [
        'building_id',
        'level',
        'bonus',
        'cost',
        'build_points',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'bonus' => 'array',
            'cost' => 'array',
            'build_points' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
