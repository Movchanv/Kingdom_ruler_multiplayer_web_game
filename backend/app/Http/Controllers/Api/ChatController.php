<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\SendChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\Player;
use App\Models\User;
use App\Services\ChatService;
use App\Services\GameService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GameService $game,
        private readonly ChatService $chat,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $player = $this->requirePlayer($request);

        $messages = $this->chat->recent($player)
            ->map(fn (ChatMessage $message): array => $this->present($message))
            ->all();

        return $this->success($messages);
    }

    public function store(SendChatMessageRequest $request): JsonResponse
    {
        $player = $this->requirePlayer($request);

        $message = $this->chat->post($player, (string) $request->string('body'));

        return $this->success($this->present($message), __('Message sent.'), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'type' => $message->type->value,
            'body' => $message->body,
            'player_id' => $message->player_id,
            'author' => $message->player?->user?->username,
            'at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function requirePlayer(Request $request): Player
    {
        /** @var User $user */
        $user = $request->user();

        $player = $this->game->activePlayer($user);

        if ($player === null) {
            throw ValidationException::withMessages([
                'player' => [__('You have not joined a country yet.')],
            ]);
        }

        return $player;
    }
}
