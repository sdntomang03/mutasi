<?php

use Database\Seeders\SubjectSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aman dijalankan ulang bila percobaan sebelumnya gagal di tengah jalan (DDL MySQL tidak ter-rollback).
        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('teacher_profiles', 'subject_id')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('level')->constrained('subjects')->restrictOnDelete();
            });
        }

        Schema::dropIfExists('teacher_destination_subjects');
        Schema::create('teacher_destination_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'subject_id'], 'tds_profile_subject_unique');
        });

        $now = now();
        $missing = collect(SubjectSeeder::DEFAULTS)->diff(DB::table('subjects')->pluck('name'));
        if ($missing->isNotEmpty()) {
            DB::table('subjects')->insert($missing
                ->map(fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
                ->values()
                ->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_destination_subjects');
        if (Schema::hasColumn('teacher_profiles', 'subject_id')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subject_id');
            });
        }
        Schema::dropIfExists('subjects');
    }
};
