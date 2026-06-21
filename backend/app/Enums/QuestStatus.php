<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Claimed = 'claimed';
}
