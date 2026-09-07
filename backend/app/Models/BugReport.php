<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property BugSeverity $severity
 * @property BugStatus $status
 * @property array<string, mixed>|null $context
 * @property Carbon|null $resolved_at
 */
class BugReport extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'title',
        'severity',
        'scope',
        'description',
        'page',
        'context',
        'status',
        'resolution',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => BugSeverity::class,
            'status' => BugStatus::class,
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
