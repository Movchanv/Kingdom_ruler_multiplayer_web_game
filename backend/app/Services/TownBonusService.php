<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Town;
use App\Models\TownLaw;

final class TownBonusService
{
    /**
     * @return array<string, int>
     */
    public function forTown(Town $town): array
    {
        $town->loadMissing('townBuildings.building.levels', 'townLaws.law');

        $bonuses = [];

        foreach ($town->townBuildings as $townBuilding) {
            if ($townBuilding->level < 1) {
                continue;
            }

            $level = $townBuilding->building->levels
                ->firstWhere('level', $townBuilding->level);

            /** @var array<string, int> $bonus */
            $bonus = $level->bonus ?? [];

            $this->merge($bonuses, $bonus);
        }

        foreach ($town->townLaws as $townLaw) {
            if ($townLaw->expires_at !== null && $townLaw->expires_at->isPast()) {
                continue;
            }

            $this->merge($bonuses, $this->lawBonus($townLaw));
        }

        return $bonuses;
    }

    /**
     * @param  array<string, int>  $bonuses
     */
    public function productionBonusPct(array $bonuses, string $resourceKey): int
    {
        return $bonuses[$resourceKey.'_bonus_pct'] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    private function lawBonus(TownLaw $townLaw): array
    {
        $law = $townLaw->law()->first();

        /** @var array<string, int> $bonus */
        $bonus = $law->bonus ?? [];

        return $bonus;
    }

    /**
     * @param  array<string, int>  $bonuses
     * @param  array<string, int>  $additions
     */
    private function merge(array &$bonuses, array $additions): void
    {
        foreach ($additions as $key => $value) {
            $bonuses[$key] = ($bonuses[$key] ?? 0) + $value;
        }
    }
}
