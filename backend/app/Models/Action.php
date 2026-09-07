<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, int>|null $effects
 */
class Action extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'ap_cost',
        'base_xp',
        'effects',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ap_cost' => 'integer',
            'base_xp' => 'integer',
            'effects' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ActionLog, $this>
     */
    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }
}
