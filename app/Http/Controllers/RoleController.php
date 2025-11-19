<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $permissions = Permission::all();
        return Inertia::render('Settings/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.index' , ['tab' => 'roles'])->with('success', 'Role created successfully!');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();

        return Inertia::render('Settings/Roles/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('settings.index')->with('success', 'Role updated successfully!');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('settings.index' , ['tab' => 'roles'])->with('success', 'Role deleted successfully!');
    }
}
