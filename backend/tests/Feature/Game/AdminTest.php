<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

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

final class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_overview_returns_global_stats(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/overview')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'users' => ['total', 'players', 'banned'],
                    'seasons' => ['active', 'ended'],
                    'towns' => ['alive', 'destroyed'],
                    'players',
                    'actions',
                ],
            ]);
    }

    public function test_users_are_listed_with_pagination(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'username', 'role', 'banned']],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_an_admin_can_ban_a_user_and_block_their_login(): void
    {
        Sanctum::actingAs($this->admin());
        $target = User::factory()->create();

        $this->postJson("/api/v1/admin/users/{$target->id}/ban", ['reason' => 'triche'])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'ban_reason' => 'triche']);
        $this->assertNotNull($target->refresh()->banned_at);

        $this->postJson('/api/v1/auth/login', [
            'email' => $target->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertStatus(422);
    }

    public function test_an_admin_can_unban_a_user(): void
    {
        Sanctum::actingAs($this->admin());
        $target = User::factory()->create(['banned_at' => now(), 'ban_reason' => 'x']);

        $this->postJson("/api/v1/admin/users/{$target->id}/unban")->assertOk();

        $this->assertNull($target->refresh()->banned_at);
    }

    public function test_an_admin_can_force_end_a_season(): void
    {
        Sanctum::actingAs($this->admin());
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
        ActionLog::create(['game_id' => $game->id, 'player_id' => $player->id, 'town_id' => $town->id, 'action_id' => $action->id, 'xp_gained' => 20]);

        $this->postJson("/api/v1/admin/games/{$game->id}/end")
            ->assertOk()
            ->assertJsonPath('data.status', 'ended')
            ->assertJsonPath('data.results.reason', 'admin_ended')
            ->assertJsonPath('data.results.ranking.0.xp', 20);

        $this->assertNotNull($town->refresh()->destroyed_at);
    }

    public function test_a_non_admin_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->getJson('/api/v1/admin/overview')->assertForbidden();
    }
}
