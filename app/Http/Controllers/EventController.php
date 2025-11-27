<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Project;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index()
    {
        try {
            return Inertia::render('Calendar/Index', [
                'projects'   => Project::select('id', 'title', 'due_date')->get(),
                'tasks'      => Task::select('id', 'title', 'project_id')->get(),
                'activities' => Activity::select('id', 'title', 'due_date')->get(),
                'events'     => Event::all()->map(fn($event) => [
                    'id'          => $event->id,
                    'title'       => $event->title,
                    'start'       => $event->start_date,
                    'description' => $event->description,
                    'color'       => $event->color,
                    'project_id'  => $event->project_id,
                    'task_id'     => $event->task_id,
                    'activity_id' => $event->activity_id,
                ])
            ]);

        } catch (\Exception $e) {
            \Log::error('Calendar error: ' . $e->getMessage());

            return Inertia::render('Calendar/Index', [
                'projects'   => Project::select('id', 'title', 'due_date')->get(),
                'tasks'      => Task::select('id', 'title', 'project_id')->get(),
                'activities' => Activity::select('id', 'title', 'due_date')->get(),
                'events'     => Event::all()
            ]);
        }
    }

   public function store(Request $request)
{
    $data = $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'nullable|string',
        'start_date'  => 'required|date',
        'end_date'    => 'nullable|date',
        'project_id'  => 'nullable|exists:projects,id',
        'task_id'     => 'nullable|exists:tasks,id',
        'activity_id' => 'nullable|exists:activities,id',
        'color'       => 'nullable|string',
    ]);

    // Default color logic
    if (empty($data['color'])) {
        if ($data['project_id']) $data['color'] = '#ef4444';
        elseif ($data['activity_id']) $data['color'] = '#22c55e';
        elseif ($data['task_id']) $data['color'] = '#facc15';
        else $data['color'] = '#3b82f6';
    }

    Event::create([
        'title'        => $data['title'],
        'description'  => $data['description'],
        'start_date'   => $data['start_date'],
        'end_date'     => $data['end_date'] ?? $data['start_date'],
        'project_id'   => $data['project_id'],
        'task_id'      => $data['task_id'],
        'activity_id'  => $data['activity_id'],
        'color'        => $data['color'],
        'created_by'   => Auth::id(),
    ]);

    // RETURN UPDATED DATA DIRECTLY
    return Inertia::render('Calendar/Index', [
        'projects'   => Project::select('id', 'title', 'due_date')->get(),
        'tasks'      => Task::select('id', 'title', 'project_id')->get(),
        'activities' => Activity::select('id', 'title', 'due_date')->get(),
        'events'     => Event::all()->map(fn($event) => [
            'id'          => $event->id,
            'title'       => $event->title,
            'start'       => $event->start_date,
            'description' => $event->description,
            'color'       => $event->color,
            'project_id'  => $event->project_id,
            'task_id'     => $event->task_id,
            'activity_id' => $event->activity_id,
        ])
    ]);
}

}
