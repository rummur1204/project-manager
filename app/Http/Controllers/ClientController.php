<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientController extends Controller
{
     public function dashboard()
    {
        // Fetch projects assigned to this client
        $projects = Project::with('developer')
            ->where('client_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Client/Dashboard', [
            'projects' => $projects,
        ]);
    }
     public function show(Project $project)
    {
        $project->load(['developer', 'tasks']);

        return Inertia::render('Client/ProjectShow', [
            'project' => $project,
        ]);
    }
}
