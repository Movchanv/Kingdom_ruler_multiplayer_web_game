<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_xp',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'min_xp' => 'integer',
            'rank' => 'integer',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
