<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\ActivityType;
use App\Models\ProjectGithubLink;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    // ======================
    //  INDEX
    // ======================
    public function index()
    {
        $user = auth()->user();

        $projects = collect();

        // Check permissions to determine which projects the user can see
        if ($user->can('view all projects')) {
            // Full access: show all projects
            $projects = Project::with(['client', 'developers', 'tasks'])
                ->latest()
                ->get();
        } elseif ($user->can('view own projects')) {
            // Limited access: show projects where user is client or developer
            $projects = Project::with(['client', 'developers', 'tasks'])
                ->where(function($query) use ($user) {
                    $query->where('client_id', $user->id)
                          ->orWhereHas('developers', fn($q) => $q->where('user_id', $user->id))
                            ->orWhere('created_by', $user->id);
                })
                ->latest()
                ->get();
        }

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'auth' => [
                'can' => [
                    'view projects' => $user->can('view projects'),
                    'create projects' => $user->can('create projects'),
                    'edit projects' => $user->can('edit projects'),
                    'delete projects' => $user->can('delete projects'),
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $user->getRoleNames(),
                ],
            ],
        ]);
    }

    // ======================
    //  CREATE
    // ======================
    public function create()
    {
        $clients = User::role('Client')->get();
        $developers = User::role('Developer')->get();

        return Inertia::render('Projects/Create', [
            'clients' => $clients,
            'developers' => $developers,
        ]);
    }

    // ======================
    //  STORE
    // ======================
    public function store(Request $request)
    {
        \Log::info('Received project creation request', $request->all());

        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'client_id' => 'required|exists:users,id',
                'developer_ids' => 'array',
                'developer_ids.*' => 'exists:users,id',
                'due_date' => 'nullable|date',
                'tasks' => 'array',
                'tasks.*.title' => 'required|string|max:255',
                'tasks.*.description' => 'nullable|string',
                'tasks.*.task_type' => 'nullable|string',
                'tasks.*.weight' => 'numeric|min:0|max:100',
                'tasks.*.developer_ids' => 'array',
                'tasks.*.developer_ids.*' => 'exists:users,id',
                'github_links' => 'array',
                'github_links.*' => 'nullable|string',
            ]);

            \Log::info('Validation passed', $data);

            // 1️⃣ Create Project
            $project = Project::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'client_id' => $data['client_id'],
                'created_by' => auth()->id(),
                'status' => 'Pending',
                'progress' => 0,
                'due_date' => $data['due_date'] ?? null,
            ]);

            \Log::info('Project created', ['project_id' => $project->id]);

            // 2️⃣ Attach project developers (if any)
            if (!empty($data['developer_ids'])) {
                $project->developers()->attach($data['developer_ids']);
                \Log::info('Developers attached', $data['developer_ids']);
            }

            // 3️⃣ Create GitHub Links
            if (!empty($data['github_links'])) {
                foreach ($data['github_links'] as $link) {
                    if (!empty(trim($link))) {
                        ProjectGithubLink::create([
                            'project_id' => $project->id,
                            'url' => trim($link)
                        ]);
                    }
                }
                \Log::info('GitHub links created', $data['github_links']);
            }

            // 4️⃣ Create Tasks and attach task developers
            \Log::info('Starting task creation', ['task_count' => count($data['tasks'] ?? [])]);
            
            if (!empty($data['tasks'])) {
                foreach ($data['tasks'] as $index => $taskData) {
                    \Log::info("Creating task {$index}", $taskData);
                    
                    $task = $project->tasks()->create([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? '',
                        'task_type' => $taskData['task_type'] ?? 'Gathering',
                        'weight' => $taskData['weight'] ?? 0,
                        'status' => 'New',
                    ]);

                    \Log::info("Task created successfully", ['task_id' => $task->id, 'project_id' => $project->id]);

                    if (!empty($taskData['developer_ids'])) {
                        $task->developers()->attach($taskData['developer_ids']);
                        \Log::info("Task developers attached", $taskData['developer_ids']);
                    }
                }
                \Log::info('All tasks created successfully');
            } else {
                \Log::info('No tasks to create');
            }

            // 5️⃣ Create Group Chat for the project
            $chat = Chat::create([
                'project_id' => $project->id,
                'type' => 'group',
                'name' => $project->title . ' Group Chat',
            ]);

            \Log::info('Chat created', ['chat_id' => $chat->id]);

            // 6️⃣ Attach chat participants
            $superAdminIds = User::role('Super Admin')->pluck('id')->toArray();
            $participantIds = array_merge(
                [$project->client_id, $project->created_by],
                $data['developer_ids'] ?? [],
                $superAdminIds
            );

            $chat->users()->sync($participantIds);
            \Log::info('Chat participants synced', $participantIds);

            \Log::info('=== PROJECT CREATION COMPLETED SUCCESSFULLY ===');
            return redirect()->route('projects.index')->with('success', 'Project created successfully!');

        } catch (\Exception $e) {
            \Log::error('=== PROJECT CREATION FAILED ===');
            \Log::error('Error: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile());
            \Log::error('Line: ' . $e->getLine());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            return back()->withErrors(['error' => 'Failed to create project: ' . $e->getMessage()]);
        }
    }

    // ======================
    //  SHOW
    // ======================
   public function show(Project $project)
{
    $user = auth()->user();

    $projectUser = $project->users()->where('user_id', $user->id)->first();

    $isDeveloper = $projectUser && $user->hasRole('Developer');
    $hasAccepted = $projectUser && $projectUser->pivot->accepted;

    if ($isDeveloper && !$hasAccepted) {
        abort(403, 'You cannot view this project until you accept it.');
    }

    $project->load([
        'client',
        'developers' => fn($q) => $q->withPivot('accepted'),
        'tasks.developers',
        'tasks.comments.user',
        'comments.user',
        'projectGithubLinks',
        'activities.developers',
        'activities.type', // Add activities if you have them
        'creator',
    ]);

    // Load activity types - you'll need to adjust this based on your model
    $activityTypes = ActivityType::all(); // Or whatever your model is

    // Debug: Check if activity types are being loaded
   


    return Inertia::render('Projects/Show', [
        'project' => $project,
        'activity_types' => $activityTypes, // Add this line
    ]);
}

    // ======================
    //  EDIT
    // ======================
    public function edit(Project $project)
    {
        $clients = User::role('Client')->get();
        $developers = User::role('Developer')->get();

        $project->load(['users', 'tasks.users', 'tasks.comments.user', 'projectGithubLinks']);
        
        $tasks = $project->tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'task_type' => $task->task_type,
                'weight' => $task->weight,
                'status' => $task->status,
                'developer_ids' => $task->users->pluck('id')->toArray(),
                'comments' => $task->comments->map(fn($c) => [
                    'id' => $c->id,
                    'message' => $c->message,
                    'user' => ['id' => $c->user->id, 'name' => $c->user->name],
                ]),
            ];
        });

        return Inertia::render('Projects/Edit', [
            'project' => [
                ...$project->toArray(),
                'tasks' => $tasks,
                'developer_ids' => $project->users->pluck('id')->toArray(),
                'github_links' => $project->projectGithubLinks->pluck('url')->toArray(),
            ],
            'clients' => $clients,
            'developers' => $developers,
        ]);
    }

    // ======================
    //  UPDATE
    // ======================
   // ======================
//  UPDATE
// ======================
public function update(Request $request, Project $project)
{
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'client_id' => 'required|exists:users,id',
        'developer_ids' => 'array',
        'developer_ids.*' => 'exists:users,id',
        'due_date' => 'nullable|date',
        'tasks' => 'array',
        'tasks.*.id' => 'nullable|exists:tasks,id',
        'tasks.*.title' => 'required|string',
        'tasks.*.description' => 'nullable|string',
        'tasks.*.task_type' => 'required|string',
        'tasks.*.weight' => 'numeric|min:0|max:100',
        'tasks.*.developer_ids' => 'array',
        'tasks.*.developer_ids.*' => 'exists:users,id',
        'github_links' => 'array',
        'github_links.*' => 'nullable|url',
    ]);

    DB::transaction(function () use ($project, $data) {
        // Get current state before updates for comparison
        $oldClientId = $project->client_id;
        $oldDeveloperIds = $project->developers()->pluck('users.id')->toArray();
        
        \Log::info('Project Update - Before Changes:', [
            'old_client_id' => $oldClientId,
            'old_developer_ids' => $oldDeveloperIds,
            'new_client_id' => $data['client_id'],
            'new_developer_ids' => $data['developer_ids'] ?? [],
        ]);

        // Update project
        $project->update($data);

        // 🔄 Sync project developers
        $project->developers()->sync($data['developer_ids'] ?? []);

        // 🔄 UPDATE GROUP CHAT PARTICIPANTS
        $chat = Chat::where('project_id', $project->id)->where('type', 'group')->first();
        
        if ($chat) {
            // Get current chat participants
            $currentChatParticipants = $chat->users()->pluck('users.id')->toArray();
            
            // Build new participant list
            $superAdminIds = User::role('Super Admin')->pluck('id')->toArray();
            $newParticipantIds = array_merge(
                [$data['client_id'], $project->created_by],
                $data['developer_ids'] ?? [],
                $superAdminIds
            );
            $newParticipantIds = array_unique($newParticipantIds);
            
            \Log::info('Chat Update:', [
                'current_participants' => $currentChatParticipants,
                'new_participants' => $newParticipantIds,
                'clients_changed' => $oldClientId != $data['client_id'],
            ]);
            
            // Handle client change if client was updated
            if ($oldClientId != $data['client_id']) {
                // Remove old client from chat if they're not a developer
                if (!in_array($oldClientId, $data['developer_ids'] ?? [])) {
                    $chat->users()->detach($oldClientId);
                    \Log::info('Removed old client from chat:', ['user_id' => $oldClientId]);
                }
            }
            
            // Handle developers added/removed
            $removedDevelopers = array_diff($oldDeveloperIds, $data['developer_ids'] ?? []);
            $addedDevelopers = array_diff($data['developer_ids'] ?? [], $oldDeveloperIds);
            
            if (!empty($removedDevelopers)) {
                foreach ($removedDevelopers as $devId) {
                    // Only remove if they're not the new client
                    if ($devId != $data['client_id']) {
                        $chat->users()->detach($devId);
                        \Log::info('Removed developer from chat:', ['user_id' => $devId]);
                    }
                }
            }
            
            // Add new participants (including new client and added developers)
            $chat->users()->sync($newParticipantIds, false); // false = don't detach existing
            
            \Log::info('Chat participants updated:', [
                'added_developers' => $addedDevelopers,
                'removed_developers' => $removedDevelopers,
                'final_participants' => $chat->fresh()->users()->pluck('users.id')->toArray(),
            ]);
            
            // Update chat name
            $newChatName = $project->title . ' Group Chat';
            if ($chat->name != $newChatName) {
                $chat->update(['name' => $newChatName]);
            }
        }

        // 🔄 Update GitHub Links
        if (isset($data['github_links'])) {
            ProjectGithubLink::where('project_id', $project->id)->delete();
            
            foreach ($data['github_links'] as $link) {
                if (!empty(trim($link))) {
                    ProjectGithubLink::create([
                        'project_id' => $project->id,
                        'url' => trim($link)
                    ]);
                }
            }
        }

        // 🔄 Update Tasks
        $existingTaskIds = $project->tasks()->pluck('id')->toArray();
        $keptTaskIds = [];

        $totalWeight = array_sum(array_map(fn($t) => $t['weight'] ?? 0, $data['tasks'] ?? []));
        if ($totalWeight <= 0) $totalWeight = 1;

        foreach (($data['tasks'] ?? []) as $taskData) {
            $normalizedWeight = round(($taskData['weight'] ?? 0) / $totalWeight * 100, 2);

            if (!empty($taskData['id'])) {
                // Update existing task
                $task = Task::find($taskData['id']);
                if ($task) {
                    $task->update([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? '',
                        'task_type' => $taskData['task_type'],
                        'weight' => $normalizedWeight,
                    ]);

                    // Sync task developers with project developers
                    $validDevelopers = array_intersect(
                        $taskData['developer_ids'] ?? [],
                        $data['developer_ids'] ?? []
                    );
                    $task->developers()->sync($validDevelopers);
                    
                    $keptTaskIds[] = $task->id;
                }
            } else {
                // Create new task
                $task = $project->tasks()->create([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? '',
                    'task_type' => $taskData['task_type'],
                    'weight' => $normalizedWeight,
                    'status' => 'New'
                ]);

                $task->developers()->sync($taskData['developer_ids'] ?? []);
                $keptTaskIds[] = $task->id;
            }
        }

        // Delete removed tasks
        $toDelete = array_diff($existingTaskIds, $keptTaskIds);
        if (!empty($toDelete)) {
            Task::destroy($toDelete);
        }

        // Cleanup: Remove removed developers from all tasks
        if (!empty($removedDevelopers)) {
            foreach ($removedDevelopers as $devId) {
                DB::table('task_user')
                    ->whereIn('task_id', $project->tasks()->pluck('id')->toArray())
                    ->where('user_id', $devId)
                    ->delete();
            }
        }

        // Update project progress
        $this->updateProjectProgress($project);
    });

    return redirect()
        ->route('projects.index')
        ->with('success', 'Project updated successfully!');
}

    // ======================
    //  DESTROY
    // ======================
    public function destroy(Project $project)
    {
        DB::transaction(function () use ($project) {
            // This will automatically delete related GitHub links due to cascade delete
            $project->delete();
        });

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully!');
    }

    // ======================
    //  ACCEPT / DECLINE
    // ======================
    public function accept(Project $project)
    {
        $user = auth()->user();

        if (!$project->developers()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not assigned to this project.');
        }

        $project->developers()->updateExistingPivot($user->id, ['accepted' => true]);

        $allAccepted = $project->developers()->wherePivot('accepted', false)->doesntExist();

        if ($allAccepted) {
            $project->update(['status' => 'In Progress']);
            $project->tasks()->update(['status' => 'In Progress']);
        }

        return back()->with('success', 'Project accepted successfully!');
    }

   public function decline(Project $project)
{
    $user = auth()->user();

    if (!$project->developers()->where('user_id', $user->id)->exists()) {
        abort(403, 'You are not assigned to this project.');
    }

    DB::transaction(function () use ($project, $user) {
        \Log::info('User declining project:', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'project_id' => $project->id,
        ]);

        // Remove from project developers
        $project->developers()->detach($user->id);
        
        // Remove from project chat
        $chat = Chat::where('project_id', $project->id)->where('type', 'group')->first();
        if ($chat) {
            // Remove user from chat participants
            $chat->users()->detach($user->id);
            
            // Double-check removal with direct DB query
            DB::table('chat_user')
                ->where('chat_id', $chat->id)
                ->where('user_id', $user->id)
                ->delete();
        }
        
        // Remove from all project tasks
        DB::table('task_user')
            ->whereIn('task_id', $project->tasks()->pluck('id')->toArray())
            ->where('user_id', $user->id)
            ->delete();
        
        // Update project status if no developers left
        if ($project->developers()->count() === 0) {
            $project->update(['status' => 'Pending']);
        }
        
        // Update project progress
        $this->updateProjectProgress($project);
        
        \Log::info('User successfully declined project:', [
            'user_id' => $user->id,
            'remaining_developers' => $project->developers()->count(),
        ]);
    });

    return back()->with('success', 'You have declined this project.');
}

    // ======================
    //  TASK MANAGEMENT - NEW METHODS
    // ======================

    /**
     * Store a new task for the project
     */
    public function storeTask(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string|in:Design,Development,Testing,Deployment,Documentation',
            'weight' => 'required|numeric|min:0|max:100',
            'developer_ids' => 'array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($project, $data) {
            $task = $project->tasks()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'task_type' => $data['task_type'],
                'weight' => $data['weight'],
                'status' => 'New',
                'due_date' => $data['due_date'] ?? null,
            ]);

            if (!empty($data['developer_ids'])) {
                $task->developers()->attach($data['developer_ids']);
            }

            // Update project progress
            $this->updateProjectProgress($project);
        });

        return back()->with('success', 'Task added successfully!');
    }

    /**
     * Update task status (mark as seen/in progress/completed)
     */
    public function updateTaskStatus(Request $request, Project $project, Task $task)
    {
        if ($task->project_id !== $project->id) {
            abort(403, 'Task does not belong to this project.');
        }

        $data = $request->validate([
            'status' => 'required|in:New,In Progress,Completed'
        ]);

        $task->update(['status' => $data['status']]);

        // Update project progress
        $this->updateProjectProgress($project);

        return back()->with('success', 'Task status updated successfully!');
    }

    /**
     * Update task details
     */
    public function updateTask(Request $request, Project $project, Task $task)
    {
        if ($task->project_id !== $project->id) {
            abort(403, 'Task does not belong to this project.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|string|in:Design,Development,Testing,Deployment,Documentation',
            'weight' => 'required|numeric|min:0|max:100',
            'developer_ids' => 'array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($task, $data) {
            $task->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'task_type' => $data['task_type'],
                'weight' => $data['weight'],
                'due_date' => $data['due_date'] ?? null,
            ]);

            if (isset($data['developer_ids'])) {
                $task->developers()->sync($data['developer_ids']);
            }
        });

        return back()->with('success', 'Task updated successfully!');
    }

    /**
     * Delete a task
     */
    public function destroyTask(Project $project, Task $task)
    {
        if ($task->project_id !== $project->id) {
            abort(403, 'Task does not belong to this project.');
        }

        DB::transaction(function () use ($task, $project) {
            $task->delete();
            $this->updateProjectProgress($project);
        });

        return back()->with('success', 'Task deleted successfully!');
    }

    /**
     * Helper method to update project progress based on tasks
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

    // ======================
    //  ADD PROJECT COMMENT
    // ======================
    public function addComment(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'urgency' => 'required|in:Normal,High,Critical',
        ]);

        $project->comments()->create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'message' => $data['message'],
            'urgency' => $data['urgency'],
        ]);

        // Optional: Reduce progress slightly if urgent comment
        if (in_array($data['urgency'], ['High', 'Critical'])) {
            $project->decrement('progress', 5);
        }

        return back()->with('success', 'Comment added successfully!');
    }

    public function storeComment(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $message = strtolower($data['message']);
        $urgency = 'Normal';

        if (preg_match('/\b(urgent|critical|immediate|asap|emergency)\b/', $message)) {
            $urgency = 'Critical';
        } elseif (preg_match('/\b(soon|important|high priority)\b/', $message)) {
            $urgency = 'High';
        }

        $project->comments()->create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'message' => $data['message'],
            'urgency' => $urgency,
        ]);

        return back()->with('success', 'Comment added successfully!');
    }

    // ======================
    //  GITHUB LINKS MANAGEMENT
    // ======================
    public function addGithubLink(Request $request, Project $project)
    {
        $data = $request->validate([
            'url' => 'required|url',
        ]);

        ProjectGithubLink::create([
            'project_id' => $project->id,
            'url' => $data['url']
        ]);

        return back()->with('success', 'GitHub link added successfully!');
    }

    public function removeGithubLink(Project $project, ProjectGithubLink $githubLink)
    {
        if ($githubLink->project_id !== $project->id) {
            abort(403, 'Unauthorized action.');
        }

        $githubLink->delete();

        return back()->with('success', 'GitHub link removed successfully!');
    }

    
}