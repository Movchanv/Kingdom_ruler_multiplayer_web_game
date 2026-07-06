<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Models\Action;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BuildEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{country: Country, game: Game, town: Town}
     */
    private function makeSeason(array $config = []): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create([
            'country_id' => $country->id,
            'config' => array_merge(['daily_actions' => 5, 'build_step' => 20], $config),
        ]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);

        return ['country' => $country, 'game' => $game, 'town' => $town];
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
     * @param  array<int, array{cost?: array<string, int>, bonus?: array<string, int>}>  $levels
     */
    private function makeBuilding(string $key, int $maxLevel, array $levels): Building
    {
        $building = Building::create([
            'key' => $key,
            'name' => ucfirst($key),
            'category' => 'production',
            'max_level' => $maxLevel,
            'is_active' => true,
        ]);

        foreach ($levels as $level => $data) {
            BuildingLevel::create([
                'building_id' => $building->id,
                'level' => $level,
                'cost' => $data['cost'] ?? null,
                'bonus' => $data['bonus'] ?? null,
            ]);
        }

        return $building;
    }

    /**
     * @param  array<string, int>  $contributions
     */
    private function placeBuilding(Town $town, Building $building, int $level = 0, array $contributions = []): TownBuilding
    {
        return TownBuilding::create([
            'town_id' => $town->id,
            'building_id' => $building->id,
            'level' => $level,
            'contributions' => $contributions,
        ]);
    }

    /**
     * @return array{0: User, 1: Player}
     */
    private function playerIn(Town $town, Game $game, Country $country): array
    {
        $user = User::factory()->create(['xp' => 0]);
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);

        return [$user, $player];
    }

    private function buildAction(): Action
    {
        return Action::create([
            'key' => 'build',
            'name' => 'Construire',
            'ap_cost' => 1,
            'base_xp' => 6,
            'effects' => null,
            'is_active' => true,
        ]);
    }

    public function test_a_contribution_advances_shared_progress_without_levelling(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason();
        $wood = $this->addResource($town, 'wood', 100);
        $stone = $this->addResource($town, 'stone', 100);
        $building = $this->makeBuilding('sawmill', 3, [
            1 => ['cost' => ['wood' => 50, 'stone' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $townBuilding = $this->placeBuilding($town, $building);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertOk()
            ->assertJsonPath('data.leveled_up', false)
            ->assertJsonPath('data.level', 0)
            ->assertJsonPath('data.spent.wood', 20)
            ->assertJsonPath('data.contributions.wood', 20)
            ->assertJsonPath('data.remaining_cost.wood', 30)
            ->assertJsonPath('data.xp_gained', 6)
            ->assertJsonPath('data.actions_remaining', 4);

        $this->assertDatabaseHas('town_resources', ['id' => $wood->id, 'amount' => 80]);
        $this->assertDatabaseHas('town_resources', ['id' => $stone->id, 'amount' => 80]);
        $this->assertDatabaseHas('town_buildings', ['id' => $townBuilding->id, 'level' => 0]);
    }

    public function test_a_contribution_levels_the_building_up_and_returns_its_bonus(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason(['build_step' => 100]);
        $wood = $this->addResource($town, 'wood', 50);
        $this->addResource($town, 'stone', 50);
        $building = $this->makeBuilding('sawmill', 3, [
            1 => ['cost' => ['wood' => 50, 'stone' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $townBuilding = $this->placeBuilding($town, $building);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertOk()
            ->assertJsonPath('data.leveled_up', true)
            ->assertJsonPath('data.level', 1)
            ->assertJsonPath('data.bonus.wood_bonus_pct', 15)
            ->assertJsonPath('data.contributions', [])
            ->assertJsonPath('data.remaining_cost', null);

        $this->assertDatabaseHas('town_buildings', ['id' => $townBuilding->id, 'level' => 1]);
        $this->assertDatabaseHas('town_resources', ['id' => $wood->id, 'amount' => 0]);
    }

    public function test_a_contribution_is_clamped_to_available_resources(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason();
        $wood = $this->addResource($town, 'wood', 5);
        $this->addResource($town, 'stone', 0);
        $building = $this->makeBuilding('sawmill', 3, [
            1 => ['cost' => ['wood' => 50, 'stone' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $townBuilding = $this->placeBuilding($town, $building);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertOk()
            ->assertJsonPath('data.leveled_up', false)
            ->assertJsonPath('data.spent.wood', 5)
            ->assertJsonMissingPath('data.spent.stone')
            ->assertJsonPath('data.contributions.wood', 5);

        $this->assertDatabaseHas('town_resources', ['id' => $wood->id, 'amount' => 0]);
    }

    public function test_a_contribution_is_rejected_when_the_town_cannot_make_progress(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason();
        $this->addResource($town, 'wood', 0);
        $this->addResource($town, 'stone', 0);
        $building = $this->makeBuilding('sawmill', 3, [
            1 => ['cost' => ['wood' => 50, 'stone' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $townBuilding = $this->placeBuilding($town, $building);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('building');
    }

    public function test_a_maxed_building_cannot_be_built_further(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason();
        $this->addResource($town, 'wood', 100);
        $building = $this->makeBuilding('sawmill', 1, [
            1 => ['cost' => ['wood' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $townBuilding = $this->placeBuilding($town, $building, level: 1);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('building');
    }

    public function test_a_player_cannot_build_in_a_town_they_are_not_in(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason();
        $otherTown = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);
        $this->addResource($otherTown, 'wood', 100);
        $building = $this->makeBuilding('sawmill', 3, [
            1 => ['cost' => ['wood' => 50], 'bonus' => ['wood_bonus_pct' => 15]],
        ]);
        $foreignBuilding = $this->placeBuilding($otherTown, $building);
        $this->buildAction();
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$foreignBuilding->id}/build")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('building');
    }

    public function test_a_levelled_production_building_boosts_the_matching_action(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town] = $this->makeSeason(['build_step' => 100]);
        $this->addResource($town, 'gold', 0);
        $this->addResource($town, 'wood', 100);
        $this->addResource($town, 'stone', 100);
        $mine = $this->makeBuilding('gold_mine', 3, [
            1 => ['cost' => ['wood' => 50, 'stone' => 50], 'bonus' => ['gold_bonus_pct' => 10]],
        ]);
        $townBuilding = $this->placeBuilding($town, $mine);
        $this->buildAction();
        Action::create([
            'key' => 'mine_gold',
            'name' => 'Miner de l\'or',
            'ap_cost' => 1,
            'base_xp' => 5,
            'effects' => ['gold' => 10],
            'is_active' => true,
        ]);
        [$user] = $this->playerIn($town, $game, $country);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/buildings/{$townBuilding->id}/build")
            ->assertOk()
            ->assertJsonPath('data.leveled_up', true);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertOk()
            ->assertJsonPath('data.resources.0.gained', 11);
    }
}
