<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_an_admin_account_with_the_requested_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertSame('Administrator', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_reseeding_keeps_existing_admin_password_and_restores_its_role(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@admin.com')->firstOrFail();
        $admin->update(['password' => 'changed-password']);
        $admin->syncRoles([]);

        $this->seed(AdminUserSeeder::class);

        $admin->refresh();

        $this->assertTrue(Hash::check('changed-password', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
    }
}
