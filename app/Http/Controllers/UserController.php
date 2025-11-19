<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 🧭 List all users
 public function index()
{ 
    $users = User::with('roles')->get()->map(function ($user) {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->getRoleNames()->implode(', ') ?: '—',
        ];
    });

    return inertia('Settings/Users/Index', [
        'users' => $users,
        'tab' => 'users'
    ]);
}



    // 🆕 Create user form
    public function create()
    {
        $roles = Role::pluck('name');

        return Inertia::render('Settings/Users/Create', [
            'roles' => $roles,
        ]);
    }

    // 💾 Store new user
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);
return redirect()->route('settings.index', ['tab' => 'users'])
    ->with('success', 'User created successfully');

    }

    // ✏️ Edit user form
    public function edit(User $user)
    {
        $roles = Role::pluck('name');
        $user->load('roles');

        return Inertia::render('Settings/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first(),
            ],
            'roles' => $roles,
        ]);
    }

    // 🔄 Update user
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('settings.index', ['tab' => 'users'])
    ->with('success', 'User updated successfully');

    }

    // 🗑️ Delete user
    public function destroy(User $user)
    {
        $user->delete();

return redirect()->route('settings.index', ['tab' => 'users'])
    ->with('success', 'User deleted successfully');
    }
    




}
