<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array<string, mixed>> */
    private const THREATS = [
        'Raid de pillards' => [
            'icon' => '🏴',
            'requirement' => ['soldiers' => 3],
            'success_effects' => ['soldiers' => -1, 'gold' => 15],
            'failure_effects' => ['gold' => -30, 'loyalty' => -3],
            'delay_min_minutes' => 30,
            'delay_max_minutes' => 90,
        ],
        'Disette' => [
            'icon' => '🌵',
            'requirement' => ['food' => 80],
            'success_effects' => ['food' => -40, 'loyalty' => 3],
            'failure_effects' => ['food' => -25, 'loyalty' => -8],
            'delay_min_minutes' => 90,
            'delay_max_minutes' => 240,
        ],
        'Révolte populaire' => [
            'icon' => '⚒️',
            'requirement' => ['soldiers' => 15],
            'success_effects' => ['soldiers' => -6, 'loyalty' => 10],
            'failure_effects' => ['loyalty' => -15, 'soldiers' => -5, 'gold' => -50],
            'delay_min_minutes' => 120,
            'delay_max_minutes' => 300,
        ],
    ];

    public function up(): void
    {
        foreach (self::THREATS as $name => $payload) {
            DB::table('events')
                ->where('name', $name)
                ->where('type', 'world')
                ->whereNull('requirement')
                ->update([
                    'icon' => $payload['icon'],
                    'requirement' => json_encode($payload['requirement'], JSON_THROW_ON_ERROR),
                    'success_effects' => json_encode($payload['success_effects'], JSON_THROW_ON_ERROR),
                    'failure_effects' => json_encode($payload['failure_effects'], JSON_THROW_ON_ERROR),
                    'delay_min_minutes' => $payload['delay_min_minutes'],
                    'delay_max_minutes' => $payload['delay_max_minutes'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('events')
            ->whereIn('name', array_keys(self::THREATS))
            ->update([
                'requirement' => null,
                'success_effects' => null,
                'failure_effects' => null,
                'delay_min_minutes' => null,
                'delay_max_minutes' => null,
            ]);
    }
};
