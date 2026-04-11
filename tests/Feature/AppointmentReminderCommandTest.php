<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AppointmentReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_reminder_command_creates_notification_for_upcoming_event(): void
    {
        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-reminder',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $event = CalendarEvent::create([
            'user_id' => $elderlyUser->id,
            'title' => 'Clinic Follow-up',
            'description' => 'Monthly blood pressure check',
            'start_time' => now()->addMinutes(30),
            'type' => 'Appointment',
        ]);

        Artisan::call('appointments:send-reminders', ['--window' => 60]);

        $customId = sprintf('appointment_reminder_%d_%s', $event->id, $event->start_time->format('YmdHi'));

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'appointment_reminder',
            'custom_id' => $customId,
        ]);
    }

    public function test_appointment_reminder_command_is_idempotent_for_same_event(): void
    {
        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-reminder-idempotent',
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $event = CalendarEvent::create([
            'user_id' => $elderlyUser->id,
            'title' => 'Cardio Check',
            'description' => null,
            'start_time' => now()->addMinutes(20),
            'type' => 'Appointment',
        ]);

        Artisan::call('appointments:send-reminders', ['--window' => 60]);
        Artisan::call('appointments:send-reminders', ['--window' => 60]);

        $customId = sprintf('appointment_reminder_%d_%s', $event->id, $event->start_time->format('YmdHi'));

        $this->assertSame(
            1,
            Notification::where('elderly_id', $elderlyProfile->id)
                ->where('type', 'appointment_reminder')
                ->where('custom_id', $customId)
                ->count()
        );
    }
}
