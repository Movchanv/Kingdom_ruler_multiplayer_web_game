<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Enums\UserRole;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BugReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Le chat ne se met pas à jour',
            'severity' => 'major',
            'scope' => 'realtime',
            'description' => 'Étapes : ouvrir le chat sur deux navigateurs. Attendu : le message apparaît. Observé : rien ne se passe.',
            'page' => '/play/town',
        ], $overrides);
    }

    public function test_a_player_can_report_an_anomaly_and_receives_a_reference(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/support/reports', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.reference', 'BUG-'.now()->format('Y').'-0001');

        $this->assertDatabaseHas('bug_reports', [
            'user_id' => $user->id,
            'title' => 'Le chat ne se met pas à jour',
            'severity' => 'major',
            'status' => 'open',
            'page' => '/play/town',
        ]);
    }

    public function test_references_are_sequential(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/support/reports', $this->validPayload())->assertCreated();
        $this->postJson('/api/v1/support/reports', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.reference', 'BUG-'.now()->format('Y').'-0002');
    }

    public function test_a_report_requires_a_detailed_description(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/support/reports', $this->validPayload(['description' => 'trop court']))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('description');
    }

    public function test_an_invalid_severity_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/support/reports', $this->validPayload(['severity' => 'catastrophique']))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('severity');
    }

    public function test_a_guest_cannot_report(): void
    {
        $this->postJson('/api/v1/support/reports', $this->validPayload())->assertUnauthorized();
    }

    public function test_an_admin_can_list_the_reports(): void
    {
        $reporter = User::factory()->create();
        Sanctum::actingAs($reporter);
        $this->postJson('/api/v1/support/reports', $this->validPayload())->assertCreated();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/admin/bug-reports')
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'Le chat ne se met pas à jour')
            ->assertJsonPath('data.data.0.severity_label', 'Majeur')
            ->assertJsonPath('data.data.0.reported_by', $reporter->username);
    }

    public function test_a_player_cannot_list_the_reports(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Player]));

        $this->getJson('/api/v1/admin/bug-reports')->assertForbidden();
    }

    public function test_reports_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/support/reports', $this->validPayload())->assertCreated();
        BugReport::query()->first()?->update(['status' => 'fixed']);

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/v1/admin/bug-reports?status=open')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }
}
