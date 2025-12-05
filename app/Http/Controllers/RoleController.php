<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    // ======================
    //  STORE (Create Role)
    // ======================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web'
            ]);
            
            if (!empty($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }
            
            DB::commit();

            return redirect()->back()->with('success', 'Role created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    // ======================
    //  UPDATE (Edit Role)
    // ======================
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role->name = $validated['name'];
            $role->save();
            
            $role->syncPermissions($validated['permissions'] ?? []);
            
            DB::commit();

            return redirect()->back()->with('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    // ======================
    //  DESTROY (Delete Role)
    // ======================
    public function destroy(Role $role)
    {
        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete role that is assigned to users.');
        }
        
        // Prevent deleting essential roles
        $essentialRoles = ['Super Admin', 'Admin'];
        if (in_array($role->name, $essentialRoles)) {
            return redirect()->back()->with('error', 'Cannot delete essential system roles.');
        }
        
        DB::beginTransaction();
        try {
            $role->delete();
            DB::commit();
            
            return redirect()->back()->with('success', 'Role deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
}