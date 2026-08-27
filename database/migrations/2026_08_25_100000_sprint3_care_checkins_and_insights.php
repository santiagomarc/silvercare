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
        Schema::create('care_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->date('checkin_date');
            $table->string('status')->default('ok'); // ok, need_help, missed
            $table->text('notes')->nullable();
            $table->string('mood')->nullable(); // great, good, okay, bad
            $table->timestamp('checked_in_at')->nullable();
            $table->string('source')->default('web_button'); // web_button, voice, sms, ai_chat
            $table->timestamps();

            $table->unique(['elderly_id', 'checkin_date']);
            $table->index(['elderly_id', 'status', 'checkin_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_checkins');
    }
};
