<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->boolean('is_mutated')->default(false)->after('destination_sudin_id');
        });

        Schema::create('profile_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('profile_id')->nullable();
            $table->foreign('profile_id')->references('id')->on('teacher_profiles')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('status', 20)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_deletion_requests');

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('is_mutated');
        });
    }
};
