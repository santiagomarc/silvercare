<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Role
            $table->string('user_type')->default('elderly'); // 'elderly' or 'caregiver'
            
            // Basic Info
            $table->string('username')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('sex')->nullable();
            $table->integer('age')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->text('address')->nullable();

            // JSON Lists
            $table->json('medical_conditions')->nullable();
            $table->json('medications')->nullable();
            $table->json('allergies')->nullable();

            // Emergency Contact (Stored as individual columns or JSON)
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('emergency_relationship')->nullable();
            
            // Legacy/App Specific Fields (Keeping to prevent errors)
            $table->json('emergency_contact')->nullable(); 
            $table->json('medical_info')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('caregiver_id')->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};