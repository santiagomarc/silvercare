<?php

namespace App\Policies;

use App\Models\HealthMetric;
use App\Models\User;

class HealthMetricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->profile?->isElderly() === true;
    }

    public function create(User $user): bool
    {
        return $user->profile?->isElderly() === true;
    }

    public function view(User $user, HealthMetric $metric): bool
    {
        return $user->profile?->id === $metric->elderly_id;
    }

    public function delete(User $user, HealthMetric $metric): bool
    {
        return $user->profile?->id === $metric->elderly_id;
    }
}