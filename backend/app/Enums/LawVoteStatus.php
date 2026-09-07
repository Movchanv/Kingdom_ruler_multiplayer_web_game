<?php

declare(strict_types=1);

namespace App\Enums;

enum LawVoteStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
