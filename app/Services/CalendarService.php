<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Add a calendar event
     */
    public function addEvent(array $data): CalendarEvent
    {
        return CalendarEvent::create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'],
            'event_type' => $data['event_type'] ?? null, // Reminder, Appointment, Medication, etc.
        ]);
    }

    /**
     * Update calendar event
     */
    public function updateEvent(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update([
            'title' => $data['title'] ?? $event->title,
            'description' => $data['description'] ?? $event->description,
            'event_date' => $data['event_date'] ?? $event->event_date,
            'event_type' => $data['event_type'] ?? $event->event_type,
        ]);

        return $event->fresh();
    }

    /**
     * Delete calendar event
     */
    public function deleteEvent(int $eventId): bool
    {
        $event = CalendarEvent::findOrFail($eventId);
        return $event->delete();
    }

    /**
     * Get all events for user
     */
    public function getEventsForUser(int $userId): Collection
    {
        return CalendarEvent::where('user_id', $userId)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Get events for specific date
     */
    public function getEventsForDate(int $userId, Carbon $date): Collection
    {
        return CalendarEvent::where('user_id', $userId)
            ->whereDate('event_date', $date)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Get events for date range
     */
    public function getEventsForDateRange(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return CalendarEvent::where('user_id', $userId)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Get upcoming events (next 7 days)
     */
    public function getUpcomingEvents(int $userId, int $days = 7): Collection
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);

        return $this->getEventsForDateRange($userId, $startDate, $endDate);
    }

    /**
     * Get today's events
     */
    public function getTodaysEvents(int $userId): Collection
    {
        return $this->getEventsForDate($userId, Carbon::today());
    }

    /**
     * Get events by type
     */
    public function getEventsByType(int $userId, string $eventType): Collection
    {
        return CalendarEvent::where('user_id', $userId)
            ->where('event_type', $eventType)
            ->orderBy('event_date', 'asc')
            ->get();
    }
}
