<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Models\Action;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Player;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdventureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * @param  array<string, int|null>  $resources
     * @return array{user: User, town: Town}
     */
    private function seedPlayer(array $resources = ['gold' => 1000], int $loyalty = 100): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'loyalty' => $loyalty]);

        foreach ($resources as $key => $capacity) {
            $resource = Resource::factory()->create(['key' => $key, 'name' => ucfirst($key)]);
            TownResource::create([
                'town_id' => $town->id,
                'resource_id' => $resource->id,
                'amount' => $capacity === null ? 10 : (int) min(10, $capacity),
                'capacity' => $capacity,
            ]);
        }

        $user = User::factory()->create(['xp' => 0]);
        Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);

        return ['user' => $user, 'town' => $town];
    }

    private function adventureAction(): Action
    {
        return Action::create([
            'key' => 'adventure',
            'name' => 'Partir à l\'aventure',
            'ap_cost' => 1,
            'base_xp' => 8,
            'effects' => null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, int>  $effects
     */
    private function adventureEvent(array $effects, string $name = 'Trésor caché'): Event
    {
        return Event::create([
            'type' => EventType::Adventure,
            'difficulty' => EventDifficulty::Easy,
            'name' => $name,
            'description' => 'Une rencontre.',
            'effects' => $effects,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    public function test_an_adventure_applies_an_event_grants_xp_and_logs_it(): void
    {
        ['user' => $user, 'town' => $town] = $this->seedPlayer(['gold' => 1000]);
        $this->adventureAction();
        $event = $this->adventureEvent(['gold' => 50]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/adventure')
            ->assertOk()
            ->assertJsonPath('data.event.name', 'Trésor caché')
            ->assertJsonPath('data.effects.0.key', 'gold')
            ->assertJsonPath('data.effects.0.delta', 50)
            ->assertJsonPath('data.xp_gained', 8)
            ->assertJsonPath('data.actions_remaining', 4);

        $this->assertDatabaseHas('town_resources', ['town_id' => $town->id, 'amount' => 60]);
        $this->assertDatabaseHas('action_logs', ['town_id' => $town->id, 'event_id' => $event->id, 'xp_gained' => 8]);
    }

    public function test_a_free_action_card_refunds_the_spent_action_point(): void
    {
        ['user' => $user] = $this->seedPlayer(['gold' => 1000]);
        $this->adventureAction();
        $this->adventureEvent(['free_action' => 1], 'Journée bénie');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/adventure')
            ->assertOk()
            ->assertJsonPath('data.free_actions', 1)
            ->assertJsonPath('data.actions_remaining', 5);
    }

    public function test_negative_effects_are_clamped_to_zero(): void
    {
        ['user' => $user, 'town' => $town] = $this->seedPlayer(['food' => 1000], loyalty: 3);
        $this->adventureAction();
        $this->adventureEvent(['food' => -50, 'loyalty' => -5], 'Peste');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/adventure')->assertOk();

        $this->assertDatabaseHas('town_resources', ['town_id' => $town->id, 'amount' => 0]);
        $this->assertDatabaseHas('towns', ['id' => $town->id, 'loyalty' => 0]);
    }

    public function test_an_adventure_fails_when_no_event_is_available(): void
    {
        ['user' => $user] = $this->seedPlayer();
        $this->adventureAction();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/adventure')
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('action');
    }
}
