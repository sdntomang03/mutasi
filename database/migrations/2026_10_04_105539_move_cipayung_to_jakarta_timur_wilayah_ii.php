<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WILAYAH_I = 'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Timur';

    private const WILAYAH_II = 'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Timur';

    private const CIPAYUNG = '31.75.07';

    private const DUREN_SAWIT = '31.75.10';

    public function up(): void
    {
        $this->assign(self::CIPAYUNG, self::WILAYAH_II);
        $this->assign(self::DUREN_SAWIT, self::WILAYAH_I);
    }

    public function down(): void
    {
        $this->assign(self::CIPAYUNG, self::WILAYAH_I);
        $this->assign(self::DUREN_SAWIT, self::WILAYAH_II);
    }

    private function assign(string $districtCode, string $sudinName): void
    {
        $sudinId = DB::table('sudins')->where('name', $sudinName)->value('id');

        if (! $sudinId || ! DB::table('districts')->where('code', $districtCode)->exists()) {
            return;
        }

        DB::table('sudin_district')->where('district_code', $districtCode)->delete();
        DB::table('sudin_district')->insert([
            'sudin_id' => $sudinId,
            'district_code' => $districtCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
