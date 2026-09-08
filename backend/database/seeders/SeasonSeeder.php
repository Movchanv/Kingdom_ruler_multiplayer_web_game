<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Country;
use App\Models\Game;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownBuilding;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $france = Country::query()->where('slug', 'france-medievale')->firstOrFail();

        $createdBy = User::query()->where('role', UserRole::Admin)->value('id');

        $game = Game::firstOrCreate(
            ['name' => 'France Médiévale — Saison 1'],
            [
                'country_id' => $france->id,
                'status' => GameStatus::Active,
                'config' => [
                    'daily_actions' => 5,
                    'build_step' => 20,
                    'upkeep_hour' => '12:00',
                    'vote_hours' => 12,
                    'soldier_upkeep' => ['gold' => 1, 'food' => 1],
                    'desertion_rate' => 0.2,
                    'upkeep_loyalty_penalty' => 5,
                ],
                'started_at' => now(),
                'created_by' => $createdBy,
            ],
        );

        $towns = [
            'Paris' => [
                'position' => [480, 300],
                'resources' => ['gold', 'food', 'soldiers', 'wood', 'stone'],
                'pois' => [
                    'town_hall' => [400, 300],
                    'barracks' => [150, 230],
                    'market' => [240, 520],
                    'mine' => [735, 150],
                    'farm' => [770, 470],
                    'forest' => [785, 760],
                ],
            ],
            'Lyon' => [
                'position' => [590, 620],
                'resources' => ['gold', 'food', 'soldiers', 'wood', 'stone'],
                'pois' => [
                    'town_hall' => [400, 300],
                    'market' => [240, 520],
                    'farm' => [770, 470],
                    'forest' => [785, 760],
                ],
            ],
        ];

        $createdTowns = [];

        foreach ($towns as $name => $config) {
            [$x, $y] = $config['position'];

            $town = Town::firstOrCreate(
                ['game_id' => $game->id, 'name' => $name],
                ['country_id' => $france->id, 'map_x' => $x, 'map_y' => $y, 'population' => 0, 'loyalty' => 100],
            );

            foreach ($config['resources'] as $key) {
                $resource = Resource::query()->where('key', $key)->firstOrFail();

                TownResource::firstOrCreate(
                    ['town_id' => $town->id, 'resource_id' => $resource->id],
                    ['amount' => 0, 'capacity' => $key === 'soldiers' ? null : 1000],
                );
            }

            foreach ($config['pois'] as $key => [$poiX, $poiY]) {
                $building = Building::query()->where('key', $key)->firstOrFail();

                TownBuilding::firstOrCreate(
                    ['town_id' => $town->id, 'building_id' => $building->id],
                    ['level' => 0, 'contributions' => [], 'map_x' => $poiX, 'map_y' => $poiY],
                );
            }

            $createdTowns[$name] = $town;
        }
    }
}
