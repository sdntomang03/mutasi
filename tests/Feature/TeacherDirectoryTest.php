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
            ->assertSee('R***n K***s')
            ->assertDontSee('Rekan Kelas')
            ->assertDontSee('Rekan Mapel')
            ->assertDontSee('Belum Verifikasi')
            ->assertDontSee('Saya Kelas')
            ->assertDontSee($phone);

        $mapel = $this->teacher('Saya Mapel', 'guru_mapel', $a, $b);
        $this->actingAs($mapel)->get('/daftar-guru')
            ->assertOk()
            ->assertSee('R***n M***l')
            ->assertDontSee('Rekan Mapel')
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

    public function test_api_lists_verified_unmutated_teachers_with_the_same_position_without_contact_details(): void
    {
        Role::findOrCreate('guru', 'web');
        $a = $this->sudin('31.71.01', 'Sudin Alfa', 'SA');
        $b = $this->sudin('31.72.01', 'Sudin Beta', 'SB');
        $me = $this->teacher('Saya Kelas', 'guru_kelas', $a, $b);
        $classTeacher = $this->teacher('Rekan Kelas', 'guru_kelas', $b, $a);
        $mapelTeacher = $this->teacher('Rekan Mapel', 'guru_mapel', $b, $a);
        $mapelMe = $this->teacher('Saya Mapel', 'guru_mapel', $a, $b);
        $mapelTeacherTwo = $this->teacher('Rekan Mapel Dua', 'guru_mapel', $b, $a);
        $unverifiedTeacher = $this->teacher('Belum Verifikasi', 'guru_kelas', $b, $a, false);
        $mutatedTeacher = $this->teacher('Sudah Mutasi', 'guru_kelas', $b, $a);
        $mutatedTeacher->teacherProfile->forceFill(['is_mutated' => true])->save();
        $this->getJson('/api/teachers')->assertUnauthorized();

        $response = $this->actingAs($me)->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonPath('profile.position', 'guru_kelas')
            ->assertJsonPath('profile.position_label', 'Guru kelas')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $classTeacher->teacherProfile->id)
            ->assertJsonPath('data.0.name', 'R***n K***s')
            ->assertJsonPath('data.0.position', 'guru_kelas')
            ->assertJsonPath('data.0.position_label', 'Guru kelas');

        $this->assertStringNotContainsString('phone', $response->getContent());
        $this->assertStringNotContainsString('Saya Kelas', $response->getContent());
        $this->assertStringNotContainsString('Rekan Kelas', $response->getContent());
        $this->assertStringNotContainsString('Rekan Mapel', $response->getContent());
        $this->assertStringNotContainsString('Belum Verifikasi', $response->getContent());
        $this->assertStringNotContainsString('Sudah Mutasi', $response->getContent());

        $mapel = $this->actingAs($mapelMe)->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonPath('profile.position', 'guru_mapel')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $mapelTeacher->teacherProfile->id)
            ->assertJsonPath('data.0.name', 'R***n M***l')
            ->assertJsonPath('data.1.id', $mapelTeacherTwo->teacherProfile->id);

        $this->assertStringNotContainsString('phone', $mapel->getContent());
        $this->assertStringNotContainsString('Rekan Mapel', $mapel->getContent());
    }

    public function test_masked_name_preserves_first_and_last_characters_of_each_name_part(): void
    {
        $profile = new TeacherProfile(['name' => 'Martha Gunj']);

        $this->assertSame('M****a G**j', $profile->maskedName());
    }

    public function test_api_requires_a_teacher_profile_with_a_position(): void
    {
        Role::findOrCreate('guru', 'web');
        $user = User::factory()->create();
        $user->assignRole('guru');

        $this->actingAs($user)->getJson('/api/teachers')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Lengkapi jabatan pada profil terlebih dahulu untuk melihat daftar guru.');

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)->getJson('/api/teachers')->assertForbidden();
    }
}
