<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('guru', 'web');
        Role::findOrCreate('admin', 'web');
    }

    public function test_admin_can_verify_a_user_from_the_user_list(): void
    {
        $admin = User::factory()->unverified()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');
        $user = User::factory()->unverified()->create(['email' => 'guru@example.test']);
        $user->assignRole('guru');

        Event::fake([Verified::class]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Email belum terverifikasi')
            ->assertSee('Verifikasi email user');

        $this->actingAs($admin)->patchJson(route('api.admin.users.verify-email', $user))
            ->assertOk()
            ->assertJsonPath('message', 'Alamat email user berhasil diverifikasi.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
    }

    public function test_non_admin_cannot_verify_another_users_email(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('guru');
        $user = User::factory()->unverified()->create();

        $this->actingAs($teacher)->patchJson(route('api.admin.users.verify-email', $user))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verifying_an_already_verified_user_is_safe(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user = User::factory()->create();

        $this->actingAs($admin)->patchJson(route('api.admin.users.verify-email', $user))
            ->assertOk()
            ->assertJsonPath('message', 'Alamat email user ini sudah terverifikasi.');
    }
}
