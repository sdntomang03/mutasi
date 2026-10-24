<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Subject;
use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_excel_compatible_csv_for_profiles_with_mutation_destinations(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $originDistrict = District::query()->create([
            'code' => '31.71.01',
            'name' => 'Kecamatan Asal',
            'regency_code' => '31.71',
            'regency_name' => 'Kota Asal',
        ]);
        $destinationDistrict = District::query()->create([
            'code' => '31.72.01',
            'name' => 'Kecamatan Tujuan',
            'regency_code' => '31.72',
            'regency_name' => 'Kota Tujuan',
        ]);
        $originSudin = Sudin::factory()->create(['name' => 'Sudin Asal', 'abbreviation' => 'ASAL']);
        $destinationSudin = Sudin::factory()->create(['name' => 'Sudin Tujuan', 'abbreviation' => 'TUJUAN']);
        $originSudin->districts()->attach($originDistrict);
        $destinationSudin->districts()->attach($destinationDistrict);
        $subject = Subject::query()->create(['name' => 'Mapel Uji Ekspor']);

        $user = User::factory()->create(['email' => 'lengkap@example.test']);
        $profile = TeacherProfile::factory()->create([
            'user_id' => $user->id,
            'name' => '=1+1',
            'phone' => '+628123456789',
            'employment_type' => 'PNS',
            'school_name' => 'SDN Contoh',
            'school_address' => 'Jl. Mawar No. 1',
            'sudin_id' => $originSudin->id,
            'destination_sudin_id' => $destinationSudin->id,
            'position' => 'guru_mapel',
            'level' => 'SD',
            'subject_id' => $subject->id,
            'destination_position' => 'guru_mapel',
            'regency_name' => 'Kota Asal',
            'district_name' => 'Kecamatan Asal',
            'village_name' => 'Kelurahan Asal',
        ]);
        $profile->destinationDistricts()->sync([$destinationDistrict->code]);
        $profile->syncDestinationLevels(['SMP', 'SMA']);
        $profile->destinationSubjects()->sync([$subject->id]);

        $incompleteProfile = TeacherProfile::factory()->create(['name' => 'Profil belum memilih tujuan']);
        $incompleteProfile->update(['destination_sudin_id' => null]);
        User::factory()->create(['email' => 'tanpa-profil@example.test']);

        $response = $this->actingAs($admin)->get(route('admin.users.export'));

        $response->assertDownload('data-user-mutasi.csv')
            ->assertStreamed();
        $this->actingAs($admin)->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Unduh Excel (CSV)')
            ->assertSee(route('admin.users.export'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $rows = array_map(
            fn (string $row): array => str_getcsv($row, ',', '"', ''),
            array_filter(explode("\r\n", substr($content, 3)), fn (string $row): bool => $row !== ''),
        );

        $this->assertSame([
            'Nama guru',
            'Email',
            'No. HP',
            'Status kepegawaian',
            'Sekolah asal',
            'Alamat sekolah asal',
            'Sudin asal',
            'Kabupaten/Kota asal',
            'Kecamatan asal',
            'Kelurahan asal',
            'Jabatan asal',
            'Jenjang asal',
            'Mata pelajaran asal',
            'Sudin tujuan',
            'Kecamatan tujuan',
            'Jabatan tujuan',
            'Jenjang tujuan',
            'Mata pelajaran tujuan',
            'Status tukeran',
        ], $rows[0]);
        $this->assertSame([
            "'=1+1",
            'lengkap@example.test',
            "'+628123456789",
            'PNS',
            'SDN Contoh',
            'Jl. Mawar No. 1',
            'ASAL',
            'Kota Asal',
            'Kecamatan Asal',
            'Kelurahan Asal',
            'Guru mapel',
            'SD',
            'Mapel Uji Ekspor',
            'TUJUAN',
            'Kecamatan Tujuan',
            'Guru mapel',
            'SMA, SMP',
            'Mapel Uji Ekspor',
            'Belum tukeran',
        ], $rows[1]);
        $this->assertCount(2, $rows);
        $this->assertStringNotContainsString('Profil belum memilih tujuan', $content);
        $this->assertStringNotContainsString('tanpa-profil@example.test', $content);
    }

    public function test_user_export_requires_admin_role(): void
    {
        $this->get(route('admin.users.export'))->assertRedirect('/login');

        Role::findOrCreate('guru', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('guru');

        $this->actingAs($teacher)->get(route('admin.users.export'))->assertForbidden();
    }
}
