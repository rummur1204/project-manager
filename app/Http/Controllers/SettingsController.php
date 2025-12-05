<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index($tab = 'users')
    {
        // Get users with proper data structure
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
            ];
        });

        // Get roles with permissions
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                }),
            ];
        });

        // Get all permissions for checkbox list
        $allPermissions = Permission::all()->pluck('name');

        // Get activity types
        $activityTypes = ActivityType::all();

        return Inertia::render('Settings/Index', [
            'tab' => $tab,
            'users' => $users,
            'roles' => $roles,
            'activityTypes' => $activityTypes,
            'allPermissions' => $allPermissions,
        ]);
    }
}