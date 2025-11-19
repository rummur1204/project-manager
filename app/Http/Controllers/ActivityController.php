<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index()
    {
        return Inertia::render('Activities/Index', [
            'activities' => Activity::with(['type', 'project', 'developers'])
                ->latest()
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Activities/Create', [
            'activityTypes' => ActivityType::all(),
            'developers' => User::role('Developer')->select('id', 'name')->get(),
            'projects' => Project::select('id', 'title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'project_id' => 'nullable|exists:projects,id',
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'required|date',
            'status' => 'required|string',
        ]);

        $data['created_by'] = auth()->id();

        $activity = Activity::create($data);

        $activity->developers()->sync($data['developer_ids']);

        return redirect()->route('activities.index')->with('success', 'Activity created.');
    }

    public function edit(Activity $activity)
    {
        return Inertia::render('Activities/Edit', [
            'activity' => $activity->load('developers'),
            'activityTypes' => ActivityType::all(),
            'developers' => User::role('Developer')->select('id', 'name')->get(),
            'projects' => Project::select('id', 'title')->get(),
        ]);
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'project_id' => 'nullable|exists:projects,id',
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'required|date',
            'status' => 'required|string',
        ]);

        $activity->update($data);
        $activity->developers()->sync($data['developer_ids']);

        return redirect()->route('activities.index')->with('success', 'Activity updated.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return back()->with('success', 'Activity deleted.');
    }
}
