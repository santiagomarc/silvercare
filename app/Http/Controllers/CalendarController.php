<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CalendarController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', CalendarEvent::class);

        // Get events for the current user
        // Some environments may not yet have the `start_time` column
        // (migration not run or schema differs). Guard the query to
        // avoid throwing an SQL error by falling back to `created_at`.
        // Prefer ordering by `start_time` when available, otherwise fall
        // back to `event_date` (legacy) or `created_at`.
        if (Schema::hasColumn('calendar_events', 'start_time')) {
            $events = CalendarEvent::where('user_id', Auth::id())
                ->orderBy('start_time')
                ->get();
        } elseif (Schema::hasColumn('calendar_events', 'event_date')) {
            $events = CalendarEvent::where('user_id', Auth::id())
                ->orderBy('event_date')
                ->get();
        } else {
            $events = CalendarEvent::where('user_id', Auth::id())
                ->orderBy('created_at')
                ->get();
        }

        return view('calendar.index', compact('events'));
    }

    public function store(StoreCalendarEventRequest $request)
    {
        $this->authorize('create', CalendarEvent::class);

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