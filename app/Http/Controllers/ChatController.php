<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Filter out project chats where developer hasn't accepted
        $chats = Chat::with(['project', 'users', 'latestMessage'])
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->get()
            ->filter(function ($chat) use ($user) {
                // Skip check for private chats
                if ($chat->type !== 'group') {
                    return true;
                }
                
                // For group chats, check if user is a developer who hasn't accepted
                if ($chat->project && $user->hasRole('Developer')) {
                    $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
                    $isDeveloper = $projectUser && $user->hasRole('Developer');
                    $hasAccepted = $projectUser && $projectUser->pivot->accepted;
                    
                    // If developer hasn't accepted, don't show this chat
                    return !($isDeveloper && !$hasAccepted);
                }
                
                return true;
            })
            ->values(); // Reset keys after filtering

        // Get unread count for remaining chats
        $chats->each(function ($chat) use ($user) {
            $chat->unread_count = $chat->messages()
                ->where('user_id', '!=', $user->id)
                ->where('read_at', null)
                ->count();
        });

        // Sort by latest message
        $sortedChats = $chats->sortByDesc(function ($chat) {
            return $chat->latestMessage ? $chat->latestMessage->created_at : $chat->created_at;
        })->values();

        return inertia('Chats/Show', [
            'chats' => $sortedChats,
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $user->getRoleNames(),
                ],
            ],
            'selectedChat' => null,
        ]);
    }

    public function list()
    {
        $user = auth()->user();

        $chats = Chat::with(['project', 'users', 'messages.user', 'latestMessage'])
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->get()
            ->filter(function ($chat) use ($user) {
                // Skip check for private chats
                if ($chat->type !== 'group') {
                    return true;
                }
                
                // For group chats, check if user is a developer who hasn't accepted
                if ($chat->project && $user->hasRole('Developer')) {
                    $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
                    $isDeveloper = $projectUser && $user->hasRole('Developer');
                    $hasAccepted = $projectUser && $projectUser->pivot->accepted;
                    
                    // If developer hasn't accepted, don't show this chat
                    return !($isDeveloper && !$hasAccepted);
                }
                
                return true;
            })
            ->map(function ($chat) use ($user) {
                $chat->display_name = $chat->type === 'group'
                    ? ($chat->project?->title ?? 'Group Chat')
                    : $chat->users->where('id', '!=', $user->id)->first()?->name;
                    
                $chat->unread_count = $chat->messages()
                    ->where('user_id', '!=', $user->id)
                    ->where('read_at', null)
                    ->count();
                    
                return $chat;
            })
            ->sortByDesc(function ($chat) {
                return $chat->latestMessage ? $chat->latestMessage->created_at : $chat->created_at;
            })
            ->values();

        return response()->json($chats);
    }

    public function show(Chat $chat, Project $project = null)
    {
        $user = auth()->user();

        // Check if user is part of the chat
        if (!$chat->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not part of this chat.');
        }

        // For group chats, check if developer has accepted the project
        if ($chat->type === 'group' && $chat->project) {
            $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
            $isDeveloper = $projectUser && $user->hasRole('Developer');
            $hasAccepted = $projectUser && $projectUser->pivot->accepted;

            if ($isDeveloper && !$hasAccepted) {
                abort(403, 'You cannot access this chat until you accept the project.');
            }
        }

        // Mark messages as read
        $chat->messages()
            ->where('user_id', '!=', $user->id)
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        $chat->load(['messages.user', 'project', 'users']);

        // Get all chats (filtered for unaccepted developers)
        $chats = Chat::with(['project', 'users', 'latestMessage'])
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->get()
            ->filter(function ($chatItem) use ($user) {
                if ($chatItem->type !== 'group') {
                    return true;
                }
                
                if ($chatItem->project && $user->hasRole('Developer')) {
                    $projectUser = $chatItem->project->users()->where('user_id', $user->id)->first();
                    $isDeveloper = $projectUser && $user->hasRole('Developer');
                    $hasAccepted = $projectUser && $projectUser->pivot->accepted;
                    
                    return !($isDeveloper && !$hasAccepted);
                }
                
                return true;
            })
            ->values();

        // Calculate unread counts
        $chats->each(function ($chatItem) use ($user) {
            $chatItem->unread_count = $chatItem->messages()
                ->where('user_id', '!=', $user->id)
                ->where('read_at', null)
                ->count();
        });

        // Sort by latest message
        $sortedChats = $chats->sortByDesc(function ($chatItem) {
            return $chatItem->latestMessage ? $chatItem->latestMessage->created_at : $chatItem->created_at;
        })->values();

        return inertia('Chats/Show', [
            'chats' => $sortedChats,
            'chat' => $chat,
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $user->getRoleNames(),
                ],
            ],
            'selectedChat' => $chat->id,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string',
        ]);

        $user = auth()->user();
        $chat = Chat::findOrFail($data['chat_id']);

        // Check if user can send messages to this chat
        if (!$chat->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not part of this chat.');
        }

        // For group chats, check if developer has accepted
        if ($chat->type === 'group' && $chat->project) {
            $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
            $isDeveloper = $projectUser && $user->hasRole('Developer');
            $hasAccepted = $projectUser && $projectUser->pivot->accepted;

            if ($isDeveloper && !$hasAccepted) {
                abort(403, 'You cannot send messages until you accept the project.');
            }
        }

        $message = Message::create([
            'chat_id' => $data['chat_id'],
            'user_id' => $user->id,
            'message' => $data['message'],
        ]);

        return response()->json($message);
    }

    public function store(Request $request, Chat $chat)
    {
        $user = auth()->user();

        // Check if user can send messages to this chat
        if (!$chat->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not part of this chat.');
        }

        // For group chats, check if developer has accepted
        if ($chat->type === 'group' && $chat->project) {
            $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
            $isDeveloper = $projectUser && $user->hasRole('Developer');
            $hasAccepted = $projectUser && $projectUser->pivot->accepted;

            if ($isDeveloper && !$hasAccepted) {
                abort(403, 'You cannot send messages until you accept the project.');
            }
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message = $chat->messages()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        return back();
    }

    public function createPrivateChat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $chat = Chat::findOrCreatePrivateChat(auth()->id(), $request->user_id);

        return redirect()->route('chats.show', $chat->id);
    }

    public function getUnreadCount()
    {
        $user = auth()->user();
        
        $unreadCount = Chat::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->get()
            ->filter(function ($chat) use ($user) {
                // Skip check for private chats
                if ($chat->type !== 'group') {
                    return true;
                }
                
                // For group chats, check if user is a developer who hasn't accepted
                if ($chat->project && $user->hasRole('Developer')) {
                    $projectUser = $chat->project->users()->where('user_id', $user->id)->first();
                    $isDeveloper = $projectUser && $user->hasRole('Developer');
                    $hasAccepted = $projectUser && $projectUser->pivot->accepted;
                    
                    return !($isDeveloper && !$hasAccepted);
                }
                
                return true;
            })
            ->sum(function ($chat) use ($user) {
                return $chat->messages()
                    ->where('user_id', '!=', $user->id)
                    ->where('read_at', null)
                    ->count();
            });

        return response()->json(['unread_count' => $unreadCount]);
    }
}