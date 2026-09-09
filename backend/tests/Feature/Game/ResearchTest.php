<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\GameStatus;
use App\Enums\UserRole;
use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ResearchTest extends TestCase
{
    use RefreshDatabase;

    private function researcher(): User
    {
        return User::factory()->create(['role' => UserRole::Researcher]);
    }

    public function test_a_researcher_can_read_the_overview(): void
    {
        Sanctum::actingAs($this->researcher());

        $this->getJson('/api/v1/research/overview')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['accounts', 'seasons' => ['active', 'ended'], 'actions_total', 'xp_total'],
            ]);
    }

    public function test_an_admin_may_also_read_research_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/research/overview')->assertOk();
    }

    public function test_a_player_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->getJson('/api/v1/research/overview')->assertForbidden();
    }

    public function test_action_usage_is_aggregated(): void
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);
        $action = Action::create(['key' => 'mine_gold', 'name' => 'Miner', 'ap_cost' => 1, 'base_xp' => 5, 'effects' => null, 'is_active' => true]);
        foreach (range(1, 3) as $ignored) {
            ActionLog::create(['game_id' => $game->id, 'player_id' => $player->id, 'town_id' => $town->id, 'action_id' => $action->id, 'xp_gained' => 5]);
        }

        Sanctum::actingAs($this->researcher());

        $this->getJson('/api/v1/research/actions')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'Miner')
            ->assertJsonPath('data.0.count', 3)
            ->assertJsonPath('data.0.xp', 15);
    }

    public function test_action_usage_can_be_downloaded_as_csv(): void
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);
        $action = Action::create(['key' => 'mine_gold', 'name' => 'Miner', 'ap_cost' => 1, 'base_xp' => 5, 'effects' => null, 'is_active' => true]);
        ActionLog::create(['game_id' => $game->id, 'player_id' => $player->id, 'town_id' => $town->id, 'action_id' => $action->id, 'xp_gained' => 5]);

        Sanctum::actingAs($this->researcher());

        $response = $this->get('/api/v1/research/exports/actions');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Miner', $response->getContent());
    }

    public function test_season_rankings_can_be_downloaded_as_csv_without_identities(): void
    {
        $country = Country::factory()->create();
        Game::factory()->create([
            'country_id' => $country->id,
            'status' => GameStatus::Ended,
            'ended_at' => now(),
            'results' => [
                'reason' => 'all_towns_destroyed',
                'ranking' => [
                    ['rank' => 1, 'player_id' => 1, 'user_id' => 42, 'xp' => 100, 'actions' => 10],
                ],
            ],
        ]);

        Sanctum::actingAs($this->researcher());

        $response = $this->get('/api/v1/research/exports/seasons');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('subject', $response->getContent());
        $this->assertStringNotContainsString('user_id', $response->getContent());
    }

    public function test_a_player_cannot_download_exports(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->get('/api/v1/research/exports/actions')->assertForbidden();
    }

    public function test_ended_season_rankings_are_pseudonymized(): void
    {
        $country = Country::factory()->create();
        Game::factory()->create([
            'country_id' => $country->id,
            'status' => GameStatus::Ended,
            'ended_at' => now(),
            'results' => [
                'reason' => 'all_towns_destroyed',
                'ranking' => [
                    ['rank' => 1, 'player_id' => 1, 'user_id' => 42, 'xp' => 100, 'actions' => 10],
                ],
            ],
        ]);

        $expected = substr(hash_hmac('sha256', '42', (string) config('app.key')), 0, 16);

        Sanctum::actingAs($this->researcher());

        $this->getJson('/api/v1/research/seasons')
            ->assertOk()
            ->assertJsonPath('data.0.ranking.0.rank', 1)
            ->assertJsonPath('data.0.ranking.0.xp', 100)
            ->assertJsonPath('data.0.ranking.0.subject', $expected)
            ->assertJsonMissingPath('data.0.ranking.0.user_id');
    }
}
