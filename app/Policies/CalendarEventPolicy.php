<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->user_id;
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->id === $event->user_id;
    }
}