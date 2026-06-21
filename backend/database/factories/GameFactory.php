<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city().' — Saison',
            'status' => GameStatus::Active,
            'config' => [
                'daily_actions' => 5,
                'upkeep_hour' => '12:00',
                'vote_hours' => 12,
            ],
            'started_at' => now(),
            'ended_at' => null,
            'created_by' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameStatus::Ended,
            'ended_at' => now(),
        ]);
    }
}
