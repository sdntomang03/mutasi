<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->string('code', 12)->primary();
            $table->string('name');
            $table->string('regency_code', 8);
            $table->string('regency_name');
            $table->timestamps();
        });

        Schema::create('sudins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('sudin_district', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sudin_id')->constrained()->cascadeOnDelete();
            $table->string('district_code', 12)->unique();
            $table->foreign('district_code')->references('code')->on('districts')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('phone', 24)->unique();
            $table->string('employment_type', 8);
            $table->string('school_name', 160);
            $table->text('school_address');
            $table->foreignId('sudin_id')->constrained()->restrictOnDelete();
            $table->string('province_code', 4)->default('31');
            $table->string('regency_code', 8);
            $table->string('regency_name');
            $table->string('district_code', 12);
            $table->string('district_name');
            $table->string('village_code', 20);
            $table->string('village_name');
            $table->timestamps();
            $table->index(['sudin_id', 'district_code']);
        });

        Schema::create('teacher_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('regency_code', 8);
            $table->string('regency_name');
            $table->string('district_code', 12);
            $table->string('district_name');
            $table->string('village_code', 20)->nullable();
            $table->string('village_name')->nullable();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'district_code', 'village_code'], 'teacher_destination_unique');
            $table->foreign('district_code')->references('code')->on('districts')->restrictOnDelete();
            $table->index(['district_code', 'village_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_destinations');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('sudin_district');
        Schema::dropIfExists('sudins');
        Schema::dropIfExists('districts');
    }
};
