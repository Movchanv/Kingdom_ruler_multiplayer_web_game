<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_the_health_endpoint_reports_its_dependencies(): void
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', true)
            ->assertJsonPath('checks.cache', true);
    }

    public function test_an_admin_sees_the_monitoring_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/admin/monitoring')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'checked_at',
                    'dependencies' => ['database', 'cache'],
                    'queues' => ['failed'],
                    'scheduler' => ['last_run_at', 'lag_minutes', 'healthy'],
                    'anomalies' => ['open', 'blocking_open', 'last_24h'],
                    'game' => ['active_seasons', 'actions_last_24h'],
                    'alerts',
                ],
            ]);
    }

    public function test_a_stopped_scheduler_raises_an_alert(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        // Aucun battement de cœur enregistré : l'ordonnanceur est signalé en défaut.
        $this->getJson('/api/v1/admin/monitoring')
            ->assertOk()
            ->assertJsonPath('data.scheduler.healthy', false)
            ->assertJsonPath('data.status', 'degraded');
    }

    public function test_a_fresh_heartbeat_clears_the_scheduler_alert(): void
    {
        $this->artisan('monitoring:heartbeat')->assertSuccessful();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/admin/monitoring')
            ->assertOk()
            ->assertJsonPath('data.scheduler.healthy', true)
            ->assertJsonPath('data.scheduler.lag_minutes', 0);
    }

    public function test_a_player_cannot_access_the_monitoring_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->getJson('/api/v1/admin/monitoring')->assertForbidden();
    }
}
