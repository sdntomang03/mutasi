<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admins_are_redirected_to_admin_user_management_without_a_teacher_profile(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.users', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull($admin->teacherProfile);
        $this->get(route('admin.users', absolute: false))
            ->assertOk()
            ->assertSee('Daftar user');
        $this->get('/')
            ->assertRedirect(route('admin.users', absolute: false));
        $this->get('/dashboard')->assertForbidden();
        $this->get('/teacher-profile')->assertForbidden();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
