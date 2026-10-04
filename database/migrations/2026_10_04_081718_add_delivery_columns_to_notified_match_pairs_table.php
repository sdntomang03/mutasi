<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notified_match_pairs', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notified_match_pairs', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'attempts', 'last_error']);
        });
    }
};
