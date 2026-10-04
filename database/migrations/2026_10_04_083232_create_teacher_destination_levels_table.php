<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_destination_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->string('level', 8);
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_destination_levels');
    }
};
