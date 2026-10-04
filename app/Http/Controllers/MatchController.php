<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use App\Services\ReciprocalMatchFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request, ReciprocalMatchFinder $matchFinder): JsonResponse
    {
        $filters = $request->validate([
            'candidate_origin_district_code' => ['nullable', 'string', 'regex:/^31\.\d{2}\.\d{2}$/', 'exists:districts,code'],
        ]);

        $profile = $request->user()->teacherProfile()
            ->with(['sudin:id,name', 'destinationSudin:id,name', 'destinationDistricts:code,name,regency_code,regency_name', 'destinationLevels', 'destinationSubjects:id,name'])
            ->first();
        if (! $profile) {
            return response()->json(['message' => 'Simpan profil guru terlebih dahulu untuk mencari tukeran.'], 422);
        }

        if (! $profile->position || ! $profile->level || ! $profile->destination_position || $profile->destinationLevels->isEmpty()) {
            return response()->json(['message' => 'Lengkapi jabatan dan jenjang asal serta tujuan pada profil terlebih dahulu.'], 422);
        }

        if (($profile->position === 'guru_mapel' && ! $profile->subject_id)
            || ($profile->destination_position === 'guru_mapel' && $profile->destinationSubjects->isEmpty())) {
            return response()->json(['message' => 'Lengkapi mapel asal dan mapel tujuan pada profil terlebih dahulu.'], 422);
        }

        if (! $profile->destination_sudin_id) {
            return response()->json(['message' => 'Lengkapi Sudin tujuan pada profil terlebih dahulu.'], 422);
        }

        if (! $profile->sudin_id) {
            return response()->json(['message' => 'Lengkapi Sudin asal pada profil terlebih dahulu.'], 422);
        }

        $candidateOriginDistrictCode = $filters['candidate_origin_district_code'] ?? null;
        if ($candidateOriginDistrictCode && ! $profile->destinationSudin->districts()->where('districts.code', $candidateOriginDistrictCode)->exists()) {
            return response()->json(['message' => 'Filter kecamatan harus berada dalam Sudin tujuanmu.'], 422);
        }

        $profiles = $matchFinder->forProfile($profile, $candidateOriginDistrictCode);

        $matches = $profiles
            ->map(fn (TeacherProfile $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'phone' => $candidate->phone,
                'employment_type' => $candidate->employment_type,
                'position' => $candidate->position,
                'level' => $candidate->level,
                'subject' => $candidate->subject?->name,
                'school_name' => $candidate->school_name,
                'school_address' => $candidate->school_address,
                'sudin' => $candidate->sudin->name,
                'destination' => [
                    'sudin' => $candidate->destinationSudin->name,
                    'position' => $candidate->destination_position,
                    'levels' => $candidate->destinationLevels->pluck('level')->values(),
                    'subjects' => $candidate->destinationSubjects->pluck('name')->values(),
                    'districts' => $candidate->destinationDistricts->map(fn ($district) => [
                        'code' => $district->code,
                        'name' => $district->name,
                        'regency' => $district->regency_name,
                    ])->values(),
                ],
                'origin' => [
                    'regency' => $candidate->regency_name,
                    'district' => $candidate->district_name,
                    'village' => $candidate->village_name,
                ],
            ])
            ->values();

        return response()->json([
            'profile' => ['id' => $profile->id, 'name' => $profile->name, 'sudin' => $profile->sudin->name],
            'data' => $matches,
            'message' => $matches->isEmpty()
                ? 'Belum ada profil yang memenuhi kecocokan dua arah untuk wilayah ini.'
                : 'Nomor kontak ditampilkan karena Sudin asal dan tujuan kedua guru saling cocok.',
        ]);
    }
}
