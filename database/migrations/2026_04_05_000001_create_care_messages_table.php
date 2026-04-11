<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('care_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caregiver_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->foreignId('elderly_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->foreignId('sender_profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['caregiver_id', 'elderly_id', 'created_at']);
            $table->index(['elderly_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_messages');
    }
};
