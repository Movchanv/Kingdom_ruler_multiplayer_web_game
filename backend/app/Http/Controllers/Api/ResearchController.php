<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
        return $this->success($this->actionsData());
    }

    public function seasons(): JsonResponse
    {
        return $this->success($this->seasonsData());
    }

    public function exportActions(): Response
    {
        $rows = array_map(
            fn (array $row): array => [$row['action'], $row['count'], $row['xp']],
            $this->actionsData(),
        );

        return $this->csvResponse(
            'medieval-realm-actions-'.now()->format('Ymd').'.csv',
            ['action', 'count', 'xp_total'],
            $rows,
        );
    }

    public function exportSeasons(): Response
    {
        $rows = [];

        foreach ($this->seasonsData() as $season) {
            foreach ($season['ranking'] as $entry) {
                $rows[] = [
                    $season['id'],
                    $season['name'],
                    $season['ended_at'],
                    $entry['rank'],
                    $entry['subject'],
                    $entry['xp'],
                    $entry['actions'],
                ];
            }
        }

        return $this->csvResponse(
            'medieval-realm-seasons-'.now()->format('Ymd').'.csv',
            ['season_id', 'season', 'ended_at', 'rank', 'subject', 'xp', 'actions'],
            $rows,
        );
    }

    /**
     * @return array<int, array{action: string, count: int, xp: int}>
     */
    private function actionsData(): array
    {
        $rows = DB::table('action_logs')
            ->join('actions', 'actions.id', '=', 'action_logs.action_id')
            ->groupBy('actions.key', 'actions.name')
            ->select('actions.key', 'actions.name')
            ->selectRaw('COUNT(*) as count, SUM(action_logs.xp_gained) as xp')
            ->orderByDesc('count')
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'action' => (string) ($row->name ?: $row->key),
                'count' => (int) $row->count,
                'xp' => (int) $row->xp,
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array{id: int, name: string, ended_at: string|null, ranking: array<int, array{rank: int, subject: string, xp: int, actions: int}>}>
     */
    private function seasonsData(): array
    {
        return Game::query()
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
    }

    private function pseudonymize(int $userId): string
    {
        return substr(hash_hmac('sha256', (string) $userId, (string) config('app.key')), 0, 16);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string|int|null>>  $rows
     */
    private function csvResponse(string $filename, array $header, array $rows): Response
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            abort(500, 'Unable to build the export.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header, ';');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
