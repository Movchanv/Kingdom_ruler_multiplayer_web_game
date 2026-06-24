<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class GameNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{country: Country, game: Game, paris: Town, lyon: Town}
     */
    private function seedSeason(): array
    {
        $country = Country::factory()->create(['is_active' => true]);
        $game = Game::factory()->create(['country_id' => $country->id]);
        $paris = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'name' => 'Paris']);
        $lyon = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'name' => 'Lyon']);

        return ['country' => $country, 'game' => $game, 'paris' => $paris, 'lyon' => $lyon];
    }

    public function test_countries_list_shows_the_active_season_and_join_availability(): void
    {
        ['country' => $country] = $this->seedSeason();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/game/countries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $country->id)
            ->assertJsonPath('data.0.season.status', 'active')
            ->assertJsonPath('data.0.can_join', true);
    }

    public function test_a_user_can_join_a_country_and_is_dropped_in_the_default_town(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris] = $this->seedSeason();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/join', ['country_id' => $country->id])
            ->assertCreated()
            ->assertJsonPath('data.current_town_id', $paris->id);

        $this->assertDatabaseHas('players', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
    }

    public function test_a_user_cannot_join_two_active_countries_at_once(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris] = $this->seedSeason();
        $other = $this->seedSeason();
        $user = User::factory()->create();
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/join', ['country_id' => $other['country']->id])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('country_id');
    }

    public function test_a_player_can_switch_to_another_town_of_their_country(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris, 'lyon' => $lyon] = $this->seedSeason();
        $user = User::factory()->create();
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/towns/{$lyon->id}/enter")
            ->assertOk()
            ->assertJsonPath('data.current_town_id', $lyon->id);

        $this->assertDatabaseHas('players', [
            'user_id' => $user->id,
            'current_town_id' => $lyon->id,
        ]);
    }

    public function test_a_player_cannot_enter_a_town_from_another_country(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris] = $this->seedSeason();
        $foreign = $this->seedSeason();
        $user = User::factory()->create();
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/towns/{$foreign['paris']->id}/enter")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('town');
    }

    public function test_a_player_cannot_enter_a_destroyed_town(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris, 'lyon' => $lyon] = $this->seedSeason();
        $lyon->update(['destroyed_at' => now()]);
        $user = User::factory()->create();
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/towns/{$lyon->id}/enter")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('town');
    }

    public function test_game_state_returns_the_player_and_current_town(): void
    {
        ['country' => $country, 'game' => $game, 'paris' => $paris] = $this->seedSeason();
        $user = User::factory()->create();
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $paris->id,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/game/state')
            ->assertOk()
            ->assertJsonPath('data.player.season.id', $game->id)
            ->assertJsonPath('data.player.daily_actions', 5)
            ->assertJsonPath('data.town.id', $paris->id)
            ->assertJsonStructure([
                'data' => [
                    'player' => ['id', 'xp', 'title', 'country', 'season', 'daily_actions'],
                    'town' => ['id', 'name', 'loyalty', 'resources', 'buildings'],
                ],
            ]);
    }

    public function test_state_fails_when_the_user_has_not_joined(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/game/state')
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('player');
    }
}
