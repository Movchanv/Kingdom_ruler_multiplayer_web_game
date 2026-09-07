<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WorldEventService;
use Illuminate\Console\Command;

final class TickWorldEvents extends Command
{
    protected $signature = 'events:tick';

    protected $description = 'Fire due automatic world events on active seasons.';

    public function handle(WorldEventService $service): int
    {
        $fired = $service->tick();

        $this->info("World events fired: {$fired}.");

        return self::SUCCESS;
    }
}
