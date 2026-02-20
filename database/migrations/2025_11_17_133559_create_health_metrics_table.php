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
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            
            $table->enum('type', ['blood_pressure', 'heart_rate', 'sugar_level', 'temperature', 'mood', 'steps', 'calories', 'sleep', 'weight']);
            $table->decimal('value', 8, 2)->nullable(); // Nullable for mood (stored as string)
            $table->string('value_text', 50)->nullable(); // For mood (happy, sad, etc.)
            $table->string('unit', 20)->nullable(); // mmHg, bpm, mg/dL, °C, etc.
            $table->timestamp('measured_at');
            $table->enum('source', ['manual', 'google_fit', 'device'])->default('manual');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['elderly_id', 'type', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
};
