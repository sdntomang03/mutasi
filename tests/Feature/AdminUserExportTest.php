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

    public function test_admin_can_download_excel_workbook_for_profiles_with_mutation_destinations(): void
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
            'school_address' => '  Jl. Mawar No. 1  ',
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

        $response->assertDownload('data-user-mutasi.xlsx')
            ->assertStreamed()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($admin)->get(route('admin.users'))
            ->assertSee('Unduh Excel')
            ->assertSee(route('admin.users.export'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);

        $workbookPath = tempnam(sys_get_temp_dir(), 'mutasi-test-');
        $this->assertIsString($workbookPath);

        try {
            $this->assertSame(strlen($content), file_put_contents($workbookPath, $content));

            $archive = new \ZipArchive;
            $this->assertTrue($archive->open($workbookPath) === true);

            try {
                $this->assertNotFalse($archive->getFromName('[Content_Types].xml'));
                $this->assertNotFalse($archive->getFromName('_rels/.rels'));
                $this->assertNotFalse($archive->getFromName('xl/workbook.xml'));
                $this->assertNotFalse($archive->getFromName('xl/_rels/workbook.xml.rels'));
                $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
                $this->assertIsString($worksheet);

                $document = new \DOMDocument;
                $this->assertTrue($document->loadXML($worksheet));
                $xpath = new \DOMXPath($document);
                $xpath->registerNamespace('sheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                $this->assertSame('Nama guru', $xpath->evaluate('string(//sheet:c[@r="A1"]/sheet:is/sheet:t)'));
                $this->assertSame('=1+1', $xpath->evaluate('string(//sheet:c[@r="A2"]/sheet:is/sheet:t)'));
                $this->assertSame('inlineStr', $xpath->evaluate('string(//sheet:c[@r="A2"]/@t)'));
                $this->assertSame('lengkap@example.test', $xpath->evaluate('string(//sheet:c[@r="B2"]/sheet:is/sheet:t)'));
                $this->assertSame('+628123456789', $xpath->evaluate('string(//sheet:c[@r="C2"]/sheet:is/sheet:t)'));
                $this->assertSame('  Jl. Mawar No. 1  ', $xpath->evaluate('string(//sheet:c[@r="F2"]/sheet:is/sheet:t)'));
                $this->assertSame('ASAL', $xpath->evaluate('string(//sheet:c[@r="G2"]/sheet:is/sheet:t)'));
                $this->assertSame('TUJUAN', $xpath->evaluate('string(//sheet:c[@r="N2"]/sheet:is/sheet:t)'));
                $this->assertSame('Kecamatan Tujuan', $xpath->evaluate('string(//sheet:c[@r="O2"]/sheet:is/sheet:t)'));
                $this->assertSame('SMA, SMP', $xpath->evaluate('string(//sheet:c[@r="Q2"]/sheet:is/sheet:t)'));
                $this->assertSame('Belum tukeran', $xpath->evaluate('string(//sheet:c[@r="S2"]/sheet:is/sheet:t)'));
                $this->assertSame('2', $xpath->evaluate('string(count(//sheet:sheetData/sheet:row))'));
                $this->assertFalse($xpath->query('//sheet:c/sheet:f')->length > 0);
                $this->assertStringNotContainsString('Profil belum memilih tujuan', $worksheet);
                $this->assertStringNotContainsString('tanpa-profil@example.test', $worksheet);
            } finally {
                $archive->close();
            }
        } finally {
            unlink($workbookPath);
        }
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
