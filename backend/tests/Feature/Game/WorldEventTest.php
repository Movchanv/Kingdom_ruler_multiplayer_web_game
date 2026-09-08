<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Models\Action;
use App\Models\ActionLog;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Player;
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

    private function logActions(Game $game, int $count, int $players = 1): void
    {
        $action = Action::query()->firstOrCreate(
            ['key' => 'harvest_food'],
            ['name' => 'Recolter', 'ap_cost' => 1, 'base_xp' => 1, 'is_active' => true],
        );

        for ($p = 0; $p < $players; $p++) {
            $player = Player::factory()->create([
                'game_id' => $game->id,
                'country_id' => $game->country_id,
            ]);

            for ($i = 0; $i < $count; $i++) {
                ActionLog::create([
                    'game_id' => $game->id,
                    'player_id' => $player->id,
                    'action_id' => $action->id,
                    'xp_gained' => 1,
                ]);
            }
        }
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

    public function test_a_quiet_young_season_mostly_draws_easy_events(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Averse');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame(0.9, $result['chances']['easy']);
        $this->assertSame(1.0, $result['intensity']);
        $this->assertSame(0, $result['actions_since_last_event']);
    }

    public function test_a_hard_event_stays_possible_even_on_a_quiet_season(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Averse');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame(0.05, $result['chances']['hard']);
        $this->assertSame(1.0, array_sum($result['chances']));
    }

    public function test_easy_events_stay_possible_under_maximum_pressure(): void
    {
        ['game' => $game] = $this->seedSeason(ageDays: 6);
        $this->logActions($game, count: 40);
        $this->worldEvent(EventDifficulty::Hard, ['loyalty' => -10], 'Révolte');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame(0.05, $result['chances']['easy']);
        $this->assertSame(0.05, $result['chances']['medium']);
        $this->assertSame(0.9, $result['chances']['hard']);
        $this->assertSame(1.0, array_sum($result['chances']));
    }

    public function test_season_age_alone_keeps_easy_events_more_likely_than_hard(): void
    {
        ['game' => $game] = $this->seedSeason(ageDays: 6);
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Averse');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertGreaterThan($result['chances']['hard'], $result['chances']['easy']);
    }

    public function test_player_activity_shifts_the_odds_toward_hard_events(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->logActions($game, count: 20);
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Averse');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame(20, $result['actions_since_last_event']);
        $this->assertGreaterThan($result['chances']['easy'], $result['chances']['hard']);
    }

    public function test_effects_are_amplified_by_pressure(): void
    {
        ['game' => $game, 'town' => $town] = $this->seedSeason(ageDays: 6);
        $this->logActions($game, count: 40);

        $food = Resource::factory()->create(['key' => 'food', 'name' => 'Nourriture']);
        TownResource::create(['town_id' => $town->id, 'resource_id' => $food->id, 'amount' => 200, 'capacity' => 1000]);

        $this->worldEvent(EventDifficulty::Medium, ['food' => -20], 'Disette');

        $result = $this->service()->fire($game);

        $this->assertNotNull($result);
        $this->assertSame(2.0, $result['intensity']);

        $applied = collect($result['effects'])->firstWhere('key', 'food');
        $this->assertNotNull($applied);
        $this->assertSame(-40, $applied['delta']);
    }

    public function test_only_actions_since_the_last_event_are_counted(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->logActions($game, count: 20);
        $game->forceFill(['last_world_event_at' => now()->subHours(13)])->save();

        ActionLog::query()->update(['created_at' => now()->subHours(20)]);

        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Averse');

        $result = $this->service()->fire($game->refresh());

        $this->assertNotNull($result);
        $this->assertSame(0, $result['actions_since_last_event']);
    }

    public function test_the_tick_command_fires_due_events_on_active_seasons(): void
    {
        ['game' => $game] = $this->seedSeason();
        $this->worldEvent(EventDifficulty::Easy, ['loyalty' => -3], 'Raid');

        $this->artisan('events:tick')->assertSuccessful();

        $this->assertNotNull($game->refresh()->last_world_event_at);
    }
}
