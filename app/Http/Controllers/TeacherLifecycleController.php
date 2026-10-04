<?php

namespace App\Http\Controllers;

use App\Models\ProfileDeletionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherLifecycleController extends Controller
{
    public function updateStatus(Request $request): JsonResponse
    {
        $data = $request->validate(['is_mutated' => ['required', 'boolean']]);
        $profile = $request->user()->teacherProfile()->firstOrFail();

        if ($profile->is_mutated && ! $data['is_mutated']
            && $profile->deletionRequests()->where('status', 'pending')->exists()) {
            return response()->json([
                'message' => 'Admin sedang memproses pengajuan penghapusan. Hubungi admin untuk membatalkan pengajuan sebelum mengubah status mutasi.',
            ], 409);
        }

        $profile->is_mutated = $data['is_mutated'];
        $profile->save();

        return response()->json([
            'data' => ['is_mutated' => $profile->is_mutated],
            'message' => $profile->is_mutated
                ? 'Status diperbarui menjadi sudah mutasi.'
                : 'Status diperbarui menjadi belum mutasi.',
        ]);
    }

    public function requestDeletion(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->teacherProfile()->firstOrFail();

        if (! $profile->is_mutated) {
            return response()->json(['message' => 'Profil hanya dapat diajukan untuk dihapus setelah ditandai sudah mutasi.'], 422);
        }

        $deletionRequest = DB::transaction(function () use ($profile, $user): ProfileDeletionRequest {
            $lockedProfile = $user->teacherProfile()->lockForUpdate()->firstOrFail();
            if (! $lockedProfile->is_mutated) {
                abort(422, 'Profil hanya dapat diajukan untuk dihapus setelah ditandai sudah mutasi.');
            }
            $pendingRequest = $lockedProfile->deletionRequests()->where('status', 'pending')->first();
            if ($pendingRequest) {
                return $pendingRequest;
            }

            return $lockedProfile->deletionRequests()->create([
                'requested_by' => $user->id,
                'requester_name' => $profile->name,
                'requester_email' => $user->email,
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'data' => ['id' => $deletionRequest->id, 'status' => $deletionRequest->status],
            'message' => 'Pengajuan penghapusan profil telah dikirim kepada admin.',
        ], 201);
    }
}
