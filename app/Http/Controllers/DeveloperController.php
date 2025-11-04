<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class DeveloperController extends Controller
{
    /**
     * Display a listing of developers.
     */
   public function index(Request $request)
{
    $query = User::role('Developer');

    if ($search = $request->input('q')) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $developers = $query->with('projects')->get();

    

    return Inertia::render('Admin/Developers/Developers', [
        'developers' => $developers,
        'filters' => $request->only('q'),
    ]);
}

    

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        return Inertia::render('Admin/Developers/CreateDeveloper');
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('Developer');

        return redirect()->route('admin.developers')->with('success', 'Developer added successfully.');
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit($id)
    {
        $developer = User::findOrFail($id);

        return Inertia::render('Admin/Developers/EditDeveloper', [
            'developer' => [
                'id' => $developer->id,
                'name' => $developer->name,
                'email' => $developer->email,
            ],
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, $id)
    {
        $developer = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $developer->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $developer->name = $validated['name'];
        $developer->email = $validated['email'];

        if (!empty($validated['password'])) {
            $developer->password = Hash::make($validated['password']);
        }

        $developer->save();

        return redirect()->route('admin.developers')->with('success', 'Developer updated successfully.');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy($id)
    {
        $developer = User::findOrFail($id);
        $developer->delete();

        return back()->with('success', 'Developer deleted successfully.');
    }
}

