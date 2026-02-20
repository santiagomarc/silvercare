<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CalendarController extends Controller
{
    public function index()
    {
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

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'type' => 'required|in:Reminder,Appointment,Event',
        ]);

        CalendarEvent::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'type' => $request->type,
        ]);

        return back()->with('success', 'Event added successfully');
    }

    public function destroy(CalendarEvent $event)
    {
        // Ensure user owns the event
        if ($event->user_id !== Auth::id()) {
            abort(403);
        }

        $event->delete();
        return back()->with('success', 'Event deleted');
    }
}