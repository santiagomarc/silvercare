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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elderly_id')->constrained('user_profiles')->onDelete('cascade');
            $table->foreignId('caregiver_id')->nullable()->constrained('user_profiles')->onDelete('set null');
            
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->string('dosage_unit')->nullable();
            $table->text('instructions')->nullable();
            
            // Scheduling
            $table->string('frequency')->nullable();
            $table->json('days_of_week')->nullable();
            $table->json('specific_dates')->nullable();
            $table->json('times_of_day')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Inventory tracking
            $table->boolean('track_inventory')->default(false);
            $table->integer('current_stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
