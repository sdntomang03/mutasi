<?php

namespace App\Services;

use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Collection;

class ReciprocalMatchFinder
{
    public function forProfile(TeacherProfile $profile, ?string $candidateOriginDistrictCode = null): Collection
    {
        $profile->load('destinationLevels');
        $destinationLevels = $profile->destinationLevels->pluck('level');
        if (! $profile->position || ! $profile->level || ! $profile->destination_position || $destinationLevels->isEmpty()
            || ! $profile->sudin_id || ! $profile->destination_sudin_id || $profile->is_mutated
            || $profile->deletionRequests()->where('status', 'pending')->exists()) {
            return new Collection;
        }

        return TeacherProfile::query()
            ->with(['sudin:id,name', 'destinationSudin:id,name', 'destinationDistricts:code,name,regency_code,regency_name', 'destinationLevels', 'user:id,email'])
            ->whereNotNull('user_id')
            ->whereHas('user', fn ($user) => $user->whereNotNull('email_verified_at'))
            ->where('user_id', '!=', $profile->user_id)
            ->where('is_mutated', false)
            ->whereDoesntHave('deletionRequests', fn ($query) => $query->where('status', 'pending'))
            ->where('destination_sudin_id', $profile->sudin_id)
            ->where('sudin_id', $profile->destination_sudin_id)
            ->where('position', $profile->destination_position)
            ->where('destination_position', $profile->position)
            ->whereIn('level', $destinationLevels)
            ->whereHas('destinationLevels', fn ($levels) => $levels->where('level', $profile->level))
            ->when(
                $profile->destinationDistricts->isNotEmpty(),
                fn ($query) => $query->whereIn('district_code', $profile->destinationDistricts->pluck('code')),
            )
            ->where(function ($query) use ($profile) {
                $query->whereDoesntHave('destinationDistricts')
                    ->orWhereHas('destinationDistricts', fn ($districts) => $districts->where('districts.code', $profile->district_code));
            })
            ->when(
                $candidateOriginDistrictCode,
                fn ($query) => $query->where('district_code', $candidateOriginDistrictCode),
            )
            ->orderBy('name')
            ->get();
    }
}
