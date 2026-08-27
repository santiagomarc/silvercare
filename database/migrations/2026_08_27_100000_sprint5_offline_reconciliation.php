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
        Schema::create('offline_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('client_mutation_id', 64)->unique();
            $table->string('action_type'); // confirm_dose, undo_dose, record_vital, daily_checkin
            $table->json('payload');
            $table->string('status')->default('applied'); // applied, conflict_skipped, failed
            $table->string('error_code')->nullable(); // DOSE_ALREADY_CONFIRMED, WINDOW_EXPIRED, etc.
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['elderly_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_sync_logs');
    }
};
