<?php

declare(strict_types=1);

namespace App\Enums;

enum BugStatus: string
{
    case Open = 'open';
    case Triaged = 'triaged';
    case Fixed = 'fixed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Triaged => 'Qualifiée',
            self::Fixed => 'Corrigée',
            self::Closed => 'Clôturée',
        };
    }
}
