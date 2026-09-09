<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('events:tick')->hourly()->withoutOverlapping();
Schedule::command('events:resolve')->everyMinute()->withoutOverlapping();

Schedule::command('votes:close')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('game:upkeep')->twiceDaily(0, 12)->withoutOverlapping();

Schedule::command('model:prune')->daily()->withoutOverlapping();

Schedule::command('monitoring:heartbeat')->everyMinute();
