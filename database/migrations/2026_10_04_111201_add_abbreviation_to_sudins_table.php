<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sudins', 'abbreviation')) {
            Schema::table('sudins', function (Blueprint $table): void {
                $table->string('abbreviation', 20)->nullable()->unique()->after('name');
            });
        }

        $cities = [
            'Jakarta Barat' => 'JB',
            'Jakarta Pusat' => 'JP',
            'Jakarta Timur' => 'JT',
            'Jakarta Selatan' => 'JS',
            'Jakarta Utara' => 'JU',
        ];

        foreach (DB::table('sudins')->whereNull('abbreviation')->get(['id', 'name']) as $sudin) {
            $abbreviation = null;

            if (str_contains($sudin->name, 'Kepulauan Seribu')) {
                $abbreviation = 'KS';
            } else {
                foreach ($cities as $city => $code) {
                    if (str_contains($sudin->name, $city)) {
                        $region = str_contains($sudin->name, 'Wilayah II') ? '2' : (str_contains($sudin->name, 'Wilayah I') ? '1' : '');
                        $abbreviation = trim($code.' '.$region);
                        break;
                    }
                }
            }

            if ($abbreviation && ! DB::table('sudins')->where('abbreviation', $abbreviation)->exists()) {
                DB::table('sudins')->where('id', $sudin->id)->update(['abbreviation' => $abbreviation]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sudins', function (Blueprint $table): void {
            $table->dropUnique(['abbreviation']);
            $table->dropColumn('abbreviation');
        });
    }
};
