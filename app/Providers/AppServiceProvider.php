<?php

namespace App\Providers;

use App\Models\CalendarEvent;
use App\Models\Checklist;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\Notification;
use App\Policies\CalendarEventPolicy;
use App\Policies\ChecklistPolicy;
use App\Policies\HealthMetricPolicy;
use App\Policies\MedicationPolicy;
use App\Policies\NotificationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Medication::class, MedicationPolicy::class);
        Gate::policy(Checklist::class, ChecklistPolicy::class);
        Gate::policy(HealthMetric::class, HealthMetricPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
    }
}
