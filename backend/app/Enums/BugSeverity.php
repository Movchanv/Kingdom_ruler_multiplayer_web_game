<?php

declare(strict_types=1);

namespace App\Enums;

enum BugSeverity: string
{
    case Blocking = 'blocking';
    case Major = 'major';
    case Minor = 'minor';

    public function label(): string
    {
        return match ($this) {
            self::Blocking => 'Bloquant',
            self::Major => 'Majeur',
            self::Minor => 'Mineur',
        };
    }
}
