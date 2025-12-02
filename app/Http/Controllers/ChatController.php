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

        $chats = Chat::with(['project', 'users', 'latestMessage'])
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                    ->where('read_at', null);
            }])
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->limit(1);
            })
            ->get();

        return inertia('Chats/Show', [
            'chats' => $chats,
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
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                    ->where('read_at', null);
            }])
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->limit(1);
            })
            ->get()
            ->map(function ($chat) use ($user) {
                $chat->display_name = $chat->type === 'group'
                    ? ($chat->project?->title ?? 'Group Chat')
                    : $chat->users->where('id', '!=', $user->id)->first()?->name;
                return $chat;
            });

        return response()->json($chats);
    }

    public function show(Chat $chat, Project $project = null)
    {
        $user = auth()->user();

        // Check if user is a developer on this project (if project exists)
        if ($project && $project->id) {
            $projectUser = $project->users()->where('user_id', $user->id)->first();
            $isDeveloper = $projectUser && $user->hasRole('Developer');
            $hasAccepted = $projectUser && $projectUser->pivot->accepted;

            if ($isDeveloper && !$hasAccepted) {
                abort(403, 'You cannot access this chat until you accept this project.');
            }
        }

        // Mark messages as read
        $chat->messages()
            ->where('user_id', '!=', $user->id)
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        $chat->load(['messages.user', 'project', 'users']);

        // Get all chats with unread counts
        $chats = Chat::with(['project', 'users', 'latestMessage'])
            ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                    ->where('read_at', null);
            }])
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->limit(1);
            })
            ->get();

        return inertia('Chats/Show', [
            'chats' => $chats,
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

        $message = Message::create([
            'chat_id' => $data['chat_id'],
            'user_id' => auth()->id(),
            'message' => $data['message'],
        ]);

        return response()->json($message);
    }

    public function store(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
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

    // New method to get unread count for the sidebar
    public function getUnreadCount()
    {
        $user = auth()->user();
        
        $unreadCount = Chat::whereHas('users', fn($q) => $q->where('user_id', $user->id))
            ->withCount(['messages as unread_messages' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                    ->where('read_at', null);
            }])
            ->get()
            ->sum('unread_messages');

        return response()->json(['unread_count' => $unreadCount]);
    }
}