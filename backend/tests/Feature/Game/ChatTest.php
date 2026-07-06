<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Enums\ChatMessageType;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Country;
use App\Models\Game;
use App\Models\Player;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{country: Country, game: Game, user: User, player: Player}
     */
    private function seedPlayer(): array
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

        return ['country' => $country, 'game' => $game, 'user' => $user, 'player' => $player];
    }

    private function message(Game $game, Country $country, ?Player $player, string $body): ChatMessage
    {
        return ChatMessage::create([
            'game_id' => $game->id,
            'country_id' => $country->id,
            'player_id' => $player?->id,
            'type' => ChatMessageType::Player,
            'body' => $body,
        ]);
    }

    public function test_a_player_can_post_a_message_which_is_stored_and_broadcast(): void
    {
        Event::fake([ChatMessageSent::class]);
        ['country' => $country, 'game' => $game, 'user' => $user, 'player' => $player] = $this->seedPlayer();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/chat', ['body' => 'Bonjour à tous'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Bonjour à tous')
            ->assertJsonPath('data.author', $user->username)
            ->assertJsonPath('data.type', 'player');

        $this->assertDatabaseHas('chat_messages', [
            'game_id' => $game->id,
            'country_id' => $country->id,
            'player_id' => $player->id,
            'body' => 'Bonjour à tous',
        ]);
        Event::assertDispatched(ChatMessageSent::class);
    }

    public function test_a_message_body_is_required(): void
    {
        ['user' => $user] = $this->seedPlayer();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/game/chat', ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('body');
    }

    public function test_history_returns_only_the_players_country_within_seven_days_oldest_first(): void
    {
        ['country' => $country, 'game' => $game, 'user' => $user, 'player' => $player] = $this->seedPlayer();
        $this->message($game, $country, $player, 'A1');
        $this->message($game, $country, $player, 'A2');

        $old = $this->message($game, $country, $player, 'trop vieux');
        $old->created_at = now()->subDays(8);
        $old->save();

        $other = $this->seedPlayer();
        $this->message($other['game'], $other['country'], $other['player'], 'B1');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/game/chat')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.body', 'A1')
            ->assertJsonPath('data.1.body', 'A2');
    }

    public function test_chat_requires_an_active_player(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/game/chat')
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('player');
    }
}
