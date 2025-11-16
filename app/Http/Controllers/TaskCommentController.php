<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = auth()->user();

        // Only the project creator or Super Admin can comment
        if ($user->id !== $task->project->created_by && !$user->hasRole('Super Admin')) {
            abort(403, 'You are not allowed to comment on this task.');
        }

        // Create the comment
        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        // If task is completed, revert to In Progress and adjust project progress
        if ($task->status === 'Completed') {
            $task->update(['status' => 'In Progress']);

            $project = $task->project;

            $completedTasks = $project->tasks()->where('status', 'Completed')->count();
            $totalTasks = max($project->tasks()->count(), 1);
            $progress = round(($completedTasks / $totalTasks) * 100);

            $project->update(['progress' => $progress]);
        }

        return back()->with('success', 'Comment added successfully.');
    }
}
