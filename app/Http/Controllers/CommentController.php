<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function addProjectComment(Request $request, Project $project)
{
    $data = $request->validate([
        'title' => 'nullable|string|max:255',
        'message' => 'required|string',
    ]);

    $message = strtolower($data['message']);
    $urgency = 'Normal';

    // Detect urgency keywords
    if (preg_match('/\b(urgent|critical|immediate|asap|emergency)\b/', $message)) {
        $urgency = 'Critical';
    } elseif (preg_match('/\b(soon|important|high priority)\b/', $message)) {
        $urgency = 'High';
    }

    $project->comments()->create([
        'user_id' => auth()->id(),
        'title' => $data['title'] ?? null,
        'message' => $data['message'],
        'urgency' => $urgency,
    ]);

    return back()->with('success', 'Comment added successfully!');
}

public function addTaskComment(Request $request, Task $task)
{
    $user = auth()->user();

    // Only project creator can comment
    if ($task->project->created_by !== $user->id) {
        abort(403, 'Only the project creator can comment on tasks.');
    }

    $data = $request->validate([
        'title' => 'nullable|string|max:255',
        'message' => 'required|string',
    ]);

    $task->comments()->create([
        'user_id' => $user->id,
        'title' => $data['title'] ?? null,
        'message' => $data['message'],
        'urgency' => 'Normal',
    ]);

    // If task was completed → revert it to In Progress
    if ($task->status === 'Completed') {
        $task->update(['status' => 'In Progress']);

        // Update project progress
        $project = $task->project;
        $completed = $project->tasks()->where('status', 'Completed')->count();
        $total = max($project->tasks()->count(), 1);
        $progress = round(($completed / $total) * 100, 2);
        $project->update(['progress' => $progress]);
    }

    return back()->with('success', 'Task comment added and task reopened.');
}


    // public function store(Request $request, Task $task)
    // {
    //     $data = $request->validate([
    //         'title' => 'required|string|max:255',
    //         'message' => 'required|string',
    //         'urgency' => 'required|in:Normal,High,Critical',
    //     ]);

    //     $task->comments()->create([
    //         'user_id' => auth()->id(),
    //         'title' => $data['title'],
    //         'message' => $data['message'],
    //         'urgency' => $data['urgency'],
    //     ]);

    //     // Optional: Decrease project progress on new comment
    //     $project = $task->project;
    //     $project->progress = max(0, $project->progress - 5);
    //     $project->save();

    //     return back()->with('success', 'Comment added successfully.');
    // }
}

