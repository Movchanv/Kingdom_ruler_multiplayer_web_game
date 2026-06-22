<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_verification_link_marks_the_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    public function test_an_unverified_user_cannot_reach_game_routes(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->postJson('/api/v1/realtime/announce', ['message' => 'Hello'])
            ->assertStatus(403);
    }

    public function test_a_verified_user_can_reach_game_routes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/realtime/announce', ['message' => 'Hello'])
            ->assertOk();
    }
}
