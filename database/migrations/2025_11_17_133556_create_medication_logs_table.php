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
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('medication_id')->constrained()->onDelete('cascade');
            
            $table->timestamp('scheduled_time');
            $table->boolean('is_taken')->default(false);
            $table->timestamp('taken_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['elderly_id', 'scheduled_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
