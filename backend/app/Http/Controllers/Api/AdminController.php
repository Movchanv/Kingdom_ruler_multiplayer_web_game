<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GameStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Models\Game;
use App\Models\Player;
use App\Models\Town;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MaintenanceService $maintenance) {}

    public function overview(): JsonResponse
    {
        return $this->success([
            'users' => [
                'total' => User::query()->count(),
                'players' => User::query()->where('role', UserRole::Player)->count(),
                'banned' => User::query()->whereNotNull('banned_at')->count(),
            ],
            'seasons' => [
                'active' => Game::query()->where('status', GameStatus::Active)->count(),
                'ended' => Game::query()->where('status', GameStatus::Ended)->count(),
            ],
            'towns' => [
                'alive' => Town::query()->whereNull('destroyed_at')->count(),
                'destroyed' => Town::query()->whereNotNull('destroyed_at')->count(),
            ],
            'players' => Player::query()->count(),
            'actions' => DB::table('action_logs')->count(),
        ]);
    }

    public function games(): JsonResponse
    {
        $games = Game::query()
            ->where('status', GameStatus::Active)
            ->with('country')
            ->get()
            ->map(fn (Game $game): array => [
                'id' => $game->id,
                'name' => $game->name,
                'country' => [
                    'id' => $game->country?->id,
                    'name' => $game->country?->name,
                ],
                'vote_hours' => (int) ($game->config['vote_hours'] ?? 12),
            ])
            ->all();

        return $this->success($games);
    }

    public function users(): JsonResponse
    {
        $users = User::query()
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'xp' => $user->xp,
                'banned' => $user->isBanned(),
            ]);

        return $this->success($users);
    }

    public function banUser(BanUserRequest $request, User $user): JsonResponse
    {
        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;

        $user->forceFill(['banned_at' => now(), 'ban_reason' => $reason])->save();
        $user->tokens()->delete();

        return $this->success(null, __('User suspended.'));
    }

    public function unbanUser(User $user): JsonResponse
    {
        $user->forceFill(['banned_at' => null, 'ban_reason' => null])->save();

        return $this->success(null, __('User reinstated.'));
    }

    public function endSeason(Game $game): JsonResponse
    {
        $this->maintenance->forceEnd($game);

        return $this->success([
            'status' => $game->refresh()->status->value,
            'results' => $game->results,
        ], __('Season ended.'));
    }
}
