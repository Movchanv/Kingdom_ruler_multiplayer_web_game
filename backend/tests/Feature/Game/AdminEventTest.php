<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownEvent;
use App\Models\TownResource;
use App\Models\User;
use App\Services\TownEventService;
use App\Services\WorldEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{town: Town, resource: TownResource}
     */
    private function seedTown(): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);
        $gold = Resource::factory()->create(['key' => 'gold', 'name' => 'Or']);
        $resource = TownResource::create(['town_id' => $town->id, 'resource_id' => $gold->id, 'amount' => 100, 'capacity' => 1000]);

        return ['town' => $town, 'resource' => $resource];
    }

    private function worldEvent(): Event
    {
        return Event::create([
            'type' => EventType::World,
            'difficulty' => EventDifficulty::Hard,
            'name' => 'Révolte',
            'description' => 'Le peuple gronde.',
            'effects' => ['gold' => -30],
            'failure_effects' => ['gold' => -30],
            'weight' => 10,
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_create_an_event(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Incendie au grenier',
            'description' => 'Les reserves partent en fumee.',
            'type' => 'world',
            'difficulty' => 'medium',
            'effects' => ['food' => -25, 'loyalty' => -4],
            'weight' => 5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Incendie au grenier')
            ->assertJsonPath('data.type', 'world')
            ->assertJsonPath('data.difficulty', 'medium')
            ->assertJsonPath('data.effects.food', -25)
            ->assertJsonPath('data.effects.loyalty', -4);

        $this->assertDatabaseHas('events', ['name' => 'Incendie au grenier', 'is_active' => true]);
    }

    public function test_an_admin_can_create_an_announced_threat(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Attaque des barbares',
            'description' => 'Une horde approche.',
            'icon' => '🪓',
            'type' => 'world',
            'difficulty' => 'medium',
            'requirement' => ['soldiers' => 8],
            'success_effects' => ['soldiers' => -5, 'gold' => 40],
            'failure_effects' => ['soldiers' => -8, 'gold' => -60],
            'delay_min_minutes' => 60,
            'delay_max_minutes' => 180,
        ])
            ->assertCreated()
            ->assertJsonPath('data.requirement.soldiers', 8)
            ->assertJsonPath('data.success_effects.gold', 40)
            ->assertJsonPath('data.failure_effects.soldiers', -8)
            ->assertJsonPath('data.delay_min_minutes', 60)
            ->assertJsonPath('data.icon', '🪓');
    }

    public function test_an_event_without_any_consequence_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Evenement creux',
            'type' => 'world',
            'difficulty' => 'easy',
            'requirement' => ['soldiers' => 5],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('effects');
    }

    public function test_a_maximum_delay_below_the_minimum_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Delai incoherent',
            'type' => 'world',
            'difficulty' => 'easy',
            'failure_effects' => ['gold' => -10],
            'delay_min_minutes' => 120,
            'delay_max_minutes' => 30,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delay_max_minutes');
    }

    public function test_a_created_event_records_its_author(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Crue de la Seine',
            'type' => 'world',
            'difficulty' => 'hard',
            'effects' => ['wood' => -40],
        ])->assertCreated();

        $this->assertDatabaseHas('events', ['name' => 'Crue de la Seine', 'created_by' => $admin->id]);
    }

    public function test_an_unknown_effect_target_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Effet farfelu',
            'type' => 'world',
            'difficulty' => 'easy',
            'effects' => ['licornes' => 10],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('effects.licornes');
    }

    public function test_a_null_effect_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Sans effet',
            'type' => 'world',
            'difficulty' => 'easy',
            'effects' => ['gold' => 0],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('effects.gold');
    }

    public function test_a_player_cannot_create_an_event(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Tentative',
            'type' => 'world',
            'difficulty' => 'easy',
            'effects' => ['gold' => -10],
        ])->assertForbidden();
    }

    public function test_an_admin_can_list_the_events(): void
    {
        $this->worldEvent();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/admin/events')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Révolte')
            ->assertJsonPath('data.0.effects.gold', -30);
    }

    public function test_an_admin_can_create_a_manual_only_event(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/events', [
            'name' => 'Edit royal',
            'type' => 'manual',
            'difficulty' => 'medium',
            'effects' => ['gold' => 100],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'manual');

        $this->assertDatabaseHas('events', ['name' => 'Edit royal', 'type' => 'manual']);
    }

    public function test_a_manual_event_is_never_drawn_automatically(): void
    {
        ['town' => $town] = $this->seedTown();

        Event::create([
            'type' => EventType::Manual,
            'difficulty' => EventDifficulty::Easy,
            'name' => 'Edit royal',
            'effects' => ['gold' => 100],
            'is_active' => true,
        ]);

        $fired = app(WorldEventService::class)->fire($town->game);

        $this->assertNull($fired);
        $this->assertNull($town->game->refresh()->last_world_event_at);
    }

    public function test_a_manual_event_can_still_be_triggered_by_an_admin(): void
    {
        ['town' => $town, 'resource' => $resource] = $this->seedTown();
        $event = Event::create([
            'type' => EventType::Manual,
            'difficulty' => EventDifficulty::Easy,
            'name' => 'Don du roi',
            'effects' => ['gold' => 50],
            'success_effects' => ['gold' => 50],
            'is_active' => true,
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson("/api/v1/admin/events/{$event->id}/trigger", [
            'town_id' => $town->id,
            'delay_minutes' => 0,
        ])->assertOk();

        app(TownEventService::class)->resolveDue();

        $this->assertSame(150, $resource->refresh()->amount);
        $this->assertDatabaseHas('town_events', ['town_id' => $town->id, 'status' => 'succeeded']);
    }

    public function test_an_admin_can_trigger_an_event_on_a_town(): void
    {
        ['town' => $town, 'resource' => $resource] = $this->seedTown();
        $event = $this->worldEvent();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson("/api/v1/admin/events/{$event->id}/trigger", ['town_id' => $town->id])
            ->assertOk()
            ->assertJsonPath('data.town_id', $town->id)
            ->assertJsonStructure(['data' => ['town_event_id', 'resolves_at']]);

        $this->assertDatabaseHas('town_events', ['town_id' => $town->id, 'status' => 'pending']);
        $this->assertSame(100, $resource->refresh()->amount);
    }

    public function test_an_admin_can_force_the_deadline(): void
    {
        ['town' => $town] = $this->seedTown();
        $event = $this->worldEvent();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson("/api/v1/admin/events/{$event->id}/trigger", [
            'town_id' => $town->id,
            'delay_minutes' => 0,
        ])->assertOk();

        $this->assertFalse(TownEvent::query()->firstOrFail()->resolves_at->isFuture());
    }

    public function test_a_non_admin_cannot_trigger_events(): void
    {
        ['town' => $town] = $this->seedTown();
        $event = $this->worldEvent();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->postJson("/api/v1/admin/events/{$event->id}/trigger", ['town_id' => $town->id])
            ->assertForbidden();

        $this->assertDatabaseHas('town_resources', ['town_id' => $town->id, 'amount' => 100]);
    }
}
