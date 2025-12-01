<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

        // Recalculate project progress using normalized weights
        $totalWeight = $project->tasks()->sum('weight') ?: 1;
        $completedWeight = $project->tasks()->where('status', 'Completed')->sum('weight');
        $progress = round(($completedWeight / $totalWeight) * 100);
        
        $project->update(['progress' => $progress]);

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

        // Update project progress
        $this->updateProjectProgress($project);

        return back();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string',
            'raw_weight' => 'required|numeric|min:1|max:5', // Store raw weight (1-5)
        ]);

        DB::transaction(function () use ($project, $data) {
            // Create task with raw weight first
            $task = $project->tasks()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'task_type' => $data['task_type'],
                'raw_weight' => $data['raw_weight'],
                'weight' => 0, // Will be recalculated
                'status' => 'New',
            ]);

            // Recalculate all normalized weights
            $this->recalculateTaskWeights($project);
        });

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string',
            'raw_weight' => 'required|numeric|min:1|max:5', // Update raw weight
            'status' => 'nullable|string',
        ]);

        DB::transaction(function () use ($project, $task, $data) {
            $task->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'task_type' => $data['task_type'],
                'raw_weight' => $data['raw_weight'],
                'status' => $data['status'] ?? $task->status,
            ]);

            if (!empty($request->developer_ids)) {
                $task->users()->sync($request->developer_ids);
            } else {
                $task->users()->sync([]);
            }

            // Recalculate all normalized weights
            $this->recalculateTaskWeights($project);
        });

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(Project $project, Task $task)
    {
        DB::transaction(function () use ($project, $task) {
            $task->delete();
            
            // Recalculate weights for remaining tasks
            $this->recalculateTaskWeights($project);
        });

        return back()->with('success', 'Task deleted.');
    }

    /**
     * Update only task status
     */
    /**
 * Update only task status
 */
public function updateStatus(Request $request, Project $project, Task $task)
{
    // Verify task belongs to project
    if ($task->project_id !== $project->id) {
        abort(403, 'Task does not belong to this project.');
    }

    $user = auth()->user();
    $data = $request->validate([
        'status' => 'required|in:Pending,In Progress,Completed'
    ]);

    // Check if user is assigned to this task (if they're a developer)
    $isAssignedDeveloper = $project->developers()->where('user_id', $user->id)->exists();
    if ($isAssignedDeveloper) {
        $isTaskAssignedToUser = $task->developers()->where('user_id', $user->id)->exists();
        if (!$isTaskAssignedToUser) {
            abort(403, 'You are not assigned to this task.');
        }
    }

    $task->update(['status' => $data['status']]);

    // Update project progress
    $this->updateProjectProgress($project);

    return back()->with('success', 'Task status updated successfully.');
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
            $this->updateProjectProgress($task->project);
        }

        return back()->with('success', 'Comment added to task.');
    }

    /**
     * Recalculate normalized weights for all tasks in project
     */
    private function recalculateTaskWeights(Project $project)
    {
        $tasks = $project->tasks;
        $totalRawWeight = $tasks->sum('raw_weight');

        if ($totalRawWeight > 0) {
            foreach ($tasks as $task) {
                $normalizedWeight = round(($task->raw_weight / $totalRawWeight) * 100, 2);
                $task->update(['weight' => $normalizedWeight]);
            }
        }

        // Update project progress after recalculating weights
        $this->updateProjectProgress($project);
    }

    /**
     * Update project progress based on normalized weights
     */
    private function updateProjectProgress(Project $project)
    {
        $tasks = $project->tasks;
        
        if ($tasks->isEmpty()) {
            $project->update(['progress' => 0]);
            return;
        }

        $totalWeight = $tasks->sum('weight') ?: 1;
        $completedWeight = $tasks->where('status', 'Completed')->sum('weight');
        
        $progress = round(($completedWeight / $totalWeight) * 100);
        $project->update(['progress' => $progress]);
    }

    /**
 * Bulk update tasks for a project
 */
public function bulkUpdate(Request $request, Project $project)
{
    \Log::info('🎯 TASK CONTROLLER BULK UPDATE STARTED for project: ' . $project->id);

    try {
        // Validate the request
        $validated = $request->validate([
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.task_type' => 'required|string',
            'tasks.*.raw_weight' => 'required|numeric|min:1|max:5',
            'tasks.*.weight' => 'required|numeric|min:0|max:100',
            'tasks.*.developer_ids' => 'sometimes|array',
            'tasks.*.developer_ids.*' => 'exists:users,id',
            'tasks.*.status' => 'required|in:Pending,In Progress,Completed', // ✅ Only these 3 statuses
        ]);

        \Log::info('✅ VALIDATION PASSED - Task count: ' . count($validated['tasks']));

        DB::beginTransaction();

        $existingTaskIds = $project->tasks()->pluck('id')->toArray();
        $updatedTaskIds = [];

        foreach ($validated['tasks'] as $taskData) {
            \Log::info('📝 Processing task:', [
                'id' => $taskData['id'] ?? 'NULL (new task)',
                'title' => $taskData['title'],
                'raw_weight' => $taskData['raw_weight'],
                'weight' => $taskData['weight'],
                'status' => $taskData['status']
            ]);

            if (isset($taskData['id']) && !empty($taskData['id'])) {
                // Update existing task
                $task = Task::where('id', $taskData['id'])
                           ->where('project_id', $project->id)
                           ->first();

                if ($task) {
                    $task->update([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? null,
                        'task_type' => $taskData['task_type'],
                        'raw_weight' => $taskData['raw_weight'],
                        'weight' => $taskData['weight'],
                        'status' => $taskData['status'],
                    ]);

                    // Sync developers
                    if (isset($taskData['developer_ids'])) {
                        $task->developers()->sync($taskData['developer_ids']);
                    }

                    $updatedTaskIds[] = $task->id;
                    \Log::info("✅ Updated existing task: {$task->id}");
                }
            } else {
                // Create new task
                $task = $project->tasks()->create([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'task_type' => $taskData['task_type'],
                    'raw_weight' => $taskData['raw_weight'],
                    'weight' => $taskData['weight'],
                    'status' => $taskData['status'],
                ]);

                // Sync developers
                if (isset($taskData['developer_ids'])) {
                    $task->developers()->sync($taskData['developer_ids']);
                }

                $updatedTaskIds[] = $task->id;
                \Log::info("✅ Created new task: {$task->id}");
            }
        }

        // Delete tasks that were removed
        $tasksToDelete = array_diff($existingTaskIds, $updatedTaskIds);
        if (!empty($tasksToDelete)) {
            Task::whereIn('id', $tasksToDelete)->delete();
            \Log::info("🗑️ Deleted tasks: " . implode(', ', $tasksToDelete));
        }

        DB::commit();

        \Log::info('🎉 BULK UPDATE COMPLETED SUCCESSFULLY');

        return back()->with('success', 'Tasks updated successfully!');

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        \Log::error('❌ VALIDATION ERROR: ' . json_encode($e->errors()));
        return back()->withErrors($e->errors())->withInput();
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('❌ BULK UPDATE ERROR: ' . $e->getMessage());
        \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        return back()->with('error', 'Failed to update tasks: ' . $e->getMessage());
    }
}
}