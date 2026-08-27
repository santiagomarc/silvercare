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
        Schema::create('capture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->string('session_type'); // prescription_scan, vital_photo_ocr, voice_vital_note
            $table->string('image_path')->nullable();
            $table->text('raw_transcript')->nullable();
            $table->json('extracted_data')->nullable();
            $table->string('status')->default('pending'); // pending, processed, confirmed, rejected, failed
            $table->text('error_message')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['elderly_id', 'session_type', 'status']);
        });

        // Broaden health_metrics source to allow 'voice_capture', 'camera_ocr', etc.
        Schema::table('health_metrics', function (Blueprint $table) {
            $table->string('source')->default('manual')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capture_sessions');
    }
};
