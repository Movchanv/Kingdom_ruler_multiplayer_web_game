<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/up', function () {
    $checks = [];

    try {
        DB::select('select 1');
        $checks['database'] = true;
    } catch (Throwable) {
        $checks['database'] = false;
    }

    try {
        Cache::put('health:ping', '1', 5);
        $checks['cache'] = Cache::get('health:ping') === '1';
    } catch (Throwable) {
        $checks['cache'] = false;
    }

    $healthy = ! in_array(false, $checks, true);

    return response()->json([
        'status' => $healthy ? 'ok' : 'degraded',
        'checks' => $checks,
        'time' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
})->name('health');
