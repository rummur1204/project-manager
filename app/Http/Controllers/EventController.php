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
            $user = Auth::user();
            
            // Get user permissions
            $permissions = [
                'calendar' => [
                    'create' => $user->can('create events'),
                    'edit' => $user->can('edit events'),
                    'delete' => $user->can('delete events'),
                    'view' => $user->can('view events'),
                ]
            ];

            return Inertia::render('Calendar/Index', [
                'projects'   => Project::select('id', 'title', 'due_date')->get(),
                'tasks'      => Task::select('id', 'title', 'project_id')->get(),
                'activities' => Activity::select('id', 'title', 'due_date')->get(),
                'events'     => Event::all()->map(fn($event) => [
                    'id'          => $event->id,
                    'title'       => $event->title,
                    'start'       => $event->start_date,
                    'end_date'    => $event->end_date,
                    'description' => $event->description,
                    'color'       => $event->color,
                    'project_id'  => $event->project_id,
                    'task_id'     => $event->task_id,
                    'activity_id' => $event->activity_id,
                    'created_by'  => $event->created_by,
                ]),
                'permissions' => $permissions,
                'auth' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                         'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $user->getRoleNames(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Calendar error: ' . $e->getMessage());

            return Inertia::render('Calendar/Index', [
                'projects'   => Project::select('id', 'title', 'due_date')->get(),
                'tasks'      => Task::select('id', 'title', 'project_id')->get(),
                'activities' => Activity::select('id', 'title', 'due_date')->get(),
                'events'     => Event::all(),
                'permissions' => [
                    'calendar' => [
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'view' => false,
                    ]
                ]
            ]);
        }
    }

    public function store(Request $request)
    {
        // Check permission
        if (!Auth::user()->can('create events')) {
            abort(403, 'Unauthorized action.');
        }

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

        // Return updated data
        $user = Auth::user();
        $permissions = [
            'calendar' => [
                'create' => $user->can('create events'),
                'edit' => $user->can('edit events'),
                'delete' => $user->can('delete events'),
                'view' => $user->can('view events'),
            ]
        ];

        return Inertia::render('Calendar/Index', [
            'projects'   => Project::select('id', 'title', 'due_date')->get(),
            'tasks'      => Task::select('id', 'title', 'project_id')->get(),
            'activities' => Activity::select('id', 'title', 'due_date')->get(),
            'events'     => Event::all()->map(fn($event) => [
                'id'          => $event->id,
                'title'       => $event->title,
                'start'       => $event->start_date,
                'end_date'    => $event->end_date,
                'description' => $event->description,
                'color'       => $event->color,
                'project_id'  => $event->project_id,
                'task_id'     => $event->task_id,
                'activity_id' => $event->activity_id,
                'created_by'  => $event->created_by,
            ]),
            'permissions' => $permissions,
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ]
        ]);
    }

    public function update(Request $request, Event $event)
    {
        // Check permission
        if (!Auth::user()->can('edit events')) {
            abort(403, 'Unauthorized action.');
        }

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

        $event->update([
            'title'        => $data['title'],
            'description'  => $data['description'],
            'start_date'   => $data['start_date'],
            'end_date'     => $data['end_date'] ?? $data['start_date'],
            'project_id'   => $data['project_id'],
            'task_id'      => $data['task_id'],
            'activity_id'  => $data['activity_id'],
            'color'        => $data['color'],
        ]);

        // Return updated data
        $user = Auth::user();
        $permissions = [
            'calendar' => [
                'create' => $user->can('create events'),
                'edit' => $user->can('edit events'),
                'delete' => $user->can('delete events'),
                'view' => $user->can('view events'),
            ]
        ];

        return Inertia::render('Calendar/Index', [
            'projects'   => Project::select('id', 'title', 'due_date')->get(),
            'tasks'      => Task::select('id', 'title', 'project_id')->get(),
            'activities' => Activity::select('id', 'title', 'due_date')->get(),
            'events'     => Event::all()->map(fn($event) => [
                'id'          => $event->id,
                'title'       => $event->title,
                'start'       => $event->start_date,
                'end_date'    => $event->end_date,
                'description' => $event->description,
                'color'       => $event->color,
                'project_id'  => $event->project_id,
                'task_id'     => $event->task_id,
                'activity_id' => $event->activity_id,
                'created_by'  => $event->created_by,
            ]),
            'permissions' => $permissions,
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ]
        ]);
    }

    public function destroy(Event $event)
    {
        // Check permission
        if (!Auth::user()->can('delete events')) {
            abort(403, 'Unauthorized action.');
        }

        $event->delete();

        // Return updated data after deletion
        $user = Auth::user();
        $permissions = [
            'calendar' => [
                'create' => $user->can('create events'),
                'edit' => $user->can('edit events'),
                'delete' => $user->can('delete events'),
                'view' => $user->can('view events'),
            ]
        ];

        return Inertia::render('Calendar/Index', [
            'projects'   => Project::select('id', 'title', 'due_date')->get(),
            'tasks'      => Task::select('id', 'title', 'project_id')->get(),
            'activities' => Activity::select('id', 'title', 'due_date')->get(),
            'events'     => Event::all()->map(fn($event) => [
                'id'          => $event->id,
                'title'       => $event->title,
                'start'       => $event->start_date,
                'end_date'    => $event->end_date,
                'description' => $event->description,
                'color'       => $event->color,
                'project_id'  => $event->project_id,
                'task_id'     => $event->task_id,
                'activity_id' => $event->activity_id,
                'created_by'  => $event->created_by,
            ]),
            'permissions' => $permissions,
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ]
        ]);
    }
}