<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LawVoteService;
use Illuminate\Console\Command;

final class CloseLawVotes extends Command
{
    protected $signature = 'votes:close';

    protected $description = 'Close law votes whose deadline has passed and apply the winning law.';

    public function handle(LawVoteService $service): int
    {
        $closed = $service->closeDue();

        $this->info("Law votes closed: {$closed}.");

        return self::SUCCESS;
    }
}
