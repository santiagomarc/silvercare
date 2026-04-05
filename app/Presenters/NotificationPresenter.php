<?php

namespace App\Presenters;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationPresenter
{
    private const CONFIG = [
        'medication_taken'      => ['icon' => '💊', 'color' => 'green'],
        'medication_taken_late' => ['icon' => '⚠️', 'color' => 'amber'],
        'medication_missed'     => ['icon' => '❌', 'color' => 'red'],
        'medication_refill'     => ['icon' => '💊', 'color' => 'amber'],
        'medication_refill_caregiver' => ['icon' => '🧑‍⚕️', 'color' => 'amber'],
        'caregiver_unlinked'    => ['icon' => '🔗', 'color' => 'amber'],
        'task_completed'        => ['icon' => '✅', 'color' => 'green'],
        'vitals_recorded'       => ['icon' => '📊', 'color' => 'blue'],
        'daily_reminder'        => ['icon' => '🔔', 'color' => 'blue'],
        'health_alert'          => ['icon' => '⚠️', 'color' => 'amber'],
    ];

    /**
     * Transform a collection of Notification models into caregiver activity items.
     */
    public static function toCaregiverActivity($notifications): \Illuminate\Support\Collection
    {
        return $notifications->map(fn (Notification $n) => [
            'type'      => 'notification_' . $n->type,
            'title'     => static::caregiverTitle($n),
            'subtitle'  => static::subtitle($n),
            'timestamp' => $n->created_at,
            'icon'      => static::CONFIG[$n->type]['icon']  ?? '🔔',
            'color'     => static::CONFIG[$n->type]['color'] ?? 'gray',
            'severity'  => $n->severity,
        ]);
    }

    public static function toElderlyFeed($notifications): Collection
    {
        return $notifications->map(fn (Notification $notification) => [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'severity' => $notification->severity,
            'is_read' => $notification->is_read,
            'relative_time' => $notification->created_at->diffForHumans(),
            'timestamp' => $notification->created_at->toISOString(),
        ]);
    }

    /**
     * Adapt notification title for caregiver context.
     */
    public static function caregiverTitle(Notification $notification): string
    {
        $elderName = $notification->elderly?->user?->name ?? 'Patient';
        $firstName = explode(' ', $elderName)[0];
        $meta = $notification->metadata ?? [];

        return match ($notification->type) {
            'medication_taken'      => "{$firstName} took " . ($meta['medicationName'] ?? 'medication'),
            'medication_taken_late' => "{$firstName} took " . ($meta['medicationName'] ?? 'medication') . ' (late)',
            'medication_missed'     => "{$firstName} missed " . ($meta['medicationName'] ?? 'medication'),
            'medication_refill'     => "{$firstName} is low on " . ($meta['medication_name'] ?? 'medication'),
            'medication_refill_caregiver' => "{$firstName} needs refill: " . ($meta['medication_name'] ?? 'medication'),
            'caregiver_unlinked'    => "{$firstName} unlinked a caregiver",
            'task_completed'        => "{$firstName} completed " . ($meta['taskName'] ?? 'a task'),
            'vitals_recorded'       => "{$firstName} recorded " . ($meta['vitalType'] ?? 'vital'),
            'daily_reminder'        => "Reminder sent to {$firstName}",
            'health_alert'          => "Health alert for {$firstName}",
            default                 => "{$firstName}: " . $notification->title,
        };
    }

    /**
     * Format notification subtitle with relevant details.
     */
    public static function subtitle(Notification $notification): string
    {
        $meta = $notification->metadata ?? [];

        return match ($notification->type) {
            'medication_taken', 'medication_taken_late' => isset($meta['takenAt'])
                ? Carbon::parse($meta['takenAt'])->format('g:i A')
                : 'Dose recorded',
            'medication_refill', 'medication_refill_caregiver' => isset($meta['days_remaining'])
                ? '~' . $meta['days_remaining'] . ' day(s) left'
                : 'Refill soon',
            'medication_missed' => $meta['scheduledTime'] ?? 'Scheduled dose',
            'vitals_recorded'   => ($meta['value'] ?? '') ?: ucfirst(str_replace('_', ' ', $meta['vitalType'] ?? '')),
            'task_completed'    => ucfirst($meta['category'] ?? 'Task'),
            default             => ucfirst($notification->severity),
        };
    }
}
