<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Game;
use App\Models\Town;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Town>
 */
class TownFactory extends Factory
{
    protected $model = Town::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'country_id' => Country::factory(),
            'name' => fake()->city(),
            'map_x' => fake()->numberBetween(0, 1000),
            'map_y' => fake()->numberBetween(0, 1000),
            'population' => fake()->numberBetween(100, 1000),
            'loyalty' => 100,
        ];
    }
}
