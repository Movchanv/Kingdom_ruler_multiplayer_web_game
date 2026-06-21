<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'country_id' => Country::factory(),
            'title_id' => null,
            'display_name' => fake()->userName(),
            'xp' => 0,
        ];
    }
}
