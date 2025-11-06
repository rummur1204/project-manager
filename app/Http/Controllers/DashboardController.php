<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::select('id', 'title', 'description', 'status', 'progress')
            ->latest()
            ->take(6)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'projects' => $projects,
        ]);
    }
}
