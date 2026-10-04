<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileDeletionRequest;
use App\Models\TeacherProfile;
use App\Models\User;
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
