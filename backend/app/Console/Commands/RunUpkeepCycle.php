<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MaintenanceService;
use Illuminate\Console\Command;

final class RunUpkeepCycle extends Command
{
    protected $signature = 'game:upkeep';

    protected $description = 'Run the upkeep cycle: per-day production, soldier upkeep, town destruction and season end.';

    public function handle(MaintenanceService $service): int
    {
        $seasons = $service->runCycle();

        $this->info("Seasons processed: {$seasons}.");

        return self::SUCCESS;
    }
}
