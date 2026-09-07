<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\GameStatus;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SeasonResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_season_results_include_the_players_usernames(): void
    {
        $winner = User::factory()->create(['username' => 'jeanne_darc']);
        $country = Country::factory()->create();
        $game = Game::factory()->create([
            'country_id' => $country->id,
            'status' => GameStatus::Ended,
            'ended_at' => now(),
            'results' => [
                'reason' => 'all_towns_destroyed',
                'ranking' => [
                    ['rank' => 1, 'player_id' => 1, 'user_id' => $winner->id, 'xp' => 120, 'actions' => 14],
                ],
            ],
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/game/seasons/{$game->id}/results")
            ->assertOk()
            ->assertJsonPath('data.country', $country->name)
            ->assertJsonPath('data.results.ranking.0.username', 'jeanne_darc')
            ->assertJsonPath('data.results.ranking.0.xp', 120);
    }

    public function test_last_season_returns_the_users_most_recent_ended_season(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create();
        $ended = Game::factory()->ended()->create(['country_id' => $country->id]);
        Player::factory()->create(['user_id' => $user->id, 'game_id' => $ended->id, 'country_id' => $country->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/game/seasons/last')
            ->assertOk()
            ->assertJsonPath('data.id', $ended->id)
            ->assertJsonPath('data.country', $country->name);
    }

    public function test_last_season_is_null_when_the_user_never_finished_one(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/game/seasons/last')
            ->assertOk()
            ->assertJsonPath('data', null);
    }
}
