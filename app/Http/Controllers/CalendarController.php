<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Load all events for FullCalendar
     */
    public function index()
    {
        $events = Event::with(['project', 'task'])
            ->where('created_by', auth()->id())
            ->orWhereNotNull('project_id')  // include project deadlines for all members
            ->get();

        return inertia('Calendar/Index', [
            'events' => $events
        ]);
    }

    /**
     * Create an event (normal or task deadline)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date',
            'project_id'  => 'nullable|exists:projects,id',
            'task_id'     => 'nullable|exists:tasks,id',
            'type'        => 'required|in:normal,project_deadline,task_deadline',
            'color'       => 'nullable|string'
        ]);

        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);

        return back()->with('success', 'Event created!');
    }

    /**
     * Update event details (drag, resize, edit form)
     */
    public function update(Request $request, Event $event)
    {
        $event->update([
            'title'      => $request->title,
            'description'=> $request->description,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'color'      => $request->color,
        ]);

        return back()->with('success', 'Event updated!');
    }

    /**
     * Delete event
     */
    public function destroy(Event $event)
    {
        $event->delete();
        return back()->with('success', 'Event deleted');
    }
}
