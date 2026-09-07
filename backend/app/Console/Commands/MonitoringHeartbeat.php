<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class MonitoringHeartbeat extends Command
{
    protected $signature = 'monitoring:heartbeat';

    protected $description = 'Écrit le battement de cœur de l\'ordonnanceur pour la supervision.';

    public function handle(): int
    {
        Cache::put('monitoring:scheduler:last_run', now()->toIso8601String(), now()->addHour());

        return self::SUCCESS;
    }
}
