<?php

namespace Tests\Feature;

use App\Mail\ReciprocalMatchFound;
use App\Models\District;
use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('guru', 'web');
        Role::findOrCreate('admin', 'web');
    }

    public function test_guest_is_required_to_login_before_using_the_application(): void
    {
        $this->get('/')->assertOk()->assertSee('Ruang Tukar Guru');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/login')->assertRedirect('/login');
        $this->getJson('/api/sudins')->assertUnauthorized();
        $this->get('/admin/sudins')->assertRedirect('/login');
        $this->getJson('/api/admin/sudins')->assertUnauthorized();
    }

    public function test_registration_assigns_the_guru_role_and_starts_a_session(): void
    {
        $this->post('/register', [
            'name' => 'Guru Baru',
            'email' => 'guru@example.test',
            'password' => 'Strong-password-123!',
            'password_confirmation' => 'Strong-password-123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => User::query()->where('email', 'guru@example.test')->value('id'),
            'model_type' => User::class,
        ]);
        $this->assertTrue(auth()->user()->hasRole('guru'));
    }

    public function test_admin_role_is_required_for_sudin_management(): void
    {
        $this->actingAs($this->teacherUser('guru@example.test'))
            ->get('/admin/sudins')
            ->assertForbidden();

        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/sudins')->assertOk()->assertSee('Atur cakupan Sudin');
        $this->actingAs($admin)->getJson('/api/admin/sudins')->assertOk();
    }

    public function test_registered_account_can_be_promoted_to_admin_by_email(): void
    {
        $user = User::factory()->create(['email' => 'promote@example.test']);

        $this->artisan('app:assign-admin', ['email' => $user->email])
            ->expectsOutput("Admin role assigned to {$user->email}.")
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_demo_seed_creates_reciprocal_fake_teachers_and_sudins_idempotently(): void
    {
        $this->seed();

        $this->assertDatabaseCount('districts', 44);
        $this->assertDatabaseCount('sudins', 11);
        $this->assertDatabaseCount('teacher_profiles', 26);
        $this->assertDatabaseCount('teacher_destinations', 0);

        $teacher = TeacherProfile::query()->with('user')->firstOrFail();
        $this->assertTrue(Hash::check('password', $teacher->user->password));
        $this->actingAs($teacher->user)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->seed();

        $this->assertDatabaseCount('sudins', 11);
        $this->assertDatabaseCount('teacher_profiles', 26);
        $this->assertDatabaseCount('teacher_destinations', 0);

        $eastJakartaTeacher = User::query()->where('email', 'guru.demo.jaktim2@example.test')->firstOrFail();
        $westJakartaTeacher = User::query()->where('email', 'guru.demo.jakbar2@example.test')->firstOrFail();
        $this->assertSame(
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Timur',
            $eastJakartaTeacher->teacherProfile->sudin->name,
        );
        $this->assertSame(
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Barat',
            $eastJakartaTeacher->teacherProfile->destinationSudin->name,
        );
        $this->actingAs($eastJakartaTeacher)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Guru Demo Jakarta Barat Wilayah II']);
        $this->actingAs($westJakartaTeacher)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Guru Demo Jakarta Timur Wilayah II']);

        $generatedSudin = Sudin::factory()->create();
        $this->assertMatchesRegularExpression('/^Jakarta |^Kepulauan Seribu/', $generatedSudin->name);

        $generatedTeacher = TeacherProfile::factory()->create();
        $this->assertTrue($generatedTeacher->user->hasRole('guru'));
        $this->assertNotNull($generatedTeacher->destination_sudin_id);
    }

    public function test_authenticated_teacher_can_create_and_update_only_their_own_profile(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $destination = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $this->sudinFor($destination, 'Jakarta Utara 1');
        $user = $this->teacherUser('profile@example.test');
        $payload = $this->profilePayload($sudin, $origin, $destination);

        $response = $this->actingAs($user)->postJson('/api/profile', $payload);
        $response->assertCreated()
            ->assertJsonPath('data.name', 'Guru');

        $this->actingAs($user)->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.school_name', 'SDN Contoh')
            ->assertJsonPath('data.destination_districts.0.code', $destination->code);

        $payload['school_name'] = 'SDN Diperbarui';
        $this->actingAs($user)->postJson('/api/profile', $payload)
            ->assertOk()
            ->assertJsonPath('data.school_name', 'SDN Diperbarui');

        $this->assertDatabaseCount('teacher_profiles', 1);
        $this->assertDatabaseHas('teacher_profiles', [
            'user_id' => $user->id,
            'school_name' => 'SDN Diperbarui',
        ]);
        $payload['destination_district_codes'] = [];
        $this->actingAs($user)->postJson('/api/profile', $payload)->assertOk();
        $this->assertDatabaseMissing('teacher_profile_destination_district', [
            'teacher_profile_id' => $user->teacherProfile->id,
        ]);
    }

    public function test_new_reciprocal_match_queues_one_private_email_for_each_teacher(): void
    {
        Mail::fake();

        $originA = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $originB = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinA = $this->sudinFor($originA, 'Jakarta Pusat 1');
        $sudinB = $this->sudinFor($originB, 'Jakarta Utara 1');
        $userA = $this->teacherUser('guru-a@example.test');

        $this->createProfile($userA, $sudinA, $originA, $originB, 'Guru A', '081234567801');
        auth()->logout();
        $this->post('/register', [
            'name' => 'Guru B',
            'email' => 'guru-b@example.test',
            'password' => 'Strong-password-123!',
            'password_confirmation' => 'Strong-password-123!',
        ])->assertRedirect('/dashboard');
        $userB = User::query()->where('email', 'guru-b@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($userB);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $userB->id, 'hash' => sha1($userB->email)],
        );
        $this->actingAs($userB)->get($verificationUrl)
            ->assertRedirect(route('dashboard').'?verified=1');

        $this->actingAs($userB)->postJson('/api/profile', $this->profilePayload(
            $sudinB,
            $originB,
            $originA,
            'Guru B',
            '081234567802',
        ))->assertCreated();

        Mail::assertQueued(ReciprocalMatchFound::class, 2);
        Mail::assertQueued(ReciprocalMatchFound::class, fn (ReciprocalMatchFound $mail) => $mail->teacherName === 'Guru A'
            && $mail->matchedTeacherName === 'Guru B'
            && $mail->hasTo('guru-a@example.test'));
        Mail::assertQueued(ReciprocalMatchFound::class, fn (ReciprocalMatchFound $mail) => $mail->teacherName === 'Guru B'
            && $mail->matchedTeacherName === 'Guru A'
            && $mail->hasTo('guru-b@example.test'));

        $payload = $this->profilePayload($sudinB, $originB, $originA, 'Guru B', '081234567802');
        $this->actingAs($userB)->postJson('/api/profile', $payload)->assertOk();

        Mail::assertQueued(ReciprocalMatchFound::class, 2);
        $this->assertDatabaseCount('notified_match_pairs', 1);
    }

    public function test_dashboard_lists_matches_and_teacher_profile_has_a_separate_page(): void
    {
        $originA = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $originB = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinA = $this->sudinFor($originA, 'Jakarta Pusat 1');
        $sudinB = $this->sudinFor($originB, 'Jakarta Utara 1');
        $userA = $this->teacherUser('guru-a@example.test');
        $userB = $this->teacherUser('guru-b@example.test');
        $this->createProfile($userA, $sudinA, $originA, $originB, 'Guru A', '081234567801');
        $this->createProfile($userB, $sudinB, $originB, $originA, 'Guru B', '081234567802');

        $this->actingAs($userA)->get('/dashboard')
            ->assertOk()
            ->assertSee('Calon tukeran')
            ->assertSee('Guru B')
            ->assertSee('+6281234567802');
        $this->actingAs($userA)->get('/teacher-profile')
            ->assertOk()
            ->assertSee('Profil mutasi')
            ->assertSee('profile-form')
            ->assertSee('multiple');
    }

    public function test_teacher_can_mark_mutated_and_request_admin_approved_profile_deletion(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $destination = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $originSudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $destinationSudin = $this->sudinFor($destination, 'Jakarta Utara 1');
        $user = $this->teacherUser('guru@example.test');
        $this->createProfile($user, $originSudin, $origin, $destination, 'Guru A', '081234567801');
        $candidate = $this->teacherUser('candidate@example.test');
        $this->createProfile($candidate, $destinationSudin, $destination, $origin, 'Guru B', '081234567802');

        $this->actingAs($user)->postJson('/api/teacher-profile/deletion-request')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Profil hanya dapat diajukan untuk dihapus setelah ditandai sudah mutasi.');
        $this->actingAs($user)->patchJson('/api/teacher-profile/status', ['is_mutated' => true])
            ->assertOk()
            ->assertJsonPath('data.is_mutated', true);
        $this->actingAs($user)->postJson('/api/teacher-profile/deletion-request')
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseCount('profile_deletion_requests', 1);
        $this->actingAs($user)->patchJson('/api/teacher-profile/status', ['is_mutated' => false])
            ->assertStatus(409);

        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('guru@example.test')
            ->assertDontSee('Setujui & hapus profil');
        $this->actingAs($admin)->get('/admin/deletion-requests')
            ->assertOk()
            ->assertSee('Pengajuan penghapusan')
            ->assertSee('guru@example.test');

        $requestId = DB::table('profile_deletion_requests')->value('id');
        $this->actingAs($admin)->postJson("/api/admin/profile-deletion-requests/{$requestId}/review", ['decision' => 'reject'])
            ->assertOk();
        $this->assertNotNull($user->fresh()->teacherProfile);
        $this->assertDatabaseHas('profile_deletion_requests', ['id' => $requestId, 'status' => 'rejected']);

        $this->actingAs($user)->postJson('/api/teacher-profile/deletion-request')->assertCreated();
        $newRequestId = DB::table('profile_deletion_requests')->where('status', 'pending')->value('id');
        $this->actingAs($admin)->postJson("/api/admin/profile-deletion-requests/{$newRequestId}/review", ['decision' => 'approve'])
            ->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSoftDeleted('teacher_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseHas('profile_deletion_requests', [
            'id' => $newRequestId,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertDontSee('guru@example.test');
        $this->actingAs($candidate)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Guru']);
        $this->actingAs($admin)->post('/logout')->assertRedirect('/');
        $this->post('/login', [
            'email' => 'guru@example.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'guru@example.test']);
    }

    public function test_mutated_profiles_are_not_returned_as_reciprocal_matches(): void
    {
        $originA = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $originB = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinA = $this->sudinFor($originA, 'Jakarta Pusat 1');
        $sudinB = $this->sudinFor($originB, 'Jakarta Utara 1');
        $userA = $this->teacherUser('guru-a@example.test');
        $userB = $this->teacherUser('guru-b@example.test');
        $this->createProfile($userA, $sudinA, $originA, $originB, 'Guru A', '081234567801');
        $this->createProfile($userB, $sudinB, $originB, $originA, 'Guru B', '081234567802');
        $userB->teacherProfile->update(['is_mutated' => true]);

        $this->actingAs($userA)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_profiles_with_pending_deletion_requests_are_excluded_on_both_sides_of_matching(): void
    {
        $originA = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $originB = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinA = $this->sudinFor($originA, 'Jakarta Pusat 1');
        $sudinB = $this->sudinFor($originB, 'Jakarta Utara 1');
        $userA = $this->teacherUser('guru-a@example.test');
        $userB = $this->teacherUser('guru-b@example.test');
        $this->createProfile($userA, $sudinA, $originA, $originB, 'Guru A', '081234567801');
        $this->createProfile($userB, $sudinB, $originB, $originA, 'Guru B', '081234567802');

        $this->actingAs($userA)->getJson('/api/matches')->assertJsonCount(1, 'data');

        $userB->teacherProfile->update(['is_mutated' => true]);
        $this->actingAs($userB)->postJson('/api/teacher-profile/deletion-request')->assertCreated();
        $userB->teacherProfile->update(['is_mutated' => false]);

        $this->actingAs($userA)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->actingAs($userB)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Mail::fake();
        $this->actingAs($userA)->postJson('/api/profile', $this->profilePayload(
            $sudinA,
            $originA,
            $originB,
            'Guru A',
            '081234567801',
        ))->assertOk();
        Mail::assertNothingQueued();
    }

    public function test_admin_can_update_any_teacher_mutation_status_but_regular_users_cannot(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $destination = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $originSudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $this->sudinFor($destination, 'Jakarta Utara 1');
        $owner = $this->teacherUser('guru@example.test');
        $this->createProfile($owner, $originSudin, $origin, $destination, 'Guru', '081234567801');
        $otherTeacher = $this->teacherUser('other@example.test');

        $this->actingAs($otherTeacher)->get('/admin/users')->assertForbidden();
        $this->actingAs($otherTeacher)->get('/admin/deletion-requests')->assertForbidden();
        $this->actingAs($otherTeacher)->patchJson('/api/admin/teachers/'.$owner->teacherProfile->id.'/status', ['is_mutated' => true])
            ->assertForbidden();

        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->patchJson('/api/admin/teachers/'.$owner->teacherProfile->id.'/status', ['is_mutated' => true])
            ->assertOk()
            ->assertJsonPath('data.is_mutated', true);
        $this->assertTrue($owner->fresh()->teacherProfile->is_mutated);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('admin@example.test')
            ->assertSee('other@example.test')
            ->assertSee('Profil guru belum dibuat');
    }

    public function test_admin_user_pagination_uses_compact_text_arrows_instead_of_large_icons(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');
        User::factory()->count(21)->create();

        $response = $this->actingAs($admin)->get('/admin/users')->assertOk();
        $response->assertSee('admin-pagination-arrow', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('<svg', false);
    }

    public function test_admin_can_permanently_delete_a_user_and_their_profile_data(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $destination = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $originSudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $this->sudinFor($destination, 'Jakarta Utara 1');
        $user = $this->teacherUser('permanent-delete@example.test');
        $this->createProfile($user, $originSudin, $origin, $destination, 'Guru Hapus', '081234567801');
        $profile = $user->teacherProfile()->firstOrFail();
        $profile->update(['is_mutated' => true]);
        $this->actingAs($user)->postJson('/api/teacher-profile/deletion-request')->assertCreated();

        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->deleteJson(route('api.admin.users.destroy', $user))
            ->assertOk()
            ->assertJsonPath('message', 'Akun user dan seluruh data profil terkait berhasil dihapus permanen.');

        $this->assertModelMissing($user);
        $this->assertModelMissing($profile);
        $this->assertDatabaseMissing('profile_deletion_requests', ['requester_email' => 'permanent-delete@example.test']);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $user->id, 'model_type' => User::class]);
    }

    public function test_admin_cannot_permanently_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->deleteJson(route('api.admin.users.destroy', $admin))
            ->assertConflict()
            ->assertJsonPath('message', 'Kamu tidak dapat menghapus akun administrator yang sedang digunakan.');

        $this->assertModelExists($admin);
    }

    public function test_non_admin_cannot_permanently_delete_a_user(): void
    {
        $teacher = $this->teacherUser('teacher@example.test');
        $user = User::factory()->create(['email' => 'target@example.test']);

        $this->actingAs($teacher)->deleteJson(route('api.admin.users.destroy', $user))
            ->assertForbidden();

        $this->assertModelExists($user);
    }

    public function test_admin_pages_link_back_to_admin_pages_instead_of_teacher_dashboard(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/sudins')
            ->assertOk()
            ->assertSee('href="'.route('admin.users').'"', false)
            ->assertDontSee('href="'.route('dashboard').'"', false);
        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('href="'.route('admin.sudins').'"', false)
            ->assertDontSee('href="'.route('dashboard').'"', false);
    }

    public function test_teacher_profiles_only_match_when_sudin_destinations_are_reciprocal(): void
    {
        $originA = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $originB = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinA = $this->sudinFor($originA, 'Jakarta Pusat 1');
        $sudinB = $this->sudinFor($originB, 'Jakarta Utara 1');
        $otherDistrictInSudinB = $this->district('31.72.02', 'Kecamatan B2', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudinB->districts()->attach($otherDistrictInSudinB->code);
        $userA = $this->teacherUser('guru-a@example.test');
        $userB = $this->teacherUser('guru-b@example.test');

        $userAPayload = $this->profilePayload($sudinA, $originA, $originB, 'Guru A', '081234567801');
        $userAPayload['destination_district_codes'][] = $otherDistrictInSudinB->code;
        $this->actingAs($userA)->postJson('/api/profile', $userAPayload)->assertCreated();
        $this->createProfile($userB, $sudinB, $originB, $originA, 'Guru B', '081234567802');
        $allDistrictsUser = $this->teacherUser('guru-all-districts@example.test');
        $allDistrictsPayload = $this->profilePayload($sudinB, $originB, $originA, 'Guru B Seluruh', '081234567803');
        $allDistrictsPayload['destination_district_codes'] = [];
        $this->actingAs($allDistrictsUser)->postJson('/api/profile', $allDistrictsPayload)->assertCreated();
        $userD = $this->teacherUser('guru-d@example.test');
        $this->createProfile($userD, $sudinB, $otherDistrictInSudinB, $originA, 'Guru D', '081234567804');
        $otherDistrictInSudinA = $this->district('31.71.02', 'Kecamatan A2', '31.71', 'Kota Administrasi Jakarta Pusat');
        $sudinA->districts()->attach($otherDistrictInSudinA->code);
        $userE = $this->teacherUser('guru-e@example.test');
        $restrictedCandidatePayload = $this->profilePayload($sudinB, $originB, $otherDistrictInSudinA, 'Guru E', '081234567805');
        $this->actingAs($userE)->postJson('/api/profile', $restrictedCandidatePayload)->assertCreated();

        $this->actingAs($userA)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Guru B')
            ->assertJsonPath('data.0.phone', '+6281234567802');

        $this->actingAs($userA)->getJson('/api/sudins/'.$sudinB->id.'/districts')
            ->assertOk()
            ->assertJsonFragment(['id' => $originB->code])
            ->assertJsonFragment(['id' => $otherDistrictInSudinB->code]);

        $this->actingAs($userA)->getJson('/api/matches?candidate_origin_district_code='.$originB->code)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Guru B');

        $this->actingAs($userA)->getJson('/api/matches?candidate_origin_district_code='.$otherDistrictInSudinB->code)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Guru D');
        $this->actingAs($userA)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Guru E']);
        $this->actingAs($userA)->getJson('/api/matches?candidate_origin_district_code='.$originA->code)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Filter kecamatan harus berada dalam Sudin tujuanmu.');

        $userC = $this->teacherUser('guru-c@example.test');
        $this->actingAs($userC)->getJson('/api/matches?profile_id='.$userA->teacherProfile->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Simpan profil guru terlebih dahulu untuk mencari tukeran.');
    }

    public function test_destination_district_options_come_from_the_selected_sudin_database_coverage(): void
    {
        $assignedDistrict = $this->district('31.73.01', 'Cengkareng', '31.73', 'Kota Administrasi Jakarta Barat');
        $unassignedDistrict = $this->district('31.73.02', 'Kebon Jeruk Lain', '31.73', 'Kota Administrasi Jakarta Barat');
        $sudin = $this->sudinFor($assignedDistrict, 'Jakarta Barat 1');
        $teacher = $this->teacherUser('district-options@example.test');

        $this->actingAs($teacher)->getJson('/api/sudins/'.$sudin->id.'/districts')
            ->assertOk()
            ->assertJsonPath('sudin.id', $sudin->id)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedDistrict->code)
            ->assertJsonPath('data.0.name', 'Cengkareng')
            ->assertJsonMissing(['id' => $unassignedDistrict->code]);
    }

    public function test_unverified_users_are_excluded_from_reciprocal_matches(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $destination = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $originSudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $destinationSudin = $this->sudinFor($destination, 'Jakarta Utara 1');
        $verifiedTeacher = $this->teacherUser('verified@example.test');
        $unverifiedTeacher = User::factory()->unverified()->create(['email' => 'unverified@example.test']);
        $unverifiedTeacher->assignRole('guru');

        $this->createProfile($verifiedTeacher, $originSudin, $origin, $destination, 'Guru Terverifikasi', '081234567801');
        TeacherProfile::factory()->for($unverifiedTeacher)->create([
            'sudin_id' => $destinationSudin->id,
            'destination_sudin_id' => $originSudin->id,
            'province_code' => '31',
            'regency_code' => $destination->regency_code,
            'regency_name' => $destination->regency_name,
            'district_code' => $destination->code,
            'district_name' => $destination->name,
            'village_code' => $destination->code.'.1001',
            'village_name' => 'Kelurahan Tujuan',
        ]);

        $this->actingAs($verifiedTeacher)->getJson('/api/matches')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_teacher_cannot_save_an_unmapped_destination(): void
    {
        $origin = $this->district('31.71.01', 'Kecamatan A', '31.71', 'Kota Administrasi Jakarta Pusat');
        $unmapped = $this->district('31.72.01', 'Kecamatan B', '31.72', 'Kota Administrasi Jakarta Utara');
        $sudin = $this->sudinFor($origin, 'Jakarta Pusat 1');
        $payload = $this->profilePayload($sudin, $origin, $unmapped);
        $payload['destination_sudin_id'] = $sudin->id;

        $this->actingAs($this->teacherUser('guru@example.test'))
            ->postJson('/api/profile', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Semua kecamatan tujuan harus berada dalam cakupan Sudin tujuan yang dipilih.');
    }

    public function test_admin_can_create_a_sudin_and_assign_its_districts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson('/api/admin/sudins', [
            'name' => 'Jakarta Barat 2',
            'districts' => [[
                'code' => '31.73.01',
                'name' => 'Kecamatan Contoh',
                'regency_code' => '31.73',
                'regency_name' => 'Kota Administrasi Jakarta Barat',
            ]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Jakarta Barat 2')
            ->assertJsonPath('data.districts.0.code', '31.73.01');

        $this->assertDatabaseHas('sudin_district', ['district_code' => '31.73.01']);
    }

    public function test_a_district_cannot_be_assigned_to_multiple_sudins(): void
    {
        $district = $this->district('31.73.01', 'Kecamatan Contoh', '31.73', 'Kota Administrasi Jakarta Barat');
        $this->sudinFor($district, 'Jakarta Barat 1');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson('/api/admin/sudins', [
            'name' => 'Jakarta Barat 2',
            'districts' => [[
                'code' => $district->code,
                'name' => $district->name,
                'regency_code' => $district->regency_code,
                'regency_name' => $district->regency_name,
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Satu kecamatan hanya dapat masuk ke satu wilayah Sudin.');
    }

    private function teacherUser(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('guru');

        return $user;
    }

    private function district(string $code, string $name, string $regencyCode, string $regencyName): District
    {
        return District::query()->create([
            'code' => $code,
            'name' => $name,
            'regency_code' => $regencyCode,
            'regency_name' => $regencyName,
        ]);
    }

    private function sudinFor(District $district, string $name): Sudin
    {
        $sudin = Sudin::query()->create(['name' => $name]);
        $sudin->districts()->attach($district->code);

        return $sudin;
    }

    private function createProfile(
        User $user,
        Sudin $sudin,
        District $origin,
        District $destination,
        string $name,
        string $phone,
    ): array {
        $response = $this->actingAs($user)->postJson(
            '/api/profile',
            $this->profilePayload($sudin, $origin, $destination, $name, $phone),
        );
        $response->assertCreated();

        return $response->json('data');
    }

    private function profilePayload(
        Sudin $originSudin,
        District $origin,
        District $destination,
        string $name = 'Guru',
        string $phone = '081234567899',
    ): array {
        return [
            'name' => $name,
            'phone' => $phone,
            'employment_type' => 'PNS',
            'school_name' => 'SDN Contoh',
            'school_address' => 'Jl. Contoh No. 1',
            'sudin_id' => $originSudin->id,
            'destination_sudin_id' => Sudin::query()->whereHas('districts', fn ($query) => $query->where('districts.code', $destination->code))->value('id'),
            'destination_district_codes' => [$destination->code],
            'province_code' => '31',
            'regency_code' => $origin->regency_code,
            'regency_name' => $origin->regency_name,
            'district_code' => $origin->code,
            'district_name' => $origin->name,
            'village_code' => $origin->code.'.1001',
            'village_name' => 'Kelurahan Asal',
        ];
    }
}
