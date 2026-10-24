<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileDeletionRequest;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()
                ->with([
                    'teacherProfile.sudin:id,name,abbreviation',
                    'teacherProfile.destinationSudin:id,name,abbreviation',
                    'teacherProfile.destinationDistricts:code,name',
                    'teacherProfile.destinationLevels',
                    'teacherProfile.subject:id,name',
                    'teacherProfile.destinationSubjects:id,name',
                    'teacherProfile.deletionRequests' => fn ($query) => $query->where('status', 'pending'),
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $profiles = TeacherProfile::query()
            ->whereNotNull('destination_sudin_id')
            ->with([
                'user:id,name,email',
                'sudin:id,name,abbreviation',
                'destinationSudin:id,name,abbreviation',
                'destinationDistricts:code,name',
                'destinationLevels:id,teacher_profile_id,level',
                'subject:id,name',
                'destinationSubjects:id,name',
            ]);

        return response()->streamDownload(function () use ($profiles): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka aliran ekspor data user.');
            }

            try {
                if (fwrite($output, "\xEF\xBB\xBF") === false) {
                    throw new RuntimeException('Tidak dapat menulis data ekspor user.');
                }

                $writeRow = static function (array $row) use ($output): void {
                    if (fputcsv($output, $row, ',', '"', '', "\r\n") === false) {
                        throw new RuntimeException('Tidak dapat menulis data ekspor user.');
                    }
                };

                $writeRow([
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
                ]);

                foreach ($profiles->lazyById(200) as $profile) {
                    $originPosition = $profile->position
                        ? TeacherProfile::POSITIONS[$profile->position] ?? $profile->position
                        : '';
                    $destinationPosition = $profile->destination_position
                        ? TeacherProfile::POSITIONS[$profile->destination_position] ?? $profile->destination_position
                        : '';
                    $destinationDistricts = $profile->destinationDistricts->pluck('name')->sort()->values()->join(', ');

                    $writeRow(array_map(
                        fn (string $value): string => $this->safeCsvValue($value),
                        [
                            $profile->name,
                            $profile->user?->email ?? '',
                            $profile->phone,
                            $profile->employment_type,
                            $profile->school_name,
                            $profile->school_address,
                            $profile->sudin?->abbreviation ?: ($profile->sudin?->name ?? ''),
                            $profile->regency_name,
                            $profile->district_name,
                            $profile->village_name,
                            $originPosition,
                            $profile->level ?? '',
                            $profile->subject?->name ?? '',
                            $profile->destinationSudin?->abbreviation ?: ($profile->destinationSudin?->name ?? ''),
                            $destinationDistricts ?: 'Semua kecamatan di Sudin tujuan',
                            $destinationPosition,
                            $profile->destinationLevels->pluck('level')->sort()->values()->join(', '),
                            $profile->destinationSubjects->pluck('name')->sort()->values()->join(', '),
                            $profile->is_mutated ? 'Sudah tukeran' : 'Belum tukeran',
                        ],
                    ));
                }
            } finally {
                fclose($output);
            }
        }, 'data-user-mutasi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }

    public function deletionRequests(): View
    {
        return view('admin.deletion-requests', [
            'deletionRequests' => ProfileDeletionRequest::query()
                ->where('status', 'pending')
                ->with(['profile:id,user_id,name,phone,school_name,is_mutated', 'requester:id,name,email'])
                ->orderBy('created_at')
                ->paginate(20),
        ]);
    }

    public function verifyEmail(User $user): JsonResponse
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Alamat email user ini sudah terverifikasi.',
            ]);
        }

        if (! $user->markEmailAsVerified()) {
            abort(500, 'Alamat email user tidak berhasil diverifikasi.');
        }

        event(new Verified($user));

        return response()->json([
            'message' => 'Alamat email user berhasil diverifikasi.',
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'Gunakan menu Password untuk mengganti password akunmu sendiri.'], 409);
        }

        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Tautan reset password telah dikirim ke email user.']);
        }

        return response()->json(
            ['message' => __($status)],
            $status === Password::RESET_THROTTLED ? 429 : 422,
        );
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'Kamu tidak dapat menghapus akun administrator yang sedang digunakan.',
            ], 409);
        }

        DB::transaction(function () use ($user): void {
            if ($user->hasRole('admin') && User::query()->role('admin')->count() <= 1) {
                abort(409, 'Administrator terakhir tidak dapat dihapus.');
            }

            $profile = $user->teacherProfile()->withTrashed()->first();

            if ($profile) {
                $profile->deletionRequests()->delete();
                $profile->forceDelete();
            }

            ProfileDeletionRequest::query()->where('requested_by', $user->id)->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }

            $user->forceDelete();
        });

        return response()->json([
            'message' => 'Akun user dan seluruh data profil terkait berhasil dihapus permanen.',
        ]);
    }

    public function updateStatus(Request $request, TeacherProfile $teacherProfile): JsonResponse
    {
        $data = $request->validate(['is_mutated' => ['required', 'boolean']]);

        if ($teacherProfile->is_mutated && ! $data['is_mutated']
            && $teacherProfile->deletionRequests()->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'Pengajuan penghapusan masih menunggu keputusan admin.'], 409);
        }

        $teacherProfile->is_mutated = $data['is_mutated'];
        $teacherProfile->save();

        return response()->json([
            'data' => ['is_mutated' => $teacherProfile->is_mutated],
            'message' => 'Status tukeran profil berhasil diperbarui.',
        ]);
    }

    public function reviewDeletion(Request $request, ProfileDeletionRequest $deletionRequest): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        $result = DB::transaction(function () use ($data, $deletionRequest, $request): array {
            $lockedRequest = ProfileDeletionRequest::query()->lockForUpdate()->findOrFail($deletionRequest->id);
            if ($lockedRequest->status !== 'pending') {
                abort(409, 'Pengajuan ini sudah diproses.');
            }

            $profile = $lockedRequest->profile;
            if (! $profile || ! $profile->is_mutated) {
                abort(409, 'Profil tidak lagi tersedia atau belum ditandai tukerannya selesai.');
            }

            $lockedRequest->reviewed_by = $request->user()->id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->status = $data['decision'] === 'approve' ? 'approved' : 'rejected';
            $lockedRequest->save();

            if ($data['decision'] === 'approve') {
                $profile->delete();
                $profile->user()->firstOrFail()->delete();
            }

            return ['decision' => $data['decision']];
        });

        return response()->json([
            'message' => $result['decision'] === 'approve'
                ? 'Pengajuan disetujui. Profil dan akun login dinonaktifkan dengan soft delete.'
                : 'Pengajuan ditolak; profil dan akun tetap aktif.',
        ]);
    }
}
