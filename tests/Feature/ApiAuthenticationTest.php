<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_registration_creates_an_unverified_teacher_and_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Guru Contoh',
            'email' => 'guru@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Guru Contoh')
            ->assertJsonPath('data.email', 'guru@example.com')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'guru@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('guru'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_api_registration_reports_validation_errors(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Guru Contoh',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'not-the-same',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_password_reset_link_can_be_requested_through_api(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
