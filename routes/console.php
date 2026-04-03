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
