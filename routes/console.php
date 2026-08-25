<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the daily reminders command to run 24/7 every 30 minutes to detect missed doses accurately
Schedule::command('silvercare:send-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminders.log'));

// Generate upcoming 7-day dose instances daily just after midnight
Schedule::command('doses:generate-instances --days=7')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/dose-instances.log'));

// Escalate unacknowledged critical clinical alerts every 5 minutes
Schedule::command('alerts:escalate')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/alert-escalations.log'));

// Check medication stock levels daily at 9 AM
Schedule::command('medications:check-stock')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/stock-alerts.log'));

// Check upcoming appointments/reminders every 15 minutes
Schedule::command('appointments:send-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/appointment-reminders.log'));

// Send weekly PDF reports every Monday morning
Schedule::command('reports:send-weekly-health')
    ->weeklyOn(1, '07:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/weekly-health-reports.log'));

// M1 FIX: Recycle recurring checklists — runs just after midnight each day.
// Creates fresh 'pending' copies of recurring tasks so they appear in
// getTodaysChecklist() for the new day without overwriting history.
Schedule::command('checklists:recycle-recurring')
    ->dailyAt('00:01')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring-checklists.log'));

// Track cognitive sentiment and mood from elderly AI chat logs daily at 11:00 PM
Schedule::command('ai:track-cognitive-sentiment')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ai-sentiment.log'));

// Delete incomplete user profiles every hour (older than 24 hours)
Schedule::command('profiles:delete-incomplete --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/incomplete-profiles-cleanup.log'));

// Background Google Fit sync — runs every 2 hours for all connected elderly users.
// Keeps health_metrics up to date without requiring a manual page visit.
Schedule::command('app:sync-google-fit')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/google-fit-sync.log'));

