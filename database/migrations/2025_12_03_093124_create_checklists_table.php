<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists (avoids duplicate table error)
        if (Schema::hasTable('checklists')) {
            return;
        }

        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            
            // Ownership
            $table->foreignId('elderly_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('caregiver_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Fallback owner

            // Task Details
            $table->string('task'); // Or 'title'
            $table->text('description')->nullable(); // Added from your deleted file
            $table->string('category')->default('General'); 
            
            // Timing
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable(); 
            
            // Recurring & Priority (From your deleted file)
            $table->string('priority')->default('medium'); 
            $table->string('frequency')->nullable(); 
            $table->boolean('is_recurring')->default(false);
            
            // Status
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
