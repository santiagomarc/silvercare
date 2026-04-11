<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the daily reminders command to run every 30 minutes between 8 AM and 9 PM
Schedule::command('silvercare:send-reminders')
    ->everyThirtyMinutes()
    ->between('08:00', '21:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reminders.log'));

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
