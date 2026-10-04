<?php

namespace App\Services;

use App\Mail\ReciprocalMatchFound;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ReciprocalMatchNotifier
{
    public function notifyFor(TeacherProfile $profile, ReciprocalMatchFinder $matchFinder): int
    {
        $delivered = 0;

        foreach ($matchFinder->forProfile($profile) as $candidate) {
            $profileIds = [$profile->id, $candidate->id];
            sort($profileIds, SORT_STRING);

            $inserted = DB::table('notified_match_pairs')->insertOrIgnore([
                'profile_one_id' => $profileIds[0],
                'profile_two_id' => $profileIds[1],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                $pairId = DB::table('notified_match_pairs')
                    ->where('profile_one_id', $profileIds[0])
                    ->where('profile_two_id', $profileIds[1])
                    ->value('id');
                $delivered += (int) $this->deliver($pairId);
            }
        }

        return $delivered;
    }

    /**
     * Kirim email ke kedua guru pada satu pasangan dan catat hasilnya agar admin dapat mengirim ulang.
     */
    public function deliver(int $pairId): bool
    {
        $pair = DB::table('notified_match_pairs')->find($pairId);
        if (! $pair) {
            return false;
        }

        $relations = ['user', 'sudin:id,name', 'destinationSudin:id,name'];
        $first = TeacherProfile::query()->with($relations)->find($pair->profile_one_id);
        $second = TeacherProfile::query()->with($relations)->find($pair->profile_two_id);

        try {
            if (! $first?->user || ! $second?->user) {
                throw new RuntimeException('Salah satu profil atau akun sudah tidak tersedia.');
            }

            Mail::to($first->user->email)->send($this->mailFor($first, $second));
            Mail::to($second->user->email)->send($this->mailFor($second, $first));
        } catch (Throwable $exception) {
            report($exception);
            DB::table('notified_match_pairs')->where('id', $pairId)->update([
                'attempts' => $pair->attempts + 1,
                'last_error' => mb_substr($exception->getMessage(), 0, 500),
                'updated_at' => now(),
            ]);

            return false;
        }

        DB::table('notified_match_pairs')->where('id', $pairId)->update([
            'sent_at' => now(),
            'attempts' => $pair->attempts + 1,
            'last_error' => null,
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Cari semua pasangan cocok aktif yang belum tercatat lalu kirim emailnya.
     */
    public function scanAll(ReciprocalMatchFinder $matchFinder): int
    {
        $delivered = 0;

        TeacherProfile::query()
            ->where('is_mutated', false)
            ->whereNotNull('user_id')
            ->each(function (TeacherProfile $profile) use ($matchFinder, &$delivered): void {
                $delivered += $this->notifyFor($profile, $matchFinder);
            });

        return $delivered;
    }

    private function mailFor(TeacherProfile $recipient, TeacherProfile $match): ReciprocalMatchFound
    {
        return new ReciprocalMatchFound(
            $recipient->name,
            $match->name,
            $recipient->sudin->name,
            $recipient->destinationSudin->name,
        );
    }
}
