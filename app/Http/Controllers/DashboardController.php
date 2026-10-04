<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use App\Services\ReciprocalMatchFinder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReciprocalMatchFinder $matchFinder): View
    {
        $profile = $request->user()->teacherProfile()
            ->with(['sudin:id,name', 'destinationSudin:id,name', 'destinationDistricts:code,name,regency_code,regency_name'])
            ->first();
        $filters = $request->validate([
            'candidate_origin_district_code' => ['nullable', 'string', 'regex:/^31\.\d{2}\.\d{2}$/', 'exists:districts,code'],
        ]);
        $districtCode = $filters['candidate_origin_district_code'] ?? null;

        if ($profile instanceof TeacherProfile && $districtCode
            && ! $profile->destinationSudin?->districts()->where('districts.code', $districtCode)->exists()) {
            abort(422, 'Filter kecamatan harus berada dalam Sudin tujuanmu.');
        }

        return view('home', [
            'profile' => $profile,
            'matches' => $profile ? $matchFinder->forProfile($profile, $districtCode) : collect(),
            'districts' => $profile?->destinationSudin?->districts()->orderBy('name')->get() ?? collect(),
            'selectedDistrictCode' => $districtCode,
        ]);
    }
}
