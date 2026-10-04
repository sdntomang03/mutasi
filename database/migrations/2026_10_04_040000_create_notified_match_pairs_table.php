<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notified_match_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('profile_one_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignUuid('profile_two_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_one_id', 'profile_two_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notified_match_pairs');
    }
};
