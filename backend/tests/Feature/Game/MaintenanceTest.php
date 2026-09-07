<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Building;
use App\Models\BuildingLevel;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownBuilding;
use App\Models\TownResource;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: Country, 1: Game}
     */
    private function makeSeason(array $config = []): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create([
            'country_id' => $country->id,
            'config' => array_merge([
                'soldier_upkeep' => ['gold' => 1, 'food' => 1],
                'desertion_rate' => 0.2,
                'upkeep_loyalty_penalty' => 5,
            ], $config),
        ]);

        return [$country, $game];
    }

    private function makeTown(Game $game, Country $country, int $loyalty = 100): Town
    {
        return Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'loyalty' => $loyalty]);
    }

    private function addResource(Town $town, string $key, int $amount, ?int $capacity = 1000): TownResource
    {
        $resource = Resource::factory()->create(['key' => $key, 'name' => ucfirst($key)]);

        return TownResource::create([
            'town_id' => $town->id,
            'resource_id' => $resource->id,
            'amount' => $amount,
            'capacity' => $capacity,
        ]);
    }

    /**
     * @param  array<string, int>  $bonus
     */
    private function placeBuilding(Town $town, string $key, array $bonus): void
    {
        $building = Building::create(['key' => $key, 'name' => ucfirst($key), 'category' => 'production', 'max_level' => 1, 'is_active' => true]);
        BuildingLevel::create(['building_id' => $building->id, 'level' => 1, 'bonus' => $bonus, 'cost' => null]);
        TownBuilding::create(['town_id' => $town->id, 'building_id' => $building->id, 'level' => 1, 'contributions' => []]);
    }

    private function service(): MaintenanceService
    {
        return app(MaintenanceService::class);
    }

    public function test_per_day_bonuses_produce_resources_and_loyalty(): void
    {
        [$country, $game] = $this->makeSeason();
        $town = $this->makeTown($game, $country, loyalty: 50);
        $food = $this->addResource($town, 'food', 0);
        $this->placeBuilding($town, 'farm', ['food_per_day' => 10]);
        $this->placeBuilding($town, 'town_hall', ['loyalty_per_day' => 2]);

        $this->service()->processGame($game);

        $this->assertDatabaseHas('town_resources', ['id' => $food->id, 'amount' => 10]);
        $this->assertDatabaseHas('towns', ['id' => $town->id, 'loyalty' => 52]);
    }

    public function test_soldiers_are_paid_when_the_town_can_afford_it(): void
    {
        [$country, $game] = $this->makeSeason();
        $town = $this->makeTown($game, $country, loyalty: 80);
        $gold = $this->addResource($town, 'gold', 100);
        $food = $this->addResource($town, 'food', 100);
        $soldiers = $this->addResource($town, 'soldiers', 10, capacity: null);

        $this->service()->processGame($game);

        $this->assertDatabaseHas('town_resources', ['id' => $gold->id, 'amount' => 90]);
        $this->assertDatabaseHas('town_resources', ['id' => $food->id, 'amount' => 90]);
        $this->assertDatabaseHas('town_resources', ['id' => $soldiers->id, 'amount' => 10]);
        $this->assertDatabaseHas('towns', ['id' => $town->id, 'loyalty' => 80]);
    }

    public function test_unpaid_soldiers_desert_and_loyalty_drops(): void
    {
        [$country, $game] = $this->makeSeason();
        $town = $this->makeTown($game, $country, loyalty: 50);
        $this->addResource($town, 'gold', 0);
        $this->addResource($town, 'food', 0);
        $soldiers = $this->addResource($town, 'soldiers', 10, capacity: null);

        $this->service()->processGame($game);

        $this->assertDatabaseHas('town_resources', ['id' => $soldiers->id, 'amount' => 8]);
        $this->assertDatabaseHas('towns', ['id' => $town->id, 'loyalty' => 45]);
    }

    public function test_a_town_starved_of_loyalty_is_destroyed_and_ends_the_season(): void
    {
        [$country, $game] = $this->makeSeason();
        $town = $this->makeTown($game, $country, loyalty: 3);
        $this->addResource($town, 'gold', 0);
        $this->addResource($town, 'food', 0);
        $this->addResource($town, 'soldiers', 10, capacity: null);

        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);
        $action = Action::create(['key' => 'mine_gold', 'name' => 'Miner', 'ap_cost' => 1, 'base_xp' => 5, 'effects' => null, 'is_active' => true]);
        ActionLog::create([
            'game_id' => $game->id,
            'player_id' => $player->id,
            'town_id' => $town->id,
            'action_id' => $action->id,
            'xp_gained' => 15,
        ]);

        $this->service()->processGame($game);

        $this->assertNotNull($town->refresh()->destroyed_at);
        $this->assertDatabaseHas('games', ['id' => $game->id, 'status' => 'ended']);

        $results = $game->refresh()->results;
        $this->assertSame('all_towns_destroyed', $results['reason']);
        $this->assertSame(15, $results['ranking'][0]['xp']);
        $this->assertSame($user->id, $results['ranking'][0]['user_id']);
    }

    public function test_the_upkeep_command_runs_the_cycle(): void
    {
        [$country, $game] = $this->makeSeason();
        $town = $this->makeTown($game, $country, loyalty: 80);
        $gold = $this->addResource($town, 'gold', 100);
        $this->addResource($town, 'food', 100);
        $this->addResource($town, 'soldiers', 5, capacity: null);

        $this->artisan('game:upkeep')->assertSuccessful();

        $this->assertDatabaseHas('town_resources', ['id' => $gold->id, 'amount' => 95]);
    }
}
