<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Country;
use App\Models\User;
use App\Services\SeasonBuilder;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function __construct(private readonly SeasonBuilder $builder) {}

    public function run(): void
    {
        $country = Country::query()
            ->where('slug', config('seasons.country'))
            ->firstOrFail();

        $createdBy = User::query()->where('role', UserRole::Admin)->value('id');

        // Le contenu vient de config/seasons.php, partage avec `season:start`.
        $this->builder->build(
            'France Médiévale — Saison 1',
            $country,
            $createdBy === null ? null : (int) $createdBy,
        );
    }
}
