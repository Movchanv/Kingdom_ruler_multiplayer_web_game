<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownResource;
use App\Services\WorldEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorldEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{game: Game, town: Town}
     */
    private function seedSeason(int $ageDays = 0, int $loyalty = 100): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create([
            'country_id' => $country->id,
            'started_at' => now()->subDays($ageDays),
        ]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'loyalty' => $loyalty]);

        $gold = Resource::factory()->create(['key' => 'gold', 'name' => 'Or']);
        TownResource::create(['town_id' => $town->id, 'resource_id' => $gold->id, 'amount' => 100, 'capacity' => 1000]);

        return ['game' => $game, 'town' => $town];
    }

    /**
     * @param  array<string, int>  $effects
     */
    private function worldEvent(EventDifficulty $difficulty, array $effects, string $name): Event
    {
        return Event::create([
            'type' => EventType::World,
            'difficulty' => $difficulty,
            'name' => $name,
            'description' => 'Calamité.',
            'effects' => $effects,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    private function service(): WorldEventService
    {
        return app(WorldEventService::class);
    }

    public function test_a_world_event_strikes_a_living_town_and_records_the_cadence(): void
    {
        ['game' => $game, 'town' => $town] = $this->seedSeason();
        $this->worldEvent(EventDifficulty::Easy, ['gold' => -30, 'loyalty' => -3], 'Raid');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame('easy', $result['difficulty']);
        $this->assertDatabaseHas('town_resources', ['town_id' => $town->id, 'amount' => 70]);
        $this->assertDatabaseHas('towns', ['id' => $town->id, 'loyalty' => 97]);
        $this->assertNotNull($game->refresh()->last_world_event_at);
    }

    public function test_an_event_is_not_fired_again_within_the_cadence_window(): void
    {
        ['game' => $game] = $this->seedSeason();
        $game->update(['last_world_event_at' => now()->subHours(1)]);
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Raid');

        $this->assertFalse($this->service()->isDue($game));
        $this->assertNull($this->service()->fire($game));
    }

    public function test_difficulty_escalates_with_season_age(): void
    {
        ['game' => $game] = $this->seedSeason(ageDays: 6);
        $this->worldEvent(EventDifficulty::Hard, ['loyalty' => -10], 'Révolte');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame('hard', $result['difficulty']);
    }

    public function test_the_tick_command_fires_due_events_on_active_seasons(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Raid');

        $this->artisan('events:tick')->assertSuccessful();

        $this->assertNotNull($game->refresh()->last_world_event_at);
    }
}
