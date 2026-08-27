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
        Schema::create('sync_telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('service_name'); // google_fit, apple_health, fitbit
            $table->string('status'); // success, token_expired, rate_limited, failed
            $table->unsignedInteger('records_synced')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_details')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->index(['elderly_id', 'service_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_telemetry_logs');
    }
};
