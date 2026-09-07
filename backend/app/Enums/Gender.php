<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Homme',
            self::Female => 'Femme',
            self::Other => 'Autre',
            self::PreferNotToSay => 'Préfère ne pas répondre',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $gender): array => ['value' => $gender->value, 'label' => $gender->label()],
            self::cases(),
        );
    }
}
