<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'arthur',
            'email' => 'arthur@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'date_of_birth' => '2000-01-01',
            'country' => 'FR',
            'gender' => 'male',
            'terms_accepted' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user' => ['id', 'username', 'email'], 'token']]);

        $this->assertDatabaseHas('users', [
            'username' => 'arthur',
            'email' => 'arthur@example.com',
        ]);

        $user = User::query()->where('email', 'arthur@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_minimum_age_of_16(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'username' => 'kid',
            'email' => 'kid@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'terms_accepted' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('date_of_birth');
    }

    public function test_registration_requires_accepting_terms(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'date_of_birth' => '2000-01-01',
            'terms_accepted' => false,
        ])->assertStatus(422)->assertJsonValidationErrors('terms_accepted');
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'arthur']);

        $this->postJson('/api/v1/auth/register', [
            'username' => 'arthur',
            'email' => 'other@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'date_of_birth' => '2000-01-01',
            'terms_accepted' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }
}
