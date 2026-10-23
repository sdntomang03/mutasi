<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_teacher_api(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_teacher_can_exchange_credentials_for_a_scoped_bearer_token(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));

        $response = $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'password',
            'device_name' => 'phone',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.abilities', ['guru'])
            ->assertJsonStructure(['data' => ['expires_at']]);

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $teacher->id,
            'name' => 'phone',
        ]);

        $this->withToken($token)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->withToken($token)->getJson('/api/sudins')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_login_does_not_require_a_device_name(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));

        $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $teacher->id,
            'name' => 'api-client',
        ]);
    }

    public function test_teacher_cannot_access_admin_api(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        $token = $teacher->createToken('phone', ['guru'])->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/sudins')
            ->assertForbidden();
    }

    public function test_unverified_teacher_cannot_access_teacher_api(): void
    {
        $teacher = User::factory()->unverified()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        $token = $teacher->createToken('phone', ['guru'])->plainTextToken;

        $this->withToken($token)->getJson('/api/profile')
            ->assertForbidden();
    }

    public function test_login_rejects_invalid_credentials_without_creating_a_token(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));

        $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Kredensial yang diberikan tidak valid.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_only_the_current_bearer_token(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        $token = $teacher->createToken('phone', ['guru'])->plainTextToken;
        $otherToken = $teacher->createToken('tablet', ['guru'])->plainTextToken;

        $this->withToken($token)->deleteJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Token berhasil dicabut.');

        $this->withToken($token)->getJson('/api/profile')->assertUnauthorized();
        $this->withToken($otherToken)->getJson('/api/profile')->assertOk();
    }
}
