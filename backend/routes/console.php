<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('events:tick')->hourly()->withoutOverlapping(30);
Schedule::command('events:resolve')->everyMinute()->withoutOverlapping(5);

Schedule::command('votes:close')->everyFiveMinutes()->withoutOverlapping(10);

Schedule::command('game:upkeep')->twiceDaily(0, 12)->withoutOverlapping(30);

Schedule::command('model:prune')->daily()->withoutOverlapping(30);

Schedule::command('monitoring:heartbeat')->everyMinute();
