<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Chat; // Add this line

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $unreadCount = 0;
        
        if ($request->user()) {
            $unreadCount = Chat::whereHas('users', function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
                ->withCount(['messages as unread_messages' => function ($query) use ($request) {
                    $query->where('user_id', '!=', $request->user()->id)
                        ->where('read_at', null);
                }])
                ->get()
                ->sum('unread_messages');
        }

        return array_merge(parent::share($request), [
            'unreadCount' => $unreadCount,
            'auth' => [
                'user' => fn() => auth()->user()
                    ? array_merge(auth()->user()->toArray(), [
                        'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
                        'roles' => auth()->user()->getRoleNames(),
                    ])
                    : null,
            ],
        ]);
    }
}