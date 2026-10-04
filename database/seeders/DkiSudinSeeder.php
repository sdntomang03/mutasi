<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Sudin;
use Illuminate\Database\Seeder;

class DkiSudinSeeder extends Seeder
{
    public function run(): void
    {
        if (Sudin::query()->exists()) {
            return;
        }

        $coverage = [
            'Kabupaten Administrasi Kepulauan Seribu' => ['31.01'],
            'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Barat' => ['31.73.01', '31.73.03', '31.73.04', '31.73.06'],
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Barat' => ['31.73.02', '31.73.05', '31.73.07', '31.73.08'],
            'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Pusat' => ['31.71.01', '31.71.02', '31.71.06', '31.71.07'],
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Pusat' => ['31.71.03', '31.71.04', '31.71.05', '31.71.08'],
            'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Timur' => ['31.75.01', '31.75.02', '31.75.03', '31.75.06'],
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Timur' => ['31.75.04', '31.75.05', '31.75.07', '31.75.08', '31.75.09', '31.75.10'],
            'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Selatan' => ['31.74.05', '31.74.06', '31.74.09', '31.74.10'],
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Selatan' => ['31.74.01', '31.74.02', '31.74.03', '31.74.04', '31.74.07', '31.74.08'],
            'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Utara' => ['31.72.01', '31.72.02', '31.72.05'],
            'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Utara' => ['31.72.03', '31.72.04', '31.72.06'],
        ];

        foreach ($coverage as $name => $districtPrefixes) {
            $sudin = Sudin::query()->where('name', $name)->first()
                ?? Sudin::factory()->create(['name' => $name]);
            $districtCodes = District::query()
                ->where(function ($query) use ($districtPrefixes): void {
                    foreach ($districtPrefixes as $prefix) {
                        $query->orWhere('code', $prefix)
                            ->orWhere('code', 'like', $prefix.'.%');
                    }
                })
                ->pluck('code');

            if ($districtCodes->isEmpty()) {
                throw new \RuntimeException("No districts were found for the seeded Sudin [{$name}].");
            }

            $sudin->districts()->sync($districtCodes);
        }
    }
}
