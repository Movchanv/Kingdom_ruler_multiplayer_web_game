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
            'player_id' => null,
            'name' => fake()->city(),
            'population' => fake()->numberBetween(100, 1000),
            'loyalty' => 100,
        ];
    }
}
