<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
{
    $projects = Project::with('client')->get();

    return Inertia::render('Projects/Index', [
        'projects' => $projects,
        'auth' => [
            'can' => [
                'view projects' => auth()->user()->can('view projects'),
                'create projects' => auth()->user()->can('create projects'),
                'edit projects' => auth()->user()->can('edit projects'),
                'delete projects' => auth()->user()->can('delete projects'),
            ],
        ],
    ]);
}


    public function create()
{
    $clients = User::role('Client')->get();
    $developers = User::role('Developer')->get();

    return Inertia::render('Projects/Create', [
        'clients' => $clients,
        'developers' => $developers,
    ]);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:users,id',
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id',
            'due_date' => 'required|date',
        ]);

        $project = Project::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'client_id' => $data['client_id'],
            'created_by' => auth()->id(),
            'due_date' => $data['due_date'],
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $project->developers()->sync($data['developer_ids']);

        return redirect()->route('projects.index')->with('success', 'Project created successfully!');
    }

    public function show(Project $project)
    {
        $project->load(['users']);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project)
    {
       $developers = User::role('Developer')->get();
          $clients = User::role('Client')->get();


        $project->load( 'users');

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'clients' => $clients,
            'developers' => $developers,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:users,id',
            'developer_ids' => 'required|array',
            'developer_ids.*' => 'exists:users,id',
            'status' => 'required|string',
            'due_date' => 'required|date',
        ]);

        $project->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'client_id' => $data['client_id'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
        ]);

        $project->developers()->sync($data['developer_ids']);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully!');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully!');
    }
}
