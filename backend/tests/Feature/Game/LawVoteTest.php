<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\LawVoteStatus;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\Game;
use App\Models\Law;
use App\Models\Player;
use App\Models\Town;
use App\Models\User;
use App\Services\LawVoteService;
use App\Services\TownBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LawVoteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{country: Country, game: Game, town: Town, lawA: Law, lawB: Law}
     */
    private function seedCountry(): array
    {
        $country = Country::factory()->create();
        $game = Game::factory()->create([
            'country_id' => $country->id,
            'config' => ['daily_actions' => 5, 'vote_hours' => 12],
        ]);
        $town = Town::factory()->create(['game_id' => $game->id, 'country_id' => $country->id]);

        $suffix = uniqid();
        $lawA = Law::create(['key' => 'tax_relief_'.$suffix, 'name' => 'Allègement fiscal', 'bonus' => ['gold_bonus_pct' => 20], 'is_active' => true]);
        $lawB = Law::create(['key' => 'trade_pact_'.$suffix, 'name' => 'Pacte commercial', 'bonus' => ['food_per_day' => 5], 'is_active' => true]);

        return ['country' => $country, 'game' => $game, 'town' => $town, 'lawA' => $lawA, 'lawB' => $lawB];
    }

    private function playerIn(Game $game, Country $country, Town $town): array
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'country_id' => $country->id,
            'current_town_id' => $town->id,
        ]);

        return [$user, $player];
    }

    private function service(): LawVoteService
    {
        return app(LawVoteService::class);
    }

    public function test_an_admin_can_open_a_vote(): void
    {
        ['country' => $country, 'game' => $game, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/v1/admin/votes', [
            'game_id' => $game->id,
            'country_id' => $country->id,
            'law_ids' => [$lawA->id, $lawB->id],
            'hours' => 12,
        ])->assertCreated();

        $this->assertDatabaseHas('law_votes', ['game_id' => $game->id, 'status' => 'open']);
        $this->assertDatabaseCount('law_vote_options', 2);
    }

    public function test_a_non_admin_cannot_open_a_vote(): void
    {
        ['country' => $country, 'game' => $game, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->postJson('/api/v1/admin/votes', [
            'game_id' => $game->id,
            'country_id' => $country->id,
            'law_ids' => [$lawA->id, $lawB->id],
        ])->assertForbidden();
    }

    public function test_a_player_can_cast_and_change_their_ballot(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        $vote = $this->service()->open($game, $country, [$lawA->id, $lawB->id], 12);
        $optA = $vote->options->firstWhere('law_id', $lawA->id);
        $optB = $vote->options->firstWhere('law_id', $lawB->id);
        [$user] = $this->playerIn($game, $country, $town);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/votes/{$vote->id}/ballot", ['option_id' => $optA->id])
            ->assertOk()
            ->assertJsonPath('data.my_ballot', $optA->id);

        $this->postJson("/api/v1/game/votes/{$vote->id}/ballot", ['option_id' => $optB->id])
            ->assertOk()
            ->assertJsonPath('data.my_ballot', $optB->id)
            ->assertJsonPath('data.total_ballots', 1);

        $this->assertDatabaseCount('law_vote_ballots', 1);
    }

    public function test_a_player_cannot_vote_in_another_countrys_vote(): void
    {
        ['country' => $country, 'game' => $game, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        $vote = $this->service()->open($game, $country, [$lawA->id, $lawB->id], 12);
        $option = $vote->options->first();

        $other = $this->seedCountry();
        [$user] = $this->playerIn($other['game'], $other['country'], $other['town']);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/game/votes/{$vote->id}/ballot", ['option_id' => $option->id])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('vote');
    }

    public function test_closing_a_vote_applies_the_winning_law_and_its_bonus(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        $vote = $this->service()->open($game, $country, [$lawA->id, $lawB->id], 12);
        $optA = $vote->options->firstWhere('law_id', $lawA->id);
        [, $player] = $this->playerIn($game, $country, $town);
        $this->service()->castBallot($player, $vote, $optA);

        $winner = $this->service()->close($vote->refresh());

        $this->assertSame($lawA->id, $winner?->id);
        $this->assertDatabaseHas('law_votes', ['id' => $vote->id, 'status' => 'closed', 'winning_law_id' => $lawA->id]);
        $this->assertDatabaseHas('town_laws', ['town_id' => $town->id, 'law_id' => $lawA->id, 'law_vote_id' => $vote->id]);

        $bonuses = app(TownBonusService::class)->forTown($town->refresh());
        $this->assertSame(20, $bonuses['gold_bonus_pct'] ?? 0);
    }

    public function test_the_close_command_closes_due_votes(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        $vote = $this->service()->open($game, $country, [$lawA->id, $lawB->id], 12);
        $optA = $vote->options->firstWhere('law_id', $lawA->id);
        [, $player] = $this->playerIn($game, $country, $town);
        $this->service()->castBallot($player, $vote, $optA);
        $vote->update(['ends_at' => now()->subMinute()]);

        $this->artisan('votes:close')->assertSuccessful();

        $this->assertDatabaseHas('law_votes', ['id' => $vote->id, 'status' => 'closed', 'winning_law_id' => $lawA->id]);
    }

    public function test_current_returns_the_open_vote_with_tallies(): void
    {
        ['country' => $country, 'game' => $game, 'town' => $town, 'lawA' => $lawA, 'lawB' => $lawB] = $this->seedCountry();
        $vote = $this->service()->open($game, $country, [$lawA->id, $lawB->id], 12);
        $optA = $vote->options->firstWhere('law_id', $lawA->id);
        [$user, $player] = $this->playerIn($game, $country, $town);
        $this->service()->castBallot($player, $vote, $optA);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/game/votes/current')
            ->assertOk()
            ->assertJsonPath('data.id', $vote->id)
            ->assertJsonPath('data.status', LawVoteStatus::Open->value)
            ->assertJsonPath('data.my_ballot', $optA->id)
            ->assertJsonPath('data.total_ballots', 1);
    }
}
