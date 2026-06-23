<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\Player;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AccountRgpdTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_export_their_personal_data(): void
    {
        $user = User::factory()->create();
        Player::factory()->create(['user_id' => $user->id, 'display_name' => 'Roi Arthur']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/export')
            ->assertOk()
            ->assertJsonPath('data.account.email', $user->email)
            ->assertJsonStructure([
                'data' => [
                    'account' => ['username', 'email', 'country', 'date_of_birth', 'gender'],
                    'players' => [['game', 'display_name', 'xp']],
                ],
            ]);
    }

    public function test_a_user_can_delete_their_account_and_personal_data_is_erased(): void
    {
        $user = User::factory()->create(['country' => 'FR']);
        $originalEmail = $user->email;
        $player = Player::factory()->create(['user_id' => $user->id, 'display_name' => 'Roi Arthur']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/account')->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'email' => $originalEmail]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'country' => null, 'username' => null]);

        $this->assertDatabaseHas('players', ['id' => $player->id, 'display_name' => 'Ancien joueur']);
    }

    public function test_a_user_can_view_and_update_their_consent(): void
    {
        $user = User::factory()->create(['privacy_policy_version' => '0.9']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/consent')
            ->assertOk()
            ->assertJsonPath('data.current_version', AuthService::PRIVACY_POLICY_VERSION)
            ->assertJsonPath('data.up_to_date', false);

        $this->postJson('/api/v1/account/consent')->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'privacy_policy_version' => AuthService::PRIVACY_POLICY_VERSION,
        ]);
    }
}
