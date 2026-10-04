<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->foreignId('destination_sudin_id')
                ->nullable()
                ->after('sudin_id')
                ->constrained('sudins')
                ->nullOnDelete();
            $table->string('destination_district_code', 12)
                ->nullable()
                ->after('destination_sudin_id');
            $table->foreign('destination_district_code')
                ->references('code')
                ->on('districts')
                ->nullOnDelete();
        });

        DB::table('teacher_profiles')
            ->orderBy('id')
            ->chunk(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $destination = DB::table('teacher_destinations')
                        ->where('teacher_profile_id', $profile->id)
                        ->orderBy('id')
                        ->first();

                    if (! $destination) {
                        continue;
                    }

                    $destinationSudinId = DB::table('sudin_district')
                        ->where('district_code', $destination->district_code)
                        ->value('sudin_id');

                    if (! $destinationSudinId) {
                        continue;
                    }

                    DB::table('teacher_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'destination_sudin_id' => $destinationSudinId,
                            'destination_district_code' => $destination->district_code,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_sudin_id');
            $table->dropForeign(['destination_district_code']);
            $table->dropColumn('destination_district_code');
        });
    }
};
