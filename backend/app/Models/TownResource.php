<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TownResource extends Model
{
    protected $fillable = [
        'town_id',
        'resource_id',
        'amount',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'capacity' => 'integer',
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
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
