<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    // Show calendar page with events + synced project deadlines
    public function index()
    {
        return Inertia::render('Calendar/Index', [
            'events' => $this->getAllEvents(),
            // optionally pass projects for linking in the create/edit form
            'projects' => Project::select('id','title')->orderBy('title')->get(),
        ]);
    }

    // Collect events + project deadlines (projects always included)
    public function getAllEvents()
{
    // Manual events
    $events = Event::with(['project','task','creator'])->get()->map(function ($e) {
    return [
        'id' => 'event-'.$e->id,
    'title' => $e->title,
    'start' => $e->start_date,
    'end' => $e->end_date,
    'description' => $e->description,
    'type' => 'event',
    'event_id' => $e->id,
    'project_id' => $e->project_id,
    ];
});

$projects = Project::whereNotNull('due_date')->get()->map(function ($p) {
    return [
       'id' => 'project-'.$p->id,
    'title' => "Project: ".$p->title,
    'start' => $p->due_date,
    'end' => $p->due_date,
    'description' => 'Project deadline',
    'type' => 'project',
    ];
});


    return collect($events->toArray())
        ->merge($projects->toArray())
        ->values();
}


    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $data['created_by'] = Auth::id();

        Event::create($data);

        // return back so Inertia will refresh the page
        return back()->with('success', 'Event created');
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $event->update($data);

        return back()->with('success', 'Event updated');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return back()->with('success', 'Event deleted');
    }
}
