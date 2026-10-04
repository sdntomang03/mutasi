<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Subject;
use App\Models\Sudin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubjectMatchingTest extends TestCase
{
    use RefreshDatabase;

    private Sudin $sudinA;

    private Sudin $sudinB;

    private District $districtA;

    private District $districtB;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Role::findOrCreate('guru', 'web');
        Role::findOrCreate('admin', 'web');
        $this->districtA = $this->district('31.71.01', '31.71');
        $this->districtB = $this->district('31.72.01', '31.72');
        $this->sudinA = Sudin::query()->create(['name' => 'Sudin A']);
        $this->sudinA->districts()->attach($this->districtA->code);
        $this->sudinB = Sudin::query()->create(['name' => 'Sudin B']);
        $this->sudinB->districts()->attach($this->districtB->code);
    }

    public function test_subject_teachers_match_only_when_subjects_are_reciprocal(): void
    {
        [$math, $english, $science] = $this->subjects(['Matematika Uji', 'Inggris Uji', 'IPA Uji']);
        $userA = $this->teacher();
        $this->saveProfile($userA, 'a', $this->districtA, $this->districtB, $math, [$english, $science])->assertCreated();
        $userB = $this->teacher();

        $this->saveProfile($userB, 'b', $this->districtB, $this->districtA, $english, [$math])->assertCreated();
        $this->assertSame(['Guru b'], $this->matchNames($userA));

        $this->saveProfile($userB, 'b', $this->districtB, $this->districtA, $english, [$science])->assertOk();
        $this->assertSame([], $this->matchNames($userA));

        $this->saveProfile($userB, 'b', $this->districtB, $this->districtA, $science, [$english])->assertOk();
        $this->assertSame([], $this->matchNames($userA));
    }

    public function test_class_teachers_do_not_use_subjects_and_may_match_a_subject_teacher_only_in_one_direction(): void
    {
        [$math] = $this->subjects(['Matematika Uji']);
        $classA = $this->teacher();
        $classB = $this->teacher();
        $this->saveProfile($classA, 'a', $this->districtA, $this->districtB, null, [], 'guru_kelas', 'guru_kelas')->assertCreated();
        $this->saveProfile($classB, 'b', $this->districtB, $this->districtA, $math, [$math], 'guru_kelas', 'guru_kelas')->assertCreated();

        $this->assertDatabaseHas('teacher_profiles', ['name' => 'Guru b', 'subject_id' => null]);
        $this->assertDatabaseCount('teacher_destination_subjects', 0);
        $this->assertSame(['Guru b'], $this->matchNames($classA));
    }

    public function test_subject_teacher_must_choose_origin_and_destination_subjects(): void
    {
        [$math] = $this->subjects(['Matematika Uji']);
        $user = $this->teacher();

        $this->saveProfile($user, 'a', $this->districtA, $this->districtB, null, [$math])->assertUnprocessable();
        $this->saveProfile($user, 'a', $this->districtA, $this->districtB, $math, [])->assertUnprocessable();
        $this->assertDatabaseCount('teacher_profiles', 0);
    }

    public function test_admin_manages_subjects_and_used_subjects_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($this->teacher())->postJson('/api/admin/subjects', ['name' => 'Baru'])->assertForbidden();

        $id = $this->actingAs($admin)->postJson('/api/admin/subjects', ['name' => 'Prakarya Uji'])->assertCreated()->json('data.id');
        $this->postJson('/api/admin/subjects', ['name' => 'Prakarya Uji'])->assertUnprocessable();
        $this->putJson("/api/admin/subjects/{$id}", ['name' => 'Prakarya Baru'])->assertOk();
        $this->assertDatabaseHas('subjects', ['id' => $id, 'name' => 'Prakarya Baru']);

        $this->saveProfile($this->teacher(), 'a', $this->districtA, $this->districtB, Subject::find($id), [Subject::find($id)])->assertCreated();
        $this->actingAs($admin)->deleteJson("/api/admin/subjects/{$id}")->assertStatus(409);

        $unused = Subject::factory()->create();
        $this->deleteJson("/api/admin/subjects/{$unused->id}")->assertOk();
        $this->assertModelMissing($unused);
        $this->get('/admin/subjects')->assertOk()->assertSee('Mata pelajaran');
    }

    public function test_admin_imports_subjects_from_csv_skipping_duplicates_and_header(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Subject::query()->create(['name' => 'Matematika Uji']);
        $csv = UploadedFile::fake()->createWithContent('mapel.csv', "nama;x\nMatematika uji;1\nSeni Rupa Uji;2\nSeni Rupa Uji;3\n\nCoding Uji;4\n");

        $this->actingAs($admin)->postJson('/api/admin/subjects/import', ['file' => $csv])
            ->assertOk()->assertJson(['created' => 2, 'skipped' => 2]);
        $this->assertDatabaseHas('subjects', ['name' => 'Coding Uji']);
        $this->assertSame(1, Subject::query()->where('name', 'Seni Rupa Uji')->count());

        $this->actingAs($this->teacher())->postJson('/api/admin/subjects/import', ['file' => $csv])->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/subjects/import', [])->assertUnprocessable();
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, Subject>
     */
    private function subjects(array $names): array
    {
        return array_map(fn (string $name) => Subject::query()->create(['name' => $name]), $names);
    }

    private function teacher(): User
    {
        $user = User::factory()->create();
        $user->assignRole('guru');

        return $user;
    }

    private function district(string $code, string $regency): District
    {
        return District::query()->create(['code' => $code, 'name' => 'Kec '.$code, 'regency_code' => $regency, 'regency_name' => 'Kota '.$regency]);
    }

    /**
     * @param  array<int, Subject>  $destinationSubjects
     */
    private function saveProfile(User $user, string $key, District $origin, District $destination, ?Subject $subject, array $destinationSubjects, string $position = 'guru_mapel', string $destinationPosition = 'guru_mapel'): TestResponse
    {
        $sudin = $origin->is($this->districtA) ? $this->sudinA : $this->sudinB;
        $destinationSudin = $destination->is($this->districtA) ? $this->sudinA : $this->sudinB;

        return $this->actingAs($user)->postJson('/api/profile', [
            'name' => 'Guru '.$key,
            'phone' => $key === 'a' ? '081234567801' : '081234567802',
            'employment_type' => 'PNS',
            'position' => $position,
            'level' => 'SMP',
            'subject_id' => $subject?->id,
            'destination_position' => $destinationPosition,
            'destination_levels' => ['SMP'],
            'destination_subject_ids' => array_map(fn (Subject $item) => $item->id, $destinationSubjects),
            'school_name' => 'SMPN Uji',
            'school_address' => 'Jl. Uji 1',
            'sudin_id' => $sudin->id,
            'destination_sudin_id' => $destinationSudin->id,
            'destination_district_codes' => [$destination->code],
            'province_code' => '31',
            'regency_code' => $origin->regency_code,
            'regency_name' => $origin->regency_name,
            'district_code' => $origin->code,
            'district_name' => $origin->name,
            'village_code' => $origin->code.'.1001',
            'village_name' => 'Kelurahan Uji',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function matchNames(User $user): array
    {
        return collect($this->actingAs($user)->getJson('/api/matches')->json('data'))->pluck('name')->all();
    }
}
