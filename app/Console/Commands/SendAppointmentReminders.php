<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\Notification;
use App\Models\UserProfile;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders {--window=60 : Reminder window in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment and schedule reminders to elderly users for upcoming calendar events';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $windowMinutes = max(5, (int) $this->option('window'));
        $now = Carbon::now();
        $windowEnd = $now->copy()->addMinutes($windowMinutes);

        $events = CalendarEvent::whereBetween('start_time', [$now, $windowEnd])
            ->orderBy('start_time', 'asc')
            ->get();

        if ($events->isEmpty()) {
            $this->line('No upcoming events within reminder window.');
            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($events as $event) {
            $profile = UserProfile::where('user_id', $event->user_id)
                ->where('user_type', 'elderly')
                ->first();

            if (!$profile) {
                continue;
            }

            $customId = sprintf(
                'appointment_reminder_%d_%s',
                $event->id,
                $event->start_time->format('YmdHi')
            );

            $alreadySent = Notification::where('custom_id', $customId)->exists();
            if ($alreadySent) {
                continue;
            }

            $minutesLeft = max(1, $now->diffInMinutes($event->start_time));
            $title = $event->type === 'Appointment'
                ? 'Upcoming appointment reminder'
                : 'Upcoming schedule reminder';

            $notificationService->createNotification([
                'elderly_id' => $profile->id,
                'type' => 'appointment_reminder',
                'title' => $title,
                'message' => sprintf('%s starts in about %d minute(s) at %s.', $event->title, $minutesLeft, $event->start_time->format('g:i A')),
                'severity' => 'reminder',
                'custom_id' => $customId,
                'metadata' => [
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                    'starts_at' => $event->start_time->toIso8601String(),
                    'starts_at_human' => $event->start_time->format('M j, g:i A'),
                    'minutes_left' => $minutesLeft,
                ],
            ]);

            $sent++;
        }

        $this->info("Appointment reminders sent: {$sent}");

        return Command::SUCCESS;
    }
}
