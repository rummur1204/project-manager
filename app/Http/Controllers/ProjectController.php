<?php


namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Chat;
use App\Models\Comment;
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
        'tasks.*.weight' => 'numeric|min:0',
        'tasks.*.developer_ids' => 'array',
        'tasks.*.developer_ids.*' => 'exists:users,id',
    ]);


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

        // 2️⃣ Attach project developers (if any)
       if (!empty($data['developer_ids'])) {
         $project->developers()->attach($data['developer_ids']); 
        }

        // 3️⃣ Create Tasks and attach task developers
        if (!empty($data['tasks'])) {
            foreach ($data['tasks'] as $taskData) {

                $task = $project->tasks()->create([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? '',
                    'task_type' => $taskData['task_type'] ?? 'Gathering',
                    'weight' => $taskData['weight'] ?? 0,
                    'status' => 'Pending',
                ]);
 
               if (!empty($taskData['developer_ids'])) { 
                $task->developers()->attach($taskData['developer_ids']); 
               }
            }
        }

        // 4️⃣ Create Group Chat for the project
        $chat = Chat::create([
            'project_id' => $project->id,
            'type' => 'group',
            'name' => $project->title . ' Group Chat',
        ]);

        // 5️⃣ Attach chat participants: client, creator, developers, super admins
        $superAdminIds = User::role('Super Admin')->pluck('id')->toArray();
        $participantIds = array_merge(
            [$project->client_id, $project->created_by],
            $data['developer_ids'] ?? [],
            $superAdminIds
        );

        $chat->users()->sync($participantIds);

     

    return redirect()->route('projects.index')->with('success', 'Project created successfully!');
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
        'tasks.developers', // ✅ fixed from tasks.users to tasks.developers
        'tasks.comments.user',
        'comments.user',
    ]);

    return Inertia::render('Projects/Show', [
        'project' => $project,
    ]);
}

    // public function show(Project $project)
    // {
    //     $project->load([
    //         'client',
    //         'developers' => function ($q) {
    //             $q->withPivot('accepted');
    //         },
    //         'tasks.comments.user',
    //         'comments.user'
    //     ]);

    //     // Calculate progress (percentage of completed tasks)
    //     $completed = $project->tasks()->where('status', 'Completed')->count();
    //     $total = max($project->tasks()->count(), 1);
    //     $progress = round(($completed / $total) * 100, 2);

    //     $project->update(['progress' => $progress]);

    //     return Inertia::render('Projects/Show', [
    //         'project' => $project,
    //         'progress' => $progress,
    //     ]);
    // }

    // ======================
    //  EDIT
    // ======================
    public function edit(Project $project)
    {
        $clients = User::role('Client')->get();
        $developers = User::role('Developer')->get();

        $project->load(['users', 'tasks.users','tasks.comments.user']);
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
    ],
    'clients' => User::role('Client')->get(),
    'developers' => User::role('Developer')->get(),
        ]);
    }

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
        'tasks.*.weight' => 'numeric|min:0',
        'tasks.*.developer_ids' => 'array',
        'tasks.*.developer_ids.*' => 'exists:users,id',
    ]);

    $project->update($data);

    // 🔄 Sync project developers
    $project->users()->sync($data['developer_ids']);

    // Get all existing task IDs for this project
    $existingTaskIds = $project->tasks()->pluck('id')->toArray();

    // Track all tasks that were kept or updated
    $keptTaskIds = [];

    $totalWeight = array_sum(array_map(fn($t) => $t['weight'] ?? 0, $data['tasks']));
    if ($totalWeight <= 0) {
        $totalWeight = 1; // prevent division by zero
    }

    foreach ($data['tasks'] as $taskData) {
        $normalizedWeight = round(($taskData['weight'] ?? 0) / $totalWeight * 100, 2);


        
        if (!empty($taskData['id'])) {
            // ✅ Update existing task
            $task = Task::find($taskData['id']);
            $task->update([
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? '',
                'task_type' => $taskData['task_type'],
                'weight' => $normalizedWeight,
            ]);

            // 🔄 Only keep developers still on project
            $validDevelopers = array_intersect(
                $taskData['developer_ids'] ?? [],
                $data['developer_ids']
            );

            $task->users()->sync($validDevelopers);
            $keptTaskIds[] = $task->id;

        } else {
            // ➕ Create new task
            $task = $project->tasks()->create([
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? '',
                'task_type' => $taskData['task_type'],
                'weight' => $normalizedWeight
            ]);

            $task->users()->sync($taskData['developer_ids'] ?? []);
            $keptTaskIds[] = $task->id;
        }
    }

    // 🧹 Delete tasks that are no longer in form
    $toDelete = array_diff($existingTaskIds, $keptTaskIds);
    if (!empty($toDelete)) {
        Task::destroy($toDelete);
    }

    return redirect()
        ->route('projects.index', $project->id)
        ->with('success', 'Project updated successfully!');
}


    // ======================
    //  DESTROY
    // ======================
    public function destroy(Project $project)
    {
        $project->delete();
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

        $project->developers()->detach($user->id);

        if ($project->developers()->count() === 0) {
            $project->update(['status' => 'Pending']);
        }

        return back()->with('success', 'You have declined this project.');
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
}
