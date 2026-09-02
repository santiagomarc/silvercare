<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('caregiver.{profileId}', function ($user, $profileId) {
    return (int) $user->profile?->id === (int) $profileId && $user->profile?->isCaregiver();
});

// C3: the senior's own channel, so a dose confirmed on another device or by the
// AI assistant updates their screen. Only the patient themselves may listen —
// a caregiver watching this channel would receive PHI outside the alert path.
Broadcast::channel('elderly.{profileId}', function ($user, $profileId) {
    return (int) $user->profile?->id === (int) $profileId;
});
