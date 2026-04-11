<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes:
     * 1. Adds `profile_skipped` boolean to distinguish "skipped onboarding"
     *    from "actually completed onboarding" — both were previously
     *    represented by profile_completed = true.
     * 2. Adds a proper index on caregiver_id to support the multi-patient
     *    relationship (HasMany) cleanly, as caregivers will eventually
     *    manage multiple elderly patients.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Separate skip intent from genuine completion
            $table->boolean('profile_skipped')->default(false)->after('profile_completed');

            // Index for the future HasMany query: UserProfile::where('caregiver_id', $id)
            $table->index('caregiver_id', 'profiles_caregiver_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_caregiver_id_index');
            $table->dropColumn('profile_skipped');
        });
    }
};
