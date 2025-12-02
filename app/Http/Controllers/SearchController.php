<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Task;
use App\Models\Chat;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q', '');
        
        $results = [
            'projects' => [],
            'tasks' => [],
            'chats' => [],
        ];

        if ($query) {
            $user = $request->user();
            
            // Search projects
            $results['projects'] = Project::where('title', 'LIKE', "%{$query}%")
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->limit(10)
                ->get(['id', 'title', 'description'])
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'description' => $project->description,
                        'url' => route('projects.show', $project->id),
                        'type' => 'project',
                    ];
                });

            // Search tasks
            $results['tasks'] = Task::where('title', 'LIKE', "%{$query}%")
                ->whereHas('project.users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with('project:id,title')
                ->limit(10)
                ->get(['id', 'title', 'project_id'])
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'project_title' => $task->project->title,
                        'url' => route('projects.show', $task->project_id),
                        'type' => 'task',
                    ];
                });

            // Search chats
            $results['chats'] = Chat::whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhereHas('project', function ($subQuery) use ($query) {
                          $subQuery->where('title', 'LIKE', "%{$query}%");
                      });
                })
                ->with('project:id,title')
                ->limit(10)
                ->get(['id', 'name', 'type', 'project_id'])
                ->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'title' => $chat->name ?? ($chat->project?->title ?? 'Chat'),
                        'type' => $chat->type === 'group' ? 'group_chat' : 'private_chat',
                        'url' => route('chats.show', $chat->id),
                    ];
                });
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function quickSearch(Request $request)
    {
        $query = $request->input('q', '');
        
        if (!$query) {
            return response()->json([]);
        }

        $user = $request->user();
        $results = [];

        // Quick search for projects
        $projects = Project::where('title', 'LIKE', "%{$query}%")
            ->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->limit(3)
            ->get(['id', 'title'])
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'url' => route('projects.show', $project->id),
                    'type' => 'project',
                ];
            });

        // Quick search for tasks
        $tasks = Task::where('title', 'LIKE', "%{$query}%")
            ->whereHas('project.users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('project:id,title')
            ->limit(3)
            ->get(['id', 'title', 'project_id'])
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title . ' (in ' . $task->project->title . ')',
                    'url' => route('projects.show', $task->project_id),
                    'type' => 'task',
                ];
            });

        // Quick search for chats
        $chats = Chat::whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhereHas('project', function ($subQuery) use ($query) {
                      $subQuery->where('title', 'LIKE', "%{$query}%");
                  });
            })
            ->with('project:id,title')
            ->limit(3)
            ->get(['id', 'name', 'type', 'project_id'])
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->name ?? ($chat->project?->title ?? 'Chat'),
                    'url' => route('chats.show', $chat->id),
                    'type' => 'chat',
                ];
            });

        // Merge all results
        $results = array_merge(
            $projects->toArray(),
            $tasks->toArray(),
            $chats->toArray()
        );

        return response()->json($results);
    }
}