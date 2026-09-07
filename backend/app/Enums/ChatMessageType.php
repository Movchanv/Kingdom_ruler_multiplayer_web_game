<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatMessageType: string
{
    case Player = 'player';
    case System = 'system';
}
