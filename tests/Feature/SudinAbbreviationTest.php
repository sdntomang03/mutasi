<?php

namespace Tests\Feature;

use App\Models\Sudin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SudinAbbreviationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        return $admin;
    }

    private function payload(string $name, ?string $abbreviation, string $code = '31.73.01'): array
    {
        return [
            'name' => $name,
            'abbreviation' => $abbreviation,
            'districts' => [[
                'code' => $code,
                'name' => 'Cengkareng',
                'regency_code' => substr($code, 0, 5),
                'regency_name' => 'Kota Administrasi Jakarta Barat',
            ]],
        ];
    }

    public function test_admin_can_create_and_update_sudin_abbreviation(): void
    {
        $admin = $this->admin();

        $id = $this->actingAs($admin)
            ->postJson('/api/admin/sudins', $this->payload('Sudin Uji', 'JB 2'))
            ->assertCreated()
            ->assertJsonPath('data.abbreviation', 'JB 2')
            ->json('data.id');

        $this->actingAs($admin)
            ->putJson("/api/admin/sudins/{$id}", $this->payload('Sudin Uji', 'JB 9'))
            ->assertOk();

        $this->assertDatabaseHas('sudins', ['id' => $id, 'abbreviation' => 'JB 9']);
        $this->actingAs($admin)->getJson('/api/admin/sudins')->assertJsonFragment(['abbreviation' => 'JB 9']);
    }

    public function test_abbreviation_is_optional_but_must_be_unique(): void
    {
        $admin = $this->admin();
        Sudin::factory()->create(['name' => 'Sudin A', 'abbreviation' => 'JT 1']);

        $this->actingAs($admin)
            ->postJson('/api/admin/sudins', $this->payload('Sudin B', 'JT 1', '31.73.02'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('abbreviation');

        $this->actingAs($admin)
            ->postJson('/api/admin/sudins', $this->payload('Sudin C', null, '31.73.03'))
            ->assertCreated();
    }
}
