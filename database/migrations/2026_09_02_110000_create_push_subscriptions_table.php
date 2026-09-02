<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H8 — browser push subscriptions.
 *
 * One row per browser a caregiver has granted notification permission in, so a
 * caregiver with a phone and a desktop receives a critical alert on both.
 * Keyed on the push service endpoint, which is what uniquely identifies a
 * subscription and is what we send to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('user_profiles')->onDelete('cascade');

            // Push service URL. Long: FCM and Mozilla endpoints run well past
            // the 255 a default string would give us.
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();

            // Encryption material from PushSubscription.getKey().
            $table->string('p256dh_key');
            $table->string('auth_token');

            // Lets a caregiver recognise a device when revoking one.
            $table->string('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
