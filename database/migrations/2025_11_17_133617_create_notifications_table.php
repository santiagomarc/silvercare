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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            
            $table->string('type'); // medication_reminder, medication_taken, medication_missed, etc.
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['positive', 'negative', 'reminder', 'warning'])->default('reminder');
            $table->json('metadata')->nullable(); // Store medicationId, taskId, etc.
            
            $table->boolean('is_read')->default(false);
            $table->string('custom_id')->nullable(); // For duplicate prevention
            
            $table->timestamps();
            
            $table->index(['elderly_id', 'created_at']);
            $table->index('custom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
