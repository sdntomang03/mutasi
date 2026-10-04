<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WILAYAH_I = 'Suku Dinas Pendidikan Wilayah I Kota Administrasi Jakarta Timur';

    private const WILAYAH_II = 'Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Timur';

    private const CIPAYUNG = '31.75.07';

    public function up(): void
    {
        $this->assign(self::WILAYAH_II);
    }

    public function down(): void
    {
        $this->assign(self::WILAYAH_I);
    }

    private function assign(string $sudinName): void
    {
        $sudinId = DB::table('sudins')->where('name', $sudinName)->value('id');

        if (! $sudinId || ! DB::table('districts')->where('code', self::CIPAYUNG)->exists()) {
            return;
        }

        DB::table('sudin_district')->where('district_code', self::CIPAYUNG)->delete();
        DB::table('sudin_district')->insert([
            'sudin_id' => $sudinId,
            'district_code' => self::CIPAYUNG,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
