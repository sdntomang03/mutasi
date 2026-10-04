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
use Illuminate\View\View;

class TeacherManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()
                ->with([
                    'teacherProfile.sudin:id,name',
                    'teacherProfile.destinationSudin:id,name',
                    'teacherProfile.deletionRequests' => fn ($query) => $query->where('status', 'pending'),
                ])
                ->latest()
                ->paginate(20),
        ]);
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
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

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
            'message' => 'Status mutasi profil berhasil diperbarui.',
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
                abort(409, 'Profil tidak lagi tersedia atau belum ditandai sudah mutasi.');
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
