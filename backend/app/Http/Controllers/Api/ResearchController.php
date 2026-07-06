<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ResearchController extends Controller
{
    use ApiResponse;

    public function overview(): JsonResponse
    {
        return $this->success([
            'accounts' => User::query()->whereNotNull('terms_accepted_at')->count(),
            'seasons' => [
                'active' => Game::query()->where('status', GameStatus::Active)->count(),
                'ended' => Game::query()->where('status', GameStatus::Ended)->count(),
            ],
            'actions_total' => DB::table('action_logs')->count(),
            'xp_total' => (int) User::query()->sum('xp'),
        ]);
    }

    public function actions(): JsonResponse
    {
        $rows = DB::table('action_logs')
            ->join('actions', 'actions.id', '=', 'action_logs.action_id')
            ->groupBy('actions.key')
            ->select('actions.key')
            ->selectRaw('COUNT(*) as count, SUM(action_logs.xp_gained) as xp')
            ->orderByDesc('count')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'action' => (string) $row->key,
                'count' => (int) $row->count,
                'xp' => (int) $row->xp,
            ];
        }

        return $this->success($data);
    }

    public function seasons(): JsonResponse
    {
        $seasons = Game::query()
            ->where('status', GameStatus::Ended)
            ->latest('ended_at')
            ->get()
            ->map(function (Game $game): array {
                $results = is_array($game->results) ? $game->results : [];
                $ranking = is_array($results['ranking'] ?? null) ? $results['ranking'] : [];

                $anonymized = [];
                foreach ($ranking as $entry) {
                    $anonymized[] = [
                        'rank' => (int) ($entry['rank'] ?? 0),
                        'subject' => $this->pseudonymize((int) ($entry['user_id'] ?? 0)),
                        'xp' => (int) ($entry['xp'] ?? 0),
                        'actions' => (int) ($entry['actions'] ?? 0),
                    ];
                }

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'ended_at' => $game->ended_at?->toIso8601String(),
                    'ranking' => $anonymized,
                ];
            })
            ->all();

        return $this->success($seasons);
    }

    private function pseudonymize(int $userId): string
    {
        return substr(hash_hmac('sha256', (string) $userId, (string) config('app.key')), 0, 16);
    }
}
