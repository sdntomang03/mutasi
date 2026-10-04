<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi jabatan dan jenjang profil lama agar tetap ikut pencocokan.
     * Jenjang ditebak dari awalan nama sekolah (default SD), jabatan default guru kelas;
     * guru dapat mengoreksinya lewat halaman profil.
     */
    public function up(): void
    {
        $detectLevel = function (string $schoolName): string {
            $name = strtoupper(trim($schoolName));

            return match (true) {
                str_starts_with($name, 'SMK') => 'SMK',
                str_starts_with($name, 'SMA') => 'SMA',
                str_starts_with($name, 'SMP') => 'SMP',
                default => 'SD',
            };
        };

        DB::table('teacher_profiles')
            ->where(fn ($query) => $query->whereNull('level')->orWhereNull('position')->orWhereNull('destination_position'))
            ->get(['id', 'school_name', 'level', 'position', 'destination_position'])
            ->each(function (object $profile) use ($detectLevel): void {
                $level = $profile->level ?? $detectLevel((string) $profile->school_name);
                $position = $profile->position ?? ($level === 'SD' ? 'guru_kelas' : 'guru_mapel');

                DB::table('teacher_profiles')->where('id', $profile->id)->update([
                    'level' => $level,
                    'position' => $position,
                    'destination_position' => $profile->destination_position ?? $position,
                ]);

                $hasLevels = DB::table('teacher_destination_levels')->where('teacher_profile_id', $profile->id)->exists();
                if (! $hasLevels) {
                    DB::table('teacher_destination_levels')->insert([
                        'teacher_profile_id' => $profile->id,
                        'level' => $level,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data hasil pengisian tidak dapat dibedakan dari input guru.
    }
};
