<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Title>
 */
class TitleFactory extends Factory
{
    protected $model = Title::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'min_xp' => fake()->numberBetween(0, 5000),
            'rank' => fake()->numberBetween(0, 10),
        ];
    }
}
