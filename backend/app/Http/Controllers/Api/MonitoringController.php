<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BugStatus;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\Game;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class MonitoringController extends Controller
{
    use ApiResponse;

    private const SCHEDULER_MAX_LAG_MINUTES = 5;

    public function index(): JsonResponse
    {
        $dependencies = $this->dependencies();
        $queues = $this->queues();
        $scheduler = $this->scheduler();
        $anomalies = $this->anomalies();

        $alerts = [];

        foreach ($dependencies as $name => $up) {
            if (! $up) {
                $alerts[] = ['level' => 'high', 'message' => "Dépendance indisponible : {$name}"];
            }
        }

        if ($queues['failed'] > 0) {
            $alerts[] = ['level' => 'high', 'message' => "{$queues['failed']} job(s) de file en échec"];
        }

        if ($scheduler['lag_minutes'] === null || $scheduler['lag_minutes'] > self::SCHEDULER_MAX_LAG_MINUTES) {
            $alerts[] = ['level' => 'high', 'message' => 'Ordonnanceur en retard ou arrêté'];
        }

        if ($anomalies['blocking_open'] > 0) {
            $alerts[] = ['level' => 'high', 'message' => "{$anomalies['blocking_open']} anomalie(s) bloquante(s) ouverte(s)"];
        }

        return $this->success([
            'status' => $alerts === [] ? 'ok' : 'degraded',
            'checked_at' => now()->toIso8601String(),
            'dependencies' => $dependencies,
            'queues' => $queues,
            'scheduler' => $scheduler,
            'anomalies' => $anomalies,
            'game' => [
                'active_seasons' => Game::query()->where('status', GameStatus::Active)->count(),
                'actions_last_24h' => DB::table('action_logs')
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
            ],
            'alerts' => $alerts,
        ]);
    }

    /**
     * @return array<string, bool>
     */
    private function dependencies(): array
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable) {
            $checks['database'] = false;
        }

        try {
            Cache::put('monitoring:ping', '1', 5);
            $checks['cache'] = Cache::get('monitoring:ping') === '1';
        } catch (Throwable) {
            $checks['cache'] = false;
        }

        try {
            Redis::connection()->command('ping');
            $checks['redis'] = true;
        } catch (Throwable) {
            $checks['redis'] = false;
        }

        return $checks;
    }

    /**
     * État des files d'attente (jobs en échec = seuil d'alerte à 1).
     *
     * @return array{failed: int, last_failure_at: string|null}
     */
    private function queues(): array
    {
        try {
            $failed = DB::table('failed_jobs');

            return [
                'failed' => (int) $failed->count(),
                'last_failure_at' => $failed->max('failed_at'),
            ];
        } catch (Throwable) {
            // La table n'existe pas tant qu'aucun job n'a échoué.
            return ['failed' => 0, 'last_failure_at' => null];
        }
    }

    /**
     * Battement de cœur de l'ordonnanceur : la commande `monitoring:heartbeat`
     * écrit un horodatage à chaque minute ; l'écart mesure son retard.
     *
     * @return array{last_run_at: string|null, lag_minutes: int|null, healthy: bool}
     */
    private function scheduler(): array
    {
        $last = Cache::get('monitoring:scheduler:last_run');

        if (! is_string($last)) {
            return ['last_run_at' => null, 'lag_minutes' => null, 'healthy' => false];
        }

        $lag = (int) now()->diffInMinutes($last, true);

        return [
            'last_run_at' => $last,
            'lag_minutes' => $lag,
            'healthy' => $lag <= self::SCHEDULER_MAX_LAG_MINUTES,
        ];
    }

    /**
     * Anomalies consignées (C4.2.1) vues depuis la supervision.
     *
     * @return array{open: int, blocking_open: int, last_24h: int}
     */
    private function anomalies(): array
    {
        return [
            'open' => BugReport::query()->where('status', BugStatus::Open)->count(),
            'blocking_open' => BugReport::query()
                ->where('status', BugStatus::Open)
                ->where('severity', 'blocking')
                ->count(),
            'last_24h' => BugReport::query()->where('created_at', '>=', now()->subDay())->count(),
        ];
    }
}
