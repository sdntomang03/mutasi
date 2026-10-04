<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profile_destination_district', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('district_code', 12);
            $table->foreign('district_code')->references('code')->on('districts')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'district_code'], 'teacher_destination_district_unique');
        });

        DB::table('teacher_profiles')
            ->whereNotNull('destination_district_code')
            ->orderBy('id')
            ->each(function (object $profile): void {
                DB::table('teacher_profile_destination_district')->insert([
                    'teacher_profile_id' => $profile->id,
                    'district_code' => $profile->destination_district_code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropForeign(['destination_district_code']);
            $table->dropColumn('destination_district_code');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('destination_district_code', 12)->nullable();
            $table->foreign('destination_district_code')
                ->references('code')
                ->on('districts')
                ->nullOnDelete();
        });

        DB::table('teacher_profile_destination_district')
            ->orderBy('id')
            ->get()
            ->each(function (object $destination): void {
                DB::table('teacher_profiles')
                    ->where('id', $destination->teacher_profile_id)
                    ->whereNull('destination_district_code')
                    ->update(['destination_district_code' => $destination->district_code]);
            });

        Schema::dropIfExists('teacher_profile_destination_district');
    }
};
