<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Start query for projects
        $projectQuery = Project::query();
        
        // Check if user has permission to view all projects
        if (!$user->hasPermissionTo('view all projects')) {
            $projectQuery->where(function($query) use ($user) {
                $query->where('created_by', $user->id)
                      ->orWhere('client_id', $user->id) // Include clients
                      ->orWhereHas('users', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            });
        }
        
        $userProjects = $projectQuery->get();
        $projectIds = $userProjects->pluck('id');
        
        // Get task counts by status
        $completedTasksCount = Task::whereIn('project_id', $projectIds)
            ->where('status', 'completed')
            ->count();
        
        $inProgressTasksCount = Task::whereIn('project_id', $projectIds)
            ->where('status', 'in_progress')
            ->orWhere('status', 'in progress')
            ->count();
        
        $pendingTasksCount = Task::whereIn('project_id', $projectIds)
            ->where('status', 'pending')
            ->count();
        
        $totalTasksCount = Task::whereIn('project_id', $projectIds)->count();
        
        // Stats with task status breakdown
        $stats = [
            'projects' => $userProjects->count(),
            'tasks' => $totalTasksCount,
            'completedTasks' => $completedTasksCount,
            'inProgressTasks' => $inProgressTasksCount,
            'pendingTasks' => $pendingTasksCount,
        ];

        // Upcoming deadlines with progress data
        $upcomingDeadlines = $userProjects
            ->filter(function($project) {
                return $project->due_date && 
                       \Carbon\Carbon::parse($project->due_date)->isFuture();
            })
            ->sortBy('due_date')
            ->take(5)
            ->map(function($project) {
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'completed')->count();
                $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                $dueDate = \Carbon\Carbon::parse($project->due_date);
                $now = now();
                
                // Calculate whole days difference (not fractional)
                if ($now->gt($dueDate)) {
                    // Past date - negative days
                    $daysLeft = -$now->diffInDays($dueDate);
                } else {
                    // Future date - positive days
                    $daysLeft = $now->diffInDays($dueDate, false); // false = not absolute
                }
                
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'due_date' => $dueDate->format('M d, Y'),
                    'days_left' => (int) $daysLeft, // Cast to integer for whole days
                    'progress' => $progress,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'status' => $project->status,
                ];
            })
            ->values();

        // Project progress analytics for bar chart
        $projectAnalytics = $userProjects
            ->map(function($project) {
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'completed')->count();
                
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'status' => $project->status,
                ];
            })
            ->sortByDesc('progress')
            ->take(5)
            ->values();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'upcomingDeadlines' => $upcomingDeadlines,
            'projectAnalytics' => $projectAnalytics,
            'userName' => $user->name,
        ]);
    }
}