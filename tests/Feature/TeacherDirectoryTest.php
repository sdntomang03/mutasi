<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function sudin(string $code, string $name, string $abbreviation): Sudin
    {
        $district = District::query()->updateOrCreate(['code' => $code], [
            'name' => 'Kec '.$code,
            'regency_code' => substr($code, 0, 5),
            'regency_name' => 'Kota Uji',
        ]);
        $sudin = Sudin::query()->create(['name' => $name, 'abbreviation' => $abbreviation]);
        $sudin->districts()->attach($district->code);

        return $sudin;
    }

    private function teacher(string $name, string $position, Sudin $origin, Sudin $destination, bool $verified = true): User
    {
        $profile = TeacherProfile::factory()->create([
            'name' => $name,
            'position' => $position,
            'sudin_id' => $origin->id,
            'destination_sudin_id' => $destination->id,
            'district_code' => $origin->districts->first()->code,
            'phone' => '+62811'.random_int(1000000, 9999999),
        ]);
        $profile->user->forceFill(['email_verified_at' => $verified ? now() : null])->save();

        return $profile->user;
    }

    public function test_directory_lists_only_same_position_teachers_without_phone_numbers(): void
    {
        Role::findOrCreate('guru', 'web');
        $a = $this->sudin('31.71.01', 'Sudin Alfa', 'SA');
        $b = $this->sudin('31.72.01', 'Sudin Beta', 'SB');
        $me = $this->teacher('Saya Kelas', 'guru_kelas', $a, $b);
        $this->teacher('Rekan Kelas', 'guru_kelas', $b, $a);
        $this->teacher('Rekan Mapel', 'guru_mapel', $b, $a);
        $this->teacher('Belum Verifikasi', 'guru_kelas', $b, $a, false);
        $phone = $me->teacherProfile->phone;

        $this->actingAs($me)->get('/daftar-guru')
            ->assertOk()
            ->assertSee('Rekan Kelas')
            ->assertDontSee('Rekan Mapel')
            ->assertDontSee('Belum Verifikasi')
            ->assertDontSee('Saya Kelas')
            ->assertDontSee($phone);

        $mapel = $this->teacher('Saya Mapel', 'guru_mapel', $a, $b);
        $this->actingAs($mapel)->get('/daftar-guru')
            ->assertOk()
            ->assertSee('Rekan Mapel')
            ->assertDontSee('Rekan Kelas');
    }

    public function test_directory_requires_teacher_role_and_a_profile_with_position(): void
    {
        $this->get('/daftar-guru')->assertRedirect('/login');

        Role::findOrCreate('guru', 'web');
        $user = User::factory()->create();
        $user->assignRole('guru');
        $this->actingAs($user)->get('/daftar-guru')->assertOk()->assertSee('Lengkapi');

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin)->get('/daftar-guru')->assertForbidden();
    }
}
