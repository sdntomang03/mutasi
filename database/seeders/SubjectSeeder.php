<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public const DEFAULTS = [
        'Pendidikan Agama Islam',
        'Pendidikan Agama Kristen',
        'Pendidikan Agama Katolik',
        'Pendidikan Agama Hindu',
        'Pendidikan Agama Buddha',
        'Pendidikan Pancasila',
        'Bahasa Indonesia',
        'Bahasa Inggris',
        'Matematika',
        'IPA',
        'IPS',
        'PJOK',
        'Seni Budaya',
        'Informatika',
        'Bimbingan Konseling',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $name) {
            Subject::firstOrCreate(['name' => $name]);
        }
    }
}
