<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Title;
use App\Models\User;

final class ProgressionService
{
    public function award(User $user, int $xp): int
    {
        $user->xp += $xp;

        $title = Title::query()
            ->where('min_xp', '<=', $user->xp)
            ->orderByDesc('min_xp')
            ->first();

        if ($title !== null && $user->title_id !== $title->id) {
            $user->title_id = $title->id;
        }

        $user->save();
        $user->load('title');

        return $xp;
    }
}
