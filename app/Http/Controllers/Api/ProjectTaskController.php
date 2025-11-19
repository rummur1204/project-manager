<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function index(Project $project)
    {
        // Only return tasks belonging to this project
        return $project->tasks()
            ->select('id', 'title')   // lightweight response
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
