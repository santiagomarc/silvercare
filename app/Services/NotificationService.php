<?php

namespace App\Services;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a notification
     */
    public function createNotification(array $data): Notification
    {
        return Notification::create([
            'elderly_id' => $data['elderly_id'],
            'type' => $data['type'], // medication_reminder, medication_taken, medication_missed, health_alert, etc.
            'title' => $data['title'],
            'message' => $data['message'],
            'severity' => $data['severity'] ?? 'reminder', // positive, negative, reminder, warning
            'metadata' => $data['metadata'] ?? null, // JSON data
            'custom_id' => $data['custom_id'] ?? null, // For duplicate prevention
        ]);
    }

    /**
     * Get all notifications for elderly
     */
    public function getNotificationsForElderly(int $elderlyProfileId, int $limit = 50): Collection
    {
        return Notification::where('elderly_id', $elderlyProfileId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType(int $elderlyProfileId, string $type): Collection
    {
        return Notification::where('elderly_id', $elderlyProfileId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get notifications by severity
     */
    public function getNotificationsBySeverity(int $elderlyProfileId, string $severity): Collection
    {
        return Notification::where('elderly_id', $elderlyProfileId)
            ->where('severity', $severity)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get today's notifications
     */
    public function getTodaysNotifications(int $elderlyProfileId): Collection
    {
        return Notification::where('elderly_id', $elderlyProfileId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        return $notification->delete();
    }

    /**
     * Delete all notifications for elderly
     */
    public function deleteAllNotifications(int $elderlyProfileId): int
    {
        return Notification::where('elderly_id', $elderlyProfileId)->delete();
    }

    /**
     * Create medication taken notification
     * @param bool $isLate Whether the medication was taken late (past grace period)
     */
    public function createMedicationTakenNotification(int $elderlyProfileId, string $medicationName, bool $isLate = false): Notification
    {
        if ($isLate) {
            return $this->createNotification([
                'elderly_id' => $elderlyProfileId,
                'type' => 'medication_taken_late',
                'title' => '⚠️ Medication Taken (Late)',
                'message' => "You've taken {$medicationName}, but it was past the scheduled time. Try to take it on time next dose!",
                'severity' => 'warning',
                'metadata' => [
                    'medicationName' => $medicationName,
                    'takenAt' => now()->toIso8601String(),
                    'wasLate' => true,
                ],
            ]);
        }
        
        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'medication_taken',
            'title' => '✓ Medication Taken',
            'message' => "Great job! You've taken {$medicationName} on time.",
            'severity' => 'positive',
            'metadata' => [
                'medicationName' => $medicationName,
                'takenAt' => now()->toIso8601String(),
                'wasLate' => false,
            ],
        ]);
    }

    /**
     * Create medication missed notification
     */
    public function createMedicationMissedNotification(int $elderlyProfileId, string $medicationName, ?string $scheduledTime = null): Notification
    {
        $message = "You missed {$medicationName}";
        if ($scheduledTime) {
            $message .= " scheduled for {$scheduledTime}";
        }
        $message .= ". Please take it as soon as possible or consult your caregiver.";
        
        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'medication_missed',
            'title' => '⚠️ Medication Missed',
            'message' => $message,
            'severity' => 'negative',
            'metadata' => [
                'medicationName' => $medicationName,
                'scheduledTime' => $scheduledTime,
            ],
        ]);
    }

    /**
     * Create daily reminder notification (for vitals, mood, etc.)
     */
    public function createDailyReminderNotification(int $elderlyProfileId, string $reminderType): Notification
    {
        $reminders = [
            'vitals' => [
                'title' => '📊 Daily Vitals Reminder',
                'message' => "Don't forget to log your vitals today! Keeping track helps monitor your health.",
            ],
            'mood' => [
                'title' => '😊 How are you feeling?',
                'message' => "Take a moment to log your mood. It helps track your overall wellness.",
            ],
            'medication' => [
                'title' => '💊 Medication Reminder',
                'message' => "You have medications scheduled for today. Check your medication list!",
            ],
        ];
        
        $reminder = $reminders[$reminderType] ?? [
            'title' => '⏰ Daily Reminder',
            'message' => 'You have pending health activities for today.',
        ];
        
        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'daily_reminder',
            'title' => $reminder['title'],
            'message' => $reminder['message'],
            'severity' => 'reminder',
            'custom_id' => "daily_{$reminderType}_" . now()->format('Y-m-d'), // Prevent duplicates
        ]);
    }

    /**
     * Create health alert notification
     */
    public function createHealthAlertNotification(int $elderlyProfileId, string $message): Notification
    {
        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'health_alert',
            'title' => 'Health Alert',
            'message' => $message,
            'severity' => 'warning',
        ]);
    }

    /**
     * Create task completed notification
     */
    public function createTaskCompletedNotification(int $elderlyProfileId, string $taskName, string $category): Notification
    {
        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'task_completed',
            'title' => '✓ Task Completed',
            'message' => "{$taskName} completed successfully",
            'severity' => 'positive',
            'metadata' => [
                'taskName' => $taskName,
                'category' => $category,
                'completedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create vitals recorded notification
     */
    public function createVitalsRecordedNotification(int $elderlyProfileId, string $vitalType, string $value): Notification
    {
        $vitalNames = [
            'blood_pressure' => 'Blood Pressure',
            'heart_rate' => 'Heart Rate',
            'sugar_level' => 'Blood Sugar',
            'temperature' => 'Temperature',
        ];
        $vitalName = $vitalNames[$vitalType] ?? ucfirst(str_replace('_', ' ', $vitalType));

        return $this->createNotification([
            'elderly_id' => $elderlyProfileId,
            'type' => 'vitals_recorded',
            'title' => '📊 Vitals Recorded',
            'message' => "{$vitalName} recorded: {$value}",
            'severity' => 'positive',
            'metadata' => [
                'vitalType' => $vitalType,
                'value' => $value,
                'recordedAt' => now()->toIso8601String(),
            ],
        ]);
    }
}
