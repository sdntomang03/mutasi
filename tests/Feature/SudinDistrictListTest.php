<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SudinDistrictListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_all_districts_with_regency_names_and_guests_are_blocked(): void
    {
        District::query()->updateOrCreate(['code' => '31.75.07'], [
            'name' => 'Cipayung',
            'regency_code' => '31.75',
            'regency_name' => 'Kota Administrasi Jakarta Timur',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->getJson('/api/admin/districts')->assertUnauthorized();
        $this->actingAs($admin)->getJson('/api/admin/districts')
            ->assertOk()
            ->assertJsonFragment(['code' => '31.75.07', 'name' => 'Cipayung', 'regency_name' => 'Kota Administrasi Jakarta Timur']);
    }
}
