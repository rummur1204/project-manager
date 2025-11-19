<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/ActivityTypes/Index', [
            'activityTypes' => ActivityType::all()
        ]);
    }

    public function create()
    {
        return Inertia::render('Settings/ActivityTypes/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        ActivityType::create($request->only('name'));

        return redirect()->route('settings.index');
            
    }

    public function edit(ActivityType $activityType)
    {
        return Inertia::render('Settings/ActivityTypes/Edit', [
            'activityType' => $activityType
        ]);
    }

    public function update(Request $request, ActivityType $activityType)
    {
        $request->validate([
            'name' => 'required|unique:activity_types,name,' . $activityType->id
        ]);

        $activityType->update($request->only('name'));

        return redirect()->route('settings.index')
            ->with(['tab' => 'activitytypes']);
    }

    public function destroy(ActivityType $activityType)
    {
        $activityType->delete();

        return redirect()->route('settings.index')
            ->with(['tab' => 'activitytypes']);
    }
}
