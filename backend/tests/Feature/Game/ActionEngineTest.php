<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Models\Action;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Resource;
use App\Models\Title;
use App\Models\Town;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ActionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * @param  array<string, int|null>  $resources
     * @return array{user: User, player: Player, town: Town}
     */
    private function seedPlayerInTown(array $resources = ['gold' => 1000]): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);

        foreach ($resources as $key => $capacity) {
            $resource = Resource::factory()->create(['key' => $key, 'name' => ucfirst($key)]);
            TownResource::create([
                'town_id' => $town->id,
                'resource_id' => $resource->id,
                'amount' => 0,
                'capacity' => $capacity,
            ]);
        }

        $user = User::factory()->create(['xp' => 0]);
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);

        return ['user' => $user, 'player' => $player, 'town' => $town];
    }

    private function mineGold(int $apCost = 1, int $baseXp = 5): Action
    {
        return Action::create([
            'key' => 'mine_gold',
            'name' => 'Miner de l\'or',
            'ap_cost' => $apCost,
            'base_xp' => $baseXp,
            'effects' => ['gold' => 10],
            'is_active' => true,
        ]);
    }

    public function test_an_action_produces_resources_grants_xp_and_logs(): void
    {
        ['user' => $user, 'player' => $player, 'town' => $town] = $this->seedPlayerInTown();
        Title::create(['name' => 'Paysan', 'slug' => 'paysan', 'min_xp' => 0, 'rank' => 0]);
        $this->mineGold();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertOk()
            ->assertJsonPath('data.xp_gained', 5)
            ->assertJsonPath('data.total_xp', 5)
            ->assertJsonPath('data.actions_remaining', 4)
            ->assertJsonPath('data.resources.0.gained', 10)
            ->assertJsonPath('data.resources.0.amount', 10);

        $this->assertDatabaseHas('town_resources', [
            'town_id' => $town->id,
            'amount' => 10,
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'xp' => 5]);
        $this->assertDatabaseHas('action_logs', [
            'player_id' => $player->id,
            'town_id' => $town->id,
            'xp_gained' => 5,
        ]);
    }

    public function test_xp_promotes_the_user_to_the_highest_earned_title(): void
    {
        ['user' => $user] = $this->seedPlayerInTown();
        $paysan = Title::create(['name' => 'Paysan', 'slug' => 'paysan', 'min_xp' => 0, 'rank' => 0]);
        $chevalier = Title::create(['name' => 'Chevalier', 'slug' => 'chevalier', 'min_xp' => 5, 'rank' => 1]);
        $user->update(['title_id' => $paysan->id]);
        $this->mineGold(baseXp: 5);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Chevalier');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'title_id' => $chevalier->id]);
    }

    public function test_production_is_clamped_to_town_capacity(): void
    {
        ['user' => $user, 'town' => $town] = $this->seedPlayerInTown(['gold' => 5]);
        $this->mineGold();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertOk()
            ->assertJsonPath('data.resources.0.gained', 5)
            ->assertJsonPath('data.resources.0.amount', 5);

        $this->assertDatabaseHas('town_resources', ['town_id' => $town->id, 'amount' => 5]);
    }

    public function test_the_daily_action_limit_is_enforced_globally(): void
    {
        ['user' => $user] = $this->seedPlayerInTown();
        $this->mineGold();
        Sanctum::actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])->assertOk();
        }

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('action');
    }

    public function test_an_action_cannot_target_a_resource_the_town_does_not_have(): void
    {
        ['user' => $user] = $this->seedPlayerInTown(['food' => 1000]);
        $this->mineGold();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('action');
    }

    public function test_actions_without_effects_are_not_available(): void
    {
        ['user' => $user] = $this->seedPlayerInTown();
        Action::create([
            'key' => 'build',
            'name' => 'Construire',
            'ap_cost' => 1,
            'base_xp' => 6,
            'effects' => null,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'build'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('action');
    }

    public function test_performing_an_action_decrements_remaining_in_game_state(): void
    {
        ['user' => $user] = $this->seedPlayerInTown();
        $this->mineGold();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])->assertOk();

        $this->getJson('/api/v1/game/state')
            ->assertOk()
            ->assertJsonPath('data.player.actions_remaining', 4);
    }

    public function test_a_user_without_a_player_cannot_perform_actions(): void
    {
        $this->mineGold();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/game/actions', ['action' => 'mine_gold'])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('player');
    }
}
