<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('events:tick')->hourly();

Schedule::command('votes:close')->everyFiveMinutes();

Schedule::command('game:upkeep')->twiceDaily(0, 12);
