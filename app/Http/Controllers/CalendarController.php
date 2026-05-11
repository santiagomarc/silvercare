<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $now = Carbon::now();

        // Determine which column to use for time ordering
        $timeColumn = match (true) {
            Schema::hasColumn('calendar_events', 'start_time') => 'start_time',
            Schema::hasColumn('calendar_events', 'event_date') => 'event_date',
            default => 'created_at',
        };

        $allEvents = CalendarEvent::where('user_id', Auth::id())
            ->orderBy($timeColumn)
            ->get();

        // Split into upcoming (now or future) and past (already happened)
        // Uses the full datetime — so a 9:59 PM event at 10:00 PM is already "past"
        $events     = $allEvents->filter(fn($e) => Carbon::parse($e->{$timeColumn})->greaterThan($now))->values();
        $pastEvents = $allEvents->filter(fn($e) => Carbon::parse($e->{$timeColumn})->lessThanOrEqualTo($now))
                                ->sortByDesc($timeColumn) // most recent past first
                                ->values();

        return view('calendar.index', compact('events', 'pastEvents'));
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $this->authorize('create', CalendarEvent::class);

        // Extra server-side guard: reject events scheduled in the past
        $startTime = Carbon::parse($request->validated()['start_time']);
        if ($startTime->lessThanOrEqualTo(Carbon::now())) {
            return back()
                ->withInput()
                ->withErrors(['start_time' => 'You cannot schedule an event in the past. Please choose a future date and time.']);
        }

        CalendarEvent::create([
            'user_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return back()->with('success', 'Event added successfully');
    }

    public function destroy(CalendarEvent $event)
    {
        $this->authorize('delete', $event);

        $event->delete();
        return back()->with('success', 'Event deleted');
    }
}