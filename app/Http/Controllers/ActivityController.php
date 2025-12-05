<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    // ======================
    //  INDEX
    // ======================

   public function index()
{
    $user = auth()->user();

    if ($user->can('view all activities')) {
        $activities = Activity::with([
            'type',
            'project',
            'developers' => function ($q) {
                $q->select('users.id', 'name')->withPivot('accepted');
            }
        ])->latest()->get();

    } elseif ($user->can('view own activities')) {

        $activities = Activity::with([
            'type',
            'project',
            'developers' => function ($q) {
                $q->select('users.id', 'name')->withPivot('accepted');
            }
        ])
        ->where(function ($query) use ($user) {
            $query->where('created_by', $user->id)
                  ->orWhereHas('developers', fn($q) => $q->where('users.id', $user->id));
        })
        ->latest()->get();

    } else {
        $activities = Activity::with([
            'type',
            'project',
            'developers' => function ($q) {
                $q->select('users.id', 'name')->withPivot('accepted');
            }
        ])
        ->whereHas('developers', fn($q) => $q->where('users.id', $user->id))
        ->latest()->get();
    }

    return Inertia::render('Activities/Index', [
        'activities' => $activities,
        'auth' => [
            'can' => [
                'view activities' => $user->can('view activities'),
                'create activities' => $user->can('create activities'),
                'edit activities' => $user->can('edit activities'),
                'delete activities' => $user->can('delete activities'),
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
        if (!auth()->user()->can('create activities')) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Activities/Create', [
            'activityTypes' => ActivityType::all(),
            'developers' => User::role('Developer')->get(),
            'projects' => Project::select('id', 'title')->get(),
        ]);
    }

    // ======================
    //  STORE
    // ======================
    // ======================
//  STORE
// ======================
public function store(Request $request)
{
    if (!auth()->user()->can('create activities')) {
        abort(403, 'Unauthorized action.');
    }

    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'activity_type_id' => 'required|exists:activity_types,id',
        'project_id' => 'nullable|exists:projects,id', // Make it nullable
        'developer_ids' => 'required|array',
        'developer_ids.*' => 'exists:users,id',
        'due_date' => 'required|date',
    ]);

    // If project_id is not provided in the form, get it from the route parameter
    if (empty($data['project_id']) && $request->route('project')) {
        $data['project_id'] = $request->route('project')->id;
    }

    // If still no project_id, use a default or require it
    if (empty($data['project_id'])) {
        return redirect()->back()->with('error', 'Project is required.');
    }

    $data['created_by'] = auth()->id();
    $data['status'] = 'Pending';

    $activity = Activity::create($data);
    
    // Attach developers with accepted = false by default
    $developerData = [];
    foreach ($data['developer_ids'] as $developerId) {
        $developerData[$developerId] = ['accepted' => false];
    }
    $activity->developers()->sync($developerData);

    // Redirect back to the project page if created from project context
    if ($request->route('project')) {
        return redirect()->route('projects.show', $request->route('project'))->with('success', 'Activity created successfully!');
    }

    return redirect()->route('activities.index')->with('success', 'Activity created successfully!');
}

    // ======================
    //  EDIT
    // ======================
    public function edit(Activity $activity)
    {
        $user = auth()->user();
        
        if (!$user->can('edit activities')) {
            abort(403, 'Unauthorized action.');
        }
        
        // If user can't view all activities, check if they created this activity
        if (!$user->can('view all activities') && $activity->created_by !== $user->id) {
            abort(403, 'You can only edit your own activities.');
        }

        return Inertia::render('Activities/Edit', [
            'activity' => $activity->load('developers'),
            'activityTypes' => ActivityType::all(),
            'developers' => User::role('Developer')->get(),
            'projects' => Project::select('id', 'title')->get(),
        ]);
    }

    // ======================
    //  UPDATE
    // ======================
    // ======================
//  UPDATE
// ======================
// ======================
//  UPDATE
// ======================
public function update(Request $request, Activity $activity)
{
    $user = auth()->user();

    // Permission checks
    if (!$user->can('edit activities')) {
        abort(403, 'Unauthorized action.');
    }
    if (!$user->can('view all activities') && $activity->created_by !== $user->id) {
        abort(403, 'You can only edit your own activities.');
    }

    // Validate input
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'activity_type_id' => 'required|exists:activity_types,id',
        'project_id' => 'nullable|exists:projects,id',
        'developer_ids' => 'required|array',
        'developer_ids.*' => 'exists:users,id',
        'due_date' => 'required|date',
    ]);

    // Update activity details
    $activity->update($data);

    // -----------------------------
    // Handle developers safely
    // -----------------------------
    $currentDevelopers = $activity->developers()->pluck('users.id')->toArray(); // current assigned developers
    $newDevelopers = $data['developer_ids']; // developers submitted from the form

    $pivotData = [];

    foreach ($newDevelopers as $devId) {
        // Preserve previous accepted status if developer was already assigned
        $acceptedStatus = $activity->developers()
                                   ->where('user_id', $devId)
                                   ->first()?->pivot->accepted ?? false;
        $pivotData[$devId] = ['accepted' => $acceptedStatus];
    }

    // Sync pivot table: removes unselected developers, adds new ones, preserves accepted status
    $activity->developers()->sync($pivotData);

    // Check if the request is coming from a project page or activities index
    // You can check the referrer or add a custom header/parameter
    $referer = $request->header('referer');
    
    // Check if the referer contains '/projects/' which means it came from a project page
    if ($referer && strpos($referer, '/projects/') !== false && $activity->project_id) {
        // Extract project ID from referer or use activity's project_id
        return redirect()->route('projects.show', $activity->project_id)
                         ->with('success', 'Activity updated successfully!');
    }
    
    // Default: redirect to activities index
    return redirect()->route('activities.index')
                     ->with('success', 'Activity updated successfully!');
}


    

    // ======================
    //  DESTROY
    // ======================
    // ======================
//  DESTROY
// ======================
public function destroy(Request $request, Activity $activity)
{
    $user = auth()->user();
    
    if (!$user->can('delete activities')) {
        abort(403, 'Unauthorized action.');
    }
    
    // If user can't view all activities, check if they created this activity
    if (!$user->can('view all activities') && $activity->created_by !== $user->id) {
        abort(403, 'You can only delete your own activities.');
    }

    // Store project_id before deletion for potential redirect
    $projectId = $activity->project_id;
    
    $activity->delete();

    // Check if request has a referer header or a source parameter
    $referer = $request->header('referer');
    $source = $request->input('source', 'activities');
    
    // Check various indicators that we came from a project page
    $isFromProject = false;
    
    // Option 1: Check referer URL
    if ($referer && strpos($referer, '/projects/') !== false) {
        $isFromProject = true;
    }
    
    // Option 2: Check explicit source parameter (you can pass this from your frontend)
    if ($source === 'project') {
        $isFromProject = true;
    }
    
    // Option 3: Check if there's a project_id query parameter
    if ($request->has('project_id')) {
        $projectId = $request->input('project_id');
        $isFromProject = true;
    }
    
    // Redirect back to project page if came from there
    if ($isFromProject && $projectId) {
        return redirect()->route('projects.show', $projectId)
                         ->with('success', 'Activity deleted successfully!');
    }
    
    // Default: redirect to activities index
    return redirect()->route('activities.index')
                     ->with('success', 'Activity deleted successfully!');
}

    // ======================
    //  ACCEPT / COMPLETE
    // ======================
    // public function accept(Activity $activity)
    // {
    //     $user = auth()->user();

    //     if (!$activity->developers()->where('user_id', $user->id)->exists()) {
    //         abort(403, 'You are not assigned to this activity.');
    //     }

    //     // Update pivot table acceptance
    //     $activity->developers()->updateExistingPivot($user->id, ['accepted' => true]);

    //     // Check if all assigned developers have accepted
    //     $allAccepted = $activity->developers()->wherePivot('accepted', false)->doesntExist();

    //     if ($allAccepted) {
    //         $activity->update(['status' => 'In Progress']);
    //     }

    //     return redirect()->route('activities.index')->with('success', 'Activity accepted successfully!');
    // }

    public function complete(Activity $activity)
    {
        $user = auth()->user();

        if (!$activity->developers()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not assigned to this activity.');
        }

        $activity->update(['status' => 'Completed']);

        return redirect()->route('activities.index')->with('success', 'Activity marked as completed!');
    }
    // ======================
//  UPDATE STATUS
// ======================
// ======================
//  UPDATE STATUS
// ======================
public function updateStatus(Request $request, Activity $activity)
{
    $user = auth()->user();
    
    // Check if user is assigned to the activity or has permission
    $isAssigned = $activity->developers()->where('user_id', $user->id)->exists();
    $canEdit = $user->can('edit activities') || 
               ($user->can('view all activities') && $activity->created_by === $user->id);
    
    if (!$isAssigned && !$canEdit) {
        abort(403, 'You are not authorized to update this activity status.');
    }

    $validated = $request->validate([
        'status' => 'required|in:Pending,In Progress,Completed'
    ]);

    // Update activity status
    $activity->update(['status' => $validated['status']]);

    return redirect()->back()->with('success', 'Activity status updated successfully!');
}
}