<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TownEventService;
use Illuminate\Console\Command;

final class ResolveTownEvents extends Command
{
    protected $signature = 'events:resolve';

    protected $description = 'Resolve announced town events whose deadline has passed.';

    public function handle(TownEventService $service): int
    {
        $resolved = $service->resolveDue();
        $this->info("Town events resolved: {$resolved}.");

        return self::SUCCESS;
    }
}
