<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
   public function index(Project $project)
{
    $user = auth()->user();

    // Check if user is a developer on this project
    $projectUser = $project->users()->where('user_id', $user->id)->first();
    $isDeveloper = $projectUser && $user->hasRole('Developer');
    $hasAccepted = $projectUser && $projectUser->pivot->accepted;

    if ($isDeveloper && !$hasAccepted) {
        abort(403, 'You cannot access tasks until you accept this project.');
    }

    // Fetch tasks
    if ($isDeveloper) {
        // Only show tasks assigned to this developer
        $tasks = $project->tasks()
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->with(['users','comments.user'])
            ->get();
    } else {
        // Non-developers (client, super admin, creator) see all tasks
        $tasks = $project->tasks()->with(['users','comments.user'])->get();
    }

    // Recalculate project progress
    $completedWeight = $project->tasks()->where('status', 'Completed')->sum('weight');
    $project->update(['progress' => $completedWeight]);

    return Inertia::render('Tasks/Index', [
        'project' => $project,
        'tasks' => $tasks,
        'progress' => $project->progress,
        'auth' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->getRoleNames(),
            ],
            'can' => [
                'edit tasks' => $user->can('edit tasks'),
                'delete tasks' => $user->can('delete tasks'),
                'comment' => true,
                'is_developer' => $isDeveloper,
            ]
        ]
    ]);
}

    public function toggleStatus(Project $project, Task $task)
    {
        $task->status = $task->status === 'Completed' ? 'Pending' : 'Completed';
        $task->save();

        return back();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string',
            'weight' => 'numeric|min:0',
        ]);

        $project->tasks()->create($data);

        return back()->with('success', 'Task created successfully.');
    }
    //  public function update(Request $request, Task $task)
    // {
        
    //     $task->update(['status' => $request->status]);

    //     // Update project progress
    //     $project = $task->project;
    //     $completed = $project->tasks()->where('status', 'Completed')->sum('weight');
    //     $project->update([
    //         'progress' => $completed,
    //         'status' => $completed >= 100 ? 'Completed' : 'In Progress',
    //     ]);

    //     return back();
    // }
    public function update(Request $request, Project $project, Task $task)
{
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'task_type' => 'required|string',
        'weight' => 'numeric|min:0',
        'status' => 'nullable|string',
    ]);

    $task->update($data);
    if (!empty($request->developer_ids)) {
    $task->users()->sync($request->developer_ids);
} else {
    $task->users()->sync([]);
}


    // Update project progress based on task weights
    $completedWeight = $project->tasks()->where('status', 'Completed')->sum('weight');
    $project->update([
        'progress' => $completedWeight,
        'status' => $completedWeight >= 100 ? 'Completed' : 'In Progress',
    ]);

    // Return updated data to Inertia
    return inertia('Tasks/Index', [
        'project' => $project->fresh(),
        'tasks' => $project->tasks()->with(['comments.user'])->get(),
        'progress' => $project->progress,
        'auth' => [
            'user' => auth()->user(),
            'can' => [
                'edit tasks' => auth()->user()->can('edit tasks'),
                'delete tasks' => auth()->user()->can('delete tasks'),
                'comment' => true,
            ]
        ]
    ])->with('success', 'Task updated successfully.');
}



    public function destroy(Project $project, Task $task)
    {
        $task->delete();
        return back()->with('success', 'Task deleted.');
    }
     public function storeComment(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'message' => $data['message'],
        ]);

        // If task was completed, reset to In Progress
        if ($task->status === 'Completed') {
            $task->update(['status' => 'In Progress']);
            $project = $task->project;
            $completed = $project->tasks()->where('status', 'Completed')->sum('weight');
            $project->update(['progress' => $completed]);
        }

        return back()->with('success', 'Comment added to task.');
    }
}