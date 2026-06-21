<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
}
