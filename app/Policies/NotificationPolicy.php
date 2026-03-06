<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->profile?->isElderly() === true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->profile?->id === $notification->elderly_id;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->profile?->id === $notification->elderly_id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->profile?->id === $notification->elderly_id;
    }
}