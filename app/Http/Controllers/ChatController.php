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

    $chats = \App\Models\Chat::with(['project', 'users'])
        ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
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

// public function list()
// {
//     $user = auth()->user();

//     $chats = $user->chats()
//         ->with(['project:id,title', 'users:id,name'])
//         ->latest()
//         ->get()
//         ->map(function ($chat) {
//             return [
//                 'id' => $chat->id,
//                 'name' => $chat->name,
//                 'project' => $chat->project ? [
//                     'id' => $chat->project->id,
//                     'title' => $chat->project->title,
//                 ] : null,
//                 'users' => $chat->users->map(fn ($u) => [
//                     'id' => $u->id,
//                     'name' => $u->name,
//                 ]),
//             ];
//         });

//     return response()->json($chats);
// }
public function list()
{
    $user = auth()->user();

    $chats = Chat::with(['project', 'users', 'messages.user'])
        ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
        ->get()
        ->map(function ($chat) use ($user) {
            $chat->display_name = $chat->type === 'group'
                ? ($chat->project?->title ?? 'Group Chat')
                : $chat->users->where('id', '!=', $user->id)->first()?->name;
            return $chat;
        });

    return response()->json($chats);
}


public function show(Chat $chat,Project $project)
{
    $user = auth()->user();

    // Check if user is a developer on this project
    $projectUser = $project->users()->where('user_id', $user->id)->first();
    $isDeveloper = $projectUser && $user->hasRole('Developer');
    $hasAccepted = $projectUser && $projectUser->pivot->accepted;

    if ($isDeveloper && !$hasAccepted) {
        abort(403, 'You cannot access this chat until you accept this project.');
    }
    
    $chat->load(['messages.user', 'project', 'users']);
    $user = auth()->user();

    $chats = Chat::with(['project', 'users'])
        ->whereHas('users', fn($q) => $q->where('user_id', $user->id))
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


//     public function show(Chat $chat)
// {
//     $chat->load(['messages.user:id,name', 'users:id,name']);

//     // make sure user is a participant
//     abort_unless($chat->users->contains(auth()->id()), 403);

//     return inertia('Chat/Show', [
//         'chat' => $chat,
//     ]);
// }

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

}
