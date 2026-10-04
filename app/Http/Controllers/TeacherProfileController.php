<?php

namespace App\Http\Controllers;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Services\ReciprocalMatchFinder;
use App\Services\ReciprocalMatchNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->teacherProfile()
            ->with(['destinationSudin:id,name', 'destinationDistricts:code,name,regency_code,regency_name', 'destinationLevels', 'sudin:id,name'])
            ->first();

        return response()->json([
            'data' => $profile,
        ]);
    }

    public function store(
        Request $request,
        ReciprocalMatchFinder $matchFinder,
        ReciprocalMatchNotifier $matchNotifier,
    ): JsonResponse {
        $existingProfile = $request->user()->teacherProfile()->first();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:8', 'max:24', 'regex:/^[+0-9(). -]+$/'],
            'employment_type' => ['required', 'in:PNS,PPPK,KKI'],
            'school_name' => ['required', 'string', 'max:160'],
            'school_address' => ['required', 'string', 'max:1000'],
            'sudin_id' => ['required', 'integer', 'exists:sudins,id'],
            'position' => ['required', Rule::in(array_keys(TeacherProfile::POSITIONS))],
            'level' => ['required', Rule::in(TeacherProfile::LEVELS)],
            'destination_position' => ['required', Rule::in(array_keys(TeacherProfile::POSITIONS))],
            'destination_levels' => ['required', 'array', 'min:1', 'max:'.count(TeacherProfile::LEVELS)],
            'destination_levels.*' => ['required', 'distinct', Rule::in(TeacherProfile::LEVELS)],
            'destination_sudin_id' => ['required', 'integer', 'exists:sudins,id'],
            'destination_district_codes' => ['nullable', 'array', 'max:44'],
            'destination_district_codes.*' => ['required', 'string', 'distinct', 'regex:/^31\.\d{2}\.\d{2}$/', 'exists:districts,code'],
            'province_code' => ['required', 'in:31'],
            'regency_code' => ['required', 'string', 'regex:/^31\.\d{2}$/'],
            'regency_name' => ['required', 'string', 'max:120'],
            'district_code' => ['required', 'string', 'regex:/^31\.\d{2}\.\d{2}$/'],
            'district_name' => ['required', 'string', 'max:120'],
            'village_code' => ['required', 'string', 'regex:/^31\.\d{2}\.\d{2}\.\d{4}$/'],
            'village_name' => ['required', 'string', 'max:120'],
        ]);

        if (! str_starts_with($data['district_code'], $data['regency_code'].'.')
            || ! str_starts_with($data['village_code'], $data['district_code'].'.')) {
            return response()->json(['message' => 'Wilayah asal sekolah tidak sesuai hierarki DKI Jakarta.'], 422);
        }

        if ($data['destination_position'] === 'guru_kelas' && count($data['destination_levels']) !== 1) {
            return response()->json(['message' => 'Guru kelas hanya dapat memilih satu jenjang tujuan.'], 422);
        }

        $phone = $this->normalizePhone($data['phone']);
        if (TeacherProfile::query()
            ->where('phone', $phone)
            ->when($existingProfile, fn ($query) => $query->where('id', '!=', $existingProfile->id))
            ->exists()) {
            return response()->json(['message' => 'Nomor HP ini sudah terdaftar.'], 422);
        }

        $sudin = Sudin::query()->with('districts:code')->findOrFail($data['sudin_id']);
        if (! $sudin->districts->contains('code', $data['district_code'])) {
            return response()->json([
                'message' => 'Kecamatan asal sekolah belum termasuk dalam cakupan Sudin yang dipilih.',
            ], 422);
        }

        $destinationSudin = Sudin::query()->with('districts:code')->findOrFail($data['destination_sudin_id']);
        $destinationDistrictCodes = $data['destination_district_codes'] ?? [];
        if (collect($destinationDistrictCodes)->diff($destinationSudin->districts->pluck('code'))->isNotEmpty()) {
            return response()->json([
                'message' => 'Semua kecamatan tujuan harus berada dalam cakupan Sudin tujuan yang dipilih.',
            ], 422);
        }

        $wasCreated = $existingProfile === null;
        $profile = DB::transaction(function () use ($data, $phone, $request, $existingProfile, $destinationDistrictCodes) {
            $attributes = [
                'name' => $data['name'],
                'phone' => $phone,
                'employment_type' => $data['employment_type'],
                'school_name' => $data['school_name'],
                'school_address' => $data['school_address'],
                'sudin_id' => $data['sudin_id'],
                'position' => $data['position'],
                'level' => $data['level'],
                'destination_position' => $data['destination_position'],
                'destination_sudin_id' => $data['destination_sudin_id'],
                'province_code' => '31',
                'regency_code' => $data['regency_code'],
                'regency_name' => $data['regency_name'],
                'district_code' => $data['district_code'],
                'district_name' => $data['district_name'],
                'village_code' => $data['village_code'],
                'village_name' => $data['village_name'],
            ];

            if ($existingProfile) {
                $profile = $existingProfile;
                $profile->fill($attributes)->save();
            } else {
                $profile = $request->user()->teacherProfile()->create($attributes);
            }

            $profile->destinationDistricts()->sync($destinationDistrictCodes);
            $profile->syncDestinationLevels($data['destination_levels']);

            return $profile;
        });
        $matchNotifier->notifyFor($profile, $matchFinder);

        return response()->json([
            'data' => $profile->load(['destinationSudin:id,name', 'destinationDistricts:code,name,regency_code,regency_name', 'destinationLevels', 'sudin:id,name']),
            'message' => $wasCreated
                ? 'Profil tersimpan. Mencari guru yang cocok dua arah berdasarkan Sudin.'
                : 'Profil berhasil diperbarui.',
        ], $wasCreated ? 201 : 200);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            return '+62'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
