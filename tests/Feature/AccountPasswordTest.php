<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $user = User::factory()->create(['password' => Hash::make('lama-12345')]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    public function test_user_can_change_own_password_with_the_current_one(): void
    {
        $guru = $this->user('guru');

        $this->actingAs($guru)->get('/akun/password')->assertOk()->assertSee('Ganti password');

        $this->actingAs($guru)->from('/akun/password')->put('/password', [
            'current_password' => 'salah',
            'password' => 'baru-12345',
            'password_confirmation' => 'baru-12345',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($guru)->put('/password', [
            'current_password' => 'lama-12345',
            'password' => 'baru-12345',
            'password_confirmation' => 'baru-12345',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('baru-12345', $guru->fresh()->password));
    }

    public function test_only_admin_can_reset_another_users_password_to_password(): void
    {
        $admin = $this->user('admin');
        $guru = $this->user('guru');
        $other = $this->user('guru');

        $this->actingAs($other)->patchJson(route('api.admin.users.reset-password', $guru))->assertForbidden();
        $this->assertTrue(Hash::check('lama-12345', $guru->fresh()->password));

        $this->actingAs($admin)->patchJson(route('api.admin.users.reset-password', $guru))->assertOk();
        $this->assertTrue(Hash::check('password', $guru->fresh()->password));

        $this->actingAs($admin)->patchJson(route('api.admin.users.reset-password', $admin))->assertStatus(409);
        $this->assertTrue(Hash::check('lama-12345', $admin->fresh()->password));
    }
}
