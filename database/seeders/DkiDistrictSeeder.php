<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DkiDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $regencies = [
            '31.01' => [
                'name' => 'Kabupaten Administrasi Kepulauan Seribu',
                'districts' => ['01' => 'Kepulauan Seribu Selatan', '02' => 'Kepulauan Seribu Utara'],
            ],
            '31.71' => [
                'name' => 'Kota Administrasi Jakarta Pusat',
                'districts' => [
                    '01' => 'Gambir', '02' => 'Sawah Besar', '03' => 'Kemayoran', '04' => 'Senen',
                    '05' => 'Cempaka Putih', '06' => 'Menteng', '07' => 'Tanah Abang', '08' => 'Johar Baru',
                ],
            ],
            '31.72' => [
                'name' => 'Kota Administrasi Jakarta Utara',
                'districts' => [
                    '01' => 'Penjaringan', '02' => 'Tanjung Priok', '03' => 'Koja',
                    '04' => 'Cilincing', '05' => 'Pademangan', '06' => 'Kelapa Gading',
                ],
            ],
            '31.73' => [
                'name' => 'Kota Administrasi Jakarta Barat',
                'districts' => [
                    '01' => 'Cengkareng', '02' => 'Grogol Petamburan', '03' => 'Taman Sari', '04' => 'Tambora',
                    '05' => 'Kebon Jeruk', '06' => 'Kalideres', '07' => 'Palmerah', '08' => 'Kembangan',
                ],
            ],
            '31.74' => [
                'name' => 'Kota Administrasi Jakarta Selatan',
                'districts' => [
                    '01' => 'Tebet', '02' => 'Setiabudi', '03' => 'Mampang Prapatan', '04' => 'Pasar Minggu',
                    '05' => 'Kebayoran Lama', '06' => 'Cilandak', '07' => 'Kebayoran Baru', '08' => 'Pancoran',
                    '09' => 'Jagakarsa', '10' => 'Pesanggrahan',
                ],
            ],
            '31.75' => [
                'name' => 'Kota Administrasi Jakarta Timur',
                'districts' => [
                    '01' => 'Matraman', '02' => 'Pulo Gadung', '03' => 'Jatinegara', '04' => 'Kramat Jati',
                    '05' => 'Pasar Rebo', '06' => 'Cakung', '07' => 'Cipayung', '08' => 'Ciracas',
                    '09' => 'Makasar', '10' => 'Duren Sawit',
                ],
            ],
        ];

        foreach ($regencies as $regencyCode => $regency) {
            foreach ($regency['districts'] as $districtNumber => $districtName) {
                District::query()->updateOrCreate(
                    ['code' => $regencyCode.'.'.$districtNumber],
                    [
                        'name' => $districtName,
                        'regency_code' => $regencyCode,
                        'regency_name' => $regency['name'],
                    ],
                );
            }
        }
    }
}
