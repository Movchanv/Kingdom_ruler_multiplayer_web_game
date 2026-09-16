<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\Building;
use App\Models\Country;
use App\Models\Game;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownBuilding;
use App\Models\TownResource;
use Illuminate\Support\Facades\DB;

/**
 * Cree une saison et son contenu a partir du plan defini dans config/seasons.php.
 *
 * Appele par SeasonSeeder a la premiere installation et par la commande
 * `season:start` pour les saisons suivantes : les deux chemins produisent
 * exactement la meme structure.
 */
final class SeasonBuilder
{
    /**
     * Idempotent : reprend la partie et les villes deja presentes plutot que
     * de les dupliquer. Le seeder tournant a chaque deploiement, c'est ce qui
     * evite de recreer des villes a chaque redemarrage des conteneurs.
     */
    public function build(string $name, Country $country, ?int $createdBy = null): Game
    {
        /** @var array{config: array<string, mixed>, storage_capacity: int, uncapped_resources: array<int, string>, towns: array<string, array{position: array{int, int}, resources: array<int, string>, pois: array<string, array{int, int}>}>} $plan */
        $plan = config('seasons');

        return DB::transaction(function () use ($name, $country, $createdBy, $plan): Game {
            $game = Game::firstOrCreate(
                ['name' => $name],
                [
                    'country_id' => $country->id,
                    'status' => GameStatus::Active,
                    'config' => $plan['config'],
                    'started_at' => now(),
                    'created_by' => $createdBy,
                ],
            );

            foreach ($plan['towns'] as $townName => $spec) {
                [$x, $y] = $spec['position'];

                $town = Town::firstOrCreate(
                    ['game_id' => $game->id, 'name' => $townName],
                    [
                        'country_id' => $country->id,
                        'map_x' => $x,
                        'map_y' => $y,
                        'population' => 0,
                        'loyalty' => 100,
                    ],
                );

                foreach ($spec['resources'] as $key) {
                    $resource = Resource::query()->where('key', $key)->firstOrFail();

                    TownResource::firstOrCreate(
                        ['town_id' => $town->id, 'resource_id' => $resource->id],
                        [
                            'amount' => 0,
                            'capacity' => in_array($key, $plan['uncapped_resources'], true)
                                ? null
                                : $plan['storage_capacity'],
                        ],
                    );
                }

                foreach ($spec['pois'] as $key => [$poiX, $poiY]) {
                    $building = Building::query()->where('key', $key)->firstOrFail();

                    TownBuilding::firstOrCreate(
                        ['town_id' => $town->id, 'building_id' => $building->id],
                        [
                            'level' => 0,
                            'contributions' => [],
                            'map_x' => $poiX,
                            'map_y' => $poiY,
                        ],
                    );
                }
            }

            return $game;
        });
    }
}
