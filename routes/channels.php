<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('caregiver.{profileId}', function ($user, $profileId) {
    return (int) $user->profile?->id === (int) $profileId && $user->profile?->isCaregiver();
});
