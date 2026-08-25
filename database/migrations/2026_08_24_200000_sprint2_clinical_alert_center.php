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
        // 1. Alerts Domain Table
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('severity')->default('warning'); // info, warning, critical, emergency
            $table->string('source_type'); // vital_threshold, sos, missed_dose, ai_emergency, check_in_missed
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->string('state')->default('open'); // open, acknowledged, resolved, expired
            $table->timestamp('escalate_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['elderly_id', 'state', 'created_at']);
            $table->index(['state', 'escalate_at']);
        });

        // 2. Alert Deliveries Audit Table
        Schema::create('alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->onDelete('cascade');
            $table->foreignId('recipient_profile_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('channel'); // in_app, email, browser_push, reverb
            $table->string('state')->default('pending'); // pending, sent, delivered, failed
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('alert_id');
            $table->index(['recipient_profile_id', 'state']);
        });

        // 3. Patient Custom Alert Thresholds
        Schema::create('patient_alert_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('metric_type'); // blood_pressure, sugar_level, temperature, heart_rate
            $table->json('thresholds');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['elderly_id', 'metric_type']);
        });

        // 4. Broaden notifications severity column to accept critical & emergency
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('severity')->default('reminder')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_alert_thresholds');
        Schema::dropIfExists('alert_deliveries');
        Schema::dropIfExists('alerts');
    }
};
