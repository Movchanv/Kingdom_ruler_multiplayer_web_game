<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChatMessageType;
use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class ChatService
{
    public function post(Player $player, string $body): ChatMessage
    {
        $message = ChatMessage::create([
            'game_id' => $player->game_id,
            'country_id' => $player->country_id,
            'player_id' => $player->id,
            'type' => ChatMessageType::Player,
            'body' => $body,
        ]);

        $author = $player->user()->value('username');

        event(new ChatMessageSent($message, $author));

        return $message->load('player.user');
    }

    public function system(int $gameId, int $countryId, string $body): ChatMessage
    {
        $message = ChatMessage::create([
            'game_id' => $gameId,
            'country_id' => $countryId,
            'player_id' => null,
            'type' => ChatMessageType::System,
            'body' => $body,
        ]);

        event(new ChatMessageSent($message));

        return $message;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function recent(Player $player, int $limit = 50): Collection
    {
        return ChatMessage::query()
            ->where('game_id', $player->game_id)
            ->where('country_id', $player->country_id)
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->with('player.user')
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
