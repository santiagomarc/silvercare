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
        // 1. Medication Dose Instances Table
        Schema::create('medication_dose_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
            $table->timestamp('scheduled_at_utc');
            $table->date('local_date');
            $table->string('timezone')->default('Asia/Manila');
            $table->string('state')->default('pending'); // pending, taken, taken_late, missed, held, skipped
            $table->timestamp('taken_at')->nullable();
            $table->string('source')->nullable(); // senior_ui, ai_assistant, offline_sync, caregiver, scheduler
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('version')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['elderly_id', 'medication_id', 'scheduled_at_utc'], 'dose_instances_unique_schedule');
            $table->index(['elderly_id', 'local_date', 'state']);
            $table->index(['medication_id', 'local_date']);
        });

        // 2. Add unique constraint to legacy medication_logs table
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->unique(['elderly_id', 'medication_id', 'scheduled_time'], 'med_logs_elderly_med_sched_unique');
        });

        // 3. Add timezone to user_profiles
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'timezone')) {
                $table->string('timezone')->default('Asia/Manila');
            }
        });

        // 4. Prescription Revisions Audit Table
        Schema::create('prescription_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['medication_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_revisions');

        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });

        Schema::table('medication_logs', function (Blueprint $table) {
            $table->dropUnique('med_logs_elderly_med_sched_unique');
        });

        Schema::dropIfExists('medication_dose_instances');
    }
};
