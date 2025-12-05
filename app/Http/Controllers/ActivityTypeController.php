<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ActivityTypeController extends Controller
{
    // ======================
    //  STORE (Create Activity Type)
    // ======================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:activity_types,name',
        ]);

        DB::beginTransaction();
        try {
            ActivityType::create($validated);
            DB::commit();

            return redirect()->back()->with('success', 'Activity type created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create activity type: ' . $e->getMessage());
        }
    }

    // ======================
    //  UPDATE (Edit Activity Type)
    // ======================
    public function update(Request $request, ActivityType $activityType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('activity_types')->ignore($activityType->id)],
        ]);

        DB::beginTransaction();
        try {
            $activityType->update($validated);
            DB::commit();

            return redirect()->back()->with('success', 'Activity type updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update activity type: ' . $e->getMessage());
        }
    }

    // ======================
    //  DESTROY (Delete Activity Type)
    // ======================
    public function destroy(ActivityType $activityType)
    {
        // Check if activity type is being used
        if ($activityType->activities()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete activity type that is being used by activities.');
        }
        
        DB::beginTransaction();
        try {
            $activityType->delete();
            DB::commit();
            
            return redirect()->back()->with('success', 'Activity type deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete activity type: ' . $e->getMessage());
        }
    }
}