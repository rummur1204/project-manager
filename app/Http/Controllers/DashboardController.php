<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats
        $stats = [
            'projects' => Project::count(),
            'tasks' => Task::count(),
            'completedTasks' => Task::where('status', 'completed')->count(),
            'users' => User::count(),
        ];

        // Upcoming deadlines (next 7 days)
        // $upcomingDeadlines = Project::whereDate('due_date', '>=', now())
        //     ->whereDate('due_date', '<=', now()->addDays(7))
        //     ->orderBy('due_date')
        //     ->select('title', 'due_date')
        //     ->get();

        // Recent activity (you can replace this with your own activity logs table)
        // $recentActivity = DB::table('activities') // or a custom log table
        //     ->orderBy('created_at', 'desc')
        //     ->take(10)
        //     ->get();
        $recentActivity = Project::latest()
    ->take(10)
    ->get(['title', 'created_at'])
    ->map(function($project) {
        return [
            'user' => 'System',
            'action' => "Created project: {$project->title}",
            'time' => $project->created_at->diffForHumans()
        ];
    });

$upcomingDeadlines = Project::whereNotNull('due_date')
    ->whereDate('due_date', '>=', now()->toDateString())
    ->orderBy('due_date', 'asc')
    ->take(5)
    ->get(['id','title','due_date']);

// Notifications: project deadlines within next 3 days
$notifications = Project::whereNotNull('due_date')
    ->whereDate('due_date', '<=', now()->addDays(3)->toDateString())
    ->whereDate('due_date', '>=', now()->toDateString())
    ->orderBy('due_date','asc')
    ->get(['id','title','due_date']);
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'upcomingDeadlines' => $upcomingDeadlines,
            'recentActivity' => $recentActivity,
             'notifications' => $notifications,
        ]);
    }
}
