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
    public function store(Request $request)
    {
        if (!auth()->user()->can('create activities')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'project_id' => 'nullable|exists:projects,id',
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'required|date',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'Pending';

        $activity = Activity::create($data);
        
        // Attach developers with accepted = false by default
        $developerData = [];
        foreach ($data['developer_ids'] as $developerId) {
            $developerData[$developerId] = ['accepted' => false];
        }
        $activity->developers()->sync($developerData);

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

            return redirect()->route('activities.index')->with('success', 'Activity updated successfully!');

}


    

    // ======================
    //  DESTROY
    // ======================
    public function destroy(Activity $activity)
    {
        $user = auth()->user();
        
        if (!$user->can('delete activities')) {
            abort(403, 'Unauthorized action.');
        }
        
        // If user can't view all activities, check if they created this activity
        if (!$user->can('view all activities') && $activity->created_by !== $user->id) {
            abort(403, 'You can only delete your own activities.');
        }

        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully!');
    }

    // ======================
    //  ACCEPT / COMPLETE
    // ======================
    public function accept(Activity $activity)
    {
        $user = auth()->user();

        if (!$activity->developers()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not assigned to this activity.');
        }

        // Update pivot table acceptance
        $activity->developers()->updateExistingPivot($user->id, ['accepted' => true]);

        // Check if all assigned developers have accepted
        $allAccepted = $activity->developers()->wherePivot('accepted', false)->doesntExist();

        if ($allAccepted) {
            $activity->update(['status' => 'In Progress']);
        }

        return redirect()->route('activities.index')->with('success', 'Activity accepted successfully!');
    }

    public function complete(Activity $activity)
    {
        $user = auth()->user();

        if (!$activity->developers()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not assigned to this activity.');
        }

        $activity->update(['status' => 'Completed']);

        return redirect()->route('activities.index')->with('success', 'Activity marked as completed!');
    }
}