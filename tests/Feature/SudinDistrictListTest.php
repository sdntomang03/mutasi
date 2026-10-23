<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Sudin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_teacher_gets_villages_for_a_district_in_the_sudin(): void
    {
        $district = District::query()->create([
            'code' => '31.75.07',
            'name' => 'Cipayung',
            'regency_code' => '31.75',
            'regency_name' => 'Kota Administrasi Jakarta Timur',
        ]);
        $sudin = Sudin::factory()->create(['name' => 'Jakarta Timur 1']);
        $sudin->districts()->attach($district->code);
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        Http::preventStrayRequests();
        Http::fake([
            'www.emsifa.com/api-wilayah-indonesia/v2/villages/31.75.07.json' => Http::response([
                'data' => [
                    ['id' => '31.75.07.1001', 'name' => 'Duren Sawit', 'postal_code' => '13440'],
                ],
            ]),
        ]);

        $this->getJson("/api/sudins/{$sudin->id}/districts/{$district->code}/villages")
            ->assertUnauthorized();

        $this->actingAs($teacher)
            ->getJson("/api/sudins/{$sudin->id}/districts/{$district->code}/villages")
            ->assertOk()
            ->assertJsonPath('sudin.id', $sudin->id)
            ->assertJsonPath('district.id', $district->code)
            ->assertExactJson([
                'sudin' => ['id' => $sudin->id, 'name' => $sudin->name, 'abbreviation' => $sudin->abbreviation],
                'district' => [
                    'id' => $district->code,
                    'name' => $district->name,
                    'regency_code' => $district->regency_code,
                    'regency_name' => $district->regency_name,
                ],
                'data' => [['id' => '31.75.07.1001', 'name' => 'Duren Sawit']],
            ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://www.emsifa.com/api-wilayah-indonesia/v2/villages/31.75.07.json');
    }

    public function test_village_endpoint_hides_districts_outside_the_sudin(): void
    {
        $district = District::query()->create([
            'code' => '31.75.07',
            'name' => 'Cipayung',
            'regency_code' => '31.75',
            'regency_name' => 'Kota Administrasi Jakarta Timur',
        ]);
        $sudin = Sudin::factory()->create();
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        Http::preventStrayRequests();

        $this->actingAs($teacher)
            ->getJson("/api/sudins/{$sudin->id}/districts/{$district->code}/villages")
            ->assertNotFound();
    }

    public function test_village_endpoint_returns_bad_gateway_when_upstream_fails(): void
    {
        $district = District::query()->create([
            'code' => '31.75.07',
            'name' => 'Cipayung',
            'regency_code' => '31.75',
            'regency_name' => 'Kota Administrasi Jakarta Timur',
        ]);
        $sudin = Sudin::factory()->create();
        $sudin->districts()->attach($district->code);
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        Http::preventStrayRequests();
        Http::fake([
            'www.emsifa.com/api-wilayah-indonesia/v2/villages/31.75.07.json' => Http::response([], 503),
        ]);

        $this->actingAs($teacher)
            ->getJson("/api/sudins/{$sudin->id}/districts/{$district->code}/villages")
            ->assertStatus(502)
            ->assertJsonPath('message', 'Data kelurahan sementara tidak dapat diakses.');
    }

    public function test_village_endpoint_returns_bad_gateway_when_upstream_connection_fails(): void
    {
        $district = District::query()->create([
            'code' => '31.75.07',
            'name' => 'Cipayung',
            'regency_code' => '31.75',
            'regency_name' => 'Kota Administrasi Jakarta Timur',
        ]);
        $sudin = Sudin::factory()->create();
        $sudin->districts()->attach($district->code);
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole(Role::findOrCreate('guru', 'web'));
        Http::preventStrayRequests();
        Http::fake([
            'www.emsifa.com/api-wilayah-indonesia/v2/villages/31.75.07.json' => Http::failedConnection(),
        ]);

        $this->actingAs($teacher)
            ->getJson("/api/sudins/{$sudin->id}/districts/{$district->code}/villages")
            ->assertStatus(502)
            ->assertJsonPath('message', 'Data kelurahan sementara tidak dapat diakses.');
    }
}
