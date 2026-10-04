<?php

namespace App\Services;

use App\Mail\ReciprocalMatchFound;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReciprocalMatchNotifier
{
    public function notifyFor(TeacherProfile $profile, ReciprocalMatchFinder $matchFinder): void
    {
        $profile->loadMissing(['user', 'sudin:id,name', 'destinationSudin:id,name']);

        foreach ($matchFinder->forProfile($profile) as $candidate) {
            if (! $candidate->user) {
                continue;
            }

            $profileIds = [$profile->id, $candidate->id];
            sort($profileIds, SORT_STRING);
            DB::transaction(function () use ($profile, $candidate, $profileIds): void {
                $inserted = DB::table('notified_match_pairs')->insertOrIgnore([
                    'profile_one_id' => $profileIds[0],
                    'profile_two_id' => $profileIds[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted !== 1) {
                    return;
                }

                Mail::to($profile->user->email)->queue(new ReciprocalMatchFound(
                    $profile->name,
                    $candidate->name,
                    $profile->sudin->name,
                    $profile->destinationSudin->name,
                ));
                Mail::to($candidate->user->email)->queue(new ReciprocalMatchFound(
                    $candidate->name,
                    $profile->name,
                    $candidate->sudin->name,
                    $candidate->destinationSudin->name,
                ));
            });
        }
    }
}
