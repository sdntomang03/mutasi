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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('level')->constrained('subjects')->restrictOnDelete();
        });

        Schema::create('teacher_destination_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'subject_id']);
        });

        $now = now();
        DB::table('subjects')->insert(collect(SubjectSeeder::DEFAULTS)
            ->map(fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
            ->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_destination_subjects');
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
        });
        Schema::dropIfExists('subjects');
    }
};
