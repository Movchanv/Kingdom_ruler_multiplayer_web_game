<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use App\Enums\TownEventStatus;
use App\Models\Country;
use App\Models\Event;
use App\Models\Game;
use App\Models\Resource;
use App\Models\Town;
use App\Models\TownEvent;
use App\Models\TownResource;
use App\Services\TownEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TownEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{town: Town, soldiers: TownResource, gold: TownResource}
     */
    private function seedTown(int $soldiers = 10, int $gold = 100): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create(['country_id' => $country->id]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id, 'loyalty' => 80]);

        $soldiersResource = Resource::factory()->create(['key' => 'soldiers', 'name' => 'Soldats']);
        $goldResource = Resource::factory()->create(['key' => 'gold', 'name' => 'Or']);

        return [
            'town' => $town,
            'soldiers' => TownResource::create(['town_id' => $town->id, 'resource_id' => $soldiersResource->id, 'amount' => $soldiers, 'capacity' => null]),
            'gold' => TownResource::create(['town_id' => $town->id, 'resource_id' => $goldResource->id, 'amount' => $gold, 'capacity' => 1000]),
        ];
    }

    private function barbarians(): Event
    {
        return Event::create([
            'type' => EventType::World,
            'difficulty' => EventDifficulty::Medium,
            'name' => 'Attaque des barbares',
            'icon' => '🪓',
            'requirement' => ['soldiers' => 8],
            'success_effects' => ['soldiers' => -5, 'gold' => 20],
            'failure_effects' => ['soldiers' => -8, 'gold' => -40],
            'delay_min_minutes' => 60,
            'delay_max_minutes' => 180,
            'is_active' => true,
        ]);
    }

    private function service(): TownEventService
    {
        return app(TownEventService::class);
    }

    public function test_scheduling_announces_the_threat_without_touching_the_town(): void
    {
        ['town' => $town, 'soldiers' => $soldiers, 'gold' => $gold] = $this->seedTown();

        $townEvent = $this->service()->schedule($this->barbarians(), $town);

        $this->assertSame(TownEventStatus::Pending, $townEvent->status);
        $this->assertTrue($townEvent->resolves_at->isFuture());
        $this->assertSame(['soldiers' => 8], $townEvent->requirement);

        $this->assertSame(10, $soldiers->refresh()->amount);
        $this->assertSame(100, $gold->refresh()->amount);
    }

    public function test_the_delay_is_drawn_within_the_declared_range(): void
    {
        ['town' => $town] = $this->seedTown();
        $event = $this->barbarians();

        for ($i = 0; $i < 15; $i++) {
            $townEvent = $this->service()->schedule($event, $town);
            $minutes = now()->diffInMinutes($townEvent->resolves_at);

            $this->assertGreaterThanOrEqual(59, $minutes);
            $this->assertLessThanOrEqual(180, $minutes);
        }
    }

    public function test_a_town_that_meets_the_requirement_wins(): void
    {
        ['town' => $town, 'soldiers' => $soldiers, 'gold' => $gold] = $this->seedTown(soldiers: 10);
        $this->service()->schedule($this->barbarians(), $town, delayMinutes: 0);

        $this->assertSame(1, $this->service()->resolveDue());

        $this->assertSame(5, $soldiers->refresh()->amount);
        $this->assertSame(120, $gold->refresh()->amount);
        $this->assertSame(TownEventStatus::Succeeded, TownEvent::query()->firstOrFail()->status);
    }

    public function test_a_town_that_falls_short_loses(): void
    {
        ['town' => $town, 'soldiers' => $soldiers, 'gold' => $gold] = $this->seedTown(soldiers: 3);
        $this->service()->schedule($this->barbarians(), $town, delayMinutes: 0);

        $this->assertSame(1, $this->service()->resolveDue());

        $this->assertSame(0, $soldiers->refresh()->amount);
        $this->assertSame(60, $gold->refresh()->amount);
        $this->assertSame(TownEventStatus::Failed, TownEvent::query()->firstOrFail()->status);
    }

    public function test_a_pending_event_is_left_alone_before_its_deadline(): void
    {
        ['town' => $town, 'soldiers' => $soldiers] = $this->seedTown();
        $this->service()->schedule($this->barbarians(), $town, delayMinutes: 120);

        $this->assertSame(0, $this->service()->resolveDue());

        $this->assertSame(10, $soldiers->refresh()->amount);
        $this->assertSame(TownEventStatus::Pending, TownEvent::query()->firstOrFail()->status);
    }

    public function test_intensity_scales_both_the_requirement_and_the_consequences(): void
    {
        ['town' => $town] = $this->seedTown();

        $townEvent = $this->service()->schedule($this->barbarians(), $town, intensity: 2.0);

        $this->assertSame(16, $townEvent->requirement['soldiers']);
        $this->assertSame(-16, $townEvent->failure_effects['soldiers']);
        $this->assertSame(-80, $townEvent->failure_effects['gold']);
    }

    public function test_several_threats_can_coexist_on_a_town(): void
    {
        ['town' => $town] = $this->seedTown();
        $event = $this->barbarians();

        $this->service()->schedule($event, $town, delayMinutes: 60);
        $this->service()->schedule($event, $town, delayMinutes: 120);

        $this->assertSame(2, TownEvent::query()->pending()->count());
    }

    public function test_the_resolve_command_settles_due_events(): void
    {
        ['town' => $town, 'soldiers' => $soldiers] = $this->seedTown(soldiers: 10);
        $this->service()->schedule($this->barbarians(), $town, delayMinutes: 0);

        $this->artisan('events:resolve')->assertSuccessful();

        $this->assertSame(5, $soldiers->refresh()->amount);
    }

    public function test_an_event_without_requirement_always_succeeds(): void
    {
        ['town' => $town, 'gold' => $gold] = $this->seedTown();
        $gift = Event::create([
            'type' => EventType::Manual,
            'difficulty' => EventDifficulty::Easy,
            'name' => 'Don du roi',
            'success_effects' => ['gold' => 50],
            'is_active' => true,
        ]);

        $this->service()->schedule($gift, $town, delayMinutes: 0);
        $this->service()->resolveDue();

        $this->assertSame(150, $gold->refresh()->amount);
        $this->assertSame(TownEventStatus::Succeeded, TownEvent::query()->firstOrFail()->status);
    }
}
